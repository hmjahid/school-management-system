<?php
/**
 * Per-student progress reports.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

$student_id = absint( $_GET['student_id'] ?? 0 );

$students = $wpdb->get_results(
	"SELECT s.id, u.display_name FROM {$wpdb->prefix}esk_students s
	JOIN {$wpdb->prefix}users u ON s.user_id = u.ID WHERE s.deleted_at IS NULL ORDER BY u.display_name"
);

$report = null;
if ( $student_id ) {
	$student = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT s.*, u.display_name FROM {$wpdb->prefix}esk_students s
			JOIN {$wpdb->prefix}users u ON s.user_id = u.ID WHERE s.id = %d",
			$student_id
		)
	);

	$results = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT er.*, e.name AS exam_name, e.total_marks, e.passing_marks, sub.name AS subject_name
			FROM {$wpdb->prefix}esk_exam_results er
			JOIN {$wpdb->prefix}esk_exams e ON er.exam_id = e.id
			LEFT JOIN {$wpdb->prefix}esk_subjects sub ON e.subject_id = sub.id
			WHERE er.student_id = %d AND er.deleted_at IS NULL
			ORDER BY e.start_date DESC",
			$student_id
		)
	);

	$attendance = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT status, COUNT(*) AS total FROM {$wpdb->prefix}esk_attendances
			WHERE student_id = %d GROUP BY status",
			$student_id
		)
	);

	$report = array(
		'student'    => $student,
		'results'    => $results,
		'attendance' => $attendance,
	);
}
?>
<div class="wrap esk-admin-wrap">
	<h1><?php esc_html_e( 'Progress Reports', 'eskoofy' ); ?></h1>

	<form method="get" class="esk-form esk-inline-form" style="margin-bottom:1rem;">
		<input type="hidden" name="page" value="esk-progress-reports">
		<label><?php esc_html_e( 'Student', 'eskoofy' ); ?>:</label>
		<select name="student_id">
			<option value=""><?php esc_html_e( 'Select', 'eskoofy' ); ?></option>
			<?php foreach ( $students as $s ) : ?>
				<option value="<?php echo esc_attr( $s->id ); ?>" <?php selected( $student_id, $s->id ); ?>><?php echo esc_html( $s->display_name ); ?></option>
			<?php endforeach; ?>
		</select>
		<button type="submit" class="button"><?php esc_html_e( 'View', 'eskoofy' ); ?></button>
	</form>

	<?php if ( $report && $report['student'] ) : ?>
		<div class="esk-card">
			<h2><?php echo esc_html( $report['student']->display_name ); ?></h2>
			<p><strong><?php esc_html_e( 'Admission No:', 'eskoofy' ); ?></strong> <?php echo esc_html( $report['student']->admission_number ); ?></p>

			<h3><?php esc_html_e( 'Attendance Summary', 'eskoofy' ); ?></h3>
			<ul>
				<?php foreach ( $report['attendance'] as $a ) : ?>
					<li><?php echo esc_html( ucfirst( $a->status ) . ': ' . (int) $a->total ); ?></li>
				<?php endforeach; ?>
			</ul>

			<h3><?php esc_html_e( 'Exam Results', 'eskoofy' ); ?></h3>
			<table class="wp-list-table widefat striped esk-table">
				<thead><tr><th><?php esc_html_e( 'Exam', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Subject', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Marks', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Status', 'eskoofy' ); ?></th></tr></thead>
				<tbody>
					<?php if ( empty( $report['results'] ) ) : ?>
						<tr><td colspan="4"><?php esc_html_e( 'No results.', 'eskoofy' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $report['results'] as $r ) : ?>
							<tr>
								<td><?php echo esc_html( $r->exam_name ); ?></td>
								<td><?php echo esc_html( $r->subject_name ); ?></td>
								<td><?php echo esc_html( $r->obtained_marks . ' / ' . $r->total_marks ); ?></td>
								<td><span class="esk-badge esk-badge-<?php echo esc_attr( $r->status ); ?>"><?php echo esc_html( ucfirst( $r->status ) ); ?></span></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</div>