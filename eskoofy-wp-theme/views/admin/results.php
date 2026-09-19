<?php
/**
 * Results entry form.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

if ( isset( $_POST['esk_results_save'] ) ) {
	check_admin_referer( 'esk_results_form' );
	$exam_id   = absint( $_POST['exam_id'] ?? 0 );
	$marks_arr = isset( $_POST['marks'] ) && is_array( $_POST['marks'] ) ? $_POST['marks'] : array();
	$remarks   = isset( $_POST['remarks'] ) && is_array( $_POST['remarks'] ) ? $_POST['remarks'] : array();

	$exam = $exam_id ? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}esk_exams WHERE id = %d", $exam_id ) ) : null;
	if ( ! $exam ) {
		esk_flash( 'error', __( 'Invalid exam.', 'eskoofy' ) );
		wp_safe_redirect( admin_url( 'admin.php?page=esk-results' ) );
		exit;
	}

	foreach ( $marks_arr as $student_id => $marks ) {
		$student_id     = absint( $student_id );
		$obtained_marks = (float) $marks;
		$passing_marks  = (float) $exam->passing_marks;
		$grade_remarks  = isset( $remarks[ $student_id ] ) ? sanitize_textarea_field( $remarks[ $student_id ] ) : '';
		$status         = $obtained_marks >= $passing_marks ? 'pass' : 'fail';

		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}esk_exam_results WHERE exam_id = %d AND student_id = %d AND deleted_at IS NULL",
				$exam_id,
				$student_id
			)
		);

		$data = array(
			'exam_id'        => $exam_id,
			'student_id'     => $student_id,
			'obtained_marks' => $obtained_marks,
			'remarks'        => $grade_remarks,
			'status'         => $status,
			'submitted_by'   => get_current_user_id(),
			'submitted_at'   => current_time( 'mysql' ),
		);

		if ( $existing ) {
			$wpdb->update( $wpdb->prefix . 'esk_exam_results', $data, array( 'id' => $existing ) );
		} else {
			$wpdb->insert( $wpdb->prefix . 'esk_exam_results', $data );
		}
	}
	esk_flash( 'success', __( 'Results saved.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-results&exam_id=' . $exam_id ) );
	exit;
}

$all_exams = $wpdb->get_results(
	"SELECT e.*, c.name AS class_name, s.name AS subject_name
	FROM {$wpdb->prefix}esk_exams e
	LEFT JOIN {$wpdb->prefix}esk_classes c ON e.class_id = c.id
	LEFT JOIN {$wpdb->prefix}esk_subjects s ON e.subject_id = s.id
	WHERE e.deleted_at IS NULL ORDER BY e.start_date DESC"
);

$selected_exam = absint( $_GET['exam_id'] ?? 0 );
$students      = array();

if ( $selected_exam ) {
	$exam = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}esk_exams WHERE id = %d", $selected_exam ) );
	if ( $exam ) {
		$students = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT s.id, u.display_name, s.roll_number,
					COALESCE(er.obtained_marks, 0) AS obtained_marks,
					COALESCE(er.remarks, '') AS remarks,
					er.id AS result_id
				FROM {$wpdb->prefix}esk_students s
				JOIN {$wpdb->prefix}users u ON s.user_id = u.ID
				LEFT JOIN {$wpdb->prefix}esk_exam_results er ON er.exam_id = %d AND er.student_id = s.id AND er.deleted_at IS NULL
				WHERE s.class_id = %d AND s.status = 'active' AND s.deleted_at IS NULL
				ORDER BY CAST(s.roll_number AS UNSIGNED), u.display_name",
				$selected_exam,
				$exam->class_id
			)
		);
	}
}
?>
<div class="wrap esk-admin-wrap">
	<h1><?php esc_html_e( 'Enter Results', 'eskoofy' ); ?></h1>

	<div class="esk-card esk-form-card" style="margin-bottom:1.5rem;">
		<form method="get" class="esk-form esk-inline-form">
			<input type="hidden" name="page" value="esk-results">
			<label><?php esc_html_e( 'Select Exam', 'eskoofy' ); ?>:</label>
			<select name="exam_id" required>
				<option value=""><?php esc_html_e( 'Choose Exam', 'eskoofy' ); ?></option>
				<?php foreach ( $all_exams as $e ) : ?>
					<option value="<?php echo esc_attr( $e->id ); ?>" <?php selected( $selected_exam, $e->id ); ?>>
						<?php echo esc_html( $e->name . ' — ' . $e->class_name . ' (' . $e->subject_name . ')' ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<button type="submit" class="button"><?php esc_html_e( 'Load', 'eskoofy' ); ?></button>
		</form>
	</div>

	<?php if ( $selected_exam && ! empty( $students ) ) : ?>
		<form method="post" class="esk-form" id="esk-results-form">
			<?php wp_nonce_field( 'esk_results_form' ); ?>
			<input type="hidden" name="exam_id" value="<?php echo esc_attr( $selected_exam ); ?>">

			<p><strong><?php esc_html_e( 'Total Marks', 'eskoofy' ); ?>:</strong> <?php echo esc_html( $exam->total_marks ); ?>
			| <strong><?php esc_html_e( 'Passing Marks', 'eskoofy' ); ?>:</strong> <?php echo esc_html( $exam->passing_marks ); ?></p>

			<table class="wp-list-table widefat striped esk-table">
				<thead><tr>
					<th><?php esc_html_e( 'Roll', 'eskoofy' ); ?></th>
					<th><?php esc_html_e( 'Name', 'eskoofy' ); ?></th>
					<th><?php esc_html_e( 'Marks Obtained', 'eskoofy' ); ?></th>
					<th><?php esc_html_e( 'Remarks', 'eskoofy' ); ?></th>
				</tr></thead>
				<tbody>
					<?php foreach ( $students as $s ) : ?>
						<tr>
							<td><?php echo esc_html( $s->roll_number ); ?></td>
							<td><?php echo esc_html( $s->display_name ); ?></td>
							<td><input type="number" name="marks[<?php echo esc_attr( $s->id ); ?>]" value="<?php echo esc_attr( $s->obtained_marks ); ?>" step="0.01" min="0" max="<?php echo esc_attr( $exam->total_marks ); ?>" style="width:100px;"></td>
							<td><input type="text" name="remarks[<?php echo esc_attr( $s->id ); ?>]" value="<?php echo esc_attr( $s->remarks ); ?>" style="width:200px;"></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<p class="submit">
				<button type="submit" name="esk_results_save" class="button button-primary"><?php esc_html_e( 'Save Results', 'eskoofy' ); ?></button>
			</p>
		</form>
	<?php elseif ( $selected_exam ) : ?>
		<p><?php esc_html_e( 'No active students found for this exam.', 'eskoofy' ); ?></p>
	<?php endif; ?>
</div>
