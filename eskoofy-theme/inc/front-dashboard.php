<?php
/**
 * Frontend management dashboard.
 *
 * Serves the app-style dashboard at `/dashboard/…` (e.g. /dashboard/,
 * /dashboard/students/) completely decoupled from the WordPress admin.
 * Requires login; anonymous visitors are sent to /login/.
 *
 * @package Eskoofy
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * esk-* slug => page callback (defined in inc/admin-pages.php).
 */
function esk_front_dashboard_pages(): array {
	return array(
		'esk-dashboard'            => 'esk_dashboard_page',
		'esk-students'             => 'esk_students_page',
		'esk-student-add'          => 'esk_student_add_page',
		'esk-teachers'             => 'esk_teachers_page',
		'esk-teacher-add'          => 'esk_teacher_add_page',
		'esk-classes'              => 'esk_classes_page',
		'esk-sections'             => 'esk_sections_page',
		'esk-subjects'             => 'esk_subjects_page',
		'esk-batches'              => 'esk_batches_page',
		'esk-academic-sessions'    => 'esk_academic_sessions_page',
		'esk-attendance'           => 'esk_attendance_page',
		'esk-attendance-mark'      => 'esk_attendance_mark_page',
		'esk-exams'                => 'esk_exams_page',
		'esk-results'              => 'esk_results_page',
		'esk-fees'                 => 'esk_fees_page',
		'esk-fee-payments'         => 'esk_fee_payments_page',
		'esk-expenses'             => 'esk_expenses_page',
		'esk-expense-categories'   => 'esk_expense_categories_page',
		'esk-ledger'               => 'esk_ledger_page',
		'esk-budgets'              => 'esk_budgets_page',
		'esk-payroll'              => 'esk_payroll_page',
		'esk-guardians'            => 'esk_guardians_page',
		'esk-admissions'           => 'esk_admissions_page',
		'esk-transport'            => 'esk_transport_page',
		'esk-hostels'              => 'esk_hostels_page',
		'esk-library'              => 'esk_library_page',
		'esk-sms'                  => 'esk_sms_page',
		'esk-notices'              => 'esk_notices_page',
		'esk-announcements'        => 'esk_announcements_page',
		'esk-certificates'         => 'esk_certificates_page',
		'esk-admit-cards'          => 'esk_admit_cards_page',
		'esk-id-cards'             => 'esk_id_cards_page',
		'esk-events'               => 'esk_events_page',
		'esk-events-calendar'      => 'esk_events_calendar_page',
		'esk-news'                 => 'esk_news_page',
		'esk-gallery'              => 'esk_gallery_page',
		'esk-testimonials'         => 'esk_testimonials_page',
		'esk-committee'            => 'esk_committee_page',
		'esk-careers'              => 'esk_careers_page',
		'esk-reports'              => 'esk_reports_page',
		'esk-users'                => 'esk_users_page',
		'esk-staff-directory'      => 'esk_staff_directory_page',
		'esk-notification-templates' => 'esk_notification_templates_page',
		'esk-notification-preferences' => 'esk_notification_preferences_page',
		'esk-roles'                => 'esk_roles_page',
		'esk-settings'             => 'esk_settings_page',
		'esk-onboarding'           => 'esk_onboarding_page',
		'esk-documents'            => 'esk_documents_page',
		'esk-media'                => 'esk_media_page',
		'esk-contact-submissions'  => 'esk_contact_submissions_page',
		'esk-search'               => 'esk_search_page',
		'esk-cms'                  => 'esk_cms_page',
		'esk-bulk'                 => 'esk_bulk_page',
		'esk-reports-builder'      => 'esk_reports_builder_page',
		'esk-analytics'            => 'esk_analytics_page',
		'esk-income-statement'     => 'esk_income_statement_page',
		'esk-balance-sheet'        => 'esk_balance_sheet_page',
		'esk-cash-flow'            => 'esk_cash_flow_page',
		'esk-bank-reconciliation'  => 'esk_bank_reconciliation_page',
		'esk-payslips'             => 'esk_payslips_page',
		'esk-salary-structures'    => 'esk_salary_structures_page',
		'esk-leave-types'          => 'esk_leave_types_page',
		'esk-leave-requests'       => 'esk_leave_requests_page',
		'esk-staff-attendance'     => 'esk_staff_attendance_page',
		'esk-refunds'              => 'esk_refunds_page',
		'esk-notifications'        => 'esk_notifications_page',
		'esk-activity'             => 'esk_activity_page',
		'esk-visitor-logs'         => 'esk_visitor_logs_page',
		'esk-messages'             => 'esk_messages_page',
		'esk-assignments'          => 'esk_assignments_page',
		'esk-routines'             => 'esk_routines_page',
		'esk-progress-reports'     => 'esk_progress_reports_page',
		'esk-seat-plans'           => 'esk_seat_plans_page',
		'esk-library-reports'      => 'esk_library_reports_page',
		'esk-profile'              => 'esk_profile_page',
		'esk-help'                 => 'esk_help_page',
		'esk-backup'               => 'esk_backup_page',
		'esk-software'             => 'esk_software_page',
	);
}

/**
 * Register the /dashboard/ rewrite rules.
 */
add_action(
	'init',
	static function (): void {
		add_rewrite_rule( '^dashboard/?$', 'index.php?esk_dash=esk-dashboard', 'top' );
		add_rewrite_rule( '^dashboard/([^/]+)/?$', 'index.php?esk_dash=esk-$matches[1]', 'top' );
	}
);

add_filter(
	'query_vars',
	static function ( array $vars ): array {
		$vars[] = 'esk_dash';
		return $vars;
	}
);

/**
 * Convert wp-admin esk-* URLs to their frontend /dashboard/… equivalents so
 * the views never navigate through the WordPress admin.
 */
add_filter(
	'admin_url',
	static function ( $url, $path, $scheme ) {
		if ( is_string( $path ) && 0 === strpos( $path, 'admin.php?page=esk-' ) ) {
			$rest  = substr( $path, strlen( 'admin.php?page=esk-' ) );
			$slug  = $rest;
			$query = '';
			if ( false !== strpos( $rest, '&' ) ) {
				list( $slug, $query ) = explode( '&', $rest, 2 );
			}
			return esk_dashboard_url( 'esk-' . $slug, $query );
		}
		return $url;
	},
	10,
	3
);

/**
 * Load admin styles/scripts on the frontend dashboard.
 */
add_action(
	'wp_enqueue_scripts',
	static function (): void {
		if ( empty( $GLOBALS['esk_front_dashboard'] ) ) {
			return;
		}
		wp_enqueue_style( 'dashicons' );
		wp_enqueue_style(
			'eskoofy-app-dashboard',
			ESK_URL . '/inc/app-dashboard.css',
			array(),
			ESK_VERSION
		);
		wp_enqueue_style(
			'eskoofy-admin-style',
			ESK_URL . '/inc/admin-style.css',
			array( 'eskoofy-app-dashboard' ),
			ESK_VERSION
		);
		wp_enqueue_style(
			'eskoofy-admin-shell-style',
			ESK_URL . '/inc/admin-shell.css',
			array( 'eskoofy-admin-style' ),
			ESK_VERSION
		);
		wp_enqueue_script(
			'eskoofy-admin',
			ESK_URL . '/inc/admin.js',
			array( 'jquery' ),
			ESK_VERSION,
			true
		);
		wp_localize_script(
			'eskoofy-admin',
			'eskAdmin',
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'esk_ajax_nonce' ),
				'restNonce' => wp_create_nonce( 'wp_rest' ),
			)
		);
	},
	20
);

/**
 * Render the frontend dashboard for /dashboard/… requests.
 */
add_action(
	'template_redirect',
	static function (): void {
		$raw = get_query_var( 'esk_dash' );
		if ( '' === $raw ) {
			return;
		}

		$slug = sanitize_key( (string) $raw );

		// Redirect /dashboard/foo to /dashboard/foo/ (canonical; only when no query).
		if ( '' !== $slug && '' === ( $_SERVER['QUERY_STRING'] ?? '' ) && ( $_SERVER['REQUEST_URI'] ?? '' ) && substr( (string) $_SERVER['REQUEST_URI'], -1 ) !== '/' ) {
			wp_safe_redirect( esk_dashboard_url( $slug ) );
			exit;
		}

		// Login required.
		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( home_url( '/login/?redirect_to=' . rawurlencode( esk_dashboard_url( $slug ) ) ) );
			exit;
		}

		$pages = esk_front_dashboard_pages();
		if ( ! isset( $pages[ $slug ] ) || ! function_exists( $pages[ $slug ] ) ) {
			status_header( 404 );
			nocache_headers();
			echo '<!DOCTYPE html><html><body><h1>404 — Page not found</h1><p><a href="' . esc_url( home_url( '/dashboard/' ) ) . '">Back to dashboard</a></p></body></html>';
			exit;
		}

		$GLOBALS['esk_front_dashboard'] = true;
		$GLOBALS['esk_front_dash_slug'] = $slug;

		// Capability gate: admins always pass; other roles require a granted
		// capability in the esk_role_caps map (see Roles & Permissions page).
		if ( ! esk_can_access_dashboard() ) {
			status_header( 403 );
			nocache_headers();
			?>
			<!DOCTYPE html><html <?php language_attributes(); ?>>
			<head><meta charset="<?php bloginfo( 'charset' ); ?>"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?php esc_html_e( 'Access denied', 'eskoofy' ); ?></title></head>
			<body style="font-family:Inter,system-ui,sans-serif;background:#0f172a;color:#e2e8f0;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;">
			<div style="text-align:center;max-width:30rem;padding:2rem;">
				<h1 style="font-size:1.5rem;margin:0 0 .5rem;"><?php esc_html_e( 'Access denied', 'eskoofy' ); ?></h1>
				<p style="color:#94a3b8;margin:0 0 1.5rem;"><?php esc_html_e( 'Your account does not have permission to use the management dashboard.', 'eskoofy' ); ?></p>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" style="color:#93c5fd;"><?php esc_html_e( 'Back to website', 'eskoofy' ); ?></a>
			</div>
			</body></html>
			<?php
			exit;
		}

		// Render the full dashboard page.
		ob_start();
		call_user_func( $pages[ $slug ] );
		$content = (string) ob_get_clean();

		header( 'Content-Type: text/html; charset=' . get_bloginfo( 'charset' ) );
		nocache_headers();
		?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex, nofollow">
	<?php
	$page_titles = esk_dashboard_page_titles();
	$dash_title  = $page_titles[ $slug ] ?? ucfirst( (string) preg_replace( '/^esk-/', '', (string) $slug ) );
	?>
	<title><?php echo esc_html( $dash_title . ' — ' . get_bloginfo( 'name' ) ); ?></title>
	<link rel="icon" type="image/svg+xml" href="<?php echo esc_url( get_template_directory_uri() . '/assets/favicon.svg' ); ?>">
	<link rel="manifest" href="<?php echo esc_url( home_url( '/manifest.json' ) ); ?>">
	<meta name="theme-color" content="<?php echo esc_attr( esk_theme_primary() ); ?>">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
	<meta name="apple-mobile-web-app-title" content="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
	<link rel="apple-touch-icon" href="<?php echo esc_url( get_template_directory_uri() . '/assets/icons/apple-touch-icon.png' ); ?>">
	<style>
		:root {
			--brand-50: color-mix(in srgb, <?php echo esc_attr( esk_theme_primary() ); ?> 10%, white);
			--brand-100: color-mix(in srgb, <?php echo esc_attr( esk_theme_primary() ); ?> 20%, white);
			--brand-500: <?php echo esc_attr( esk_theme_primary() ); ?>;
			--brand-600: color-mix(in srgb, <?php echo esc_attr( esk_theme_primary() ); ?> 80%, black);
			--brand-700: color-mix(in srgb, <?php echo esc_attr( esk_theme_primary() ); ?> 65%, black);
			--accent-500: <?php echo esc_attr( esk_theme_secondary() ); ?>;
			--accent-600: color-mix(in srgb, <?php echo esc_attr( esk_theme_secondary() ); ?> 80%, black);
			--theme-radius: 0.75rem;
			--theme-card-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
			--theme-heading-weight: 600;
			--theme-accent: var(--brand-500);
		}
		body.theme-modern {
			--theme-radius: 1rem;
			--theme-card-shadow: 0 12px 32px -12px rgb(0 0 0 / 0.22);
			--theme-heading-weight: 700;
			--theme-accent: var(--accent-500);
		}
		body.theme-classic {
			--theme-radius: 0.375rem;
			--theme-card-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.12);
			--theme-heading-weight: 600;
			--theme-accent: var(--brand-700);
		}
		body.theme-minimal {
			--theme-radius: 0.5rem;
			--theme-card-shadow: none;
			--theme-heading-weight: 500;
			--theme-accent: var(--brand-500);
		}
		body.theme-modern .rounded-xl,
		body.theme-classic .rounded-xl,
		body.theme-minimal .rounded-xl {
			border-radius: var(--theme-radius) !important;
			box-shadow: var(--theme-card-shadow) !important;
		}
		body.theme-modern h1,
		body.theme-modern h2,
		body.theme-modern h3,
		body.theme-classic h1,
		body.theme-classic h2,
		body.theme-classic h3,
		body.theme-minimal h1,
		body.theme-minimal h2,
		body.theme-minimal h3 {
			font-weight: var(--theme-heading-weight) !important;
		}
	</style>
	<?php wp_head(); ?>
</head>
<body class="bg-slate-50 font-sans text-slate-900 antialiased dark:bg-slate-900 dark:text-slate-100 esk-admin-shell esk-front-dashboard">
<?php wp_body_open(); ?>
<?php esk_admin_shell_head_scripts(); ?>
<?php esk_render_admin_shell_open( $slug ); ?>
<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendered view. ?>
<?php esk_render_admin_shell_close(); ?>
<?php esk_admin_shell_footer_scripts(); ?>
<?php wp_footer(); ?>
</body>
</html>
		<?php
		exit;
	}
);

/**
 * Hide the frontend admin bar on the dashboard.
 */
add_filter(
	'show_admin_bar',
	static function ( bool $show ): bool {
		if ( ! empty( $GLOBALS['esk_front_dashboard'] ) ) {
			return false;
		}
		return $show;
	}
);