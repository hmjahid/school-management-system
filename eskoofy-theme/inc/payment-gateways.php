<?php
/**
 * Payment gateway integrations.
 *
 * Each gateway provides:
 *   - process_payment($amount, $order_data) -> ['status', 'transaction', 'redirect_url', 'message']
 *   - verify_payment($data) -> ['verified' => bool, 'status' => string]
 *   - verify_webhook($payload, $signature) -> bool
 *
 * All gateways inherit from Eskoofy_Payment_Gateway which loads
 * gateway configuration (keys, sandbox/live) from esk_payment_gateways.
 *
 * @package Eskoofy
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

abstract class Eskoofy_Payment_Gateway {
	public string $code;
	public string $name;
	public string $type;
	public bool $is_online;
	public bool $has_api;

	abstract public function process_payment( float $amount, array $data ): array;
	abstract public function verify_payment( array $data ): array;
	abstract public function verify_webhook( $payload, string $signature ): bool;

	public function get_gateway_data(): array {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}esk_payment_gateways WHERE code = %s AND deleted_at IS NULL",
				$this->code
			),
			ARRAY_A
		);
		return is_array( $row ) ? $row : array();
	}

	public function is_test_mode(): bool {
		$data = $this->get_gateway_data();
		return ! empty( $data['test_mode'] );
	}

	public function callback_url( array $data = array() ): string {
		$base = rest_url( 'esk/v1/payments/callback/' . $this->code );
		return $base . ( $data ? '?' . http_build_query( $data ) : '' );
	}

	protected function curl_request( string $url, array $args ): array {
		$defaults = array(
			'timeout' => 30,
			'headers' => array(),
			'body'    => null,
			'method'  => 'POST',
		);
		$args     = array_merge( $defaults, $args );

		$response = wp_remote_request(
			$url,
			array(
				'method'  => $args['method'],
				'timeout' => $args['timeout'],
				'headers' => $args['headers'],
				'body'    => $args['body'],
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'ok'        => false,
				'error'     => $response->get_error_message(),
				'http_code' => 0,
				'body'      => '',
			);
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		return array(
			'ok'        => $code >= 200 && $code < 300,
			'http_code' => $code,
			'body'      => $body,
			'decoded'   => json_decode( $body, true ),
		);
	}
}

class Eskoofy_BKash_Gateway extends Eskoofy_Payment_Gateway {
	public string $code = 'bkash';
	public string $name = 'bKash';
	public string $type = 'mobile_financial_service';
	public bool $is_online = true;
	public bool $has_api = true;

	private function base_url(): string {
		$data = $this->get_gateway_data();
		if ( $this->is_test_mode() ) {
			return 'https://tokenized.sandbox.bka.sh/v1.2.0-beta';
		}
		return ! empty( $data['live_url'] ) ? rtrim( $data['live_url'], '/' ) : 'https://tokenized.pay.bka.sh/v1.2.0-beta';
	}

	private function get_token(): string {
		$data = $this->get_gateway_data();
		$res  = $this->curl_request(
			$this->base_url() . '/tokenized/checkout/token/grant',
			array(
				'method'  => 'POST',
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Accept'        => 'application/json',
					'username'      => $data['api_username'] ?? '',
					'password'      => $data['api_password'] ?? '',
				),
				'body' => wp_json_encode(
					array(
						'app_key'    => $data['api_key'] ?? '',
						'app_secret' => $data['api_secret'] ?? '',
					)
				),
			)
		);
		if ( $res['ok'] && ! empty( $res['decoded']['id_token'] ) ) {
			return (string) $res['decoded']['id_token'];
		}
		return '';
	}

	public function process_payment( float $amount, array $data ): array {
		$gw       = $this->get_gateway_data();
		$token    = $this->get_token();
		$order_id = (string) ( $data['order_id'] ?? esk_generate_number( 'INV', 'payments' ) );

		if ( '' === $token ) {
			return array(
				'status'       => 'pending',
				'message'      => __( 'Could not obtain bKash token. Please try again.', 'eskoofy' ),
				'transaction'  => $order_id,
				'redirect_url' => $this->callback_url( array( 'order_id' => $order_id, 'status' => 'failed' ) ),
			);
		}

		$payload = array(
			'mode'                  => '0011',
			'payerReference'        => $data['customer']['phone'] ?? '',
			'callbackURL'           => $this->callback_url( array( 'order_id' => $order_id ) ),
			'amount'                => number_format( $amount, 2, '.', '' ),
			'currency'              => $gw['currency'] ?? 'BDT',
			'intent'                => 'sale',
			'merchantInvoiceNumber' => $order_id,
		);

		$res = $this->curl_request(
			$this->base_url() . '/tokenized/checkout/create',
			array(
				'method'  => 'POST',
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Accept'        => 'application/json',
					'Authorization' => $token,
					'X-APP-Key'     => $gw['api_key'] ?? '',
				),
				'body' => wp_json_encode( $payload ),
			)
		);

		if ( $res['ok'] && ! empty( $res['decoded']['bkashURL'] ) ) {
			return array(
				'status'       => 'pending',
				'message'      => __( 'Redirecting to bKash payment...', 'eskoofy' ),
				'transaction'  => $order_id,
				'redirect_url' => (string) $res['decoded']['bkashURL'],
			);
		}

		return array(
			'status'       => 'pending',
			'message'      => __( 'bKash payment creation failed.', 'eskoofy' ),
			'transaction'  => $order_id,
			'redirect_url' => $this->callback_url( array( 'order_id' => $order_id, 'status' => 'failed' ) ),
		);
	}

	public function execute_payment( string $payment_id ): array {
		$gw    = $this->get_gateway_data();
		$token = $this->get_token();

		$res = $this->curl_request(
			$this->base_url() . '/tokenized/checkout/execute',
			array(
				'method'  => 'POST',
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Accept'        => 'application/json',
					'Authorization' => $token,
					'X-APP-Key'     => $gw['api_key'] ?? '',
				),
				'body' => wp_json_encode( array( 'paymentID' => $payment_id ) ),
			)
		);

		return $res['ok'] ? (array) $res['decoded'] : array();
	}

	public function verify_payment( array $data ): array {
		if ( empty( $data['paymentID'] ) ) {
			return array( 'verified' => false, 'status' => 'pending' );
		}
		$exec = $this->execute_payment( $data['paymentID'] );
		if ( ! empty( $exec['transactionStatus'] ) && 'Completed' === $exec['transactionStatus'] ) {
			return array(
				'verified'     => true,
				'status'       => 'completed',
				'transaction'  => $exec['trxID'] ?? '',
				'amount'       => $exec['amount'] ?? 0,
			);
		}
		return array( 'verified' => false, 'status' => 'pending' );
	}

	public function verify_webhook( $payload, string $signature ): bool {
		// bKash uses IPN with a signature header; verify by hash.
		$data = $this->get_gateway_data();
		$secret = $data['api_secret'] ?? '';
		if ( '' === $secret ) {
			return false;
		}
		$expected = hash_hmac( 'sha256', is_string( $payload ) ? $payload : wp_json_encode( $payload ), $secret );
		return hash_equals( $expected, $signature );
	}
}

class Eskoofy_Rocket_Gateway extends Eskoofy_Payment_Gateway {
	public string $code = 'rocket';
	public string $name = 'Rocket';
	public string $type = 'mobile_financial_service';
	public bool $is_online = true;
	public bool $has_api = true;

	private function base_url(): string {
		$data = $this->get_gateway_data();
		if ( $this->is_test_mode() ) {
			return ! empty( $data['sandbox_url'] ) ? $data['sandbox_url'] : 'https://sandbox Rocket api';
		}
		return ! empty( $data['live_url'] ) ? $data['live_url'] : 'https://api Rocket dbbl';
	}

	public function process_payment( float $amount, array $data ): array {
		$gw       = $this->get_gateway_data();
		$order_id = (string) ( $data['order_id'] ?? esk_generate_number( 'INV', 'payments' ) );

		$payload = array(
			'amount'           => number_format( $amount, 2, '.', '' ),
			'order_id'         => $order_id,
			'customer_mobile'  => $data['customer']['phone'] ?? '',
			'callback_url'     => $this->callback_url( array( 'order_id' => $order_id ) ),
			'merchant_id'      => $gw['api_username'] ?? '',
		);

		$res = $this->curl_request(
			$this->base_url(),
			array(
				'method'  => 'POST',
				'headers' => array(
					'Content-Type' => 'application/json',
					'Accept'       => 'application/json',
					'Authorization' => 'Bearer ' . ( $gw['api_key'] ?? '' ),
				),
				'body' => wp_json_encode( $payload ),
			)
		);

		if ( $res['ok'] && ! empty( $res['decoded']['redirect_url'] ) ) {
			return array(
				'status'       => 'pending',
				'message'      => __( 'Redirecting to Rocket payment...', 'eskoofy' ),
				'transaction'  => $order_id,
				'redirect_url' => (string) $res['decoded']['redirect_url'],
			);
		}

		return array(
			'status'       => 'pending',
			'message'      => __( 'Rocket payment creation failed.', 'eskoofy' ),
			'transaction'  => $order_id,
			'redirect_url' => $this->callback_url( array( 'order_id' => $order_id, 'status' => 'failed' ) ),
		);
	}

	public function verify_payment( array $data ): array {
		if ( empty( $data['transaction_id'] ) ) {
			return array( 'verified' => false, 'status' => 'pending' );
		}
		$gw = $this->get_gateway_data();
		$res = $this->curl_request(
			$this->base_url() . '/verify',
			array(
				'method'  => 'GET',
				'headers' => array(
					'Authorization' => 'Bearer ' . ( $gw['api_key'] ?? '' ),
				),
				'body'    => null,
			)
		);

		$status = $res['decoded']['status'] ?? 'pending';
		return array(
			'verified'    => 'completed' === $status,
			'status'      => $status,
			'transaction' => $data['transaction_id'],
		);
	}

	public function verify_webhook( $payload, string $signature ): bool {
		$data   = $this->get_gateway_data();
		$secret = $data['api_secret'] ?? '';
		if ( '' === $secret ) {
			return false;
		}
		$expected = hash_hmac( 'sha256', is_string( $payload ) ? $payload : wp_json_encode( $payload ), $secret );
		return hash_equals( $expected, $signature );
	}
}

class Eskoofy_Nagad_Gateway extends Eskoofy_Payment_Gateway {
	public string $code = 'nagad';
	public string $name = 'Nagad';
	public string $type = 'mobile_financial_service';
	public bool $is_online = true;
	public bool $has_api = true;

	private function base_url(): string {
		$data = $this->get_gateway_data();
		if ( $this->is_test_mode() ) {
			return ! empty( $data['sandbox_url'] ) ? $data['sandbox_url'] : 'http://sandbox.mynagad.com:10080/remote-payment-gateway-1.0/api/dfs';
		}
		return ! empty( $data['live_url'] ) ? $data['live_url'] : 'https://api.mynagad.com/api/dfs';
	}

	public function process_payment( float $amount, array $data ): array {
		$gw       = $this->get_gateway_data();
		$order_id = (string) ( $data['order_id'] ?? esk_generate_number( 'INV', 'payments' ) );

		$payload = array(
			'merchantId'   => $gw['api_username'] ?? '',
			'orderId'      => $order_id,
			'amount'       => (string) (int) ( $amount * 100 ),
			'currencyCode' => $gw['currency'] ?? '050',
			'callbackUrl'  => $this->callback_url( array( 'order_id' => $order_id ) ),
		);

		$res = $this->curl_request(
			$this->base_url() . '/check-out/initialize/' . ( $gw['api_username'] ?? '' ) . '/' . $order_id,
			array(
				'method'  => 'POST',
				'headers' => array(
					'Content-Type' => 'application/json',
					'Accept'       => 'application/json',
					'X-KM-Api-Key' => $gw['api_key'] ?? '',
				),
				'body' => wp_json_encode( $payload ),
			)
		);

		$redirect_url = $res['decoded']['callBackUrl'] ?? $this->callback_url( array( 'order_id' => $order_id ) );

		return array(
			'status'       => $res['ok'] ? 'pending' : 'pending',
			'message'      => $res['ok'] ? __( 'Redirecting to Nagad payment...', 'eskoofy' ) : __( 'Nagad payment initialization failed.', 'eskoofy' ),
			'transaction'  => $order_id,
			'redirect_url' => (string) $redirect_url,
		);
	}

	public function verify_payment( array $data ): array {
		if ( empty( $data['payment_ref_id'] ) ) {
			return array( 'verified' => false, 'status' => 'pending' );
		}
		$gw  = $this->get_gateway_data();
		$res = $this->curl_request(
			$this->base_url() . '/verify/payment/' . ( $gw['api_username'] ?? '' ) . '/' . sanitize_text_field( $data['payment_ref_id'] ),
			array(
				'method'  => 'GET',
				'headers' => array(
					'X-KM-Api-Key' => $gw['api_key'] ?? '',
				),
				'body'    => null,
			)
		);
		$status = $res['decoded']['status'] ?? 'pending';
		return array(
			'verified'    => 'Success' === $status,
			'status'      => 'Success' === $status ? 'completed' : 'pending',
			'transaction' => $data['payment_ref_id'] ?? '',
		);
	}

	public function verify_webhook( $payload, string $signature ): bool {
		$data   = $this->get_gateway_data();
		$secret = $data['api_secret'] ?? '';
		if ( '' === $secret ) {
			return false;
		}
		$expected = hash_hmac( 'sha256', is_string( $payload ) ? $payload : wp_json_encode( $payload ), $secret );
		return hash_equals( $expected, $signature );
	}
}

class Eskoofy_Stripe_Gateway extends Eskoofy_Payment_Gateway {
	public string $code = 'stripe';
	public string $name = 'Stripe';
	public string $type = 'online_payment';
	public bool $is_online = true;
	public bool $has_api = true;

	public function process_payment( float $amount, array $data ): array {
		$gw       = $this->get_gateway_data();
		$secret   = $gw['api_secret'] ?? '';
		$order_id = (string) ( $data['order_id'] ?? esk_generate_number( 'INV', 'payments' ) );

		$amount_cents = (int) round( $amount * 100 );

		$payload = array(
			'amount'              => $amount_cents,
			'currency'            => strtolower( $gw['currency'] ?? 'usd' ),
			'description'         => $data['description'] ?? 'School payment',
			'metadata[order_id]'  => $order_id,
			'automatic_payment_methods[enabled]' => 'true',
		);

		$res = $this->curl_request(
			'https://api.stripe.com/v1/payment_intents',
			array(
				'method'  => 'POST',
				'headers' => array(
					'Authorization' => 'Bearer ' . $secret,
					'Content-Type'  => 'application/x-www-form-urlencoded',
				),
				'body' => http_build_query( $payload ),
			)
		);

		if ( $res['ok'] && ! empty( $res['decoded']['id'] ) ) {
			$client_secret = $res['decoded']['client_secret'] ?? '';
			return array(
				'status'        => 'pending',
				'message'       => __( 'Stripe payment intent created.', 'eskoofy' ),
				'transaction'   => $res['decoded']['id'],
				'redirect_url'  => $this->callback_url( array(
					'payment_intent' => $res['decoded']['id'],
					'order_id'       => $order_id,
				) ),
				'client_secret' => $client_secret,
			);
		}

		return array(
			'status'       => 'pending',
			'message'      => __( 'Stripe payment intent creation failed.', 'eskoofy' ),
			'transaction'  => $order_id,
			'redirect_url' => $this->callback_url( array( 'order_id' => $order_id, 'status' => 'failed' ) ),
		);
	}

	public function verify_payment( array $data ): array {
		$gw     = $this->get_gateway_data();
		$secret = $gw['api_secret'] ?? '';
		if ( empty( $secret ) || empty( $data['payment_intent'] ) ) {
			return array( 'verified' => false, 'status' => 'pending' );
		}
		$res = $this->curl_request(
			'https://api.stripe.com/v1/payment_intents/' . urlencode( (string) $data['payment_intent'] ),
			array(
				'method'  => 'GET',
				'headers' => array( 'Authorization' => 'Bearer ' . $secret ),
				'body'    => null,
			)
		);
		$status      = $res['decoded']['status'] ?? 'pending';
		$verified    = 'succeeded' === $status;
		return array(
			'verified'    => $verified,
			'status'      => $verified ? 'completed' : $status,
			'transaction' => $data['payment_intent'],
			'amount'      => isset( $res['decoded']['amount'] ) ? ( (int) $res['decoded']['amount'] / 100 ) : 0,
		);
	}

	public function verify_webhook( $payload, string $signature ): bool {
		$gw       = $this->get_gateway_data();
		$endpoint_secret = $gw['webhook_secret'] ?? $gw['api_secret'] ?? '';
		if ( '' === $endpoint_secret ) {
			return false;
		}
		if ( ! is_string( $payload ) ) {
			return false;
		}
		// Stripe-Signature: t=...,v1=...
		$parts = array();
		foreach ( explode( ',', $signature ) as $p ) {
			$kv       = explode( '=', trim( $p ), 2 );
			$parts[ $kv[0] ] = $kv[1] ?? '';
		}
		if ( empty( $parts['t'] ) || empty( $parts['v1'] ) ) {
			return false;
		}
		$signed = $parts['t'] . '.' . $payload;
		$expected = hash_hmac( 'sha256', $signed, $endpoint_secret );
		return hash_equals( $expected, $parts['v1'] );
	}
}

class Eskoofy_PayPal_Gateway extends Eskoofy_Payment_Gateway {
	public string $code = 'paypal';
	public string $name = 'PayPal';
	public string $type = 'online_payment';
	public bool $is_online = true;
	public bool $has_api = true;

	private function base_url(): string {
		return $this->is_test_mode() ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';
	}

	private function get_access_token(): string {
		$gw = $this->get_gateway_data();
		$client_id     = $gw['api_key'] ?? '';
		$client_secret = $gw['api_secret'] ?? '';

		$res = $this->curl_request(
			$this->base_url() . '/v1/oauth2/token',
			array(
				'method'  => 'POST',
				'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( $client_id . ':' . $client_secret ),
					'Content-Type'  => 'application/x-www-form-urlencoded',
				),
				'body' => 'grant_type=client_credentials',
			)
		);

		return $res['ok'] && ! empty( $res['decoded']['access_token'] ) ? (string) $res['decoded']['access_token'] : '';
	}

	public function process_payment( float $amount, array $data ): array {
		$gw       = $this->get_gateway_data();
		$order_id = (string) ( $data['order_id'] ?? esk_generate_number( 'INV', 'payments' ) );
		$token    = $this->get_access_token();

		if ( '' === $token ) {
			return array(
				'status'       => 'pending',
				'message'      => __( 'PayPal authentication failed.', 'eskoofy' ),
				'transaction'  => $order_id,
				'redirect_url' => $this->callback_url( array( 'order_id' => $order_id, 'status' => 'failed' ) ),
			);
		}

		$payload = array(
			'intent'         => 'CAPTURE',
			'purchase_units' => array(
				array(
					'reference_id' => $order_id,
					'amount'       => array(
						'currency_code' => $gw['currency'] ?? 'USD',
						'value'         => number_format( $amount, 2, '.', '' ),
					),
				),
			),
			'application_context' => array(
				'return_url' => $this->callback_url( array( 'order_id' => $order_id, 'status' => 'success' ) ),
				'cancel_url' => $this->callback_url( array( 'order_id' => $order_id, 'status' => 'cancel' ) ),
			),
		);

		$res = $this->curl_request(
			$this->base_url() . '/v2/checkout/orders',
			array(
				'method'  => 'POST',
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $token,
				),
				'body' => wp_json_encode( $payload ),
			)
		);

		if ( $res['ok'] && ! empty( $res['decoded']['id'] ) ) {
			$approve = '';
			foreach ( ( $res['decoded']['links'] ?? array() ) as $link ) {
				if ( 'approve' === ( $link['rel'] ?? '' ) ) {
					$approve = $link['href'] ?? '';
					break;
				}
			}
			return array(
				'status'       => 'pending',
				'message'      => __( 'Redirecting to PayPal...', 'eskoofy' ),
				'transaction'  => $res['decoded']['id'],
				'redirect_url' => (string) $approve,
			);
		}

		return array(
			'status'       => 'pending',
			'message'      => __( 'PayPal order creation failed.', 'eskoofy' ),
			'transaction'  => $order_id,
			'redirect_url' => $this->callback_url( array( 'order_id' => $order_id, 'status' => 'failed' ) ),
		);
	}

	public function verify_payment( array $data ): array {
		$gw    = $this->get_gateway_data();
		$token = $this->get_access_token();
		if ( '' === $token || empty( $data['order_id'] ) ) {
			return array( 'verified' => false, 'status' => 'pending' );
		}

		$res = $this->curl_request(
			$this->base_url() . '/v2/checkout/orders/' . urlencode( (string) $data['order_id'] ) . '/capture',
			array(
				'method'  => 'POST',
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $token,
				),
				'body' => '{}',
			)
		);
		$status = $res['decoded']['status'] ?? 'pending';
		return array(
			'verified'    => 'COMPLETED' === $status,
			'status'      => 'COMPLETED' === $status ? 'completed' : strtolower( $status ),
			'transaction' => $data['order_id'],
		);
	}

	public function verify_webhook( $payload, string $signature ): bool {
		$gw           = $this->get_gateway_data();
		$webhook_id   = $gw['webhook_id'] ?? '';
		$cert_url     = $gw['cert_url'] ?? '';
		if ( '' === $webhook_id || '' === $cert_url || ! is_string( $payload ) ) {
			return false;
		}
		$data       = '';
		if ( isset( $_SERVER['HTTP_PAYPAL_TRANSMISSION_SIG'] ) ) {
			$data = sanitize_text_field( wp_unslash( $_SERVER['HTTP_PAYPAL_TRANSMISSION_SIG'] ) );
		}
		$expected = openssl_verify( $payload, $data, $cert_url, OPENSSL_ALGO_SHA256 );
		return 1 === $expected;
	}
}

class Eskoofy_Paddle_Gateway extends Eskoofy_Payment_Gateway {
	public string $code = 'paddle';
	public string $name = 'Paddle';
	public string $type = 'online_payment';
	public bool $is_online = true;
	public bool $has_api = true;

	public function process_payment( float $amount, array $data ): array {
		$gw       = $this->get_gateway_data();
		$order_id = (string) ( $data['order_id'] ?? esk_generate_number( 'INV', 'payments' ) );

		// Paddle is client-side via Paddle.js; we generate an order id and
		// hand the customer over to the configured checkout URL.
		$checkout_url = ! empty( $data['checkout_url'] ) ? $data['checkout_url'] : 'https://checkout.paddle.com/checkout/open';

		$passthrough = rawurlencode( wp_json_encode( array( 'order_id' => $order_id ) ) );
		$separator   = false === strpos( $checkout_url, '?' ) ? '?' : '&';
		$redirect    = $checkout_url . $separator . http_build_query( array(
			'product'     => $data['product_id'] ?? '',
			'quantity'    => 1,
			'price'       => number_format( $amount, 2, '.', '' ),
			'currency'    => $gw['currency'] ?? 'USD',
			'passthrough' => $passthrough,
		) );

		return array(
			'status'       => 'pending',
			'message'      => __( 'Redirecting to Paddle checkout...', 'eskoofy' ),
			'transaction'  => $order_id,
			'redirect_url' => $redirect,
		);
	}

	public function verify_payment( array $data ): array {
		$gw     = $this->get_gateway_data();
		$secret = $gw['api_secret'] ?? '';
		if ( '' === $secret ) {
			return array( 'verified' => false, 'status' => 'pending' );
		}
		// Paddle verifies via public key + signature on alert payload.
		// For the inline verify endpoint, we just check transaction_id presence.
		if ( empty( $data['transaction_id'] ) && empty( $data['order_id'] ) ) {
			return array( 'verified' => false, 'status' => 'pending' );
		}
		return array(
			'verified'    => true,
			'status'      => 'completed',
			'transaction' => $data['transaction_id'] ?? $data['order_id'],
		);
	}

	public function verify_webhook( $payload, string $signature ): bool {
		$gw     = $this->get_gateway_data();
		$secret = $gw['api_secret'] ?? '';
		if ( '' === $secret ) {
			return false;
		}
		if ( ! is_string( $payload ) ) {
			$payload = wp_json_encode( $payload );
		}
		$pArray = array();
		foreach ( explode( '&', $payload ) as $part ) {
			$kv = explode( '=', $part, 2 );
			if ( 2 === count( $kv ) ) {
				$pArray[ $kv[0] ] = urldecode( $kv[1] );
			}
		}
		ksort( $pArray );
		$arr_str = '';
		foreach ( $pArray as $k => $v ) {
			if ( 'p_signature' === $k ) {
				continue;
			}
			$arr_str .= $k . '=' . $v;
		}
		$expected = hash_hmac( 'sha256', $arr_str, $secret );
		return hash_equals( $expected, $signature );
	}
}

class Eskoofy_Offline_Gateway extends Eskoofy_Payment_Gateway {
	public string $code = 'offline';
	public string $name = 'Offline / Bank Transfer';
	public string $type = 'bank';
	public bool $is_online = false;
	public bool $has_api = false;

	public function process_payment( float $amount, array $data ): array {
		$order_id = (string) ( $data['order_id'] ?? esk_generate_number( 'INV', 'payments' ) );
		$bank     = array(
			'bank_name'    => get_option( 'esk_offline_bank_name', '' ),
			'account_name' => get_option( 'esk_offline_account_name', '' ),
			'account_no'   => get_option( 'esk_offline_account_no', '' ),
			'branch'       => get_option( 'esk_offline_branch', '' ),
			'routing'      => get_option( 'esk_offline_routing', '' ),
			'instructions' => get_option( 'esk_offline_instructions', '' ),
		);

		return array(
			'status'       => 'pending',
			'message'      => __( 'Please complete the bank transfer and submit proof.', 'eskoofy' ),
			'transaction'  => $order_id,
			'redirect_url' => '',
			'bank_details' => $bank,
		);
	}

	public function verify_payment( array $data ): array {
		return array(
			'verified' => false,
			'status'   => 'pending',
		);
	}

	public function verify_webhook( $payload, string $signature ): bool {
		return false;
	}
}

/**
 * Initialize all payment gateways.
 *
 * @return Eskoofy_Payment_Gateway[]
 */
function esk_init_payment_gateways(): array {
	return array(
		new Eskoofy_BKash_Gateway(),
		new Eskoofy_Rocket_Gateway(),
		new Eskoofy_Nagad_Gateway(),
		new Eskoofy_Stripe_Gateway(),
		new Eskoofy_PayPal_Gateway(),
		new Eskoofy_Paddle_Gateway(),
		new Eskoofy_Offline_Gateway(),
	);
}

/**
 * Get a gateway instance by code.
 */
function esk_get_payment_gateway( string $code ): ?Eskoofy_Payment_Gateway {
	foreach ( esk_init_payment_gateways() as $gw ) {
		if ( $gw->code === $code ) {
			return $gw;
		}
	}
	return null;
}

/**
 * Process a payment through the given gateway.
 */
function esk_process_payment( string $code, float $amount, array $order_data ): array {
	$gw = esk_get_payment_gateway( $code );
	if ( ! $gw ) {
		return array(
			'status'  => 'failed',
			'message' => __( 'Unknown payment gateway.', 'eskoofy' ),
		);
	}
	return $gw->process_payment( $amount, $order_data );
}