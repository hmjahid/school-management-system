<?php
/**
 * Payments — esk_payments records with refund initiation and status update.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

if ( isset( $_POST['esk_payment_mark_paid'] ) ) {
	check_admin_referer( 'esk_payment_form' );
	$id = absint( $_POST['payment_id'] ?? 0 );
	if ( $id ) {
		$wpdb->update( $wpdb->prefix . 'esk_payments', array(
			'payment_status' => 'completed',
			'payment_date'   => gmdate( 'Y-m-d' ),
			'updated_by'     => get_current_user_id(),
		), array( 'id' => $id ) );
		esk_flash( 'success', __( 'Payment marked as paid.', 'eskoofy' ) );
	}
	wp_safe_redirect( admin_url( 'admin.php?page=esk-fee-payments' ) );
	exit;
}

if ( isset( $_POST['esk_refund_request'] ) ) {
	check_admin_referer( 'esk_payment_form' );
	$id     = absint( $_POST['payment_id'] ?? 0 );
	$amount = (float) ( $_POST['amount'] ?? 0 );
	$reason = sanitize_text_field( wp_unslash( $_POST['reason'] ?? '' ) );
	if ( $id && $amount > 0 ) {
		$wpdb->insert( $wpdb->prefix . 'esk_refunds', array(
			'payment_id'   => $id,
			'user_id'      => get_current_user_id(),
			'processed_by' => get_current_user_id(),
			'amount'       => $amount,
			'currency'     => 'BDT',
			'status'       => 'pending',
			'reason'       => $reason,
		) );
		esk_flash( 'success', __( 'Refund requested.', 'eskoofy' ) );
	}
	wp_safe_redirect( admin_url( 'admin.php?page=esk-fee-payments' ) );
	exit;
}

$payments = $wpdb->get_results(
	"SELECT p.*, u.display_name AS creator_name
	FROM {$wpdb->prefix}esk_payments p
	LEFT JOIN {$wpdb->prefix}users u ON p.created_by = u.ID
	WHERE p.deleted_at IS NULL ORDER BY p.id DESC LIMIT 200"
);

$refund_map = array();
$refunds = $wpdb->get_results( "SELECT payment_id, SUM(amount) AS total FROM {$wpdb->prefix}esk_refunds WHERE status != 'cancelled' GROUP BY payment_id" );
foreach ( $refunds as $r ) {
	$refund_map[ (int) $r->payment_id ] = (float) $r->total;
}

$flash = esk_get_flash( 'success' );
?>
<div class="wrap esk-admin-wrap">
	<h1><?php esc_html_e( 'Payments', 'eskoofy' ); ?></h1>

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<table class="wp-list-table widefat striped esk-table">
		<thead><tr>
			<th><?php esc_html_e( 'Invoice', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Method', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Amount', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Total', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Refunded', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Status', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Date', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Actions', 'eskoofy' ); ?></th>
		</tr></thead>
		<tbody>
			<?php if ( empty( $payments ) ) : ?>
				<tr><td colspan="8"><?php esc_html_e( 'No payment records.', 'eskoofy' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $payments as $p ) : ?>
					<?php $refunded = $refund_map[ (int) $p->id ] ?? 0; ?>
					<tr>
						<td><strong><?php echo esc_html( $p->invoice_number ); ?></strong></td>
						<td><?php echo esc_html( ucfirst( str_replace( '_', ' ', $p->payment_method ) ) ); ?></td>
						<td><?php echo esc_html( esk_format_currency( $p->amount ) ); ?></td>
						<td><?php echo esc_html( esk_format_currency( $p->total_amount ) ); ?></td>
						<td><?php echo $refunded ? esc_html( esk_format_currency( $refunded ) ) : '—'; ?></td>
						<td><span class="esk-badge esk-badge-<?php echo esc_attr( $p->payment_status ); ?>"><?php echo esc_html( ucfirst( $p->payment_status ) ); ?></span></td>
						<td><?php echo $p->payment_date ? esc_html( esk_date_format( $p->payment_date ) ) : '—'; ?></td>
						<td>
							<?php if ( 'pending' === $p->payment_status ) : ?>
								<form method="post" style="display:inline;">
									<?php wp_nonce_field( 'esk_payment_form' ); ?>
									<input type="hidden" name="payment_id" value="<?php echo esc_attr( $p->id ); ?>">
									<button type="submit" name="esk_payment_mark_paid" class="button button-small"><?php esc_html_e( 'Mark Paid', 'eskoofy' ); ?></button>
								</form>
							<?php endif; ?>
							<form method="post" style="display:inline;" onsubmit="return confirm('Initiate refund?');">
								<?php wp_nonce_field( 'esk_payment_form' ); ?>
								<input type="hidden" name="payment_id" value="<?php echo esc_attr( $p->id ); ?>">
								<input type="number" name="amount" step="0.01" max="<?php echo esc_attr( $p->total_amount - $refunded ); ?>" placeholder="<?php esc_attr_e( 'Amount', 'eskoofy' ); ?>" style="width:80px;">
								<input type="text" name="reason" placeholder="<?php esc_attr_e( 'Reason', 'eskoofy' ); ?>" style="width:120px;">
								<button type="submit" name="esk_refund_request" class="button button-small"><?php esc_html_e( 'Refund', 'eskoofy' ); ?></button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>