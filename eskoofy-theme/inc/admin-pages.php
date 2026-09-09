<?php
/**
 * Admin menu pages.
 *
 * @package Eskoofy
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

function esk_register_admin_menus(): void {
	add_menu_page(
		esc_html__( 'Eskoofy', 'eskoofy' ),
		esc_html__( 'Eskoofy', 'eskoofy' ),
		'manage_options',
		'esk-dashboard',
		'esk_dashboard_page',
		'dashicons-school',
		3
	);

	add_submenu_page(
		'esk-dashboard',
		esc_html__( 'Dashboard', 'eskoofy' ),
		esc_html__( 'Dashboard', 'eskoofy' ),
		'manage_options',
		'esk-dashboard',
		'esk_dashboard_page'
	);

	add_submenu_page(
		'esk-dashboard',
		esc_html__( 'Students', 'eskoofy' ),
		esc_html__( 'Students', 'eskoofy' ),
		'manage_options',
		'esk-students',
		'esk_students_page'
	);

	add_submenu_page(
		'esk-dashboard',
		esc_html__( 'Add Student', 'eskoofy' ),
		esc_html__( 'Add Student', 'eskoofy' ),
		'manage_options',
		'esk-student-add',
		'esk_student_add_page'
	);

	add_submenu_page(
		'esk-dashboard',
		esc_html__( 'Teachers', 'eskoofy' ),
		esc_html__( 'Teachers', 'eskoofy' ),
		'manage_options',
		'esk-teachers',
		'esk_teachers_page'
	);

	add_submenu_page(
		'esk-dashboard',
		esc_html__( 'Add Teacher', 'eskoofy' ),
		esc_html__( 'Add Teacher', 'eskoofy' ),
		'manage_options',
		'esk-teacher-add',
		'esk_teacher_add_page'
	);

	add_submenu_page(
		'esk-dashboard',
		esc_html__( 'Classes', 'eskoofy' ),
		esc_html__( 'Classes', 'eskoofy' ),
		'manage_options',
		'esk-classes',
		'esk_classes_page'
	);

	add_submenu_page(
		'esk-dashboard',
		esc_html__( 'Attendance', 'eskoofy' ),
		esc_html__( 'Attendance', 'eskoofy' ),
		'manage_options',
		'esk-attendance',
		'esk_attendance_page'
	);

	add_submenu_page(
		'esk-dashboard',
		esc_html__( 'Mark Attendance', 'eskoofy' ),
		esc_html__( 'Mark Attendance', 'eskoofy' ),
		'manage_options',
		'esk-attendance-mark',
		'esk_attendance_mark_page'
	);

	add_submenu_page(
		'esk-dashboard',
		esc_html__( 'Exams', 'eskoofy' ),
		esc_html__( 'Exams', 'eskoofy' ),
		'manage_options',
		'esk-exams',
		'esk_exams_page'
	);

	add_submenu_page(
		'esk-dashboard',
		esc_html__( 'Results', 'eskoofy' ),
		esc_html__( 'Results', 'eskoofy' ),
		'manage_options',
		'esk-results',
		'esk_results_page'
	);

	add_submenu_page(
		'esk-dashboard',
		esc_html__( 'Fees', 'eskoofy' ),
		esc_html__( 'Fees', 'eskoofy' ),
		'manage_options',
		'esk-fees',
		'esk_fees_page'
	);

	add_submenu_page(
		'esk-dashboard',
		esc_html__( 'Fee Payments', 'eskoofy' ),
		esc_html__( 'Fee Payments', 'eskoofy' ),
		'manage_options',
		'esk-fee-payments',
		'esk_fee_payments_page'
	);

	add_submenu_page(
		'esk-dashboard',
		esc_html__( 'Admissions', 'eskoofy' ),
		esc_html__( 'Admissions', 'eskoofy' ),
		'manage_options',
		'esk-admissions',
		'esk_admissions_page'
	);

	add_submenu_page(
		'esk-dashboard',
		esc_html__( 'Notices', 'eskoofy' ),
		esc_html__( 'Notices', 'eskoofy' ),
		'manage_options',
		'esk-notices',
		'esk_notices_page'
	);

	add_submenu_page(
		'esk-dashboard',
		esc_html__( 'Settings', 'eskoofy' ),
		esc_html__( 'Settings', 'eskoofy' ),
		'manage_options',
		'esk-settings',
		'esk_settings_page'
	);
}
add_action( 'admin_menu', 'esk_register_admin_menus' );

/* ─── Page renderers ───────────────────────────────────────────── */

function esk_dashboard_page(): void {
	esk_render_admin_view( 'dashboard' );
}

function esk_students_page(): void {
	esk_render_admin_view( 'students' );
}

function esk_student_add_page(): void {
	esk_render_admin_view( 'student-form' );
}

function esk_teachers_page(): void {
	esk_render_admin_view( 'teachers' );
}

function esk_teacher_add_page(): void {
	esk_render_admin_view( 'teacher-form' );
}

function esk_classes_page(): void {
	esk_render_admin_view( 'classes' );
}

function esk_attendance_page(): void {
	esk_render_admin_view( 'attendance' );
}

function esk_attendance_mark_page(): void {
	esk_render_admin_view( 'attendance-mark' );
}

function esk_exams_page(): void {
	esk_render_admin_view( 'exams' );
}

function esk_results_page(): void {
	esk_render_admin_view( 'results' );
}

function esk_fees_page(): void {
	esk_render_admin_view( 'fees' );
}

function esk_fee_payments_page(): void {
	esk_render_admin_view( 'fee-payments' );
}

function esk_admissions_page(): void {
	esk_render_admin_view( 'admissions' );
}

function esk_notices_page(): void {
	esk_render_admin_view( 'notices' );
}

function esk_settings_page(): void {
	esk_render_admin_view( 'settings' );
}

/**
 * Render an admin view template.
 */
function esk_render_admin_view( string $view, array $data = array() ): void {
	$template = ESK_PATH . '/views/admin/' . $view . '.php';
	if ( ! file_exists( $template ) ) {
		echo '<div class="notice notice-error"><p>' . esc_html__( 'View template not found: ', 'eskoofy' ) . esc_html( $view ) . '</p></div>';
		return;
	}
	extract( $data ); // phpcs:ignore
	include $template;
}
