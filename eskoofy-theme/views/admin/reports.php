<?php
/**
 * Reports — read-only overview of key metrics.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

$student_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}esk_students WHERE status = 'active' AND deleted_at IS NULL" );
$teacher_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}esk_teachers" );
$class_count   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}esk_classes" );

$fee_total = (float) $wpdb->get_var(
	"SELECT COALESCE(SUM(paid_amount), 0) FROM {$wpdb->prefix}esk_fee_payments WHERE deleted_at IS NULL"
);

$expense_total = (float) $wpdb->get_var(
	"SELECT COALESCE(SUM(amount), 0) FROM {$wpdb->prefix}esk_expenses"
);

$attendance_today = $wpdb->get_row(
	$wpdb->prepare(
		"SELECT
			COUNT(*) AS total,
			SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) AS present,
			SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) AS absent
		FROM {$wpdb->prefix}esk_attendances WHERE date = %s",
		gmdate( 'Y-m-d' )
	)
);

$students_by_class = $wpdb->get_results(
	"SELECT c.name, COUNT(s.id) AS count
	FROM {$wpdb->prefix}esk_classes c
	LEFT JOIN {$wpdb->prefix}esk_students s ON s.class_id = c.id AND s.status = 'active' AND s.deleted_at IS NULL
	GROUP BY c.id, c.name
	ORDER BY c.name"
);
?>
<div class="wrap esk-admin-wrap">
	<h1><?php esc_html_e( 'Reports', 'eskoofy' ); ?></h1>

	<div class="esk-dashboard-columns">
		<div class="esk-dashboard-column">
			<div class="esk-card">
				<h2><?php esc_html_e( 'Overview', 'eskoofy' ); ?></h2>
				<table class="esk-table">
					<tr><th><?php esc_html_e( 'Total Students', 'eskoofy' ); ?></th><td><strong><?php echo esc_html( $student_count ); ?></strong></td></tr>
					<tr><th><?php esc_html_e( 'Total Teachers', 'eskoofy' ); ?></th><td><strong><?php echo esc_html( $teacher_count ); ?></strong></td></tr>
					<tr><th><?php esc_html_e( 'Total Classes', 'eskoofy' ); ?></th><td><strong><?php echo esc_html( $class_count ); ?></strong></td></tr>
					<tr><th><?php esc_html_e( 'Total Fee Collected', 'eskoofy' ); ?></th><td><strong><?php echo esc_html( esk_format_currency( $fee_total ) ); ?></strong></td></tr>
					<tr><th><?php esc_html_e( 'Total Expenses', 'eskoofy' ); ?></th><td><strong><?php echo esc_html( esk_format_currency( $expense_total ) ); ?></strong></td></tr>
				</table>
			</div>
		</div>

		<div class="esk-dashboard-column">
			<div class="esk-card">
				<h2><?php esc_html_e( 'Attendance Today', 'eskoofy' ); ?></h2>
				<?php if ( $attendance_today && $attendance_today->total > 0 ) : ?>
					<table class="esk-table">
						<tr><th><?php esc_html_e( 'Total Marked', 'eskoofy' ); ?></th><td><?php echo esc_html( $attendance_today->total ); ?></td></tr>
						<tr><th><?php esc_html_e( 'Present', 'eskoofy' ); ?></th><td><?php echo esc_html( $attendance_today->present ); ?></td></tr>
						<tr><th><?php esc_html_e( 'Absent', 'eskoofy' ); ?></th><td><?php echo esc_html( $attendance_today->absent ); ?></td></tr>
					</table>
				<?php else : ?>
					<p><?php esc_html_e( 'No attendance marked today.', 'eskoofy' ); ?></p>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<div class="esk-card" style="margin-top:1.5rem;">
		<h2><?php esc_html_e( 'Students by Class', 'eskoofy' ); ?></h2>
		<table class="wp-list-table widefat striped esk-table">
			<thead><tr><th><?php esc_html_e( 'Class', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Students', 'eskoofy' ); ?></th></tr></thead>
			<tbody>
				<?php if ( empty( $students_by_class ) ) : ?>
					<tr><td colspan="2"><?php esc_html_e( 'No data.', 'eskoofy' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $students_by_class as $row ) : ?>
						<tr>
							<td><?php echo esc_html( $row->name ); ?></td>
							<td><?php echo esc_html( $row->count ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>
