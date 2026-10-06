<?php
/**
 * Payment sandbox — simulate a payment outcome for a reference.
 *
 * Two jobs:
 *   1. Start a throwaway test payment for any registered gateway (the "Run
 *      test payment" action on the Payment Gateways diagnostics table).
 *   2. Simulate success / failure / cancel for a payment reference by running
 *      it through the theme's normal callback + verify path
 *      (esk_rest_payment_callback) with the credential-free test gateway —
 *      so no external host is ever contacted, and a paid payment re-verifies
 *      as paid (idempotent).
 *
 * @package Eskoofy
 */

defined( 'ABSPATH' ) || exit;
global $wpdb;

$esk_payments_table = $wpdb->prefix . 'esk_payments';

/* ─── Start a test payment ─────────────────────────────────────── */

if ( isset( $_POST['esk_sandbox_create'] ) ) {
	check_admin_referer( 'esk_payment_sandbox_create' );

	$esk_new_code   = sanitize_key( wp_unslash( $_POST['gateway'] ?? 'test_gateway' ) );
	$esk_new_amount = (float) ( $_POST['amount'] ?? 0 );
	$esk_new_gw     = esk_get_payment_gateway( $esk_new_code );

	if ( ! $esk_new_gw ) {
		esk_flash( 'error', __( 'That payment gateway is not available.', 'eskoofy' ) );
		wp_safe_redirect( admin_url( 'admin.php?page=esk-payment-sandbox' ) );
		exit;
	}

	if ( $esk_new_amount <= 0 ) {
		$esk_new_amount = 100;
	}

	$esk_new_invoice = esk_generate_number( 'INV', 'payments' );
	$esk_inserted    = $wpdb->insert(
		$esk_payments_table,
		array(
			'invoice_number'  => $esk_new_invoice,
			'amount'          => $esk_new_amount,
			'paid_amount'     => 0,
			'due_amount'      => $esk_new_amount,
			'total_amount'    => $esk_new_amount,
			'payment_method'  => $esk_new_code,
			'payment_status'  => 'pending',
			'payment_details' => wp_json_encode( array( 'description' => 'Sandbox test payment' ) ),
			'metadata'        => wp_json_encode( array( 'sandbox' => true ) ),
			'created_by'      => get_current_user_id(),
			'created_at'      => current_time( 'mysql' ),
			'updated_at'      => current_time( 'mysql' ),
		)
	);

	if ( ! $esk_inserted ) {
		esk_flash( 'error', __( 'Could not create the test payment. Try again.', 'eskoofy' ) );
		wp_safe_redirect( admin_url( 'admin.php?page=esk-payment-sandbox' ) );
		exit;
	}

	wp_safe_redirect(
		admin_url(
			'admin.php?page=esk-payment-sandbox&' . http_build_query(
				array(
					'gateway'  => $esk_new_code,
					'order_id' => $esk_new_invoice,
				)
			)
		)
	);
	exit;
}

/* ─── Simulate an outcome ──────────────────────────────────────── */

if ( isset( $_POST['esk_sandbox_simulate'] ) ) {
	check_admin_referer( 'esk_payment_sandbox' );

	$esk_sim   = sanitize_key( wp_unslash( $_POST['simulate'] ?? '' ) );
	$esk_ref   = sanitize_text_field( wp_unslash( $_POST['order_id'] ?? '' ) );
	$esk_gw_in = sanitize_key( wp_unslash( $_POST['gateway'] ?? 'test_gateway' ) );
	$esk_back  = admin_url(
		'admin.php?page=esk-payment-sandbox&' . http_build_query(
			array(
				'gateway'  => $esk_gw_in,
				'order_id' => $esk_ref,
			)
		)
	);

	if ( '' === $esk_ref || ! in_array( $esk_sim, array( 'success', 'failure', 'cancel' ), true ) ) {
		esk_flash( 'error', __( 'The sandbox simulation could not run. Reload the page and try again.', 'eskoofy' ) );
		wp_safe_redirect( admin_url( 'admin.php?page=esk-payment-sandbox' ) );
		exit;
	}

	// Reuse the product's payment callback + verify path. The test gateway
	// decides the outcome, so verification never leaves this host.
	$esk_request = new WP_REST_Request( 'GET' );
	$esk_request->set_param( 'gateway', 'test_gateway' );
	$esk_request->set_param( 'order_id', $esk_ref );
	$esk_request->set_param( 'simulate', $esk_sim );
	if ( 'success' !== $esk_sim ) {
		$esk_request->set_param( 'status', 'cancel' === $esk_sim ? 'cancel' : 'failed' );
	}

	$esk_callback = esk_rest_payment_callback( $esk_request );
	$esk_cb_data  = (array) $esk_callback->get_data();

	if ( 200 !== (int) $esk_callback->get_status() ) {
		esk_flash( 'error', __( 'Payment reference not found in the sandbox.', 'eskoofy' ) );
		wp_safe_redirect( admin_url( 'admin.php?page=esk-payment-sandbox' ) );
		exit;
	}

	if ( ! empty( $esk_cb_data['success'] ) ) {
		// Paid — hand over to the product's normal payment-success surface.
		$esk_success = ! empty( $esk_cb_data['redirect'] ) ? (string) $esk_cb_data['redirect'] : home_url( '/fees/?payment=success' );
		wp_safe_redirect( $esk_success );
		exit;
	}

	esk_flash(
		'error',
		'cancel' === $esk_sim
			? __( 'Test payment cancelled — the payment was not completed.', 'eskoofy' )
			: __( 'Test payment failed — the payment was marked as failed.', 'eskoofy' )
	);
	wp_safe_redirect( $esk_back );
	exit;
}

/* ─── Render ───────────────────────────────────────────────────── */

$esk_reference = sanitize_text_field( wp_unslash( $_GET['order_id'] ?? '' ) );
$esk_gw_pref   = sanitize_key( wp_unslash( $_GET['gateway'] ?? '' ) );

$esk_payment = null;
if ( '' !== $esk_reference ) {
	$esk_payment = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$esk_payments_table} WHERE invoice_number = %s AND deleted_at IS NULL",
			$esk_reference
		)
	);
}

$esk_gw_code  = is_object( $esk_payment ) ? (string) $esk_payment->payment_method : $esk_gw_pref;
$esk_gw       = '' !== $esk_gw_code ? esk_get_payment_gateway( $esk_gw_code ) : null;
$esk_gw_label = $esk_gw ? $esk_gw->name : $esk_gw_code;

$esk_currency = 'BDT';
if ( is_object( $esk_payment ) ) {
	$esk_currency = esk_refund_currency( (int) $esk_payment->id );
} elseif ( $esk_gw ) {
	$esk_gw_data  = $esk_gw->get_gateway_data();
	$esk_currency = ! empty( $esk_gw_data['currency'] ) ? (string) $esk_gw_data['currency'] : 'BDT';
}

$esk_flash_ok  = esk_get_flash( 'success' );
$esk_flash_err = esk_get_flash( 'error' );
$esk_gateways  = esk_init_payment_gateways();
?>
<div class="wrap esk-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Payment Sandbox', 'eskoofy' ); ?></h1>
	<hr class="wp-header-end">

	<?php if ( $esk_flash_ok ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $esk_flash_ok ); ?></p></div>
	<?php endif; ?>
	<?php if ( $esk_flash_err ) : ?>
		<div class="notice notice-error is-dismissible"><p><?php echo esc_html( $esk_flash_err ); ?></p></div>
	<?php endif; ?>

	<?php if ( is_object( $esk_payment ) ) : ?>
		<div class="esk-card esk-form-card" style="margin-bottom:1.5rem;">
			<h2><?php esc_html_e( 'Simulate a payment outcome', 'eskoofy' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Free test payment — no money moves and no external service is called. Pick an outcome below to run the payment through the normal verify and status flow.', 'eskoofy' ); ?>
			</p>

			<table class="form-table" style="max-width:40rem;">
				<tr>
					<th><?php esc_html_e( 'Gateway', 'eskoofy' ); ?></th>
					<td><strong><?php echo esc_html( $esk_gw_label ); ?></strong> <code><?php echo esc_html( $esk_gw_code ); ?></code></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Amount', 'eskoofy' ); ?></th>
					<td><?php echo esc_html( esk_format_currency( $esk_payment->total_amount ) ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Currency', 'eskoofy' ); ?></th>
					<td><?php echo esc_html( $esk_currency ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Reference', 'eskoofy' ); ?></th>
					<td><code><?php echo esc_html( $esk_payment->invoice_number ); ?></code></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Status', 'eskoofy' ); ?></th>
					<td><span class="esk-badge esk-badge-<?php echo esc_attr( $esk_payment->payment_status ); ?>"><?php echo esc_html( ucfirst( $esk_payment->payment_status ) ); ?></span></td>
				</tr>
				<?php if ( ! empty( $esk_payment->transaction_id ) ) : ?>
					<tr>
						<th><?php esc_html_e( 'Transaction', 'eskoofy' ); ?></th>
						<td><code><?php echo esc_html( $esk_payment->transaction_id ); ?></code></td>
					</tr>
				<?php endif; ?>
			</table>

			<form method="post" class="esk-form">
				<?php wp_nonce_field( 'esk_payment_sandbox' ); ?>
				<input type="hidden" name="gateway" value="<?php echo esc_attr( $esk_gw_code ); ?>">
				<input type="hidden" name="order_id" value="<?php echo esc_attr( $esk_payment->invoice_number ); ?>">
				<input type="hidden" name="esk_sandbox_simulate" value="1">
				<button type="submit" name="simulate" value="success" class="button button-primary"><?php esc_html_e( 'Simulate success', 'eskoofy' ); ?></button>
				<button type="submit" name="simulate" value="failure" class="button"><?php esc_html_e( 'Simulate failure', 'eskoofy' ); ?></button>
				<button type="submit" name="simulate" value="cancel" class="button"><?php esc_html_e( 'Cancel', 'eskoofy' ); ?></button>
			</form>

			<p class="description" style="margin-top:1rem;">
				<?php esc_html_e( 'Simulate success marks the payment paid with the transaction id TEST-<reference> and continues to the usual payment-success page. Simulating success again on a paid payment is a no-op. Failure and cancel leave the payment unpaid.', 'eskoofy' ); ?>
			</p>
		</div>

		<p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=esk-payment-gateways' ) ); ?>" class="button">&laquo; <?php esc_html_e( 'Back to Payment Gateways', 'eskoofy' ); ?></a>
		</p>
	<?php else : ?>
		<p class="description" style="margin-bottom:1rem;">
			<?php esc_html_e( 'Start a throwaway payment to test a gateway end to end, or pay a fee with the Test / Sandbox gateway to land on this page automatically.', 'eskoofy' ); ?>
		</p>

		<div class="esk-card esk-form-card" style="margin-bottom:1.5rem;">
			<h2><?php esc_html_e( 'Run test payment', 'eskoofy' ); ?></h2>
			<form method="post" class="esk-form">
				<?php wp_nonce_field( 'esk_payment_sandbox_create' ); ?>
				<div class="esk-form-row">
					<div class="esk-form-group">
						<label><?php esc_html_e( 'Payment Gateway', 'eskoofy' ); ?></label>
						<select name="gateway">
							<?php foreach ( $esk_gateways as $esk_option ) : ?>
								<option value="<?php echo esc_attr( $esk_option->code ); ?>" <?php selected( $esk_gw_pref ? $esk_gw_pref : 'test_gateway', $esk_option->code ); ?>>
									<?php echo esc_html( $esk_option->name ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="esk-form-group">
						<label><?php esc_html_e( 'Amount', 'eskoofy' ); ?></label>
						<input type="number" name="amount" step="0.01" min="1" value="100">
					</div>
				</div>
				<button type="submit" name="esk_sandbox_create" class="button button-primary"><?php esc_html_e( 'Create test payment', 'eskoofy' ); ?></button>
			</form>
			<p class="description">
				<?php esc_html_e( 'The payment starts as pending; the next screen lets you simulate success, failure or cancel. Verification always runs through the local test gateway, so no external API is ever contacted.', 'eskoofy' ); ?>
			</p>
		</div>

		<p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=esk-payment-gateways' ) ); ?>" class="button">&laquo; <?php esc_html_e( 'Back to Payment Gateways', 'eskoofy' ); ?></a>
		</p>
	<?php endif; ?>
</div>
