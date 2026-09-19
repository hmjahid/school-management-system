<?php
/**
 * Income statement from esk_ledger_entries (or fall back to fee_payments + expenses).
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

$date_from = sanitize_text_field( $_GET['date_from'] ?? gmdate( 'Y-01-01' ) );
$date_to   = sanitize_text_field( $_GET['date_to'] ?? gmdate( 'Y-m-d' ) );

$revenue = (float) $wpdb->get_var(
	$wpdb->prepare(
		"SELECT COALESCE(SUM(paid_amount), 0) FROM {$wpdb->prefix}esk_fee_payments
		WHERE deleted_at IS NULL AND payment_date BETWEEN %s AND %s",
		$date_from, $date_to
	)
);

$expenses = (float) $wpdb->get_var(
	$wpdb->prepare(
		"SELECT COALESCE(SUM(amount), 0) FROM {$wpdb->prefix}esk_expenses
		WHERE date BETWEEN %s AND %s",
		$date_from, $date_to
	)
);

$net = $revenue - $expenses;
?>
<div class="wrap esk-admin-wrap">
	<h1><?php esc_html_e( 'Income Statement', 'eskoofy' ); ?></h1>

	<form method="get" class="esk-form esk-inline-form" style="margin-bottom:1rem;">
		<input type="hidden" name="page" value="esk-income-statement">
		<label><?php esc_html_e( 'From', 'eskoofy' ); ?>:</label>
		<input type="date" name="date_from" value="<?php echo esc_attr( $date_from ); ?>">
		<label><?php esc_html_e( 'To', 'eskoofy' ); ?>:</label>
		<input type="date" name="date_to" value="<?php echo esc_attr( $date_to ); ?>">
		<button type="submit" class="button"><?php esc_html_e( 'Apply', 'eskoofy' ); ?></button>
	</form>

	<div class="esk-card esk-form-card">
		<table class="wp-list-table widefat striped esk-table">
			<tbody>
				<tr><th><?php esc_html_e( 'Revenue (Fee Collections)', 'eskoofy' ); ?></th><td><?php echo esc_html( esk_format_currency( $revenue ) ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Expenses', 'eskoofy' ); ?></th><td><?php echo esc_html( esk_format_currency( $expenses ) ); ?></td></tr>
				<tr style="background:#f9fafb;font-weight:bold;"><th><?php esc_html_e( 'Net Income', 'eskoofy' ); ?></th><td><?php echo esc_html( esk_format_currency( $net ) ); ?></td></tr>
			</tbody>
		</table>
	</div>
</div>