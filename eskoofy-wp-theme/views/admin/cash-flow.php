<?php
/**
 * Cash flow statement.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

$date_from = sanitize_text_field( $_GET['date_from'] ?? gmdate( 'Y-01-01' ) );
$date_to   = sanitize_text_field( $_GET['date_to'] ?? gmdate( 'Y-m-d' ) );

$inflows = (float) $wpdb->get_var(
	$wpdb->prepare(
		"SELECT COALESCE(SUM(paid_amount), 0) FROM {$wpdb->prefix}esk_fee_payments
		WHERE deleted_at IS NULL AND payment_date BETWEEN %s AND %s",
		$date_from, $date_to
	)
);

$outflows = (float) $wpdb->get_var(
	$wpdb->prepare(
		"SELECT COALESCE(SUM(amount), 0) FROM {$wpdb->prefix}esk_expenses
		WHERE date BETWEEN %s AND %s",
		$date_from, $date_to
	)
);

$net = $inflows - $outflows;
?>
<div class="wrap esk-admin-wrap">
	<h1><?php esc_html_e( 'Cash Flow', 'eskoofy' ); ?></h1>

	<form method="get" class="esk-form esk-inline-form" style="margin-bottom:1rem;">
		<input type="hidden" name="page" value="esk-cash-flow">
		<label><?php esc_html_e( 'From', 'eskoofy' ); ?>:</label>
		<input type="date" name="date_from" value="<?php echo esc_attr( $date_from ); ?>">
		<label><?php esc_html_e( 'To', 'eskoofy' ); ?>:</label>
		<input type="date" name="date_to" value="<?php echo esc_attr( $date_to ); ?>">
		<button type="submit" class="button"><?php esc_html_e( 'Apply', 'eskoofy' ); ?></button>
	</form>

	<div class="esk-card esk-form-card">
		<table class="wp-list-table widefat striped esk-table">
			<tbody>
				<tr><th><?php esc_html_e( 'Inflows (Fee Payments)', 'eskoofy' ); ?></th><td><?php echo esc_html( esk_format_currency( $inflows ) ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Outflows (Expenses)', 'eskoofy' ); ?></th><td><?php echo esc_html( esk_format_currency( $outflows ) ); ?></td></tr>
				<tr style="background:#f9fafb;font-weight:bold;"><th><?php esc_html_e( 'Net Cash Flow', 'eskoofy' ); ?></th><td><?php echo esc_html( esk_format_currency( $net ) ); ?></td></tr>
			</tbody>
		</table>
	</div>
</div>