<?php
/**
 * REST API routes for Eskoofy.
 *
 * @package Eskoofy
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

function esk_register_rest_routes(): void {
	register_rest_route(
		'esk/v1',
		'/students',
		array(
			array(
				'methods'             => 'GET',
				'callback'            => 'esk_rest_get_students',
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			),
			array(
				'methods'             => 'POST',
				'callback'            => 'esk_rest_create_student',
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			),
		)
	);

	register_rest_route(
		'esk/v1',
		'/teachers',
		array(
			array(
				'methods'             => 'GET',
				'callback'            => 'esk_rest_get_teachers',
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			),
			array(
				'methods'             => 'POST',
				'callback'            => 'esk_rest_create_teacher',
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			),
		)
	);

	register_rest_route(
		'esk/v1',
		'/classes',
		array(
			array(
				'methods'             => 'GET',
				'callback'            => 'esk_rest_get_classes',
				'permission_callback' => '__return_true',
			),
			array(
				'methods'             => 'POST',
				'callback'            => 'esk_rest_create_class',
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			),
		)
	);

	register_rest_route(
		'esk/v1',
		'/exams',
		array(
			array(
				'methods'             => 'GET',
				'callback'            => 'esk_rest_get_exams',
				'permission_callback' => '__return_true',
			),
			array(
				'methods'             => 'POST',
				'callback'            => 'esk_rest_create_exam',
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			),
		)
	);

	register_rest_route(
		'esk/v1',
		'/results',
		array(
			array(
				'methods'             => 'GET',
				'callback'            => 'esk_rest_get_results',
				'permission_callback' => '__return_true',
			),
			array(
				'methods'             => 'POST',
				'callback'            => 'esk_rest_create_result',
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			),
		)
	);

	register_rest_route(
		'esk/v1',
		'/fees',
		array(
			array(
				'methods'             => 'GET',
				'callback'            => 'esk_rest_get_fees',
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			),
			array(
				'methods'             => 'POST',
				'callback'            => 'esk_rest_create_fee',
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			),
		)
	);

	register_rest_route(
		'esk/v1',
		'/payments',
		array(
			array(
				'methods'             => 'GET',
				'callback'            => 'esk_rest_get_payments',
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			),
			array(
				'methods'             => 'POST',
				'callback'            => 'esk_rest_create_payment',
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			),
		)
	);

	register_rest_route(
		'esk/v1',
		'/admissions',
		array(
			array(
				'methods'             => 'GET',
				'callback'            => 'esk_rest_get_admissions',
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			),
			array(
				'methods'             => 'POST',
				'callback'            => 'esk_rest_create_admission',
				'permission_callback' => '__return_true',
			),
		)
	);

	register_rest_route(
		'esk/v1',
		'/notices',
		array(
			array(
				'methods'             => 'GET',
				'callback'            => 'esk_rest_get_notices',
				'permission_callback' => '__return_true',
			),
			array(
				'methods'             => 'POST',
				'callback'            => 'esk_rest_create_notice',
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			),
		)
	);

	register_rest_route(
		'esk/v1',
		'/news',
		array(
			array(
				'methods'             => 'GET',
				'callback'            => 'esk_rest_get_news',
				'permission_callback' => '__return_true',
			),
			array(
				'methods'             => 'POST',
				'callback'            => 'esk_rest_create_news',
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			),
		)
	);

	register_rest_route(
		'esk/v1',
		'/results/lookup',
		array(
			'methods'             => 'GET',
			'callback'            => 'esk_rest_lookup_results',
			'permission_callback' => '__return_true',
		)
	);
}
add_action( 'rest_api_init', 'esk_register_rest_routes' );

/* ─── Callbacks ────────────────────────────────────────────────── */

function esk_rest_get_students( WP_REST_Request $request ): WP_REST_Response {
	global $wpdb;
	$page     = max( 1, (int) $request->get_param( 'page' ) );
	$per_page = max( 1, min( 100, (int) $request->get_param( 'per_page' ) ?? 20 ) );
	$offset   = ( $page - 1 ) * $per_page;

	$total = (int) $wpdb->get_var(
		"SELECT COUNT(*) FROM {$wpdb->prefix}esk_students WHERE deleted_at IS NULL"
	);

	$results = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT s.*, u.display_name FROM {$wpdb->prefix}esk_students s
			JOIN {$wpdb->prefix}users u ON s.user_id = u.ID
			WHERE s.deleted_at IS NULL
			ORDER BY s.id DESC LIMIT %d OFFSET %d",
			$per_page,
			$offset
		)
	);

	return new WP_REST_Response( array(
		'data'  => $results,
		'total' => $total,
		'pages' => (int) ceil( $total / $per_page ),
	), 200 );
}

function esk_rest_create_student( WP_REST_Request $request ): WP_REST_Response {
	global $wpdb;
	$params = $request->get_json_params();

	$user_id = wp_insert_user( array(
		'user_login'   => $params['email'] ?? '',
		'user_email'   => $params['email'] ?? '',
		'display_name' => ( $params['first_name'] ?? '' ) . ' ' . ( $params['last_name'] ?? '' ),
		'user_pass'    => wp_generate_password(),
		'role'         => 'subscriber',
	) );

	if ( is_wp_error( $user_id ) ) {
		return new WP_REST_Response( array( 'success' => false, 'message' => $user_id->get_error_message() ), 400 );
	}

	$wpdb->insert( $wpdb->prefix . 'esk_students', array(
		'user_id'           => $user_id,
		'class_id'          => $params['class_id'] ?? 0,
		'admission_number'  => $params['admission_number'] ?? esk_generate_number( 'ADM' ),
		'admission_date'    => $params['admission_date'] ?? gmdate( 'Y-m-d' ),
		'roll_number'       => $params['roll_number'] ?? null,
		'status'            => $params['status'] ?? 'active',
	) );

	return new WP_REST_Response( array( 'success' => true, 'id' => $wpdb->insert_id ), 201 );
}

function esk_rest_get_teachers( WP_REST_Request $request ): WP_REST_Response {
	global $wpdb;
	$results = $wpdb->get_results(
		"SELECT t.*, u.display_name, u.user_email FROM {$wpdb->prefix}esk_teachers t
		JOIN {$wpdb->prefix}users u ON t.user_id = u.ID
		ORDER BY t.id DESC"
	);
	return new WP_REST_Response( array( 'data' => $results ), 200 );
}

function esk_rest_create_teacher( WP_REST_Request $request ): WP_REST_Response {
	global $wpdb;
	$params = $request->get_json_params();

	$user_id = wp_insert_user( array(
		'user_login'   => $params['email'] ?? '',
		'user_email'   => $params['email'] ?? '',
		'display_name' => ( $params['first_name'] ?? '' ) . ' ' . ( $params['last_name'] ?? '' ),
		'user_pass'    => wp_generate_password(),
		'role'         => 'editor',
	) );

	if ( is_wp_error( $user_id ) ) {
		return new WP_REST_Response( array( 'success' => false, 'message' => $user_id->get_error_message() ), 400 );
	}

	$wpdb->insert( $wpdb->prefix . 'esk_teachers', array(
		'user_id'        => $user_id,
		'qualification'  => $params['qualification'] ?? null,
		'subjects'       => isset( $params['subjects'] ) ? wp_json_encode( $params['subjects'] ) : null,
	) );

	return new WP_REST_Response( array( 'success' => true, 'id' => $wpdb->insert_id ), 201 );
}

function esk_rest_get_classes( WP_REST_Request $request ): WP_REST_Response {
	global $wpdb;
	$results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}esk_classes ORDER BY name" );
	return new WP_REST_Response( array( 'data' => $results ), 200 );
}

function esk_rest_create_class( WP_REST_Request $request ): WP_REST_Response {
	global $wpdb;
	$params = $request->get_json_params();
	$wpdb->insert( $wpdb->prefix . 'esk_classes', array(
		'name' => $params['name'] ?? '',
	) );
	return new WP_REST_Response( array( 'success' => true, 'id' => $wpdb->insert_id ), 201 );
}

function esk_rest_get_exams( WP_REST_Request $request ): WP_REST_Response {
	global $wpdb;
	$results = $wpdb->get_results(
		"SELECT e.*, c.name AS class_name, s.name AS subject_name
		FROM {$wpdb->prefix}esk_exams e
		LEFT JOIN {$wpdb->prefix}esk_classes c ON e.class_id = c.id
		LEFT JOIN {$wpdb->prefix}esk_subjects s ON e.subject_id = s.id
		WHERE e.deleted_at IS NULL ORDER BY e.id DESC"
	);
	return new WP_REST_Response( array( 'data' => $results ), 200 );
}

function esk_rest_create_exam( WP_REST_Request $request ): WP_REST_Response {
	global $wpdb;
	$params = $request->get_json_params();
	$wpdb->insert( $wpdb->prefix . 'esk_exams', array(
		'name'          => $params['name'] ?? '',
		'code'          => $params['code'] ?? '',
		'class_id'      => $params['class_id'] ?? 0,
		'subject_id'    => $params['subject_id'] ?? 0,
		'start_date'    => $params['start_date'] ?? '',
		'end_date'      => $params['end_date'] ?? '',
		'start_time'    => $params['start_time'] ?? '',
		'end_time'      => $params['end_time'] ?? '',
		'total_marks'   => $params['total_marks'] ?? 0,
		'passing_marks' => $params['passing_marks'] ?? 0,
		'status'        => $params['status'] ?? 'upcoming',
		'created_by'    => get_current_user_id(),
	) );
	return new WP_REST_Response( array( 'success' => true, 'id' => $wpdb->insert_id ), 201 );
}

function esk_rest_get_results( WP_REST_Request $request ): WP_REST_Response {
	global $wpdb;
	$results = $wpdb->get_results(
		"SELECT er.*, e.name AS exam_name, s.admission_number
		FROM {$wpdb->prefix}esk_exam_results er
		JOIN {$wpdb->prefix}esk_exams e ON er.exam_id = e.id
		JOIN {$wpdb->prefix}esk_students s ON er.student_id = s.id
		WHERE er.deleted_at IS NULL ORDER BY er.id DESC"
	);
	return new WP_REST_Response( array( 'data' => $results ), 200 );
}

function esk_rest_create_result( WP_REST_Request $request ): WP_REST_Response {
	global $wpdb;
	$params = $request->get_json_params();
	$wpdb->insert( $wpdb->prefix . 'esk_exam_results', array(
		'exam_id'        => $params['exam_id'] ?? 0,
		'student_id'     => $params['student_id'] ?? 0,
		'obtained_marks' => $params['obtained_marks'] ?? 0,
		'grade'          => $params['grade'] ?? null,
		'remarks'        => $params['remarks'] ?? null,
		'status'         => $params['status'] ?? 'pending',
	) );
	return new WP_REST_Response( array( 'success' => true, 'id' => $wpdb->insert_id ), 201 );
}

function esk_rest_get_fees( WP_REST_Request $request ): WP_REST_Response {
	global $wpdb;
	$results = $wpdb->get_results(
		"SELECT f.*, c.name AS class_name
		FROM {$wpdb->prefix}esk_fees f
		LEFT JOIN {$wpdb->prefix}esk_classes c ON f.class_id = c.id
		WHERE f.deleted_at IS NULL ORDER BY f.id DESC"
	);
	return new WP_REST_Response( array( 'data' => $results ), 200 );
}

function esk_rest_create_fee( WP_REST_Request $request ): WP_REST_Response {
	global $wpdb;
	$params = $request->get_json_params();
	$wpdb->insert( $wpdb->prefix . 'esk_fees', array(
		'name'       => $params['name'] ?? '',
		'code'       => $params['code'] ?? '',
		'class_id'   => $params['class_id'] ?? 0,
		'amount'     => $params['amount'] ?? 0,
		'fee_type'   => $params['fee_type'] ?? 'tuition',
		'frequency'  => $params['frequency'] ?? 'monthly',
		'created_by' => get_current_user_id(),
	) );
	return new WP_REST_Response( array( 'success' => true, 'id' => $wpdb->insert_id ), 201 );
}

function esk_rest_get_payments( WP_REST_Request $request ): WP_REST_Response {
	global $wpdb;
	$results = $wpdb->get_results(
		"SELECT * FROM {$wpdb->prefix}esk_payments WHERE deleted_at IS NULL ORDER BY id DESC"
	);
	return new WP_REST_Response( array( 'data' => $results ), 200 );
}

function esk_rest_create_payment( WP_REST_Request $request ): WP_REST_Response {
	global $wpdb;
	$params = $request->get_json_params();
	$wpdb->insert( $wpdb->prefix . 'esk_payments', array(
		'invoice_number'  => $params['invoice_number'] ?? esk_generate_number( 'INV' ),
		'amount'          => $params['amount'] ?? 0,
		'total_amount'    => $params['total_amount'] ?? $params['amount'] ?? 0,
		'payment_method'  => $params['payment_method'] ?? 'cash',
		'payment_status'  => $params['payment_status'] ?? 'pending',
		'payment_date'    => $params['payment_date'] ?? null,
	) );
	return new WP_REST_Response( array( 'success' => true, 'id' => $wpdb->insert_id ), 201 );
}

function esk_rest_get_admissions( WP_REST_Request $request ): WP_REST_Response {
	global $wpdb;
	$results = $wpdb->get_results(
		"SELECT * FROM {$wpdb->prefix}esk_admissions WHERE deleted_at IS NULL ORDER BY id DESC"
	);
	return new WP_REST_Response( array( 'data' => $results ), 200 );
}

function esk_rest_create_admission( WP_REST_Request $request ): WP_REST_Response {
	global $wpdb;
	$params = $request->get_json_params();

	$application_number = esk_generate_number( 'APP' );
	$wpdb->insert( $wpdb->prefix . 'esk_admissions', array(
		'application_number'   => $application_number,
		'academic_session_id'  => $params['academic_session_id'] ?? 0,
		'batch_id'             => $params['batch_id'] ?? 0,
		'first_name'           => $params['first_name'] ?? '',
		'last_name'            => $params['last_name'] ?? '',
		'gender'               => $params['gender'] ?? 'male',
		'date_of_birth'        => $params['date_of_birth'] ?? '',
		'email'                => $params['email'] ?? '',
		'phone'                => $params['phone'] ?? '',
		'address'              => $params['address'] ?? '',
		'city'                 => $params['city'] ?? '',
		'postal_code'          => $params['postal_code'] ?? '',
		'father_name'          => $params['father_name'] ?? '',
		'father_phone'         => $params['father_phone'] ?? '',
		'mother_name'          => $params['mother_name'] ?? '',
		'mother_phone'         => $params['mother_phone'] ?? '',
		'status'               => 'submitted',
		'submitted_at'         => current_time( 'mysql' ),
	) );

	return new WP_REST_Response( array( 'success' => true, 'id' => $wpdb->insert_id, 'application_number' => $application_number ), 201 );
}

function esk_rest_get_notices( WP_REST_Request $request ): WP_REST_Response {
	global $wpdb;
	$results = $wpdb->get_results(
		"SELECT n.*, u.display_name AS author_name
		FROM {$wpdb->prefix}esk_notices n
		JOIN {$wpdb->prefix}users u ON n.created_by = u.ID
		ORDER BY n.pinned DESC, n.id DESC"
	);
	return new WP_REST_Response( array( 'data' => $results ), 200 );
}

function esk_rest_create_notice( WP_REST_Request $request ): WP_REST_Response {
	global $wpdb;
	$params = $request->get_json_params();
	$wpdb->insert( $wpdb->prefix . 'esk_notices', array(
		'title'      => $params['title'] ?? '',
		'content'    => $params['content'] ?? '',
		'pinned'     => $params['pinned'] ?? 0,
		'audience'   => isset( $params['audience'] ) ? wp_json_encode( $params['audience'] ) : null,
		'created_by' => get_current_user_id(),
	) );
	return new WP_REST_Response( array( 'success' => true, 'id' => $wpdb->insert_id ), 201 );
}

function esk_rest_get_news( WP_REST_Request $request ): WP_REST_Response {
	global $wpdb;
	$results = $wpdb->get_results(
		"SELECT * FROM {$wpdb->prefix}esk_news WHERE deleted_at IS NULL ORDER BY id DESC"
	);
	return new WP_REST_Response( array( 'data' => $results ), 200 );
}

function esk_rest_create_news( WP_REST_Request $request ): WP_REST_Response {
	global $wpdb;
	$params = $request->get_json_params();
	$slug   = sanitize_title( $params['title'] ?? '' );
	$wpdb->insert( $wpdb->prefix . 'esk_news', array(
		'title'         => $params['title'] ?? '',
		'slug'          => $slug,
		'content'       => $params['content'] ?? '',
		'category'      => $params['category'] ?? null,
		'is_published'  => $params['is_published'] ?? 0,
	) );
	return new WP_REST_Response( array( 'success' => true, 'id' => $wpdb->insert_id ), 201 );
}

function esk_rest_lookup_results( WP_REST_Request $request ): WP_REST_Response {
	global $wpdb;

	$roll_number     = $request->get_param( 'roll_number' ) ?? '';
	$admission_number = $request->get_param( 'admission_number' ) ?? '';
	$exam_id         = $request->get_param( 'exam_id' ) ?? 0;

	if ( ! $roll_number && ! $admission_number ) {
		return new WP_REST_Response( array( 'success' => false, 'message' => 'Roll number or admission number required' ), 400 );
	}

	$student = null;
	if ( $admission_number ) {
		$student = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}esk_students WHERE admission_number = %s AND deleted_at IS NULL",
				$admission_number
			)
		);
	}

	if ( ! $student && $roll_number ) {
		$student = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}esk_students WHERE roll_number = %s AND deleted_at IS NULL",
				$roll_number
			)
		);
	}

	if ( ! $student ) {
		return new WP_REST_Response( array( 'success' => false, 'message' => 'Student not found' ), 404 );
	}

	$where   = "WHERE er.student_id = %d AND er.deleted_at IS NULL AND er.is_published = 1";
	$params  = array( $student->id );

	if ( $exam_id ) {
		$where  .= " AND er.exam_id = %d";
		$params[] = $exam_id;
	}

	$results = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT er.*, e.name AS exam_name, e.total_marks, e.passing_marks, s.name AS subject_name
			FROM {$wpdb->prefix}esk_exam_results er
			JOIN {$wpdb->prefix}esk_exams e ON er.exam_id = e.id
			LEFT JOIN {$wpdb->prefix}esk_subjects s ON e.subject_id = s.id
			{$where}
			ORDER BY e.start_date DESC",
			...$params
		)
	);

	$user_display = '';
	$user_row     = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT display_name FROM {$wpdb->prefix}users WHERE ID = %d",
			$student->user_id
		)
	);
	if ( $user_row ) {
		$user_display = $user_row->display_name;
	}

	return new WP_REST_Response( array(
		'success'  => true,
		'student'  => array(
			'name'              => $user_display ? $user_display : ( $student->parent_name ? $student->parent_name : 'Student' ),
			'admission_number'  => $student->admission_number,
			'roll_number'       => $student->roll_number,
		),
		'results'  => $results,
	), 200 );
}
