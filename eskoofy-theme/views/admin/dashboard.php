<?php
/**
 * Admin Dashboard.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

$students_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}esk_students WHERE deleted_at IS NULL AND status = 'active'" );
$teachers_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}esk_teachers" );
$classes_count  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}esk_classes" );
$admissions_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}esk_admissions WHERE status IN ('submitted','under_review') AND deleted_at IS NULL" );

$recent_students = $wpdb->get_results(
	"SELECT s.*, u.display_name FROM {$wpdb->prefix}esk_students s
	JOIN {$wpdb->prefix}users u ON s.user_id = u.ID
	WHERE s.deleted_at IS NULL ORDER BY s.created_at DESC LIMIT 5"
);

$recent_admissions = $wpdb->get_results(
	"SELECT * FROM {$wpdb->prefix}esk_admissions WHERE deleted_at IS NULL ORDER BY created_at DESC LIMIT 5"
);
?>
<div class="wrap esk-admin-wrap">
	<h1><?php esc_html_e( 'Dashboard', 'eskoofy' ); ?></h1>

	<div class="esk-stats-grid">
		<div class="esk-stat-card esk-stat-primary">
			<div class="esk-stat-icon"><span class="dashicons dashicons-groups"></span></div>
			<div class="esk-stat-content">
				<h3><?php echo esc_html( number_format_i18n( $students_count ) ); ?></h3>
				<p><?php esc_html_e( 'Active Students', 'eskoofy' ); ?></p>
			</div>
		</div>
		<div class="esk-stat-card esk-stat-success">
			<div class="esk-stat-icon"><span class="dashicons dashicons-welcome-learn-more"></span></div>
			<div class="esk-stat-content">
				<h3><?php echo esc_html( number_format_i18n( $teachers_count ) ); ?></h3>
				<p><?php esc_html_e( 'Teachers', 'eskoofy' ); ?></p>
			</div>
		</div>
		<div class="esk-stat-card esk-stat-warning">
			<div class="esk-stat-icon"><span class="dashicons dashicons-building"></span></div>
			<div class="esk-stat-content">
				<h3><?php echo esc_html( number_format_i18n( $classes_count ) ); ?></h3>
				<p><?php esc_html_e( 'Classes', 'eskoofy' ); ?></p>
			</div>
		</div>
		<div class="esk-stat-card esk-stat-info">
			<div class="esk-stat-icon"><span class="dashicons dashicons-clipboard"></span></div>
			<div class="esk-stat-content">
				<h3><?php echo esc_html( number_format_i18n( $admissions_count ) ); ?></h3>
				<p><?php esc_html_e( 'Pending Admissions', 'eskoofy' ); ?></p>
			</div>
		</div>
	</div>

	<div class="esk-dashboard-columns">
		<div class="esk-dashboard-column esk-col-wide">
			<div class="esk-card">
				<h2><?php esc_html_e( 'Recent Students', 'eskoofy' ); ?></h2>
				<table class="esk-table esk-table-striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Name', 'eskoofy' ); ?></th>
							<th><?php esc_html_e( 'Admission No', 'eskoofy' ); ?></th>
							<th><?php esc_html_e( 'Status', 'eskoofy' ); ?></th>
							<th><?php esc_html_e( 'Date', 'eskoofy' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $recent_students ) ) : ?>
							<tr><td colspan="4"><?php esc_html_e( 'No students yet.', 'eskoofy' ); ?></td></tr>
						<?php else : ?>
							<?php foreach ( $recent_students as $s ) : ?>
								<tr>
									<td><?php echo esc_html( $s->display_name ); ?></td>
									<td><?php echo esc_html( $s->admission_number ); ?></td>
									<td><span class="esk-badge esk-badge-<?php echo esc_attr( $s->status ); ?>"><?php echo esc_html( ucfirst( $s->status ) ); ?></span></td>
									<td><?php echo esc_html( esk_date_format( $s->created_at ) ); ?></td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
		<div class="esk-dashboard-column esk-col-narrow">
			<div class="esk-card">
				<h2><?php esc_html_e( 'Recent Admissions', 'eskoofy' ); ?></h2>
				<ul class="esk-activity-list">
					<?php if ( empty( $recent_admissions ) ) : ?>
						<li><?php esc_html_e( 'No recent admissions.', 'eskoofy' ); ?></li>
					<?php else : ?>
						<?php foreach ( $recent_admissions as $a ) : ?>
							<li>
								<strong><?php echo esc_html( $a->first_name . ' ' . $a->last_name ); ?></strong>
								<span class="esk-badge esk-badge-<?php echo esc_attr( $a->status ); ?>"><?php echo esc_html( ucfirst( $a->status ) ); ?></span>
								<br><small><?php echo esc_html( esk_date_format( $a->created_at ) ); ?></small>
							</li>
						<?php endforeach; ?>
					<?php endif; ?>
				</ul>
			</div>
		</div>
	</div>
</div>
