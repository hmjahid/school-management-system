<?php
/**
 * SMS gateway integration (Twilio / Vonage).
 *
 * International-first: delivers SMS through Twilio or Vonage using the
 * WordPress HTTP API when the site is configured, and falls back to a log.
 * The driver + credentials are stored in the `esk_sms_options` option.
 *
 * @package Eskoofy
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * Get the SMS gateway options.
 *
 * @return array<string, mixed>
 */
function esk_sms_options(): array {
	$defaults = array(
		'driver'             => 'log',
		'twilio_account_sid' => '',
		'twilio_auth_token'  => '',
		'twilio_from'        => '',
		'vonage_api_key'     => '',
		'vonage_api_secret'  => '',
		'vonage_from'        => '',
	);

	return wp_parse_args( (array) get_option( 'esk_sms_options', array() ), $defaults );
}

/**
 * Save the SMS gateway options.
 *
 * @param array<string, mixed> $options Options to merge and persist.
 */
function esk_update_sms_options( array $options ): void {
	$current = esk_sms_options();
	$current = wp_parse_args( $options, $current );
	update_option( 'esk_sms_options', $current );
}

/**
 * The configured SMS driver name.
 */
function esk_sms_driver(): string {
	$options = esk_sms_options();
	$driver  = (string) ( $options['driver'] ?? 'log' );

	return in_array( $driver, array( 'log', 'twilio', 'vonage' ), true ) ? $driver : 'log';
}

/**
 * Send one SMS message through the configured gateway.
 *
 * @param string               $to      Recipient phone number.
 * @param string               $message Message body.
 * @param array<string, mixed> $extra   Optional extra payload (e.g. status callback).
 *
 * @return array<string, mixed> Result: success, status, message_id, provider, error.
 */
function esk_send_sms( string $to, string $message, array $extra = array() ): array {
	$driver = esk_sms_driver();

	if ( 'twilio' === $driver ) {
		$result = esk_twilio_send_sms( $to, $message, $extra );
	} elseif ( 'vonage' === $driver ) {
		$result = esk_vonage_send_sms( $to, $message, $extra );
	} else {
		$result = array(
			'success'    => true,
			'status'     => 'logged',
			'message_id' => 'log-' . gmdate( 'YmdHis' ) . '-' . wp_rand( 1000, 9999 ),
			'provider'   => 'log',
			'error'      => null,
		);
	}

	$result['to']      = $to;
	$result['message'] = $message;
	$result['driver']  = $driver;
	$result['error']   = $result['error'] ?? null;

	esk_log_sms_result( $result );

	return $result;
}

/**
 * Deliver via Twilio Messages API.
 *
 * @param string               $to      Recipient phone number.
 * @param string               $message Message body.
 * @param array<string, mixed> $extra   Optional payload.
 *
 * @return array<string, mixed>
 */
function esk_twilio_send_sms( string $to, string $message, array $extra = array() ): array {
	$options = esk_sms_options();
	$sid     = (string) $options['twilio_account_sid'];
	$token   = (string) $options['twilio_auth_token'];
	$from    = (string) $options['twilio_from'];

	if ( '' === $sid || '' === $token ) {
		return array(
			'success'    => false,
			'status'     => 'failed',
			'message_id' => null,
			'provider'   => 'twilio',
			'error'      => __( 'Twilio credentials are not configured.', 'eskoofy' ),
		);
	}

	$body = array(
		'To'   => esk_e164_phone( $to ),
		'From' => $from,
		'Body' => $message,
	);

	if ( ! empty( $extra['status_callback'] ) ) {
		$body['StatusCallback'] = (string) $extra['status_callback'];
	}

	$response = wp_remote_post(
		'https://api.twilio.com/2010-04-01/Accounts/' . rawurlencode( $sid ) . '/Messages.json',
		array(
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( $sid . ':' . $token ),
			),
			'body'    => $body,
			'timeout' => 30,
		)
	);

	return esk_parse_http_result( $response, 'twilio' );
}

/**
 * Deliver via Vonage Messages API.
 *
 * @param string               $to      Recipient phone number.
 * @param string               $message Message body.
 * @param array<string, mixed> $extra   Optional payload (callback_url, client_ref).
 *
 * @return array<string, mixed>
 */
function esk_vonage_send_sms( string $to, string $message, array $extra = array() ): array {
	$options = esk_sms_options();
	$key     = (string) $options['vonage_api_key'];
	$secret  = (string) $options['vonage_api_secret'];
	$from    = (string) $options['vonage_from'];

	if ( '' === $key || '' === $secret ) {
		return array(
			'success'    => false,
			'status'     => 'failed',
			'message_id' => null,
			'provider'   => 'vonage',
			'error'      => __( 'Vonage credentials are not configured.', 'eskoofy' ),
		);
	}

	$payload = array(
		'from'         => '' !== $from ? $from : 'Eskoofy',
		'to'           => esk_e164_phone( $to ),
		'message_type' => 'text',
		'text'         => $message,
		'channel'      => 'sms',
	);

	if ( ! empty( $extra['client_ref'] ) ) {
		$payload['client_ref'] = (string) $extra['client_ref'];
	}

	if ( ! empty( $extra['callback_url'] ) ) {
		$payload['webhooks'] = array(
			'delivery' => array(
				'address' => (string) $extra['callback_url'],
			),
		);
	}

	$response = wp_remote_post(
		'https://api.nexmo.com/v1/messages',
		array(
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( $key . ':' . $secret ),
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode( $payload ),
			'timeout' => 30,
		)
	);

	return esk_parse_http_result( $response, 'vonage' );
}

/**
 * Parse a wp_remote_post response into a unified result array.
 *
 * @param WP_Error|array $response WP HTTP response.
 * @param string         $provider Provider code ('twilio'|'vonage').
 *
 * @return array<string, mixed>
 */
function esk_parse_http_result( $response, string $provider ): array {
	if ( is_wp_error( $response ) ) {
		return array(
			'success'    => false,
			'status'     => 'failed',
			'message_id' => null,
			'provider'   => $provider,
			'error'      => $response->get_error_message(),
		);
	}

	$code = (int) wp_remote_retrieve_response_code( $response );
	$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );

	if ( ! is_array( $data ) ) {
		$data = array();
	}

	if ( $code < 200 || $code >= 300 ) {
		$error = $data['error'] ?? $data['message'] ?? 'SMS gateway request failed (HTTP ' . $code . ').';
		if ( ! is_string( $error ) ) {
			$error = 'SMS gateway request failed (HTTP ' . $code . ').';
		}

		return array(
			'success'    => false,
			'status'     => 'failed',
			'message_id' => null,
			'provider'   => $provider,
			'error'      => $error,
		);
	}

	if ( 'twilio' === $provider && isset( $data['sid'] ) ) {
		return array(
			'success'    => true,
			'status'     => is_string( $data['status'] ?? '' ) && '' !== $data['status'] ? $data['status'] : 'created',
			'message_id' => (string) $data['sid'],
			'provider'   => 'twilio',
			'error'      => null,
		);
	}

	if ( 'vonage' === $provider && isset( $data['message_uuid'] ) ) {
		return array(
			'success'    => true,
			'status'     => 'accepted',
			'message_id' => (string) $data['message_uuid'],
			'provider'   => 'vonage',
			'error'      => null,
		);
	}

	return array(
		'success'    => false,
		'status'     => 'failed',
		'message_id' => null,
		'provider'   => $provider,
		'error'      => __( 'Unexpected SMS gateway response.', 'eskoofy' ),
	);
}

/**
 * Format a phone number to E.164-ish form.
 */
function esk_e164_phone( string $phone ): string {
	$phone = preg_replace( '/[^0-9+]/', '', $phone ) ?? '';

	if ( str_starts_with( $phone, '00' ) ) {
		$phone = '+' . substr( $phone, 2 );
	}

	if ( 10 === strlen( $phone ) && str_starts_with( $phone, '0' ) ) {
		$phone = '+' . $phone;
	}

	if ( 11 === strlen( $phone ) && str_starts_with( $phone, '1' ) ) {
		$phone = '+1' . substr( $phone, 1 );
	}

	if ( '' !== $phone && ! str_starts_with( $phone, '+' ) ) {
		$phone = '+' . $phone;
	}

	return $phone;
}

/**
 * Deliver all queued recipients of an SMS campaign.
 *
 * @param int    $campaign_id Campaign ID.
 * @param string $message     Message body.
 */
function esk_process_sms_campaign( int $campaign_id, string $message ): void {
	global $wpdb;

	$recipients = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT id, phone FROM {$wpdb->prefix}esk_sms_campaign_recipients
			 WHERE sms_campaign_id = %d AND status != 'sent' ORDER BY id ASC LIMIT 500",
			$campaign_id
		)
	);

	if ( empty( $recipients ) ) {
		return;
	}

	$sent   = 0;
	$failed = 0;

	foreach ( $recipients as $recipient ) {
		$result = esk_send_sms( (string) $recipient->phone, $message );

		if ( $result['success'] ) {
			++$sent;
		} else {
			++$failed;
		}

		$wpdb->update(
			$wpdb->prefix . 'esk_sms_campaign_recipients',
			array(
				'status' => $result['success'] ? 'sent' : 'failed',
				'error'  => is_string( $result['error'] ) ? $result['error'] : null,
			),
			array( 'id' => (int) $recipient->id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
	}

	if ( $failed > 0 && $sent === 0 ) {
		$status = 'failed';
	} elseif ( $failed > 0 ) {
		$status = 'partial';
	} else {
		$status = 'sent';
	}

	$campaign_data = array(
		'status'  => $status,
		'sent_at' => gmdate( 'Y-m-d H:i:s' ),
	);

	$wpdb->update(
		$wpdb->prefix . 'esk_sms_campaigns',
		$campaign_data,
		array( 'id' => $campaign_id ),
		array( '%s', '%s' ),
		array( '%d' )
	);
}

/**
 * Log a send result to WP_DEBUG log + esk_sms_logs table.
 *
 * @param array<string, mixed> $result The unified send result.
 */
function esk_log_sms_result( array $result ): void {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( 'Eskoofy SMS ' . wp_json_encode( $result ) );
	}

	global $wpdb;

	$wpdb->insert(
		$wpdb->prefix . 'esk_sms_logs',
		array(
			'recipient'  => sanitize_text_field( (string) ( $result['to'] ?? '' ) ),
			'message'    => sanitize_textarea_field( (string) ( $result['message'] ?? '' ) ),
			'provider'   => sanitize_key( (string) ( $result['provider'] ?? 'log' ) ),
			'driver'     => sanitize_key( (string) ( $result['driver'] ?? 'log' ) ),
			'status'     => sanitize_key( (string) ( $result['status'] ?? 'unknown' ) ),
			'message_id' => sanitize_text_field( (string) ( $result['message_id'] ?? '' ) ),
			'error'      => sanitize_textarea_field( (string) ( $result['error'] ?? '' ) ),
			'created_at' => gmdate( 'Y-m-d H:i:s' ),
		),
		array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
	);
}
