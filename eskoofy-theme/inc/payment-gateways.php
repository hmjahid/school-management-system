<?php
/**
 * Payment gateway integrations.
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

	public function get_gateway_data(): array {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}esk_payment_gateways WHERE code = %s AND deleted_at IS NULL",
				$this->code
			),
			ARRAY_A
		) ?: array();
	}
}

class Eskoofy_BKash_Gateway extends Eskoofy_Payment_Gateway {
	public string $code = 'bkash';
	public string $name = 'bKash';
	public string $type = 'mobile_financial_service';
	public bool $is_online = true;
	public bool $has_api = true;

	public function process_payment( float $amount, array $data ): array {
		$gateway_data = $this->get_gateway_data();
		$api_url      = ! empty( $gateway_data['test_mode'] ) ? $gateway_data['sandbox_url'] : $gateway_data['live_url'];
		return array(
			'status'       => 'pending',
			'message'      => 'Redirecting to bKash payment...',
			'gateway_url'  => $api_url,
			'transaction'  => 'bkash_' . time(),
		);
	}

	public function verify_payment( array $data ): array {
		return array( 'status' => 'completed', 'verified' => true );
	}
}

class Eskoofy_Rocket_Gateway extends Eskoofy_Payment_Gateway {
	public string $code = 'rocket';
	public string $name = 'Rocket';
	public string $type = 'mobile_financial_service';
	public bool $is_online = true;
	public bool $has_api = true;

	public function process_payment( float $amount, array $data ): array {
		$gateway_data = $this->get_gateway_data();
		$api_url      = ! empty( $gateway_data['test_mode'] ) ? $gateway_data['sandbox_url'] : $gateway_data['live_url'];
		return array(
			'status'      => 'pending',
			'message'     => 'Redirecting to Rocket payment...',
			'gateway_url' => $api_url,
			'transaction' => 'rocket_' . time(),
		);
	}

	public function verify_payment( array $data ): array {
		return array( 'status' => 'completed', 'verified' => true );
	}
}

class Eskoofy_Nagad_Gateway extends Eskoofy_Payment_Gateway {
	public string $code = 'nagad';
	public string $name = 'Nagad';
	public string $type = 'mobile_financial_service';
	public bool $is_online = true;
	public bool $has_api = true;

	public function process_payment( float $amount, array $data ): array {
		$gateway_data = $this->get_gateway_data();
		$api_url      = ! empty( $gateway_data['test_mode'] ) ? $gateway_data['sandbox_url'] : $gateway_data['live_url'];
		return array(
			'status'      => 'pending',
			'message'     => 'Redirecting to Nagad payment...',
			'gateway_url' => $api_url,
			'transaction' => 'nagad_' . time(),
		);
	}

	public function verify_payment( array $data ): array {
		return array( 'status' => 'completed', 'verified' => true );
	}
}

class Eskoofy_Stripe_Gateway extends Eskoofy_Payment_Gateway {
	public string $code = 'stripe';
	public string $name = 'Stripe';
	public string $type = 'online_payment';
	public bool $is_online = true;
	public bool $has_api = true;

	public function process_payment( float $amount, array $data ): array {
		$gateway_data = $this->get_gateway_data();
		return array(
			'status'      => 'pending',
			'message'     => 'Redirecting to Stripe checkout...',
			'gateway_url' => 'https://checkout.stripe.com',
			'transaction' => 'stripe_' . time(),
		);
	}

	public function verify_payment( array $data ): array {
		return array( 'status' => 'completed', 'verified' => true );
	}
}

class Eskoofy_PayPal_Gateway extends Eskoofy_Payment_Gateway {
	public string $code = 'paypal';
	public string $name = 'PayPal';
	public string $type = 'online_payment';
	public bool $is_online = true;
	public bool $has_api = true;

	public function process_payment( float $amount, array $data ): array {
		$gateway_data = $this->get_gateway_data();
		$api_url      = ! empty( $gateway_data['test_mode'] )
			? 'https://www.sandbox.paypal.com/cgi-bin/webscr'
			: 'https://www.paypal.com/cgi-bin/webscr';
		return array(
			'status'      => 'pending',
			'message'     => 'Redirecting to PayPal...',
			'gateway_url' => $api_url,
			'transaction' => 'paypal_' . time(),
		);
	}

	public function verify_payment( array $data ): array {
		return array( 'status' => 'completed', 'verified' => true );
	}
}

class Eskoofy_Paddle_Gateway extends Eskoofy_Payment_Gateway {
	public string $code = 'paddle';
	public string $name = 'Paddle';
	public string $type = 'online_payment';
	public bool $is_online = true;
	public bool $has_api = true;

	public function process_payment( float $amount, array $data ): array {
		return array(
			'status'      => 'pending',
			'message'     => 'Redirecting to Paddle checkout...',
			'gateway_url' => 'https://checkout.paddle.com',
			'transaction' => 'paddle_' . time(),
		);
	}

	public function verify_payment( array $data ): array {
		return array( 'status' => 'completed', 'verified' => true );
	}
}

class Eskoofy_Offline_Gateway extends Eskoofy_Payment_Gateway {
	public string $code = 'offline';
	public string $name = 'Offline / Bank Transfer';
	public string $type = 'bank';
	public bool $is_online = false;
	public bool $has_api = false;

	public function process_payment( float $amount, array $data ): array {
		return array(
			'status'      => 'pending',
			'message'     => 'Please complete the bank transfer and upload proof of payment.',
			'gateway_url' => '',
			'transaction' => 'offline_' . time(),
		);
	}

	public function verify_payment( array $data ): array {
		return array( 'status' => 'pending', 'verified' => false );
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
	$gateways = esk_init_payment_gateways();
	foreach ( $gateways as $gw ) {
		if ( $gw->code === $code ) {
			return $gw;
		}
	}
	return null;
}
