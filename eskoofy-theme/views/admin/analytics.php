<?php
/**
 * Analytics dashboard.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

$student_growth = $wpdb->get_results(
	"SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, COUNT(*) AS total
	FROM {$wpdb->prefix}esk_students
	WHERE deleted_at IS NULL AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
	GROUP BY month ORDER BY month"
);

$fee_collection = $wpdb->get_results(
	"SELECT DATE_FORMAT(payment_date, '%Y-%m') AS month, SUM(paid_amount) AS total
	FROM {$wpdb->prefix}esk_fee_payments
	WHERE deleted_at IS NULL AND payment_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
	GROUP BY month ORDER BY month"
);

$attendance_trend = $wpdb->get_results(
	"SELECT status, COUNT(*) AS total FROM {$wpdb->prefix}esk_attendances
	WHERE date >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY status"
);
?>
<div class="wrap esk-admin-wrap">
	<h1><?php esc_html_e( 'Analytics', 'eskoofy' ); ?></h1>

	<div class="esk-dashboard-columns">
		<div class="esk-dashboard-column esk-col-wide">
			<div class="esk-card">
				<h2><?php esc_html_e( 'Student Growth (last 12 months)', 'eskoofy' ); ?></h2>
				<table class="esk-table">
					<thead><tr><th><?php esc_html_e( 'Month', 'eskoofy' ); ?></th><th><?php esc_html_e( 'New Students', 'eskoofy' ); ?></th></tr></thead>
					<tbody>
						<?php if ( empty( $student_growth ) ) : ?>
							<tr><td colspan="2"><?php esc_html_e( 'No data.', 'eskoofy' ); ?></td></tr>
						<?php else : ?>
							<?php foreach ( $student_growth as $g ) : ?>
								<tr><td><?php echo esc_html( $g->month ); ?></td><td><?php echo esc_html( number_format_i18n( $g->total ) ); ?></td></tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>

			<div class="esk-card">
				<h2><?php esc_html_e( 'Fee Collection (last 12 months)', 'eskoofy' ); ?></h2>
				<table class="esk-table">
					<thead><tr><th><?php esc_html_e( 'Month', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Total', 'eskoofy' ); ?></th></tr></thead>
					<tbody>
						<?php if ( empty( $fee_collection ) ) : ?>
							<tr><td colspan="2"><?php esc_html_e( 'No data.', 'eskoofy' ); ?></td></tr>
						<?php else : ?>
							<?php foreach ( $fee_collection as $f ) : ?>
								<tr><td><?php echo esc_html( $f->month ); ?></td><td><?php echo esc_html( esk_format_currency( $f->total ) ); ?></td></tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>

		<div class="esk-dashboard-column esk-col-narrow">
			<div class="esk-card">
				<h2><?php esc_html_e( 'Attendance (last 30 days)', 'eskoofy' ); ?></h2>
				<table class="esk-table">
					<thead><tr><th><?php esc_html_e( 'Status', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Count', 'eskoofy' ); ?></th></tr></thead>
					<tbody>
						<?php if ( empty( $attendance_trend ) ) : ?>
							<tr><td colspan="2"><?php esc_html_e( 'No data.', 'eskoofy' ); ?></td></tr>
						<?php else : ?>
							<?php foreach ( $attendance_trend as $a ) : ?>
								<tr><td><span class="esk-badge esk-badge-<?php echo esc_attr( $a->status ); ?>"><?php echo esc_html( ucfirst( $a->status ) ); ?></span></td><td><?php echo esc_html( number_format_i18n( $a->total ) ); ?></td></tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>