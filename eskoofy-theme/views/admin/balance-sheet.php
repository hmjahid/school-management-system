<?php
/**
 * Balance sheet — assets, liabilities, equity.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

$cash_at_bank = (float) $wpdb->get_var(
	"SELECT COALESCE(SUM(paid_amount), 0) FROM {$wpdb->prefix}esk_fee_payments WHERE deleted_at IS NULL"
) - (float) $wpdb->get_var( "SELECT COALESCE(SUM(amount), 0) FROM {$wpdb->prefix}esk_expenses" );

$accounts_receivable = (float) $wpdb->get_var(
	"SELECT COALESCE(SUM(balance), 0) FROM {$wpdb->prefix}esk_fee_payments WHERE deleted_at IS NULL"
);

$total_assets = $cash_at_bank + $accounts_receivable;

$equity = $cash_at_bank;
?>
<div class="wrap esk-admin-wrap">
	<h1><?php esc_html_e( 'Balance Sheet', 'eskoofy' ); ?></h1>

	<div class="esk-dashboard-columns">
		<div class="esk-dashboard-column esk-col-wide">
			<div class="esk-card">
				<h2><?php esc_html_e( 'Assets', 'eskoofy' ); ?></h2>
				<table class="wp-list-table widefat striped esk-table">
					<tbody>
						<tr><th><?php esc_html_e( 'Cash at Bank', 'eskoofy' ); ?></th><td><?php echo esc_html( esk_format_currency( $cash_at_bank ) ); ?></td></tr>
						<tr><th><?php esc_html_e( 'Accounts Receivable', 'eskoofy' ); ?></th><td><?php echo esc_html( esk_format_currency( $accounts_receivable ) ); ?></td></tr>
						<tr style="background:#f9fafb;font-weight:bold;"><th><?php esc_html_e( 'Total Assets', 'eskoofy' ); ?></th><td><?php echo esc_html( esk_format_currency( $total_assets ) ); ?></td></tr>
					</tbody>
				</table>
			</div>
		</div>
		<div class="esk-dashboard-column esk-col-narrow">
			<div class="esk-card">
				<h2><?php esc_html_e( 'Equity', 'eskoofy' ); ?></h2>
				<table class="wp-list-table widefat striped esk-table">
					<tbody>
						<tr><th><?php esc_html_e( 'Retained Earnings', 'eskoofy' ); ?></th><td><?php echo esc_html( esk_format_currency( $equity ) ); ?></td></tr>
						<tr style="background:#f9fafb;font-weight:bold;"><th><?php esc_html_e( 'Total Equity', 'eskoofy' ); ?></th><td><?php echo esc_html( esk_format_currency( $equity ) ); ?></td></tr>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>