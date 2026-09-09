<?php
/**
 * AJAX handlers for Eskoofy.
 *
 * @package Eskoofy
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

add_action( 'wp_ajax_esk_search_students', 'esk_ajax_search_students' );
add_action( 'wp_ajax_esk_mark_attendance', 'esk_ajax_mark_attendance' );
add_action( 'wp_ajax_esk_save_results', 'esk_ajax_save_results' );
add_action( 'wp_ajax_esk_get_students_by_class', 'esk_ajax_get_students_by_class' );

/**
 * Live search students.
 */
function esk_ajax_search_students(): void {
	check_ajax_referer( 'esk_ajax_nonce', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
	}

	global $wpdb;
	$search = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
	$class_id = isset( $_GET['class_id'] ) ? absint( $_GET['class_id'] ) : 0;

	$students_table = $wpdb->prefix . 'esk_students';
	$users_table    = $wpdb->prefix . 'users';

	$where = "WHERE s.deleted_at IS NULL";
	$params = array();

	if ( $search ) {
		$where .= " AND (u.display_name LIKE %s OR s.admission_number LIKE %s OR s.phone_1 LIKE %s)";
		$like    = '%' . $wpdb->esc_like( $search ) . '%';
		$params[] = $like;
		$params[] = $like;
		$params[] = $like;
	}

	if ( $class_id ) {
		$where   .= " AND s.class_id = %d";
		$params[] = $class_id;
	}

	$query = "SELECT s.id, s.admission_number, s.roll_number, u.display_name, s.class_id, s.status
		FROM {$students_table} s
		JOIN {$users_table} u ON s.user_id = u.ID
		{$where}
		ORDER BY u.display_name ASC
		LIMIT 20";

	if ( ! empty( $params ) ) {
		$results = $wpdb->get_results( $wpdb->prepare( $query, ...$params ) ); // phpcs:ignore
	} else {
		$results = $wpdb->get_results( $query ); // phpcs:ignore
	}

	wp_send_json_success( array( 'students' => $results ) );
}

/**
 * Mark attendance for a class.
 */
function esk_ajax_mark_attendance(): void {
	check_ajax_referer( 'esk_ajax_nonce', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
	}

	global $wpdb;

	$date      = isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : gmdate( 'Y-m-d' );
	$class_id  = isset( $_POST['class_id'] ) ? absint( $_POST['class_id'] ) : 0;
	$student_ids = isset( $_POST['student_ids'] ) ? array_map( 'absint', (array) $_POST['student_ids'] ) : array();
	$statuses  = isset( $_POST['statuses'] ) ? array_map( 'sanitize_text_field', (array) $_POST['statuses'] ) : array();
	$marked_by = get_current_user_id();

	if ( ! $class_id || empty( $student_ids ) ) {
		wp_send_json_error( array( 'message' => 'Missing required fields' ) );
	}

	$table = $wpdb->prefix . 'esk_attendances';
	$count = 0;

	foreach ( $student_ids as $index => $student_id ) {
		$status = isset( $statuses[ $index ] ) ? $statuses[ $index ] : 'present';

		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE student_id = %d AND school_class_id = %d AND date = %s",
				$student_id,
				$class_id,
				$date
			)
		);

		$data = array(
			'student_id'      => $student_id,
			'school_class_id' => $class_id,
			'date'            => $date,
			'status'          => $status,
			'marked_by'       => $marked_by,
		);

		if ( $existing ) {
			$wpdb->update( $table, $data, array( 'id' => $existing ) );
		} else {
			$wpdb->insert( $table, $data );
		}
		++$count;
	}

	wp_send_json_success( array( 'message' => "Attendance saved for {$count} students.", 'count' => $count ) );
}

/**
 * Save exam results.
 */
function esk_ajax_save_results(): void {
	check_ajax_referer( 'esk_ajax_nonce', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
	}

	global $wpdb;

	$exam_id     = isset( $_POST['exam_id'] ) ? absint( $_POST['exam_id'] ) : 0;
	$student_ids = isset( $_POST['student_ids'] ) ? array_map( 'absint', (array) $_POST['student_ids'] ) : array();
	$marks       = isset( $_POST['marks'] ) ? array_map( 'floatval', (array) $_POST['marks'] ) : array();
	$remarks     = isset( $_POST['remarks'] ) ? array_map( 'sanitize_text_field', (array) $_POST['remarks'] ) : array();

	if ( ! $exam_id || empty( $student_ids ) ) {
		wp_send_json_error( array( 'message' => 'Missing required fields' ) );
	}

	$exam = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}esk_exams WHERE id = %d", $exam_id ) );
	if ( ! $exam ) {
		wp_send_json_error( array( 'message' => 'Exam not found' ) );
	}

	$table = $wpdb->prefix . 'esk_exam_results';
	$count = 0;

	foreach ( $student_ids as $index => $student_id ) {
		$obtained = isset( $marks[ $index ] ) ? $marks[ $index ] : 0;
		$remark   = isset( $remarks[ $index ] ) ? $remarks[ $index ] : '';
		$status   = $obtained >= $exam->passing_marks ? 'passed' : 'failed';

		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE exam_id = %d AND student_id = %d",
				$exam_id,
				$student_id
			)
		);

		$data = array(
			'exam_id'         => $exam_id,
			'student_id'      => $student_id,
			'obtained_marks'  => $obtained,
			'remarks'         => $remark,
			'status'          => $status,
			'submitted_by'    => get_current_user_id(),
			'submitted_at'    => current_time( 'mysql' ),
		);

		if ( $existing ) {
			$wpdb->update( $table, $data, array( 'id' => $existing ) );
		} else {
			$wpdb->insert( $table, $data );
		}
		++$count;
	}

	wp_send_json_success( array( 'message' => "Results saved for {$count} students.", 'count' => $count ) );
}

/**
 * Get students by class.
 */
function esk_ajax_get_students_by_class(): void {
	check_ajax_referer( 'esk_ajax_nonce', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
	}

	global $wpdb;
	$class_id = isset( $_GET['class_id'] ) ? absint( $_GET['class_id'] ) : 0;

	if ( ! $class_id ) {
		wp_send_json_error( array( 'message' => 'Class ID required' ) );
	}

	$results = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT s.id, s.admission_number, s.roll_number, u.display_name
			FROM {$wpdb->prefix}esk_students s
			JOIN {$wpdb->prefix}users u ON s.user_id = u.ID
			WHERE s.class_id = %d AND s.status = 'active' AND s.deleted_at IS NULL
			ORDER BY CAST(s.roll_number AS UNSIGNED), u.display_name",
			$class_id
		)
	);

	wp_send_json_success( array( 'students' => $results ) );
}
