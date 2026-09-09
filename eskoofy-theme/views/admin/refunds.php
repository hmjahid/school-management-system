<?php
/**
 * Refunds.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

if ( isset( $_POST['esk_refund_save'] ) ) {
	check_admin_referer( 'esk_refund_form' );
	$payment_id  = absint( $_POST['payment_id'] ?? 0 );
	$amount      = (float) ( $_POST['amount'] ?? 0 );
	$reason      = sanitize_text_field( wp_unslash( $_POST['reason'] ?? '' ) );
	if ( $payment_id && $amount > 0 ) {
		$wpdb->insert( $wpdb->prefix . 'esk_refunds', array(
			'payment_id'   => $payment_id,
			'user_id'      => get_current_user_id(),
			'processed_by' => get_current_user_id(),
			'amount'       => $amount,
			'currency'     => 'BDT',
			'status'       => 'pending',
			'reason'       => $reason,
		) );
		esk_flash( 'success', __( 'Refund request created.', 'eskoofy' ) );
	}
	wp_safe_redirect( admin_url( 'admin.php?page=esk-refunds' ) );
	exit;
}

if ( isset( $_GET['action'] ) && 'process' === $_GET['action'] && ! empty( $_GET['id'] ) ) {
	$id = absint( $_GET['id'] );
	check_admin_referer( 'esk_refund_process_' . $id );
	$wpdb->update( $wpdb->prefix . 'esk_refunds', array(
		'status'       => 'processed',
		'processed_at' => current_time( 'mysql' ),
	), array( 'id' => $id ) );
	esk_flash( 'success', __( 'Refund processed.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-refunds' ) );
	exit;
}

if ( isset( $_GET['action'] ) && 'cancel' === $_GET['action'] && ! empty( $_GET['id'] ) ) {
	$id = absint( $_GET['id'] );
	check_admin_referer( 'esk_refund_cancel_' . $id );
	$wpdb->update( $wpdb->prefix . 'esk_refunds', array( 'status' => 'cancelled' ), array( 'id' => $id ) );
	esk_flash( 'success', __( 'Refund cancelled.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-refunds' ) );
	exit;
}

$refunds = $wpdb->get_results(
	"SELECT r.*, p.invoice_number, p.payment_method
	FROM {$wpdb->prefix}esk_refunds r
	LEFT JOIN {$wpdb->prefix}esk_payments p ON r.payment_id = p.id
	ORDER BY r.created_at DESC"
);
$flash = esk_get_flash( 'success' );
?>
<div class="wrap esk-admin-wrap">
	<h1><?php esc_html_e( 'Refunds', 'eskoofy' ); ?></h1>

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<div class="esk-card esk-form-card" style="margin-bottom:1.5rem;">
		<h2><?php esc_html_e( 'New Refund', 'eskoofy' ); ?></h2>
		<form method="post" class="esk-form">
			<?php wp_nonce_field( 'esk_refund_form' ); ?>
			<div class="esk-form-row">
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Payment', 'eskoofy' ); ?></label>
					<select name="payment_id" required>
						<option value=""><?php esc_html_e( 'Select', 'eskoofy' ); ?></option>
						<?php
						$payments = $wpdb->get_results( "SELECT id, invoice_number, total_amount FROM {$wpdb->prefix}esk_payments WHERE payment_status = 'completed' AND deleted_at IS NULL ORDER BY id DESC LIMIT 100" );
						foreach ( $payments as $p ) :
							?>
							<option value="<?php echo esc_attr( $p->id ); ?>"><?php echo esc_html( $p->invoice_number . ' (' . esk_format_currency( $p->total_amount ) . ')' ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Amount', 'eskoofy' ); ?></label>
					<input type="number" step="0.01" name="amount" min="0" required>
				</div>
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Reason', 'eskoofy' ); ?></label>
					<input type="text" name="reason">
				</div>
			</div>
			<button type="submit" name="esk_refund_save" class="button button-primary"><?php esc_html_e( 'Create Refund', 'eskoofy' ); ?></button>
		</form>
	</div>

	<table class="wp-list-table widefat striped esk-table">
		<thead><tr>
			<th><?php esc_html_e( 'Invoice', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Amount', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Reason', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Status', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Actions', 'eskoofy' ); ?></th>
		</tr></thead>
		<tbody>
			<?php if ( empty( $refunds ) ) : ?>
				<tr><td colspan="5"><?php esc_html_e( 'No refunds.', 'eskoofy' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $refunds as $r ) : ?>
					<tr>
						<td><?php echo esc_html( $r->invoice_number ); ?></td>
						<td><?php echo esc_html( esk_format_currency( $r->amount ) ); ?></td>
						<td><?php echo esc_html( $r->reason ); ?></td>
						<td><span class="esk-badge esk-badge-<?php echo esc_attr( $r->status ); ?>"><?php echo esc_html( ucfirst( $r->status ) ); ?></span></td>
						<td>
							<?php if ( 'pending' === $r->status ) : ?>
								<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=esk-refunds&action=process&id=' . $r->id ), 'esk_refund_process_' . $r->id ) ); ?>" class="button button-small"><?php esc_html_e( 'Process', 'eskoofy' ); ?></a>
								<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=esk-refunds&action=cancel&id=' . $r->id ), 'esk_refund_cancel_' . $r->id ) ); ?>" class="button button-small"><?php esc_html_e( 'Cancel', 'eskoofy' ); ?></a>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>