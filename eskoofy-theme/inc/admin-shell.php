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
		'Main'        => array( 'esk-dashboard' ),
		'People'      => array( 'esk-students', 'esk-student-add', 'esk-teachers', 'esk-teacher-add', 'esk-guardians', 'esk-users' ),
		'Academics'   => array( 'esk-classes', 'esk-sections', 'esk-subjects', 'esk-batches', 'esk-academic-sessions', 'esk-exams', 'esk-results', 'esk-routines', 'esk-assignments', 'esk-progress-reports', 'esk-seat-plans', 'esk-admit-cards', 'esk-certificates', 'esk-id-cards' ),
		'Admissions'  => array( 'esk-admissions' ),
		'Attendance'  => array( 'esk-attendance', 'esk-attendance-mark', 'esk-staff-attendance' ),
		'Finance'     => array( 'esk-fees', 'esk-fee-payments', 'esk-expenses', 'esk-refunds', 'esk-income-statement', 'esk-balance-sheet', 'esk-cash-flow', 'esk-bank-reconciliation', 'esk-payroll', 'esk-payslips', 'esk-salary-structures' ),
		'HR'          => array( 'esk-leave-types', 'esk-leave-requests' ),
		'Communications' => array( 'esk-notices', 'esk-announcements', 'esk-sms', 'esk-messages' ),
		'Facilities'  => array( 'esk-transport', 'esk-hostels', 'esk-library', 'esk-library-reports' ),
		'Content'     => array( 'esk-cms', 'esk-careers', 'esk-committee', 'esk-testimonials', 'esk-events', 'esk-news', 'esk-gallery', 'esk-documents', 'esk-media', 'esk-contact-submissions' ),
		'System'      => array( 'esk-search', 'esk-activity', 'esk-visitor-logs', 'esk-notifications', 'esk-reports', 'esk-reports-builder', 'esk-analytics', 'esk-bulk' ),
		'Settings'    => array( 'esk-settings', 'esk-onboarding' ),
	);
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
		'esk-results'              => 'Results',
		'esk-fees'                 => 'Fees',
		'esk-fee-payments'         => 'Fee Payments',
		'esk-expenses'             => 'Expenses',
		'esk-payroll'              => 'Payroll',
		'esk-guardians'            => 'Guardians',
		'esk-admissions'           => 'Admissions',
		'esk-transport'            => 'Transport',
		'esk-hostels'              => 'Hostels',
		'esk-library'              => 'Library',
		'esk-sms'                  => 'SMS',
		'esk-notices'              => 'Notices',
		'esk-announcements'        => 'Announcements',
		'esk-certificates'         => 'Certificates',
		'esk-admit-cards'          => 'Admit Cards',
		'esk-id-cards'             => 'ID Cards',
		'esk-testimonials'         => 'Testimonials',
		'esk-committee'            => 'Committee',
		'esk-events'               => 'Events',
		'esk-news'                 => 'News',
		'esk-gallery'              => 'Gallery',
		'esk-careers'              => 'Careers',
		'esk-reports'              => 'Reports',
		'esk-users'                => 'Users',
		'esk-settings'             => 'Settings',
		'esk-onboarding'           => 'Onboarding',
		'esk-documents'            => 'Documents',
		'esk-media'                => 'Media Library',
		'esk-contact-submissions'  => 'Contact Submissions',
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
		'esk-routines'             => 'Class Routines',
		'esk-progress-reports'     => 'Progress Reports',
		'esk-seat-plans'           => 'Seat Plans',
		'esk-library-reports'      => 'Library Reports',
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
	);
	return isset( $icons[ $slug ] ) ? $icons[ $slug ] : 'dashicons-admin-generic';
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
<div class="admin-shell flex h-screen overflow-hidden">

	<aside id="sidebar" class="no-print flex w-64 flex-shrink-0 flex-col border-r border-slate-200/80 bg-white dark:border-slate-700/80 dark:bg-slate-800">

		<div class="flex h-[4.25rem] flex-shrink-0 items-center gap-2.5 border-b border-slate-100 px-4 dark:border-slate-700">
			<span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-600 text-sm font-bold text-white"><?php echo esc_html( $initials ); ?></span>
			<div class="min-w-0">
				<p class="truncate text-sm font-bold text-slate-900 dark:text-white"><?php echo esc_html( $school ); ?></p>
				<p class="text-[0.65rem] text-slate-400"><?php esc_html_e( 'Management System', 'eskoofy' ); ?></p>
			</div>
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

			<?php foreach ( esk_admin_shell_groups() as $group => $slugs ) : ?>
				<?php $links = array(); foreach ( $slugs as $slug ) { if ( isset( $titles[ $slug ] ) ) { $links[ $slug ] = $titles[ $slug ]; } } ?>
				<?php if ( empty( $links ) ) { continue; } ?>
				<p class="mb-2 px-3 text-[0.65rem] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 esk-group-label"><?php echo esc_html( $group ); ?></p>
				<ul class="mb-4 space-y-0.5 esk-group-menu">
					<?php foreach ( $links as $slug => $title ) : ?>
						<li>
							<a href="<?php echo esc_url( esk_dashboard_url( $slug ) ); ?>" data-esk-nav="<?php echo esc_attr( $slug ); ?>" class="admin-nav-link <?php echo $current === $slug ? 'admin-nav-link--active' : ''; ?>">
								<span class="flex h-5 w-5 shrink-0 items-center justify-center opacity-80"><span class="dashicons <?php echo esc_attr( esk_admin_shell_icon( $slug ) ); ?>" style="font-size:1.1rem;width:1.1rem;height:1.1rem;"></span></span>
								<span class="flex-1"><?php echo esc_html( $title ); ?></span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endforeach; ?>
		</nav>

		<div class="flex flex-col gap-1 border-t border-slate-100 p-3 dark:border-slate-700">
			<button type="button" id="esk-dark-toggle-foot" class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-700">
				<span class="dashicons dashicons-lightbulb"></span>
				<?php esc_html_e( 'Dark mode', 'eskoofy' ); ?>
			</button>
			<a href="<?php echo esc_url( wp_logout_url( home_url( '/login/' ) ) ); ?>" class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20">
				<span class="dashicons dashicons-exit"></span>
				<?php esc_html_e( 'Log out', 'eskoofy' ); ?>
			</a>
		</div>
	</aside>

	<div class="flex min-w-0 flex-1 flex-col">
		<header class="no-print flex h-16 flex-shrink-0 items-center gap-4 border-b border-slate-200/80 bg-white/95 px-4 backdrop-blur-md dark:border-slate-700/80 dark:bg-slate-800/95">
			<button type="button" id="esk-shell-burger" class="-ml-2 rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700 lg:hidden dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-slate-200" aria-label="<?php esc_attr_e( 'Toggle menu', 'eskoofy' ); ?>">
				<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
			</button>

			<div class="hidden items-center gap-2 text-xs text-slate-500 md:flex">
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

				<a href="<?php echo esc_url( esk_dashboard_url( 'esk-notifications' ) ); ?>" class="relative rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-slate-200" aria-label="<?php esc_attr_e( 'Notifications', 'eskoofy' ); ?>">
					<span class="dashicons dashicons-bell"></span>
					<?php if ( $notif_count > 0 ) : ?><span class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-amber-500 px-1 text-[0.6rem] font-bold text-white"><?php echo esc_html( (string) $notif_count ); ?></span><?php endif; ?>
				</a>

				<button type="button" id="esk-dark-toggle" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-slate-200" aria-label="<?php esc_attr_e( 'Toggle dark mode', 'eskoofy' ); ?>">
					<span class="dashicons dashicons-lightbulb"></span>
				</button>

				<div class="relative" id="esk-user-menu">
					<button type="button" class="flex items-center gap-2 rounded-lg p-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-700" aria-expanded="false">
						<span class="flex h-7 w-7 items-center justify-center rounded-full bg-brand-600 text-xs font-bold text-white ring-2 ring-slate-200 dark:ring-slate-600"><?php echo esc_html( $initials ); ?></span>
						<span class="hidden md:inline"><?php echo esc_html( $user->display_name ); ?></span>
						<svg class="hidden h-4 w-4 text-slate-400 md:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
					</button>
					<div class="absolute right-0 top-full z-50 mt-2 hidden w-56 rounded-xl border border-slate-200 bg-white p-1.5 shadow-lg dark:border-slate-700 dark:bg-slate-800 esk-user-dropdown">
						<a href="<?php echo esc_url( esk_dashboard_url( 'esk-dashboard' ) ); ?>" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-700"><span class="dashicons dashicons-dashboard"></span><?php esc_html_e( 'Dashboard', 'eskoofy' ); ?></a>
						<a href="<?php echo esc_url( admin_url( 'profile.php' ) ); ?>" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-700"><span class="dashicons dashicons-admin-users"></span><?php esc_html_e( 'My Profile', 'eskoofy' ); ?></a>
						<a href="<?php echo esc_url( esk_dashboard_url( 'esk-settings' ) ); ?>" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-700"><span class="dashicons dashicons-admin-generic"></span><?php esc_html_e( 'School Settings', 'eskoofy' ); ?></a>
						<hr class="my-1 border-slate-100 dark:border-slate-700">
						<a href="<?php echo esc_url( wp_logout_url( home_url( '/login/' ) ) ); ?>" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20"><span class="dashicons dashicons-exit"></span><?php esc_html_e( 'Log out', 'eskoofy' ); ?></a>
					</div>
				</div>
			</div>
		</header>

		<main id="main-content" class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
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
			<button type="button" id="esk-palette-close" aria-label="<?php esc_attr_e( 'Close', 'eskoofy' ); ?>">&times;</button>
		</div>
		<ul id="esk-palette-results" class="esk-palette-results"></ul>
	</div>
</div>

<script type="application/json" id="esk-palette-data"><?php echo wp_json_encode( esk_render_palette_data() ); ?></script>
<span id="esk-current-slug" hidden><?php echo esc_html( $GLOBALS['esk_front_dash_slug'] ?? 'esk-dashboard' ); ?></span>
	<?php
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
		if (!root) { return; }
		var t = document.createElement('div');
		t.className = 'esk-toast esk-toast--' + (type || 'info');
		t.textContent = message;
		root.appendChild(t);
		setTimeout(function () { t.classList.add('esk-toast--leaving'); }, 3200);
		setTimeout(function () { t.remove(); }, 3600);
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
	if (burger) {
		burger.addEventListener('click', function () {
			document.body.classList.toggle('esk-shell-nav-open');
		});
	}

	/* Live clock. */
	var clock = document.getElementById('esk-shell-clock');
	if (clock) {
		function tick() {
			var now = new Date();
			clock.textContent = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
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

	/* Sidebar filter. */
	var filter = document.getElementById('esk-shell-filter');
	if (filter) {
		filter.addEventListener('input', function () {
			var q = filter.value.trim().toLowerCase();
			document.querySelectorAll('.esk-group-menu').forEach(function (ul) {
				var any = false;
				ul.querySelectorAll('li').forEach(function (li) {
					var match = !q || li.textContent.toLowerCase().indexOf(q) !== -1;
					li.style.display = match ? '' : 'none';
					if (match) { any = true; }
				});
				var label = ul.previousElementSibling;
				if (label && label.classList.contains('esk-group-label')) {
					label.style.display = any ? '' : 'none';
				}
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
			msgEl.textContent = m && m[1] ? m[1] : 'Are you sure?';
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
			msgEl.textContent = m && m[1] ? m[1] : 'Are you sure?';
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
		if (e.key === 'Escape') { closePalette(); }
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