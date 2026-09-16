<?php
/**
 * Custom admin shell for the Eskoofy management dashboard.
 *
 * Renders an app-style full-screen dashboard chrome (grouped sidebar, topbar,
 * toasts, confirm modal, dark mode) that mirrors the Laravel/PHP dashboards.
 * Used both inside wp-admin (legacy) and — primarily — on the frontend
 * dashboard route `/dashboard/…` (see inc/front-dashboard.php).
 *
 * @package Eskoofy
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * Whether the shell is active: on esk-* wp-admin pages or the frontend dashboard.
 */
function esk_admin_shell_active(): bool {
	if ( ! empty( $GLOBALS['esk_front_dashboard'] ) ) {
		return true;
	}
	if ( ! is_admin() ) {
		return false;
	}
	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
	return '' !== $page && ( 'esk-dashboard' === $page || 0 === strpos( $page, 'esk-' ) );
}

/**
 * Frontend dashboard URL for an esk-* slug (e.g. esk-students -> /dashboard/students/).
 */
function esk_dashboard_url( string $slug = 'esk-dashboard', string $query = '' ): string {
	if ( 'esk-dashboard' === $slug ) {
		$url = home_url( '/dashboard/' );
	} else {
		$slug = preg_replace( '/^esk-/', '', (string) $slug );
		$url  = home_url( '/dashboard/' . rawurlencode( $slug ) . '/' );
	}
	if ( '' !== $query ) {
		$url .= ( strpos( $url, '?' ) === false ? '?' : '&' ) . $query;
	}
	return $url;
}

/**
 * Nav groups (app sidebar order) => slugs of the esk-* pages.
 */
function esk_admin_shell_groups(): array {
	return array(
		'Main'          => array( 'esk-dashboard', 'esk-messages', 'esk-sms' ),
		'People'        => array( 'esk-students', 'esk-student-add', 'esk-teachers', 'esk-teacher-add', 'esk-guardians', 'esk-users' ),
		'Academics'     => array( 'esk-classes', 'esk-sections', 'esk-subjects', 'esk-batches', 'esk-academic-sessions', 'esk-exams', 'esk-results', 'esk-routines', 'esk-assignments', 'esk-progress-reports', 'esk-seat-plans' ),
		'Admissions'    => array( 'esk-admissions' ),
		'Attendance'    => array( 'esk-attendance', 'esk-attendance-mark', 'esk-staff-attendance' ),
		'Finance'       => array( 'esk-fees', 'esk-fee-payments', 'esk-expenses', 'esk-refunds', 'esk-income-statement', 'esk-balance-sheet', 'esk-cash-flow', 'esk-bank-reconciliation' ),
		'HR'            => array( 'esk-payroll', 'esk-payslips', 'esk-salary-structures', 'esk-leave-types', 'esk-leave-requests' ),
		'Documents'     => array( 'esk-admit-cards', 'esk-certificates', 'esk-id-cards', 'esk-testimonials', 'esk-committee' ),
		'Facilities'    => array( 'esk-transport', 'esk-hostels', 'esk-library', 'esk-library-reports' ),
		'Communications'=> array( 'esk-notices', 'esk-announcements' ),
		'Website'       => array( 'esk-cms', 'esk-careers', 'esk-events', 'esk-news', 'esk-gallery', 'esk-documents', 'esk-media', 'esk-contact-submissions' ),
		'System'        => array( 'esk-activity', 'esk-visitor-logs', 'esk-backup', 'esk-notifications', 'esk-search' ),
		'Settings'      => array( 'esk-settings', 'esk-onboarding', 'esk-bulk' ),
		'Reports'       => array( 'esk-reports', 'esk-reports-builder', 'esk-analytics' ),
		'Help'          => array( 'esk-software', 'esk-help', 'esk-profile' ),
	);
}

/**
 * Ordered sidebar structure — section headings, flat links and collapsible
 * `<details>` groups, mirroring the Laravel app's sidebar (partials/dashboard/
 * sidebar.blade.php). Every registered esk-* slug appears exactly once.
 *
 * @return array[]
 */
function esk_admin_sidebar_sections(): array {
	$label   = static fn( string $text ): array => array( 'type' => 'label', 'label' => $text );
	$link    = static fn( string $slug ): array => array( 'type' => 'link', 'slug' => $slug );
	$details = static fn( string $text, string $icon, array $slugs ): array => array( 'type' => 'details', 'label' => $text, 'icon' => $icon, 'slugs' => $slugs );

	// Admin-only groups: shown when the user has the matching capability.
	$can = static fn( string $cap ): bool => function_exists( 'esk_can' ) && esk_can( $cap );

	return array(
		$label( 'Main' ),
		$link( 'esk-dashboard' ),
		$link( 'esk-messages' ),
		$link( 'esk-sms' ),

		$label( 'Academic' ),
		$details( 'People', 'dashicons-groups', array( 'esk-students', 'esk-teachers', 'esk-guardians', 'esk-users' ) ),
		$details( 'Academics', 'dashicons-book-alt', array( 'esk-classes', 'esk-exams', 'esk-results', 'esk-assignments', 'esk-routines' ) ),
		$link( 'esk-admissions' ),
		$details( 'Daily', 'dashicons-calendar-alt', array( 'esk-attendance', 'esk-attendance-mark', 'esk-staff-attendance' ) ),
		$details( 'Finance', 'dashicons-money-alt', array( 'esk-fees', 'esk-fee-payments', 'esk-expenses', 'esk-expense-categories', 'esk-ledger', 'esk-budgets', 'esk-income-statement', 'esk-balance-sheet', 'esk-cash-flow' ) ),
		$details( 'HR', 'dashicons-businessperson', array( 'esk-leave-requests', 'esk-leave-types', 'esk-payroll', 'esk-payslips', 'esk-salary-structures' ) ),
		$details( 'Documents', 'dashicons-media-document', array( 'esk-admit-cards', 'esk-id-cards', 'esk-certificates', 'esk-testimonials', 'esk-committee' ) ),
		$details( 'Library', 'dashicons-book', array( 'esk-library', 'esk-library-reports' ) ),
		$link( 'esk-events' ),
		$link( 'esk-events-calendar' ),
		$link( 'esk-transport' ),
		$link( 'esk-hostels' ),

		$label( 'System' ),
		$link( 'esk-activity' ),
		$link( 'esk-visitor-logs' ),
		$link( 'esk-backup' ),

		$label( 'Website' ),
		$details( 'Website CMS', 'dashicons-admin-site-alt3', array( 'esk-cms', 'esk-news', 'esk-gallery', 'esk-announcements', 'esk-notices', 'esk-documents', 'esk-media', 'esk-contact-submissions', 'esk-careers' ) ),

		$label( 'Administration' ),
		$details( 'Users & Roles', 'dashicons-admin-users', array( 'esk-users', 'esk-roles' ) ),

		$label( 'Configuration' ),
		$link( 'esk-settings' ),
		$link( 'esk-reports' ),
		$link( 'esk-bulk' ),

		$label( 'Help' ),
		$link( 'esk-software' ),
		$link( 'esk-help' ),
		$link( 'esk-profile' ),
	);
}

/**
 * Pending-count badge for a sidebar nav item, or 0 when none applies.
 */
function esk_admin_shell_nav_badge( string $slug, array $badges ): int {
	if ( 'esk-messages' === $slug ) {
		return (int) ( $badges['messages'] ?? 0 );
	}
	if ( 'esk-admissions' === $slug ) {
		return (int) ( $badges['admissions'] ?? 0 );
	}
	if ( 'esk-leave-requests' === $slug ) {
		return (int) ( $badges['leaves'] ?? 0 );
	}
	if ( 'esk-fee-payments' === $slug ) {
		return (int) ( $badges['payments'] ?? 0 );
	}
	return 0;
}

/**
 * All esk-* page titles keyed by slug (used by the shell sidebar on the
 * frontend, where the wp-admin submenu structure is not available).
 */
function esk_dashboard_page_titles(): array {
	return array(
		'esk-dashboard'            => 'Dashboard',
		'esk-students'             => 'Students',
		'esk-student-add'          => 'Add Student',
		'esk-teachers'             => 'Teachers',
		'esk-teacher-add'          => 'Add Teacher',
		'esk-classes'              => 'Classes',
		'esk-sections'             => 'Sections',
		'esk-subjects'             => 'Subjects',
		'esk-batches'              => 'Batches',
		'esk-academic-sessions'    => 'Academic Sessions',
		'esk-attendance'           => 'Attendance',
		'esk-attendance-mark'      => 'Mark Attendance',
		'esk-exams'                => 'Exams',
		'esk-results'              => 'My Results',
		'esk-fees'                 => 'Fees',
		'esk-fee-payments'         => 'Payments',
		'esk-expenses'             => 'Expenses',
		'esk-expense-categories'   => 'Expense Categories',
		'esk-ledger'               => 'Ledger',
		'esk-budgets'              => 'Budgets',
		'esk-payroll'              => 'Payroll',
		'esk-guardians'            => 'Parents',
		'esk-admissions'           => 'Admissions',
		'esk-transport'            => 'Transport',
		'esk-hostels'              => 'Hostel Management',
		'esk-library'              => 'Library',
		'esk-sms'                  => 'Bulk SMS',
		'esk-notices'              => 'Notices',
		'esk-announcements'        => 'Announcements',
		'esk-certificates'         => 'Certificates',
		'esk-admit-cards'          => 'Admit Cards',
		'esk-id-cards'             => 'Student ID Cards',
		'esk-testimonials'         => 'Testimonials',
		'esk-committee'            => 'Committee',
		'esk-events'               => 'Events',
		'esk-events-calendar'      => 'Calendar',
		'esk-news'                 => 'News',
		'esk-gallery'              => 'Gallery',
		'esk-careers'              => 'Careers',
		'esk-reports'              => 'Reports',
		'esk-users'                => 'Users',
		'esk-roles'                => 'Roles & Permissions',
		'esk-settings'             => 'Settings',
		'esk-onboarding'           => 'Onboarding',
		'esk-documents'            => 'Documents',
		'esk-media'                => 'Media Library',
		'esk-contact-submissions'  => 'Form Submissions',
		'esk-search'               => 'Search',
		'esk-cms'                  => 'CMS Pages',
		'esk-bulk'                 => 'Bulk Import/Export',
		'esk-reports-builder'      => 'Reports Builder',
		'esk-analytics'            => 'Analytics',
		'esk-income-statement'     => 'Income Statement',
		'esk-balance-sheet'        => 'Balance Sheet',
		'esk-cash-flow'            => 'Cash Flow',
		'esk-bank-reconciliation'  => 'Bank Recon',
		'esk-payslips'             => 'Payslips',
		'esk-salary-structures'    => 'Salary Structures',
		'esk-leave-types'          => 'Leave Types',
		'esk-leave-requests'       => 'Leave Requests',
		'esk-staff-attendance'     => 'Staff Attendance',
		'esk-refunds'              => 'Refunds',
		'esk-notifications'        => 'Notifications',
		'esk-activity'             => 'Activity Log',
		'esk-visitor-logs'         => 'Visitor Logs',
		'esk-messages'             => 'Messages',
		'esk-assignments'          => 'Assignments',
		'esk-routines'             => 'Class Routine',
		'esk-progress-reports'     => 'Progress Reports',
		'esk-seat-plans'           => 'Seat Plans',
		'esk-library-reports'      => 'Library Reports',
		'esk-profile'              => 'My Profile',
		'esk-help'                 => 'Help & Documentation',
		'esk-backup'               => 'Backups',
		'esk-software'             => 'About',
	);
}

/**
 * Dashicon per esk slug (fallback to a generic gear).
 */
function esk_admin_shell_icon( string $slug ): string {
	$icons = array(
		'esk-dashboard'          => 'dashicons-dashboard',
		'esk-students'           => 'dashicons-groups',
		'esk-student-add'        => 'dashicons-admin-users',
		'esk-teachers'           => 'dashicons-welcome-learn-more',
		'esk-teacher-add'        => 'dashicons-welcome-learn-more',
		'esk-guardians'          => 'dashicons-universal-access',
		'esk-users'              => 'dashicons-admin-users',
		'esk-roles'              => 'dashicons-admin-network',
		'esk-classes'            => 'dashicons-editor-ul',
		'esk-sections'           => 'dashicons-editor-ul',
		'esk-subjects'           => 'dashicons-book-alt',
		'esk-batches'            => 'dashicons-editor-ol',
		'esk-academic-sessions'  => 'dashicons-calendar',
		'esk-exams'              => 'dashicons-welcome-write-blog',
		'esk-results'            => 'dashicons-awards',
		'esk-routines'           => 'dashicons-schedule',
		'esk-assignments'        => 'dashicons-clipboard',
		'esk-progress-reports'   => 'dashicons-chart-line',
		'esk-seat-plans'         => 'dashicons-layout',
		'esk-admit-cards'        => 'dashicons-tickets-alt',
		'esk-certificates'       => 'dashicons-awards',
		'esk-id-cards'           => 'dashicons-id',
		'esk-admissions'         => 'dashicons-welcome-add-page',
		'esk-attendance'         => 'dashicons-calendar-alt',
		'esk-attendance-mark'    => 'dashicons-yes-alt',
		'esk-staff-attendance'   => 'dashicons-businessperson',
		'esk-fees'               => 'dashicons-money-alt',
		'esk-fee-payments'       => 'dashicons-credit-card',
		'esk-expenses'           => 'dashicons-chart-pie',
		'esk-expense-categories' => 'dashicons-category',
		'esk-ledger'             => 'dashicons-list-view',
		'esk-budgets'            => 'dashicons-chart-area',
		'esk-refunds'            => 'dashicons-undo',
		'esk-income-statement'   => 'dashicons-chart-area',
		'esk-balance-sheet'      => 'dashicons-chart-bar',
		'esk-cash-flow'          => 'dashicons-arrow-left-alt',
		'esk-bank-reconciliation'=> 'dashicons-bank',
		'esk-payroll'            => 'dashicons-money',
		'esk-payslips'           => 'dashicons-media-text',
		'esk-salary-structures'  => 'dashicons-forms',
		'esk-leave-types'        => 'dashicons-calendar',
		'esk-leave-requests'     => 'dashicons-palmtree',
		'esk-transport'          => 'dashicons-car',
		'esk-hostels'            => 'dashicons-building',
		'esk-library'            => 'dashicons-book',
		'esk-library-reports'    => 'dashicons-chart-pie',
		'esk-cms'                => 'dashicons-welcome-write-blog',
		'esk-careers'            => 'dashicons-businessperson',
		'esk-committee'          => 'dashicons-groups',
		'esk-testimonials'       => 'dashicons-format-quote',
		'esk-events'             => 'dashicons-calendar-alt',
		'esk-events-calendar'    => 'dashicons-calendar',
		'esk-news'               => 'dashicons-megaphone',
		'esk-gallery'            => 'dashicons-format-gallery',
		'esk-documents'          => 'dashicons-media-document',
		'esk-media'              => 'dashicons-admin-media',
		'esk-contact-submissions'=> 'dashicons-email-alt',
		'esk-search'             => 'dashicons-search',
		'esk-activity'           => 'dashicons-clock',
		'esk-visitor-logs'       => 'dashicons-visibility',
		'esk-notifications'      => 'dashicons-bell',
		'esk-reports'            => 'dashicons-chart-bar',
		'esk-reports-builder'    => 'dashicons-editor-table',
		'esk-analytics'          => 'dashicons-chart-line',
		'esk-bulk'               => 'dashicons-upload',
		'esk-settings'           => 'dashicons-admin-generic',
		'esk-onboarding'         => 'dashicons-megaphone',
		'esk-profile'            => 'dashicons-admin-users',
		'esk-help'               => 'dashicons-editor-help',
		'esk-backup'             => 'dashicons-media-text',
		'esk-software'           => 'dashicons-info-outline',
	);
	return isset( $icons[ $slug ] ) ? $icons[ $slug ] : 'dashicons-admin-generic';
}

/**
 * Breadcrumbs for the current page.
 *
 * Pages can set `$GLOBALS['esk_breadcrumbs']` to an array of
 * [ 'label' => ..., 'url' => ... ] entries (last entry may omit 'url').
 * Defaults to Dashboard / current page title.
 */
function esk_admin_shell_breadcrumbs( string $current = '' ): array {
	if ( ! empty( $GLOBALS['esk_breadcrumbs'] ) && is_array( $GLOBALS['esk_breadcrumbs'] ) ) {
		return $GLOBALS['esk_breadcrumbs'];
	}
	$titles = esk_dashboard_page_titles();
	$current = '' !== $current ? $current : 'esk-dashboard';
	return array(
		array( 'label' => __( 'Dashboard', 'eskoofy' ), 'url' => esk_dashboard_url( 'esk-dashboard' ) ),
		array( 'label' => isset( $titles[ $current ] ) ? $titles[ $current ] : __( 'Page', 'eskoofy' ) ),
	);
}

/**
 * Render the breadcrumb nav inside <main> (mirrors the app layout).
 */
function esk_render_admin_shell_breadcrumbs( string $current = '' ): void {
	$crumbs = esk_admin_shell_breadcrumbs( $current );
	if ( empty( $crumbs ) ) {
		return;
	}
	?>
	<nav class="mb-4 flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400" aria-label="<?php echo esc_attr__( 'Breadcrumb', 'eskoofy' ); ?>">
		<?php
		$count = count( $crumbs );
		foreach ( $crumbs as $i => $crumb ) :
			$last = ( $i === $count - 1 );
			if ( $last || empty( $crumb['url'] ) ) :
				?>
				<span class="font-medium text-slate-900 dark:text-slate-100"><?php echo esc_html( $crumb['label'] ); ?></span>
			<?php else : ?>
				<a href="<?php echo esc_url( $crumb['url'] ); ?>" class="transition-colors hover:text-slate-700 dark:hover:text-slate-200"><?php echo esc_html( $crumb['label'] ); ?></a>
				<svg class="h-4 w-4 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
			<?php endif; ?>
		<?php endforeach; ?>
	</nav>
	<?php
}

/**
 * Resolve the school logo URL for the sidebar brand (mirrors the app layout).
 */
function esk_admin_shell_logo_url(): string {
	$logo = (string) esk_get_option( 'logo_url', '' );
	if ( '' === $logo ) {
		$logo = (string) get_theme_mod( 'esk_custom_logo', '' );
	}
	return '' !== $logo ? esc_url( $logo ) : '';
}

/**
 * Sidebar badge counts (mirrors the app's sidebar pending counts).
 */
function esk_admin_shell_badge_counts(): array {
	global $wpdb;
	$counts = array(
		'messages'   => 0,
		'admissions' => 0,
		'leaves'     => 0,
		'payments'   => 0,
	);
	$user = wp_get_current_user();

	$messages = $wpdb->prefix . 'esk_messages';
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $messages ) ) === $messages ) {
		$counts['messages'] = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$messages} WHERE receiver_id = %d AND read_at IS NULL",
			(int) $user->ID
		) );
	}

	$admissions = $wpdb->prefix . 'esk_admissions';
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $admissions ) ) === $admissions ) {
		$counts['admissions'] = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$admissions} WHERE status IN ('pending','submitted') AND deleted_at IS NULL"
		);
	}

	$leaves = $wpdb->prefix . 'esk_leave_requests';
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $leaves ) ) === $leaves ) {
		$counts['leaves'] = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$leaves} WHERE status IN ('pending','submitted')"
		);
	}

	$payments = $wpdb->prefix . 'esk_fee_payments';
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $payments ) ) === $payments ) {
		$counts['payments'] = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$payments} WHERE status = 'pending'"
		);
	}

	return $counts;
}

/**
 * Pre-paint dark mode script (head).
 */
function esk_admin_shell_head_scripts(): void {
	?>
<script>
	(function () {
		try {
			if ('1' === window.localStorage.getItem('school-dark-mode')) {
				document.documentElement.classList.add('dark');
			}
		} catch (e) {}
	})();
</script>
	<?php
}

/**
 * Render the app-style dashboard shell — mirrors the Laravel app layout.
 *
 * Opens: <div class="admin-shell flex h-screen overflow-hidden"> with the
 * sidebar, the right column and the topbar, ending with an open <main>.
 * Call esk_render_admin_shell_close() after outputting page content.
 *
 * @param string $current Active esk-* slug.
 */
function esk_render_admin_shell_open( string $current = '' ): void {
	$titles   = esk_dashboard_page_titles();
	$current  = '' !== $current ? $current : 'esk-dashboard';
	$school   = esc_html( get_bloginfo( 'name' ) );
	$initials = strtoupper( (string) preg_replace( '/[^A-Z]/', '', substr( (string) get_bloginfo( 'name' ), 0, 2 ) ) );
	$initials = '' !== $initials ? $initials : 'ES';
	$user     = wp_get_current_user();
	$is_bn    = 'bn_BD' === get_option( 'esk_locale', 'en' ) || 0 === strpos( get_locale(), 'bn' );

	$logo_url  = esk_admin_shell_logo_url();
	$badges    = esk_admin_shell_badge_counts();
	$timezone  = (string) esk_get_option( 'timezone', '' );
	if ( '' === $timezone ) {
		$timezone = function_exists( 'wp_timezone_string' ) ? wp_timezone_string() : 'UTC';
	}
	$avatar = get_avatar_url( $user->ID, array( 'size' => 32 ) );

	$notif_count = 0;
	$notif_table = $GLOBALS['wpdb']->prefix . 'esk_notifications';
	if ( $GLOBALS['wpdb']->get_var( "SHOW TABLES LIKE '{$notif_table}'" ) === $notif_table ) {
		$notif_count = (int) $GLOBALS['wpdb']->get_var( "SELECT COUNT(*) FROM {$notif_table} WHERE read_at IS NULL AND user_id = " . (int) $user->ID );
	}

	$palette = array();
	foreach ( $titles as $slug => $title ) {
		$group = '';
		foreach ( esk_admin_shell_groups() as $g => $slugs ) {
			if ( in_array( $slug, $slugs, true ) ) { $group = $g; break; }
		}
		$palette[] = array( 'label' => $title, 'url' => esk_dashboard_url( $slug ), 'group' => $group );
	}
	?>
<a href="#main-content" class="skip-link"><?php esc_html_e( 'Skip to content', 'eskoofy' ); ?></a>
<div id="esk-loading-bar" class="fixed left-0 top-0 z-[200] h-1 bg-brand-600 transition-all duration-300 ease-out" style="width:0;opacity:0;"></div>
<div class="admin-shell flex h-screen overflow-hidden">

	<aside id="sidebar" class="no-print flex w-64 flex-shrink-0 flex-col border-r border-slate-200/80 bg-white dark:border-slate-700/80 dark:bg-slate-800">

		<div class="flex h-[4.25rem] flex-shrink-0 items-center gap-2.5 border-b border-slate-100 px-4 dark:border-slate-700">
			<a href="<?php echo esc_url( esk_dashboard_url( 'esk-dashboard' ) ); ?>" class="flex min-w-0 items-center gap-2.5">
				<?php if ( '' !== $logo_url ) : ?>
					<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $school ); ?>" class="h-9 w-9 shrink-0 rounded-lg object-cover ring-1 ring-slate-200 dark:ring-slate-600">
				<?php else : ?>
					<span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-600 text-sm font-bold text-white"><?php echo esc_html( $initials ); ?></span>
				<?php endif; ?>
				<div class="min-w-0">
					<?php if ( '' === $logo_url ) : ?>
						<p class="truncate text-sm font-bold text-slate-900 dark:text-white"><?php echo esc_html( $school ); ?></p>
					<?php endif; ?>
					<p class="text-[0.65rem] text-slate-400"><?php esc_html_e( 'Management System', 'eskoofy' ); ?></p>
				</div>
			</a>
		</div>

		<div class="p-3">
			<div class="relative">
				<span class="dashicons dashicons-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></span>
				<input type="search" id="esk-shell-filter" class="admin-input pl-9" placeholder="<?php esc_attr_e( 'Filter menu…', 'eskoofy' ); ?>" autocomplete="off">
			</div>
		</div>

		<nav class="admin-sidebar-nav flex-1 overflow-y-auto px-3 py-2">
			<p class="mb-2 px-3 text-[0.65rem] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 esk-fav-group" id="esk-fav-group" hidden><?php esc_html_e( 'Favorites', 'eskoofy' ); ?></p>
			<ul class="mb-4 space-y-0.5" id="esk-fav-list"></ul>

			<?php
			$esk_is_admin  = current_user_can( 'manage_options' );
			$esk_sections  = esk_admin_sidebar_sections();
			$esk_admin_labels = array( 'System', 'Website', 'Administration', 'Configuration' );
			if ( ! $esk_is_admin ) {
				foreach ( $esk_sections as $esk_i => $esk_item ) {
					if ( 'link' === $esk_item['type'] && 'esk-sms' === $esk_item['slug'] ) {
						unset( $esk_sections[ $esk_i ] );
					}
					if ( 'label' === $esk_item['type'] && in_array( $esk_item['label'], $esk_admin_labels, true ) ) {
						unset( $esk_sections[ $esk_i ] );
					}
				}
				$esk_sections = array_values( $esk_sections );
			}
			?>
			<?php foreach ( $esk_sections as $esk_item ) : ?>
				<?php if ( 'label' === $esk_item['type'] ) : ?>
					<p class="mb-2 mt-5 px-3 text-[0.65rem] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 esk-group-label"><?php echo esc_html( $esk_item['label'] ); ?></p>
				<?php elseif ( 'link' === $esk_item['type'] ) : ?>
					<?php
					$esk_slug  = $esk_item['slug'];
					if ( ! isset( $titles[ $esk_slug ] ) ) { continue; }
					$esk_badge = esk_admin_shell_nav_badge( $esk_slug, $badges );
					?>
					<div class="space-y-0.5 esk-nav-block">
						<a href="<?php echo esc_url( esk_dashboard_url( $esk_slug ) ); ?>" data-esk-nav="<?php echo esc_attr( $esk_slug ); ?>" class="admin-nav-link <?php echo $current === $esk_slug ? 'admin-nav-link--active' : ''; ?>">
							<span class="flex h-5 w-5 shrink-0 items-center justify-center opacity-80"><span class="dashicons <?php echo esc_attr( esk_admin_shell_icon( $esk_slug ) ); ?>" style="font-size:1.1rem;width:1.1rem;height:1.1rem;"></span></span>
							<span class="flex-1 truncate"><?php echo esc_html( $titles[ $esk_slug ] ); ?></span>
							<?php if ( $esk_badge > 0 ) : ?>
								<span class="inline-flex min-w-[1.25rem] items-center justify-center rounded-full bg-amber-500 px-1.5 py-0.5 text-[10px] font-bold leading-none text-white"><?php echo esc_html( (string) $esk_badge ); ?></span>
							<?php endif; ?>
						</a>
					</div>
				<?php else : ?>
					<?php
					$esk_links = array();
					foreach ( $esk_item['slugs'] as $esk_sub_slug ) {
						if ( isset( $titles[ $esk_sub_slug ] ) ) { $esk_links[ $esk_sub_slug ] = $titles[ $esk_sub_slug ]; }
					}
					if ( empty( $esk_links ) ) { continue; }
					$esk_open = in_array( $current, array_keys( $esk_links ), true );
					?>
					<details class="group esk-nav-block"<?php echo $esk_open ? ' open' : ''; ?>>
						<summary class="admin-nav-link cursor-pointer list-none esk-nav-summary <?php echo $esk_open ? 'admin-nav-link--active' : ''; ?>">
							<span class="flex h-5 w-5 shrink-0 items-center justify-center opacity-80"><span class="dashicons <?php echo esc_attr( $esk_item['icon'] ); ?>" style="font-size:1.1rem;width:1.1rem;height:1.1rem;"></span></span>
							<span class="flex-1 truncate"><?php echo esc_html( $esk_item['label'] ); ?></span>
							<svg class="h-4 w-4 shrink-0 text-slate-400 transition group-open:rotate-90 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
						</summary>
						<div class="ml-4 mt-1 space-y-0.5 border-l border-slate-200 pl-3 dark:border-slate-700">
							<?php foreach ( $esk_links as $esk_sub_slug => $esk_sub_title ) : ?>
								<?php $esk_sub_badge = esk_admin_shell_nav_badge( $esk_sub_slug, $badges ); ?>
								<a href="<?php echo esc_url( esk_dashboard_url( $esk_sub_slug ) ); ?>" data-esk-nav="<?php echo esc_attr( $esk_sub_slug ); ?>" class="block rounded-lg py-2 pl-2 text-sm <?php echo $current === $esk_sub_slug ? 'font-semibold text-brand-700 dark:text-brand-400' : 'text-slate-600 hover:text-brand-600 dark:text-slate-400 dark:hover:text-brand-400'; ?>">
									<?php if ( $esk_sub_badge > 0 ) : ?>
										<span class="inline-flex w-full items-center justify-between gap-2">
											<span><?php echo esc_html( $esk_sub_title ); ?></span>
											<span class="inline-flex min-w-[1.25rem] items-center justify-center rounded-full bg-amber-500 px-1.5 py-0.5 text-[10px] font-bold leading-none text-white"><?php echo esc_html( (string) $esk_sub_badge ); ?></span>
										</span>
									<?php else : ?>
										<?php echo esc_html( $esk_sub_title ); ?>
									<?php endif; ?>
								</a>
							<?php endforeach; ?>
						</div>
					</details>
				<?php endif; ?>
			<?php endforeach; ?>
		</nav>

		<div class="flex flex-col gap-1 border-t border-slate-100 p-3 dark:border-slate-700">
			<button type="button" id="esk-pwa-install" class="hidden items-center gap-2 rounded-lg px-2 py-1.5 text-sm text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-700">
				<span class="dashicons dashicons-download"></span>
				<?php esc_html_e( 'Install App', 'eskoofy' ); ?>
			</button>
			<button type="button" id="esk-dark-toggle-foot" class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-700">
				<span class="dashicons dashicons-lightbulb"></span>
				<?php esc_html_e( 'Dark mode', 'eskoofy' ); ?>
			</button>
			<a href="<?php echo esc_url( wp_logout_url( home_url( '/login/' ) ) ); ?>" class="flex w-full items-center justify-center gap-2 rounded-lg bg-red-600 px-2 py-2 text-sm font-medium text-white transition hover:bg-red-700 dark:bg-red-600 dark:hover:bg-red-700">
				<span class="dashicons dashicons-exit"></span>
				<?php esc_html_e( 'Log out', 'eskoofy' ); ?>
			</a>
		</div>
	</aside>

	<div class="no-print fixed inset-0 z-40 hidden bg-slate-900/50 backdrop-blur-sm lg:hidden" id="esk-sidebar-overlay"></div>

	<div class="flex min-w-0 flex-1 flex-col">
		<header class="no-print flex h-16 flex-shrink-0 items-center gap-4 border-b border-slate-200/80 bg-white/95 px-4 backdrop-blur-md dark:border-slate-700/80 dark:bg-slate-800/95">
			<button type="button" id="esk-shell-burger" class="-ml-2 rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700 lg:hidden dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-slate-200" aria-label="<?php esc_attr_e( 'Toggle menu', 'eskoofy' ); ?>">
				<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
			</button>

			<div class="hidden items-center gap-2 text-xs text-slate-500 md:flex" id="esk-shell-clock-container" data-timezone="<?php echo esc_attr( $timezone ); ?>">
				<span class="dashicons dashicons-clock"></span>
				<span id="esk-shell-clock"></span>
			</div>

			<div class="flex-1"></div>

			<div class="flex items-center gap-2">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" rel="noopener noreferrer" class="flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-slate-200">
					<span class="dashicons dashicons-external"></span>
					<span class="hidden md:inline"><?php esc_html_e( 'Website', 'eskoofy' ); ?></span>
				</a>

				<button type="button" id="esk-search-trigger" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-slate-200" aria-label="<?php esc_attr_e( 'Search (Ctrl+K)', 'eskoofy' ); ?>">
					<span class="dashicons dashicons-search"></span>
				</button>

				<a href="<?php echo esc_url( add_query_arg( 'esk_lang', $is_bn ? 'en' : 'bn_BD' ) ); ?>" rel="nofollow" class="flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-slate-200">
					<span class="dashicons dashicons-translation"></span>
					<span class="hidden md:inline"><?php echo $is_bn ? 'English' : 'বাংলা'; ?></span>
				</a>

				<button type="button" id="esk-fav-toggle" class="rounded-lg p-2 text-slate-500 transition hover:bg-amber-100 hover:text-amber-600 dark:text-slate-400 dark:hover:bg-amber-900/30 dark:hover:text-amber-400" aria-label="<?php esc_attr_e( 'Pin current page', 'eskoofy' ); ?>">
					<span class="dashicons dashicons-star-filled" id="esk-fav-star"></span>
				</button>

				<button type="button" data-help-modal-open class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-slate-200" aria-label="<?php esc_attr_e( 'Help', 'eskoofy' ); ?>" title="<?php esc_attr_e( 'Help', 'eskoofy' ); ?>">
					<span class="dashicons dashicons-editor-help"></span>
				</button>

				<button type="button" id="esk-dark-toggle" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-slate-200" aria-label="<?php esc_attr_e( 'Toggle dark mode', 'eskoofy' ); ?>">
					<span class="dashicons dashicons-lightbulb"></span>
				</button>

				<a href="<?php echo esc_url( esk_dashboard_url( 'esk-notifications' ) ); ?>" class="relative rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-slate-200" aria-label="<?php esc_attr_e( 'Notifications', 'eskoofy' ); ?>">
					<span class="dashicons dashicons-bell"></span>
					<?php if ( $notif_count > 0 ) : ?><span class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-amber-500 px-1 text-[0.6rem] font-bold text-white"><?php echo esc_html( (string) $notif_count ); ?></span><?php endif; ?>
				</a>

				<div class="relative" id="esk-user-menu">
					<button type="button" class="flex items-center gap-2 rounded-lg p-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-700" aria-expanded="false">
						<?php if ( $avatar ) : ?>
							<img src="<?php echo esc_url( $avatar ); ?>" alt="" class="h-7 w-7 rounded-full object-cover ring-2 ring-slate-200 dark:ring-slate-600">
						<?php else : ?>
							<span class="flex h-7 w-7 items-center justify-center rounded-full bg-brand-600 text-xs font-bold text-white ring-2 ring-slate-200 dark:ring-slate-600"><?php echo esc_html( $initials ); ?></span>
						<?php endif; ?>
						<span class="hidden md:inline"><?php echo esc_html( $user->display_name ); ?></span>
						<svg class="hidden h-4 w-4 text-slate-400 md:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
					</button>
					<div class="absolute right-0 top-full z-50 mt-2 hidden w-56 rounded-xl border border-slate-200 bg-white p-1.5 shadow-lg dark:border-slate-700 dark:bg-slate-800 esk-user-dropdown">
						<a href="<?php echo esc_url( esk_dashboard_url( 'esk-dashboard' ) ); ?>" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-700"><span class="dashicons dashicons-dashboard"></span><?php esc_html_e( 'Dashboard', 'eskoofy' ); ?></a>
						<a href="<?php echo esc_url( esk_dashboard_url( 'esk-onboarding' ) ); ?>" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-700"><span class="dashicons dashicons-megaphone"></span><?php esc_html_e( 'Setup & Onboarding', 'eskoofy' ); ?></a>
						<a href="<?php echo esc_url( esk_dashboard_url( 'esk-profile' ) ); ?>" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-700"><span class="dashicons dashicons-admin-users"></span><?php esc_html_e( 'My Profile', 'eskoofy' ); ?></a>
						<a href="<?php echo esc_url( esk_dashboard_url( 'esk-settings' ) ); ?>" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-700"><span class="dashicons dashicons-admin-generic"></span><?php esc_html_e( 'School Settings', 'eskoofy' ); ?></a>
						<hr class="my-1 border-slate-100 dark:border-slate-700">
						<a href="<?php echo esc_url( wp_logout_url( home_url( '/login/' ) ) ); ?>" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20"><span class="dashicons dashicons-exit"></span><?php esc_html_e( 'Log out', 'eskoofy' ); ?></a>
					</div>
				</div>
			</div>
		</header>

		<main id="main-content" tabindex="-1" class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
	<?php esk_render_admin_shell_breadcrumbs( $current ); ?>
	<?php
	$flash_status = esk_get_flash( 'status' );
	$flash_error  = esk_get_flash( 'error' );
	$flash_info   = esk_get_flash( 'info' );
	?>
	<?php if ( '' !== $flash_status ) : ?><div data-flash-toast data-type="success" data-message="<?php echo esc_attr( $flash_status ); ?>"></div><?php endif; ?>
	<?php if ( '' !== $flash_error ) : ?><div data-flash-toast data-type="error" data-message="<?php echo esc_attr( $flash_error ); ?>"></div><?php endif; ?>
	<?php if ( '' !== $flash_info ) : ?><div data-flash-toast data-type="info" data-message="<?php echo esc_attr( $flash_info ); ?>"></div><?php endif; ?>
	<?php
}

/**
 * Close the dashboard shell (main, columns, wrapper) + overlays.
 */
function esk_render_admin_shell_close(): void {
	?>
		</main>
	</div>
</div>

<div id="esk-toast-root" class="esk-toast-container"></div>

<div id="esk-confirm-modal" class="esk-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="esk-confirm-title">
	<div class="esk-modal-panel">
		<div class="esk-modal-icon"><span class="dashicons dashicons-warning"></span></div>
		<p class="esk-modal-title" id="esk-confirm-title"><?php esc_html_e( 'Are you sure?', 'eskoofy' ); ?></p>
		<p class="esk-modal-message" id="esk-confirm-message"></p>
		<div class="esk-modal-actions">
			<button type="button" class="esk-modal-btn esk-modal-cancel" id="esk-confirm-cancel"><?php esc_html_e( 'Cancel', 'eskoofy' ); ?></button>
			<button type="button" class="esk-modal-btn esk-modal-ok" id="esk-confirm-ok"><?php esc_html_e( 'Confirm', 'eskoofy' ); ?></button>
		</div>
	</div>
</div>

<div id="esk-palette" class="esk-palette" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Search', 'eskoofy' ); ?>">
	<div class="esk-palette-panel">
		<div class="esk-palette-input">
			<span class="dashicons dashicons-search"></span>
			<input type="text" id="esk-palette-q" placeholder="<?php esc_attr_e( 'Type to search pages or students…', 'eskoofy' ); ?>" autocomplete="off">
			<kbd class="esk-palette-esc">ESC</kbd>
			<button type="button" id="esk-palette-close" aria-label="<?php esc_attr_e( 'Close', 'eskoofy' ); ?>">&times;</button>
		</div>
		<ul id="esk-palette-results" class="esk-palette-results"></ul>
	</div>
</div>

<div id="esk-help-modal" class="esk-modal-backdrop esk-help-modal" role="dialog" aria-modal="true" aria-labelledby="esk-help-title">
	<div class="esk-modal-panel esk-help-panel">
		<div class="esk-help-head">
			<div class="esk-help-head-icon"><span class="dashicons dashicons-editor-help"></span></div>
			<div>
				<p class="esk-modal-title" id="esk-help-title"><?php esc_html_e( 'Getting started', 'eskoofy' ); ?></p>
				<p class="esk-help-subtitle" id="esk-help-subtitle"><?php esc_html_e( 'A few steps to get your school up and running.', 'eskoofy' ); ?></p>
			</div>
			<button type="button" id="esk-help-close" class="esk-help-close" aria-label="<?php esc_attr_e( 'Close', 'eskoofy' ); ?>">&times;</button>
		</div>
		<ol class="esk-help-steps" id="esk-help-steps"></ol>
		<div class="esk-help-topics">
			<p class="esk-help-topics-label"><?php esc_html_e( 'Jump to a topic', 'eskoofy' ); ?></p>
			<div class="esk-help-topics-grid" id="esk-help-topics"></div>
		</div>
	</div>
</div>
<script type="application/json" id="esk-help-data"><?php echo wp_json_encode( esk_render_help_sections() ); ?></script>

<div id="esk-document-preview-modal" class="esk-modal-backdrop esk-doc-modal" role="dialog" aria-modal="true" aria-labelledby="esk-doc-title">
	<div class="esk-doc-panel">
		<div class="esk-doc-head">
			<h2 id="esk-doc-title"><?php esc_html_e( 'Document preview', 'eskoofy' ); ?></h2>
			<div class="flex items-center gap-2">
				<a href="#" target="_blank" rel="noopener noreferrer" id="esk-doc-print" class="esk-btn esk-btn-primary"><?php esc_html_e( 'Print / Download', 'eskoofy' ); ?></a>
				<button type="button" id="esk-doc-close" class="esk-doc-close" aria-label="<?php esc_attr_e( 'Close', 'eskoofy' ); ?>">&times;</button>
			</div>
		</div>
		<div class="esk-doc-body"><iframe id="esk-doc-frame" title="<?php esc_attr_e( 'Document preview', 'eskoofy' ); ?>"></iframe></div>
	</div>
</div>

<script type="application/json" id="esk-palette-data"><?php echo wp_json_encode( esk_render_palette_data() ); ?></script>
<span id="esk-current-slug" hidden><?php echo esc_html( $GLOBALS['esk_front_dash_slug'] ?? 'esk-dashboard' ); ?></span>
	<?php
}

/**
 * Contextual help sections (mirrors the app dashboard help modal).
 */
function esk_render_help_sections(): array {
	return array(
		'getting_started' => array(
			'title'       => __( 'Getting started', 'eskoofy' ),
			'description' => __( 'A few steps to get your school up and running.', 'eskoofy' ),
			'steps'       => array(
				__( 'Open School Settings and fill in your school name, address, and contact details.', 'eskoofy' ),
				__( 'Create Academic Sessions, Classes, Sections, and Subjects.', 'eskoofy' ),
				__( 'Add Teachers and Students, then assign them to classes and sections.', 'eskoofy' ),
				__( 'Set up Fees, then collect and manage payments.', 'eskoofy' ),
			),
		),
		'students' => array(
			'title'       => __( 'Students', 'eskoofy' ),
			'description' => __( 'Manage student records and enrollment.', 'eskoofy' ),
			'steps'       => array(
				__( 'Add a student under People → Students.', 'eskoofy' ),
				__( 'Assign class, section, batch, and student ID.', 'eskoofy' ),
				__( 'Optionally upload a photo and guardian details.', 'eskoofy' ),
			),
		),
		'fees' => array(
			'title'       => __( 'Fees & payments', 'eskoofy' ),
			'description' => __( 'Set up fees and track payments.', 'eskoofy' ),
			'steps'       => array(
				__( 'Create fee structures under Finance → Fees.', 'eskoofy' ),
				__( 'Record payments, view receipts, and approve pending payments.', 'eskoofy' ),
				__( 'Track expenses and view financial reports.', 'eskoofy' ),
			),
		),
		'attendance' => array(
			'title'       => __( 'Attendance', 'eskoofy' ),
			'description' => __( 'Take and review student attendance.', 'eskoofy' ),
			'steps'       => array(
				__( 'Open Attendance, pick a class and section.', 'eskoofy' ),
				__( 'Mark present/absent and save the day\'s attendance.', 'eskoofy' ),
			),
		),
		'exams' => array(
			'title'       => __( 'Exams & results', 'eskoofy' ),
			'description' => __( 'Create exams and publish results.', 'eskoofy' ),
			'steps'       => array(
				__( 'Add an exam under Academics → Exams.', 'eskoofy' ),
				__( 'Enter marks per student, then publish when ready.', 'eskoofy' ),
			),
		),
		'payroll' => array(
			'title'       => __( 'Payroll & HR', 'eskoofy' ),
			'description' => __( 'Salary structures, leaves, and payslips.', 'eskoofy' ),
			'steps'       => array(
				__( 'Define salary structures under HR.', 'eskoofy' ),
				__( 'Generate payslips and approve leave requests.', 'eskoofy' ),
			),
		),
	);
}

/**
 * Palette page data (kept in a helper so both open/close can share it).
 */
function esk_render_palette_data(): array {
	$palette = array();
	foreach ( esk_dashboard_page_titles() as $slug => $title ) {
		$group = '';
		foreach ( esk_admin_shell_groups() as $g => $slugs ) {
			if ( in_array( $slug, $slugs, true ) ) { $group = $g; break; }
		}
		$palette[] = array( 'label' => $title, 'url' => esk_dashboard_url( $slug ), 'group' => $group );
	}
	return $palette;
}

/**
 * Legacy render (used by the wp-admin integration hooks).
 */
function esk_render_admin_shell( string $current = '' ): void {
	esk_render_admin_shell_open( $current );
	esk_render_admin_shell_close();
}

/**
 * Toast + burger + confirm-modal JS for the shell.
 */
function esk_admin_shell_footer_scripts(): void {
	?>
<script>
(function () {
	function eskToast(message, type) {
		var root = document.getElementById('esk-toast-root');
		if (!root || !message) { return; }
		var t = document.createElement('div');
		t.className = 'esk-toast esk-toast--' + (type || 'info');
		t.setAttribute('role', 'alert');
		var msg = document.createElement('span');
		msg.className = 'esk-toast-text';
		msg.textContent = message;
		var close = document.createElement('button');
		close.type = 'button';
		close.className = 'esk-toast-dismiss';
		close.setAttribute('aria-label', 'Dismiss');
		close.innerHTML = '&times;';
		t.appendChild(msg);
		t.appendChild(close);
		root.appendChild(t);
		function dismiss() {
			t.classList.add('esk-toast--leaving');
			setTimeout(function () { t.remove(); }, 250);
		}
		close.addEventListener('click', dismiss);
		setTimeout(dismiss, 5000);
	}
	window.eskToast = eskToast;

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('.wrap .notice').forEach(function (n) {
			var msg = n.textContent.trim();
			if (!msg) { return; }
			var type = n.classList.contains('notice-error') ? 'error' : (n.classList.contains('notice-warning') ? 'warning' : 'success');
			eskToast(msg, type);
			n.remove();
		});

		document.querySelectorAll('[data-flash-toast]').forEach(function (el) {
			var msg = el.getAttribute('data-message');
			if (!msg) { return; }
			eskToast(msg, el.getAttribute('data-type') || 'success');
			el.remove();
		});
	});

	/* Dark mode. */
	function applyDark(dark) {
		document.documentElement.classList.toggle('dark', dark);
		try { window.localStorage.setItem('school-dark-mode', dark ? '1' : ''); } catch (e) {}
	}
	var darkToggles = document.querySelectorAll('#esk-dark-toggle, #esk-dark-toggle-foot');
	darkToggles.forEach(function (btn) {
		btn.addEventListener('click', function () {
			applyDark(!document.documentElement.classList.contains('dark'));
		});
	});

	/* Mobile sidebar. */
	var burger = document.getElementById('esk-shell-burger');
	var shellOverlay = document.getElementById('esk-sidebar-overlay');
	function eskShellNavOpen() {
		document.body.classList.add('esk-shell-nav-open');
		if (shellOverlay) { shellOverlay.classList.remove('hidden'); }
	}
	function eskShellNavClose() {
		document.body.classList.remove('esk-shell-nav-open');
		if (shellOverlay) { shellOverlay.classList.add('hidden'); }
	}
	if (burger) {
		burger.addEventListener('click', function () {
			if (document.body.classList.contains('esk-shell-nav-open')) {
				eskShellNavClose();
			} else {
				eskShellNavOpen();
			}
		});
	}
	if (shellOverlay) {
		shellOverlay.addEventListener('click', eskShellNavClose);
	}
	document.addEventListener('keydown', function (e) {
		if ('Escape' === e.key && document.body.classList.contains('esk-shell-nav-open')) {
			eskShellNavClose();
		}
	});

	/* Live clock. */
	var clock = document.getElementById('esk-shell-clock');
	var clockWrap = document.getElementById('esk-shell-clock-container');
	if (clock) {
		var timezone = (clockWrap && clockWrap.getAttribute('data-timezone')) || 'UTC';
		function tick() {
			try {
				var now = new Date();
				var options = {
					timeZone: timezone,
					weekday: 'long', year: 'numeric', month: 'short', day: 'numeric',
					hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true
				};
				var parts = new Intl.DateTimeFormat('en-US', options).formatToParts(now);
				var map = {};
				parts.forEach(function (p) { map[p.type] = p.value; });
				var tzName = timezone.split('/').pop().replace(/_/g, ' ');
				clock.textContent = map.weekday + ', ' + map.day + ' ' + map.month + ' ' + map.year + ', ' + map.hour + ':' + map.minute + ':' + map.second + ' ' + map.dayPeriod + ' ' + tzName;
			} catch (e) {
				clock.textContent = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
			}
		}
		tick();
		setInterval(tick, 1000);
	}

	/* User dropdown. */
	var userMenu = document.getElementById('esk-user-menu');
	if (userMenu) {
		userMenu.addEventListener('click', function (e) {
			e.stopPropagation();
			var dd = userMenu.querySelector('.esk-user-dropdown');
			if (dd) { dd.classList.toggle('hidden'); }
		});
		document.addEventListener('click', function () {
			var dd = userMenu.querySelector('.esk-user-dropdown');
			if (dd) { dd.classList.add('hidden'); }
		});
	}

	/* Sidebar filter (matches nav links, auto-opens matching accordion groups). */
	var filter = document.getElementById('esk-shell-filter');
	if (filter) {
		filter.addEventListener('input', function () {
			var q = filter.value.trim().toLowerCase();
			var nav = document.querySelector('.admin-sidebar-nav');
			if (!nav) { return; }
			var links = nav.querySelectorAll('a[data-esk-nav]');
			links.forEach(function (a) {
				a.style.display = !q || a.textContent.toLowerCase().indexOf(q) !== -1 ? '' : 'none';
			});
			nav.querySelectorAll('details.group').forEach(function (d) {
				var hasMatch = false;
				d.querySelectorAll('a[data-esk-nav]').forEach(function (a) {
					if (a.style.display !== 'none') { hasMatch = true; }
				});
				if (!q) {
					d.style.display = '';
				} else if (hasMatch) {
					d.style.display = '';
					d.open = true;
				} else {
					d.style.display = 'none';
				}
			});
			nav.querySelectorAll('p.esk-group-label').forEach(function (h) {
				var sibling = h.nextElementSibling;
				var anyVisible = false;
				while (sibling && !sibling.classList.contains('esk-group-label')) {
					if (sibling.style.display !== 'none') { anyVisible = true; }
					sibling = sibling.nextElementSibling;
				}
				h.style.display = anyVisible ? '' : 'none';
			});
		});
	}

	/* Confirm modal. */
	var modalRoot = document.getElementById('esk-confirm-modal');
	if (modalRoot) {
		var confirmBtn = document.getElementById('esk-confirm-ok');
		var cancelBtn = document.getElementById('esk-confirm-cancel');
		var msgEl = document.getElementById('esk-confirm-message');
		var pending = null;
		function closeModal() { modalRoot.classList.remove('is-open'); pending = null; }

		document.addEventListener('click', function (e) {
			var target = e.target;
			if (!(target instanceof Element)) { return; }
			var el = target.closest('a, button, input[type="submit"], input[type="button"]');
			if (!el) { return; }
			var oc = el.getAttribute && (el.getAttribute('onclick') || '');
			if (oc.indexOf('confirm(') === -1) { return; }
			e.preventDefault();
			e.stopPropagation();
			var m = oc.match(/confirm\(\s*['"]([^'"]*)['"]\s*\)/);
			var brand = oc.indexOf("'brand'") !== -1 || oc.indexOf('"brand"') !== -1;
			msgEl.textContent = m && m[1] ? m[1] : 'Are you sure?';
			modalRoot.classList.toggle('esk-modal-brand', brand);
			modalRoot.classList.add('is-open');
			pending = el;
		}, true);

		document.addEventListener('submit', function (e) {
			var form = e.target;
			if (!(form instanceof HTMLFormElement)) { return; }
			var os = form.getAttribute && (form.getAttribute('onsubmit') || '');
			if (os.indexOf('confirm(') === -1) { return; }
			e.preventDefault();
			e.stopPropagation();
			var m = os.match(/confirm\(\s*['"]([^'"]*)['"]\s*\)/);
			var brand = os.indexOf("'brand'") !== -1 || os.indexOf('"brand"') !== -1;
			msgEl.textContent = m && m[1] ? m[1] : 'Are you sure?';
			modalRoot.classList.toggle('esk-modal-brand', brand);
			modalRoot.classList.add('is-open');
			pending = form;
		}, true);

		if (confirmBtn) {
			confirmBtn.addEventListener('click', function () {
				var action = pending;
				closeModal();
				if (action instanceof HTMLFormElement) { action.submit(); return; }
				if (!action) { return; }
				if (action.tagName.toLowerCase() === 'a') {
					var href = action.getAttribute('href');
					if (href) { window.location.href = href; }
				} else {
					var form = action.form;
					if (form) { form.submit(); }
				}
			});
		}
		if (cancelBtn) { cancelBtn.addEventListener('click', closeModal); }
		modalRoot.addEventListener('click', function (e) { if (e.target === modalRoot) { closeModal(); } });
	}

	/* Command palette. */
	var palette = document.getElementById('esk-palette');
	var paletteQ = document.getElementById('esk-palette-q');
	var paletteResults = document.getElementById('esk-palette-results');
	var paletteClose = document.getElementById('esk-palette-close');
	var paletteTrigger = document.getElementById('esk-search-trigger');
	var paletteData = [];
	try { paletteData = JSON.parse(document.getElementById('esk-palette-data').textContent || '[]'); } catch (e) {}

	function openPalette() {
		if (!palette) { return; }
		palette.classList.add('is-open');
		paletteQ.value = '';
		renderPalette(paletteData);
		paletteQ.focus();
	}
	function closePalette() { if (palette) { palette.classList.remove('is-open'); } }
	function renderPalette(pages, students) {
		if (!paletteResults) { return; }
		var html = '';
		var groups = {};
		pages.forEach(function (p) { (groups[p.group] = groups[p.group] || []).push(p); });
		Object.keys(groups).forEach(function (g) {
			html += '<li class="esk-palette-group">' + g + '</li>';
			groups[g].forEach(function (p) {
				html += '<li><a href="' + p.url + '"><span class="dashicons dashicons-admin-generic"></span>' + p.label + '</a></li>';
			});
		});
		(students || []).forEach(function (s) {
			html += '<li><a href="' + (window.eskDashStudentsUrl || '#') + '"><span class="dashicons dashicons-groups"></span>' + (s.display_name || s.name || 'Student') + ' — ' + (s.admission_number || '') + '</a></li>';
		});
		paletteResults.innerHTML = html || '<li class="esk-palette-empty">No results</li>';
	}
	if (paletteTrigger) { paletteTrigger.addEventListener('click', openPalette); }
	if (paletteClose) { paletteClose.addEventListener('click', closePalette); }
	if (palette) {
		palette.addEventListener('click', function (e) { if (e.target === palette) { closePalette(); } });
	}
	document.addEventListener('keydown', function (e) {
		if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); openPalette(); }
		if (e.key === '/' && !e.ctrlKey && !e.metaKey && !e.altKey) {
			var tag = e.target && e.target.tagName ? e.target.tagName.toLowerCase() : '';
			var editable = e.target && (e.target.isContentEditable || ['input', 'textarea', 'select'].indexOf(tag) !== -1);
			if (!editable) { e.preventDefault(); openPalette(); }
		}
		if (e.key === 'Escape') { closePalette(); closeHelp(); closeDocPreview(); }
	});
	if (paletteQ) {
		paletteQ.addEventListener('input', function () {
			var q = paletteQ.value.trim().toLowerCase();
			var pages = q ? paletteData.filter(function (p) { return p.label.toLowerCase().indexOf(q) !== -1; }) : paletteData;
			renderPalette(pages, []);
			if (q.length >= 2 && window.eskAdmin && window.eskAdmin.ajaxUrl) {
				var params = new URLSearchParams({ action: 'esk_search_students', nonce: window.eskAdmin.nonce, q: q });
				fetch(window.eskAdmin.ajaxUrl + '?' + params.toString())
					.then(function (r) { return r.json(); })
					.then(function (res) { if (res && res.success) { renderPalette(pages, res.data.students); } })
					.catch(function () {});
			}
		});
	}

	/* Contextual help modal. */
	var helpModal = document.getElementById('esk-help-modal');
	var helpSteps = document.getElementById('esk-help-steps');
	var helpTopics = document.getElementById('esk-help-topics');
	var helpData = [];
	try { helpData = JSON.parse(document.getElementById('esk-help-data').textContent || '[]'); } catch (e) {}
	var ESC = function (s) {
		var d = document.createElement('div');
		d.textContent = s || '';
		return d.innerHTML;
	};
	function renderHelp() {
		if (!helpSteps || !helpTopics) { return; }
		var first = helpData['getting_started'] || Object.values(helpData)[0] || null;
		var stepsHtml = '<li class="esk-help-step">' + ESC(first.title) + '</li>';
		if (first) {
			stepsHtml = (first.steps || []).map(function (s, i) {
				return '<li class="esk-help-step"><span class="esk-help-step-num">' + (i + 1) + '</span><span>' + ESC(s) + '</span></li>';
			}).join('');
		}
		helpSteps.innerHTML = stepsHtml;
		helpTopics.innerHTML = Object.keys(helpData).map(function (key) {
			var s = helpData[key];
			return '<button type="button" data-help-topic="' + key + '" class="esk-help-topic"><span class="dashicons dashicons-editor-help"></span>' + ESC(s.title) + '</button>';
		}).join('');
	}
	function openHelp() {
		if (!helpModal) { return; }
		renderHelp();
		helpModal.classList.add('is-open');
	}
	function closeHelp() { if (helpModal) { helpModal.classList.remove('is-open'); } }
	document.querySelectorAll('[data-help-modal-open]').forEach(function (btn) {
		btn.addEventListener('click', openHelp);
	});
	var helpClose = document.getElementById('esk-help-close');
	if (helpClose) { helpClose.addEventListener('click', closeHelp); }
	if (helpModal) {
		helpModal.addEventListener('click', function (e) { if (e.target === helpModal) { closeHelp(); } });
		helpModal.addEventListener('click', function (e) {
			var topic = e.target.closest('[data-help-topic]');
			if (!topic) { return; }
			var s = helpData[topic.getAttribute('data-help-topic')];
			if (!s) { return; }
			document.getElementById('esk-help-title').textContent = s.title;
			document.getElementById('esk-help-subtitle').textContent = s.description || '';
			helpSteps.innerHTML = (s.steps || []).map(function (step, i) {
				return '<li class="esk-help-step"><span class="esk-help-step-num">' + (i + 1) + '</span><span>' + ESC(step) + '</span></li>';
			}).join('');
			helpTopics.querySelectorAll('[data-help-topic]').forEach(function (b) { b.classList.remove('is-active'); });
			topic.classList.add('is-active');
		});
	}

	/* Document preview modal. */
	var docModal = document.getElementById('esk-document-preview-modal');
	var docFrame = document.getElementById('esk-doc-frame');
	var docPrint = document.getElementById('esk-doc-print');
	function openDocPreview(url) {
		if (!docModal || !docFrame || !url) { return; }
		docFrame.src = url;
		if (docPrint) { docPrint.href = url.replace('/preview', '/print'); }
		docModal.classList.add('is-open');
	}
	function closeDocPreview() {
		if (!docModal) { return; }
		docModal.classList.remove('is-open');
		if (docFrame) { docFrame.src = 'about:blank'; }
	}
	document.addEventListener('click', function (e) {
		var btn = e.target.closest('[data-preview-url]');
		if (btn) {
			e.preventDefault();
			openDocPreview(btn.getAttribute('data-preview-url'));
		}
	});
	var docClose = document.getElementById('esk-doc-close');
	if (docClose) { docClose.addEventListener('click', closeDocPreview); }
	if (docModal) {
		docModal.addEventListener('click', function (e) { if (e.target === docModal) { closeDocPreview(); } });
	}

	/* Top loading bar (mirrors the app layout). */
	var loadingBar = document.getElementById('esk-loading-bar');
	if (loadingBar) {
		var barTimer = null;
		document.addEventListener('click', function (e) {
			var link = e.target.closest('a:not([target="_blank"]):not([href^="#"]):not([href^="javascript"]):not([data-no-loading])');
			if (link && link.href && link.href.indexOf(window.location.origin) === 0 && link.href !== window.location.href) {
				loadingBar.style.opacity = '1';
				loadingBar.style.width = '10%';
				barTimer = setInterval(function () {
					var width = parseFloat(loadingBar.style.width) || 0;
					if (width >= 90) { clearInterval(barTimer); return; }
					loadingBar.style.width = (width + 5) + '%';
				}, 100);
			}
		});
		window.addEventListener('load', function () {
			if (barTimer) { clearInterval(barTimer); }
			if (loadingBar) {
				loadingBar.style.width = '100%';
				setTimeout(function () { loadingBar.style.opacity = '0'; loadingBar.style.width = '0'; }, 300);
			}
		});
	}

	/* PWA install button. */
	var pwaBtn = document.getElementById('esk-pwa-install');
	if (pwaBtn && window.matchMedia('(display-mode: standalone)').matches) {
		pwaBtn.parentElement && pwaBtn.parentElement.classList.add('hidden');
	}
	if (pwaBtn) {
		var deferredPrompt = null;
		window.addEventListener('beforeinstallprompt', function (e) {
			e.preventDefault();
			deferredPrompt = e;
			pwaBtn.classList.remove('hidden');
		});
		pwaBtn.addEventListener('click', function () {
			if (!deferredPrompt) { return; }
			deferredPrompt.prompt();
			deferredPrompt.userChoice.then(function () { deferredPrompt = null; pwaBtn.classList.add('hidden'); });
		});
	}

	/* Favorites (pin current page). */
	var currentSlug = (document.getElementById('esk-current-slug') || {}).textContent || '';
	var favKey = 'esk_favs';
	function getFavs() { try { return JSON.parse(localStorage.getItem(favKey) || '[]'); } catch (e) { return []; } }
	function setFavs(f) { try { localStorage.setItem(favKey, JSON.stringify(f)); } catch (e) {} }
	function renderFavs() {
		var list = document.getElementById('esk-fav-list');
		var group = document.getElementById('esk-fav-group');
		if (!list) { return; }
		var favs = getFavs().filter(function (slug) { return paletteData.some(function (p) { return p.url.indexOf('/dashboard/' + slug.replace(/^esk-/, '') + '/') !== -1; }); });
		if (favs.length === 0) { group.hidden = true; list.innerHTML = ''; return; }
		group.hidden = false;
		var html = '';
		favs.forEach(function (slug) {
			var item = paletteData.find(function (p) { return p.url.indexOf('/dashboard/' + slug.replace(/^esk-/, '') + '/') !== -1; });
			if (item) { html += '<li><a href="' + item.url + '" class="esk-fav-link">' + item.label + '</a></li>'; }
		});
		list.innerHTML = html;
	}
	renderFavs();
	var favToggle = document.getElementById('esk-fav-toggle');
	var favStar = document.getElementById('esk-fav-star');
	if (favToggle && currentSlug) {
		var isPinned = getFavs().indexOf(currentSlug) !== -1;
		if (favStar) { favStar.style.color = isPinned ? '#f59e0b' : ''; }
		favToggle.addEventListener('click', function () {
			var favs = getFavs();
			var idx = favs.indexOf(currentSlug);
			if (idx !== -1) { favs.splice(idx, 1); }
			else { favs.push(currentSlug); }
			setFavs(favs);
			if (favStar) { favStar.style.color = favs.indexOf(currentSlug) !== -1 ? '#f59e0b' : ''; }
			renderFavs();
		});
	}
})();
</script>
	<?php
}

/* ── wp-admin integration (legacy path; the primary path is the frontend) ── */

add_filter(
	'admin_body_class',
	static function ( string $classes ): string {
		if ( esk_admin_shell_active() && is_admin() ) {
			$classes .= ' esk-admin-shell';
		}
		return $classes;
	}
);

add_action(
	'admin_head',
	static function (): void {
		if ( esk_admin_shell_active() && is_admin() ) {
			esk_admin_shell_head_scripts();
		}
	}
);

add_action(
	'in_admin_header',
	static function (): void {
		if ( esk_admin_shell_active() && is_admin() ) {
			$current = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : 'esk-dashboard';
			esk_render_admin_shell( $current );
		}
	}
);

add_action(
	'admin_footer',
	static function (): void {
		if ( esk_admin_shell_active() && is_admin() ) {
			esk_admin_shell_footer_scripts();
		}
	}
);