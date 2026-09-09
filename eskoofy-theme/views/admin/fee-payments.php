<?php
/**
 * Fee payment records view.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

$payments = $wpdb->get_results(
	"SELECT fp.*, s.admission_number, u.display_name, f.name AS fee_name
	FROM {$wpdb->prefix}esk_fee_payments fp
	JOIN {$wpdb->prefix}esk_students s ON fp.student_id = s.id
	JOIN {$wpdb->prefix}users u ON s.user_id = u.ID
	JOIN {$wpdb->prefix}esk_fees f ON fp.fee_id = f.id
	WHERE fp.deleted_at IS NULL
	ORDER BY fp.created_at DESC
	LIMIT 100"
);
?>
<div class="wrap esk-admin-wrap">
	<h1><?php esc_html_e( 'Fee Payments', 'eskoofy' ); ?></h1>

	<table class="wp-list-table widefat striped esk-table">
		<thead><tr>
			<th><?php esc_html_e( 'Invoice', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Student', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Fee', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Amount', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Paid', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Method', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Date', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Status', 'eskoofy' ); ?></th>
		</tr></thead>
		<tbody>
			<?php if ( empty( $payments ) ) : ?>
				<tr><td colspan="8"><?php esc_html_e( 'No payment records.', 'eskoofy' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $payments as $p ) : ?>
					<tr>
						<td><?php echo esc_html( $p->invoice_number ); ?></td>
						<td><?php echo esc_html( $p->display_name ); ?> <small>(<?php echo esc_html( $p->admission_number ); ?>)</small></td>
						<td><?php echo esc_html( $p->fee_name ); ?></td>
						<td><?php echo esc_html( esk_format_currency( $p->amount ) ); ?></td>
						<td><?php echo esc_html( esk_format_currency( $p->paid_amount ) ); ?></td>
						<td><?php echo esc_html( ucfirst( str_replace( '_', ' ', $p->payment_method ) ) ); ?></td>
						<td><?php echo esc_html( esk_date_format( $p->payment_date ) ); ?></td>
						<td><span class="esk-badge esk-badge-<?php echo esc_attr( $p->status ); ?>"><?php echo esc_html( ucfirst( $p->status ) ); ?></span></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
