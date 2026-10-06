<?php
/**
 * Payment gateway management.
 *
 * Lists every gateway and lets an admin create/edit/delete them, enable them
 * for payers, and store credentials. Optional international gateways are seeded
 * disabled and only appear to payers once enabled — mirroring the app's
 * Dashboard > Payment Gateways screen.
 *
 * @package Eskoofy
 */

defined( 'ABSPATH' ) || exit;
global $wpdb;

$esk_table = $wpdb->prefix . 'esk_payment_gateways';

/**
 * International gateways seeded disabled by default.
 */
$esk_international = array(
	'gpay'          => array( 'Google Pay', 'USD', 'USD, EUR, GBP, INR, SGD, AUD, CAD', 20 ),
	'applepay'      => array( 'Apple Pay', 'USD', 'USD, EUR, GBP, AUD, CAD, SGD', 21 ),
	'razorpay'      => array( 'Razorpay', 'INR', 'INR, USD', 22 ),
	'paystack'      => array( 'Paystack', 'NGN', 'NGN, GHS, ZAR, KES, USD', 23 ),
	'flutterwave'   => array( 'Flutterwave', 'NGN', 'NGN, GHS, KES, ZAR, USD, EUR, GBP', 24 ),
	'sslcommerz'    => array( 'SSLCommerz', 'BDT', 'BDT, USD, EUR, GBP, INR', 25 ),
	'square'        => array( 'Square', 'USD', 'USD, CAD, GBP, AUD, JPY, EUR', 26 ),
	'mollie'        => array( 'Mollie', 'EUR', 'EUR, USD, GBP, CHF, SEK, NOK, DKK, PLN', 27 ),
	'authorize_net' => array( 'Authorize.Net', 'USD', 'USD, CAD, GBP, EUR, AUD', 28 ),
	'xendit'        => array( 'Xendit', 'IDR', 'IDR, PHP, THB, VND, MYR, SGD, USD', 29 ),
	'adyen'         => array( 'Adyen', 'EUR', 'EUR, USD, GBP, AUD, CAD, JPY, SGD, INR', 30 ),
	'skrill'        => array( 'Skrill', 'USD', 'USD, EUR, GBP, AUD, CAD, JPY', 31 ),
);

// Seed any missing optional gateways (idempotent).
foreach ( $esk_international as $esk_code => $esk_meta ) {
	$esk_exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$esk_table} WHERE code = %s LIMIT 1", $esk_code ) );
	if ( ! $esk_exists ) {
		$wpdb->insert(
			$esk_table,
			array(
				'name'                 => $esk_meta[0],
				'code'                 => $esk_code,
				'type'                 => 'online_payment',
				'is_active'            => 0,
				'is_online'            => 1,
				'has_api'              => 1,
				'test_mode'            => 1,
				'callback_url'         => rest_url( 'esk/v1/payments/callback/' . $esk_code ),
				'webhook_url'          => rest_url( 'esk/v1/payments/callback/' . $esk_code ),
				'description'          => $esk_meta[0] . ' — international, hosted checkout.',
				'currency'             => $esk_meta[1],
				'supported_currencies' => $esk_meta[2],
				'extra_attributes'     => wp_json_encode( array() ),
				'sort_order'           => $esk_meta[3],
				'created_at'           => current_time( 'mysql' ),
				'updated_at'           => current_time( 'mysql' ),
			)
		);
	}
}

/**
 * Credential-free test/sandbox gateway — active by default (it never charges
 * money, so it is always safe to offer at checkout).
 */
$esk_exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$esk_table} WHERE code = %s LIMIT 1", 'test_gateway' ) );
if ( ! $esk_exists ) {
	$wpdb->insert(
		$esk_table,
		array(
			'name'                 => 'Test / Sandbox',
			'code'                 => 'test_gateway',
			'type'                 => 'online_payment',
			'is_active'            => 1,
			'is_online'            => 1,
			'has_api'              => 0,
			'test_mode'            => 1,
			'callback_url'         => rest_url( 'esk/v1/payments/callback/test_gateway' ),
			'webhook_url'          => rest_url( 'esk/v1/payments/callback/test_gateway' ),
			'description'          => 'Credential-free sandbox gateway: simulates payment outcomes without contacting any external service.',
			'currency'             => 'BDT',
			'supported_currencies' => 'BDT',
			'extra_attributes'     => wp_json_encode( array() ),
			'sort_order'           => 99,
			'created_at'           => current_time( 'mysql' ),
			'updated_at'           => current_time( 'mysql' ),
		)
	);
}

/**
 * Bangladeshi hosted gateways seeded disabled (same pattern as the
 * international ones above): an admin enables one and fills in its
 * credentials when ready, so existing installs see no change until then.
 *
 * The URLs below are DRAFT placeholders — verify them against the official
 * gateway documentation before going live. Checkout and verify endpoints are
 * carried in extra_attributes (GenericHosted contract); `{base_url}` resolves
 * to sandbox_url / live_url according to the gateway's test mode.
 */
$esk_bd = array(
	// code => array( name, sandbox_url, live_url, sort_order ).
	'shurjopay'  => array( 'ShurjoPay', 'https://sandbox.shurjopay.io/', 'https://payment.shurjohub.com/', 40 ), // DRAFT URLs — verify before use.
	'portwallet' => array( 'PortWallet', 'https://sandbox.portwallet.com/cloud-payment/', '', 41 ),
	'cellfin'    => array( 'Cellfin', 'https://sandbox.cellfin.io', '', 42 ),
	'purse'      => array( 'Purse', 'https://sandbox.purse.com.bd', '', 43 ),
	'cashby'     => array( 'Cashby', 'https://sandbox.cashby.com.bd', '', 44 ),
	'upay'       => array( 'UPay', 'https://sandbox.upay.ltd', '', 45 ),
	'mycash'     => array( 'MyCash', 'https://sandbox.mycash.com.bd', '', 46 ),
	'payer'      => array( 'Payer', 'https://sandbox.payer.com.bd', '', 47 ),
);

foreach ( $esk_bd as $esk_code => $esk_meta ) {
	$esk_exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$esk_table} WHERE code = %s LIMIT 1", $esk_code ) );
	if ( $esk_exists ) {
		continue;
	}
	$wpdb->insert(
		$esk_table,
		array(
			'name'                 => $esk_meta[0],
			'code'                 => $esk_code,
			'type'                 => 'online_payment',
			'is_active'            => 0,
			'is_online'            => 1,
			'has_api'              => 1,
			'test_mode'            => 1,
			'sandbox_url'          => $esk_meta[1],
			'live_url'             => $esk_meta[2],
			'callback_url'         => rest_url( 'esk/v1/payments/callback/' . $esk_code ),
			'webhook_url'          => rest_url( 'esk/v1/payments/callback/' . $esk_code ),
			'description'          => $esk_meta[0] . ' — Bangladeshi hosted checkout (draft sandbox endpoint).',
			'currency'             => 'BDT',
			'supported_currencies' => 'BDT',
			'extra_attributes'     => wp_json_encode(
				array(
					'checkout_method'       => 'GET',
					'checkout_url_template' => '{base_url}/?amount={amount}&currency={currency}&reference={reference}&invoice={invoice}&callback={callback}&cancel={cancel}&api_key={api_key}',
					'verify_url'            => rtrim( $esk_meta[1], '/' ),
					'verify_success_path'   => 'status',
					'verify_success_value'  => 'COMPLETED',
					'signature_header'      => 'X-Webhook-Signature',
				)
			),
			'sort_order'           => $esk_meta[3],
			'created_at'           => current_time( 'mysql' ),
			'updated_at'           => current_time( 'mysql' ),
		)
	);
}

/**
 * Build the row payload from the submitted form.
 *
 * @return array<string,mixed>
 */
function esk_gateway_payload(): array {
	$currencies = array_filter( array_map( 'trim', explode( ',', sanitize_text_field( wp_unslash( $_POST['supported_currencies'] ?? '' ) ) ) ) );
	$currency   = strtoupper( sanitize_text_field( wp_unslash( $_POST['currency'] ?? '' ) ) );
	if ( ! $currency ) {
		$currency = 'USD';
	}
	if ( ! $currencies ) {
		$currencies = array( $currency );
	}

	$extra = array();
	foreach ( preg_split( '/\r\n|\r|\n/', (string) wp_unslash( $_POST['extra_attributes'] ?? '' ) ) as $line ) {
		$line = trim( $line );
		if ( '' === $line || ! str_contains( $line, '=' ) ) {
			continue;
		}
		list( $key, $value ) = explode( '=', $line, 2 );
		$key                 = trim( $key );
		if ( '' !== $key ) {
			$extra[ $key ] = trim( $value );
		}
	}

	$encrypt = static fn ( string $value ): string => '' !== $value && function_exists( 'esk_encrypt_secret' )
		? esk_encrypt_secret( $value )
		: $value;

	return array(
		'name'                 => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
		'code'                 => strtolower( preg_replace( '/[^A-Za-z0-9_]/', '_', sanitize_text_field( wp_unslash( $_POST['code'] ?? '' ) ) ) ),
		'type'                 => sanitize_text_field( wp_unslash( $_POST['type'] ?? 'online_payment' ) ),
		'is_active'            => isset( $_POST['is_active'] ) ? 1 : 0,
		'is_online'            => isset( $_POST['is_online'] ) ? 1 : 0,
		'has_api'              => isset( $_POST['has_api'] ) ? 1 : 0,
		'test_mode'            => isset( $_POST['test_mode'] ) ? 1 : 0,
		'sandbox_url'          => esc_url_raw( wp_unslash( $_POST['sandbox_url'] ?? '' ) ),
		'live_url'             => esc_url_raw( wp_unslash( $_POST['live_url'] ?? '' ) ),
		'api_key'              => $encrypt( sanitize_text_field( wp_unslash( $_POST['api_key'] ?? '' ) ) ),
		'api_secret'           => $encrypt( sanitize_text_field( wp_unslash( $_POST['api_secret'] ?? '' ) ) ),
		'api_username'         => sanitize_text_field( wp_unslash( $_POST['api_username'] ?? '' ) ),
		'api_password'         => $encrypt( sanitize_text_field( wp_unslash( $_POST['api_password'] ?? '' ) ) ),
		'callback_url'         => esc_url_raw( wp_unslash( $_POST['callback_url'] ?? '' ) ),
		'webhook_url'          => esc_url_raw( wp_unslash( $_POST['webhook_url'] ?? '' ) ),
		'description'          => sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) ),
		'instructions'         => sanitize_textarea_field( wp_unslash( $_POST['instructions'] ?? '' ) ),
		'currency'             => $currency,
		'supported_currencies' => implode( ', ', $currencies ),
		'extra_attributes'     => wp_json_encode( $extra ),
		'sort_order'           => absint( $_POST['sort_order'] ?? 0 ),
		'updated_at'           => current_time( 'mysql' ),
	);
}

if ( isset( $_POST['esk_gateway_save'] ) ) {
	check_admin_referer( 'esk_gateway_form' );
	$esk_data = esk_gateway_payload();
	if ( '' !== $esk_data['name'] && '' !== $esk_data['code'] ) {
		$esk_dup = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$esk_table} WHERE code = %s LIMIT 1", $esk_data['code'] ) );
		if ( $esk_dup ) {
			esk_flash( 'error', __( 'A gateway with that code already exists.', 'eskoofy' ) );
		} else {
			$esk_data['created_at'] = current_time( 'mysql' );
			$wpdb->insert( $esk_table, $esk_data );
			esk_flash( 'success', __( 'Payment gateway created.', 'eskoofy' ) );
		}
	} else {
		esk_flash( 'error', __( 'Name and code are required.', 'eskoofy' ) );
	}
	wp_safe_redirect( admin_url( 'admin.php?page=esk-payment-gateways' ) );
	exit;
}

if ( isset( $_POST['esk_gateway_update'] ) ) {
	check_admin_referer( 'esk_gateway_form' );
	$esk_id = absint( $_POST['gateway_id'] ?? 0 );
	if ( $esk_id ) {
		$esk_data  = esk_gateway_payload();
		$esk_exist = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$esk_table} WHERE code = %s AND id <> %d LIMIT 1", $esk_data['code'], $esk_id ) );
		if ( $esk_exist ) {
			esk_flash( 'error', __( 'A gateway with that code already exists.', 'eskoofy' ) );
		} else {
			// Keep stored secrets when the form leaves them blank.
			$esk_current = $wpdb->get_row( $wpdb->prepare( "SELECT api_key, api_secret, api_password FROM {$esk_table} WHERE id = %d", $esk_id ), ARRAY_A );
			if ( is_array( $esk_current ) ) {
				foreach ( array( 'api_key', 'api_secret', 'api_password' ) as $esk_secret_col ) {
					if ( '' === (string) ( $_POST[ $esk_secret_col ] ?? '' ) && ! empty( $esk_current[ $esk_secret_col ] ) ) {
						unset( $esk_data[ $esk_secret_col ] );
					}
				}
			}
			$wpdb->update( $esk_table, $esk_data, array( 'id' => $esk_id ) );
			esk_flash( 'success', __( 'Payment gateway updated.', 'eskoofy' ) );
		}
	}
	wp_safe_redirect( admin_url( 'admin.php?page=esk-payment-gateways' ) );
	exit;
}

if ( isset( $_POST['esk_gateway_delete'] ) ) {
	check_admin_referer( 'esk_gateway_delete_' . absint( $_POST['gateway_id'] ?? 0 ) );
	$wpdb->update( $esk_table, array( 'deleted_at' => current_time( 'mysql' ) ), array( 'id' => absint( $_POST['gateway_id'] ?? 0 ) ) );
	esk_flash( 'success', __( 'Payment gateway deleted.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-payment-gateways' ) );
	exit;
}

$esk_rows      = $wpdb->get_results( "SELECT * FROM {$esk_table} WHERE deleted_at IS NULL ORDER BY sort_order ASC, name ASC" );
$esk_flash_ok  = esk_get_flash( 'success' );
$esk_flash_err = esk_get_flash( 'error' );

$esk_edit = null;
if ( isset( $_GET['edit_gateway'] ) ) {
	$esk_edit = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$esk_table} WHERE id = %d AND deleted_at IS NULL", absint( $_GET['edit_gateway'] ) ), ARRAY_A );
}

$esk_extra_text = '';
if ( is_array( $esk_edit ) ) {
	$esk_extra = json_decode( (string) ( $esk_edit['extra_attributes'] ?? '' ), true ) ?: array();
	$esk_lines = array();
	foreach ( $esk_extra as $esk_k => $esk_v ) {
		$esk_lines[] = $esk_k . '=' . $esk_v;
	}
	$esk_extra_text = implode( "\n", $esk_lines );
}
$esk_val = static fn ( string $key, string $default = '' ): string => (string) ( $esk_edit[ $key ] ?? $default );
?>
<div class="wrap esk-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Payment Gateways', 'eskoofy' ); ?></h1>
	<hr class="wp-header-end">

	<?php if ( $esk_flash_ok ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $esk_flash_ok ); ?></p></div>
	<?php endif; ?>
	<?php if ( $esk_flash_err ) : ?>
		<div class="notice notice-error is-dismissible"><p><?php echo esc_html( $esk_flash_err ); ?></p></div>
	<?php endif; ?>

	<p class="description" style="margin-bottom:1rem;">
		<?php esc_html_e( 'Only enabled gateways are offered to payers. Enable the optional gateways here, or add your own with its credentials and checkout endpoints. The table shows each gateway\'s driver, test mode and configuration status, plus a test payment action that simulates a checkout locally.', 'eskoofy' ); ?>
	</p>

	<div class="esk-card esk-form-card" style="margin-bottom:1.5rem;">
		<h2><?php echo $esk_edit ? esc_html__( 'Edit Gateway', 'eskoofy' ) : esc_html__( 'Add Gateway', 'eskoofy' ); ?></h2>
		<form method="post" class="esk-form">
			<?php wp_nonce_field( 'esk_gateway_form' ); ?>
			<?php if ( $esk_edit ) : ?>
				<input type="hidden" name="gateway_id" value="<?php echo esc_attr( (string) $esk_edit['id'] ); ?>">
			<?php endif; ?>

			<div class="esk-form-row">
				<div class="esk-form-group"><label><?php esc_html_e( 'Name', 'eskoofy' ); ?> *</label><input type="text" name="name" required value="<?php echo esc_attr( $esk_val( 'name' ) ); ?>"></div>
				<div class="esk-form-group"><label><?php esc_html_e( 'Code', 'eskoofy' ); ?> *</label><input type="text" name="code" required pattern="[A-Za-z0-9_]+" value="<?php echo esc_attr( $esk_val( 'code' ) ); ?>"></div>
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Type', 'eskoofy' ); ?></label>
					<select name="type">
						<?php
						foreach ( array(
							'online_payment'           => __( 'Online payment', 'eskoofy' ),
							'mobile_financial_service' => __( 'Mobile financial service', 'eskoofy' ),
							'bank'                     => __( 'Bank', 'eskoofy' ),
							'other'                    => __( 'Other', 'eskoofy' ),
						) as $esk_type => $esk_label ) :
							?>
							<option value="<?php echo esc_attr( $esk_type ); ?>" <?php selected( $esk_val( 'type', 'online_payment' ), $esk_type ); ?>><?php echo esc_html( $esk_label ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="esk-form-group"><label><?php esc_html_e( 'Currency', 'eskoofy' ); ?></label><input type="text" name="currency" maxlength="3" value="<?php echo esc_attr( $esk_val( 'currency', 'USD' ) ); ?>"></div>
			</div>

			<div class="esk-form-row">
				<div class="esk-form-group"><label><?php esc_html_e( 'Sandbox URL', 'eskoofy' ); ?></label><input type="url" name="sandbox_url" class="regular-text" value="<?php echo esc_attr( $esk_val( 'sandbox_url' ) ); ?>"></div>
				<div class="esk-form-group"><label><?php esc_html_e( 'Live URL', 'eskoofy' ); ?></label><input type="url" name="live_url" class="regular-text" value="<?php echo esc_attr( $esk_val( 'live_url' ) ); ?>"></div>
			</div>

			<div class="esk-form-row">
				<div class="esk-form-group"><label><?php esc_html_e( 'API Key', 'eskoofy' ); ?></label><input type="text" name="api_key" class="regular-text" autocomplete="off" value="<?php echo esc_attr( '' !== $esk_val( 'api_key' ) ? esk_decrypt_secret( (string) $esk_edit['api_key'] ) : '' ); ?>"></div>
				<div class="esk-form-group"><label><?php esc_html_e( 'API Secret', 'eskoofy' ); ?></label><input type="password" name="api_secret" class="regular-text" autocomplete="off" value="<?php echo esc_attr( '' !== $esk_val( 'api_secret' ) ? esk_decrypt_secret( (string) $esk_edit['api_secret'] ) : '' ); ?>"></div>
			</div>

			<div class="esk-form-row">
				<div class="esk-form-group"><label><?php esc_html_e( 'API Username', 'eskoofy' ); ?></label><input type="text" name="api_username" value="<?php echo esc_attr( $esk_val( 'api_username' ) ); ?>"></div>
				<div class="esk-form-group"><label><?php esc_html_e( 'API Password', 'eskoofy' ); ?></label><input type="password" name="api_password" autocomplete="off" value="<?php echo esc_attr( '' !== $esk_val( 'api_password' ) ? esk_decrypt_secret( (string) $esk_edit['api_password'] ) : '' ); ?>"></div>
			</div>

			<div class="esk-form-row">
				<div class="esk-form-group"><label><?php esc_html_e( 'Callback URL', 'eskoofy' ); ?></label><input type="url" name="callback_url" class="regular-text" value="<?php echo esc_attr( $esk_val( 'callback_url' ) ); ?>"></div>
				<div class="esk-form-group"><label><?php esc_html_e( 'Webhook URL', 'eskoofy' ); ?></label><input type="url" name="webhook_url" class="regular-text" value="<?php echo esc_attr( $esk_val( 'webhook_url' ) ); ?>"></div>
			</div>

			<div class="esk-form-row">
				<div class="esk-form-group"><label><?php esc_html_e( 'Supported currencies', 'eskoofy' ); ?></label><input type="text" name="supported_currencies" class="regular-text" value="<?php echo esc_attr( $esk_val( 'supported_currencies' ) ); ?>" placeholder="USD, EUR, GBP"></div>
				<div class="esk-form-group"><label><?php esc_html_e( 'Sort order', 'eskoofy' ); ?></label><input type="number" name="sort_order" min="0" value="<?php echo esc_attr( $esk_val( 'sort_order', '0' ) ); ?>"></div>
			</div>

			<div class="esk-form-group">
				<label><?php esc_html_e( 'Advanced options (one key=value per line)', 'eskoofy' ); ?></label>
				<textarea name="extra_attributes" rows="4" class="large-text" placeholder="checkout_url_template=https://pay.example.com/checkout/{reference}&#10;verify_url=https://api.example.com/verify&#10;refund_url=https://api.example.com/refund"><?php echo esc_textarea( $esk_extra_text ); ?></textarea>
				<p class="description"><?php esc_html_e( 'Supported keys: checkout_url_template, checkout_method, verify_url, verify_success_path, verify_success_value, refund_url, signature_header.', 'eskoofy' ); ?></p>
			</div>

			<div class="esk-form-group">
				<label><?php esc_html_e( 'Description', 'eskoofy' ); ?></label>
				<textarea name="description" rows="2" class="large-text"><?php echo esc_textarea( $esk_val( 'description' ) ); ?></textarea>
			</div>

			<div class="esk-form-row">
				<label><input type="checkbox" name="is_active" value="1" <?php checked( $esk_edit ? (int) $esk_edit['is_active'] : 0, 1 ); ?>> <?php esc_html_e( 'Enabled (show to payers)', 'eskoofy' ); ?></label>
				<label><input type="checkbox" name="is_online" value="1" <?php checked( $esk_edit ? (int) $esk_edit['is_online'] : 1, 1 ); ?>> <?php esc_html_e( 'Online (hosted checkout)', 'eskoofy' ); ?></label>
				<label><input type="checkbox" name="has_api" value="1" <?php checked( $esk_edit ? (int) $esk_edit['has_api'] : 1, 1 ); ?>> <?php esc_html_e( 'Has API', 'eskoofy' ); ?></label>
				<label><input type="checkbox" name="test_mode" value="1" <?php checked( $esk_edit ? (int) $esk_edit['test_mode'] : 1, 1 ); ?>> <?php esc_html_e( 'Sandbox / test mode', 'eskoofy' ); ?></label>
			</div>

			<button type="submit" name="<?php echo $esk_edit ? 'esk_gateway_update' : 'esk_gateway_save'; ?>" class="button button-primary"><?php echo $esk_edit ? esc_html__( 'Update Gateway', 'eskoofy' ) : esc_html__( 'Add Gateway', 'eskoofy' ); ?></button>
			<?php if ( $esk_edit ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=esk-payment-gateways' ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'eskoofy' ); ?></a>
			<?php endif; ?>
		</form>
	</div>

	<table class="wp-list-table widefat striped esk-table">
		<thead><tr>
			<th><?php esc_html_e( 'Name', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Code', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Type', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Currency', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Status', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Test mode', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Configured', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Driver', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Actions', 'eskoofy' ); ?></th>
		</tr></thead>
		<tbody>
			<?php if ( empty( $esk_rows ) ) : ?>
				<tr><td colspan="9"><?php esc_html_e( 'No gateways yet.', 'eskoofy' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $esk_rows as $esk_row ) : ?>
					<?php
					$esk_driver      = function_exists( 'esk_get_payment_gateway' ) ? esk_get_payment_gateway( $esk_row->code ) : null;
					$esk_configured  = $esk_driver ? $esk_driver->is_configured() : false;
					$esk_driver_name = $esk_driver ? $esk_driver->driver_name() : '-';
					?>
					<tr>
						<td><strong><?php echo esc_html( $esk_row->name ); ?></strong></td>
						<td><code><?php echo esc_html( $esk_row->code ); ?></code></td>
						<td><?php echo esc_html( $esk_row->type ); ?></td>
						<td><?php echo esc_html( $esk_row->currency ); ?></td>
						<td>
							<?php if ( (int) $esk_row->is_active ) : ?>
								<span class="esk-badge esk-badge-active"><?php esc_html_e( 'Enabled', 'eskoofy' ); ?></span>
							<?php else : ?>
								<span class="esk-badge esk-badge-pending"><?php esc_html_e( 'Disabled', 'eskoofy' ); ?></span>
							<?php endif; ?>
						</td>
						<td>
							<?php if ( (int) $esk_row->test_mode ) : ?>
								<span class="esk-badge esk-badge-pending"><?php esc_html_e( 'Sandbox', 'eskoofy' ); ?></span>
							<?php else : ?>
								<span class="esk-badge esk-badge-active"><?php esc_html_e( 'Live', 'eskoofy' ); ?></span>
							<?php endif; ?>
						</td>
						<td>
							<?php if ( $esk_configured ) : ?>
								<span class="esk-badge esk-badge-active"><?php esc_html_e( 'Yes', 'eskoofy' ); ?></span>
							<?php else : ?>
								<span class="esk-badge esk-badge-pending"><?php esc_html_e( 'No', 'eskoofy' ); ?></span>
							<?php endif; ?>
						</td>
						<td><code><?php echo esc_html( $esk_driver_name ); ?></code></td>
						<td>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=esk-payment-sandbox&gateway=' . rawurlencode( (string) $esk_row->code ) ) ); ?>" class="button button-small"><?php esc_html_e( 'Run test payment', 'eskoofy' ); ?></a>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=esk-payment-gateways&edit_gateway=' . (int) $esk_row->id ) ); ?>" class="button button-small"><?php esc_html_e( 'Edit', 'eskoofy' ); ?></a>
							<form method="post" style="display:inline;" onsubmit="return confirm('<?php esc_attr_e( 'Delete this gateway?', 'eskoofy' ); ?>');">
								<?php wp_nonce_field( 'esk_gateway_delete_' . (int) $esk_row->id ); ?>
								<input type="hidden" name="gateway_id" value="<?php echo esc_attr( (string) $esk_row->id ); ?>">
								<button type="submit" name="esk_gateway_delete" class="button button-small"><?php esc_html_e( 'Delete', 'eskoofy' ); ?></button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
