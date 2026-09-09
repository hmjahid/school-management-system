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
		esc_html__( 'Sections', 'eskoofy' ),
		esc_html__( 'Sections', 'eskoofy' ),
		'manage_options',
		'esk-sections',
		'esk_sections_page'
	);

	add_submenu_page(
		'esk-dashboard',
		esc_html__( 'Subjects', 'eskoofy' ),
		esc_html__( 'Subjects', 'eskoofy' ),
		'manage_options',
		'esk-subjects',
		'esk_subjects_page'
	);

	add_submenu_page(
		'esk-dashboard',
		esc_html__( 'Batches', 'eskoofy' ),
		esc_html__( 'Batches', 'eskoofy' ),
		'manage_options',
		'esk-batches',
		'esk_batches_page'
	);

	add_submenu_page(
		'esk-dashboard',
		esc_html__( 'Academic Sessions', 'eskoofy' ),
		esc_html__( 'Academic Sessions', 'eskoofy' ),
		'manage_options',
		'esk-academic-sessions',
		'esk_academic_sessions_page'
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
		esc_html__( 'Expenses', 'eskoofy' ),
		esc_html__( 'Expenses', 'eskoofy' ),
		'manage_options',
		'esk-expenses',
		'esk_expenses_page'
	);

	add_submenu_page(
		'esk-dashboard',
		esc_html__( 'Payroll', 'eskoofy' ),
		esc_html__( 'Payroll', 'eskoofy' ),
		'manage_options',
		'esk-payroll',
		'esk_payroll_page'
	);

	add_submenu_page(
		'esk-dashboard',
		esc_html__( 'Guardians', 'eskoofy' ),
		esc_html__( 'Guardians', 'eskoofy' ),
		'manage_options',
		'esk-guardians',
		'esk_guardians_page'
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
		esc_html__( 'Transport', 'eskoofy' ),
		esc_html__( 'Transport', 'eskoofy' ),
		'manage_options',
		'esk-transport',
		'esk_transport_page'
	);

	add_submenu_page(
		'esk-dashboard',
		esc_html__( 'Hostels', 'eskoofy' ),
		esc_html__( 'Hostels', 'eskoofy' ),
		'manage_options',
		'esk-hostels',
		'esk_hostels_page'
	);

	add_submenu_page(
		'esk-dashboard',
		esc_html__( 'Library', 'eskoofy' ),
		esc_html__( 'Library', 'eskoofy' ),
		'manage_options',
		'esk-library',
		'esk_library_page'
	);

	add_submenu_page(
		'esk-dashboard',
		esc_html__( 'SMS', 'eskoofy' ),
		esc_html__( 'SMS', 'eskoofy' ),
		'manage_options',
		'esk-sms',
		'esk_sms_page'
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
		esc_html__( 'Announcements', 'eskoofy' ),
		esc_html__( 'Announcements', 'eskoofy' ),
		'manage_options',
		'esk-announcements',
		'esk_announcements_page'
	);

	add_submenu_page(
		'esk-dashboard',
		esc_html__( 'Certificates', 'eskoofy' ),
		esc_html__( 'Certificates', 'eskoofy' ),
		'manage_options',
		'esk-certificates',
		'esk_certificates_page'
	);

	add_submenu_page(
		'esk-dashboard',
		esc_html__( 'Admit Cards', 'eskoofy' ),
		esc_html__( 'Admit Cards', 'eskoofy' ),
		'manage_options',
		'esk-admit-cards',
		'esk_admit_cards_page'
	);

	add_submenu_page(
		'esk-dashboard',
		esc_html__( 'ID Cards', 'eskoofy' ),
		esc_html__( 'ID Cards', 'eskoofy' ),
		'manage_options',
		'esk-id-cards',
		'esk_id_cards_page'
	);

	add_submenu_page(
		'esk-dashboard',
		esc_html__( 'Testimonials', 'eskoofy' ),
		esc_html__( 'Testimonials', 'eskoofy' ),
		'manage_options',
		'esk-testimonials',
		'esk_testimonials_page'
	);

	add_submenu_page(
		'esk-dashboard',
		esc_html__( 'Committee', 'eskoofy' ),
		esc_html__( 'Committee', 'eskoofy' ),
		'manage_options',
		'esk-committee',
		'esk_committee_page'
	);

	add_submenu_page(
		'esk-dashboard',
		esc_html__( 'Careers', 'eskoofy' ),
		esc_html__( 'Careers', 'eskoofy' ),
		'manage_options',
		'esk-careers',
		'esk_careers_page'
	);

	add_submenu_page(
		'esk-dashboard',
		esc_html__( 'Reports', 'eskoofy' ),
		esc_html__( 'Reports', 'eskoofy' ),
		'manage_options',
		'esk-reports',
		'esk_reports_page'
	);

	add_submenu_page(
		'esk-dashboard',
		esc_html__( 'Users', 'eskoofy' ),
		esc_html__( 'Users', 'eskoofy' ),
		'manage_options',
		'esk-users',
		'esk_users_page'
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
	if ( ( $_GET['action'] ?? '' ) === 'view' && ! empty( $_GET['id'] ) ) {
		esk_render_admin_view( 'student-detail' );
		return;
	}
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
	if ( ( $_GET['action'] ?? '' ) === 'edit' && ! empty( $_GET['id'] ) ) {
		esk_render_admin_view( 'class-form' );
		return;
	}
	esk_render_admin_view( 'classes' );
}

function esk_sections_page(): void {
	esk_render_admin_view( 'sections' );
}

function esk_subjects_page(): void {
	esk_render_admin_view( 'subjects' );
}

function esk_batches_page(): void {
	esk_render_admin_view( 'batches' );
}

function esk_academic_sessions_page(): void {
	esk_render_admin_view( 'academic-sessions' );
}

function esk_attendance_page(): void {
	esk_render_admin_view( 'attendance' );
}

function esk_attendance_mark_page(): void {
	esk_render_admin_view( 'attendance-mark' );
}

function esk_exams_page(): void {
	if ( ( $_GET['action'] ?? '' ) === 'add' ) {
		esk_render_admin_view( 'exam-form' );
		return;
	}
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

function esk_expenses_page(): void {
	esk_render_admin_view( 'expenses' );
}

function esk_payroll_page(): void {
	esk_render_admin_view( 'payroll' );
}

function esk_guardians_page(): void {
	esk_render_admin_view( 'guardians' );
}

function esk_admissions_page(): void {
	if ( ( $_GET['action'] ?? '' ) === 'view' && ! empty( $_GET['id'] ) ) {
		esk_render_admin_view( 'admission-detail' );
		return;
	}
	esk_render_admin_view( 'admissions' );
}

function esk_transport_page(): void {
	esk_render_admin_view( 'transport' );
}

function esk_hostels_page(): void {
	esk_render_admin_view( 'hostels' );
}

function esk_library_page(): void {
	esk_render_admin_view( 'library' );
}

function esk_sms_page(): void {
	esk_render_admin_view( 'sms' );
}

function esk_notices_page(): void {
	esk_render_admin_view( 'notices' );
}

function esk_announcements_page(): void {
	esk_render_admin_view( 'announcements' );
}

function esk_certificates_page(): void {
	esk_render_admin_view( 'certificates' );
}

function esk_admit_cards_page(): void {
	esk_render_admin_view( 'admit-cards' );
}

function esk_id_cards_page(): void {
	esk_render_admin_view( 'id-cards' );
}

function esk_testimonials_page(): void {
	esk_render_admin_view( 'testimonials-admin' );
}

function esk_committee_page(): void {
	esk_render_admin_view( 'committee' );
}

function esk_careers_page(): void {
	esk_render_admin_view( 'careers' );
}

function esk_reports_page(): void {
	esk_render_admin_view( 'reports' );
}

function esk_users_page(): void {
	esk_render_admin_view( 'users' );
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
