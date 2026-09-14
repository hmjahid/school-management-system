<?php
/**
 * Dashboard home — mirrors the Laravel app's dashboard markup/classes.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

/* ── Stats ─────────────────────────────────────────────────────────── */
$stats = array(
	'students'  => 0, 'teachers' => 0, 'guardians' => 0,
	'revenue'   => 0.0, 'dues' => 0.0, 'pending_admissions' => 0,
	'rate7'     => 0, 'present_today' => 0, 'absent_today' => 0,
	'late_today' => 0, 'leave_today' => 0, 'today_rate' => 0,
);
$t = $wpdb->prefix;

if ( $wpdb->get_var( "SHOW TABLES LIKE '{$t}esk_students'" ) === $t . 'esk_students' ) {
	$stats['students']  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t}esk_students WHERE deleted_at IS NULL AND status = 'active'" );
	$stats['guardians'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t}esk_guardians" );
}
if ( $wpdb->get_var( "SHOW TABLES LIKE '{$t}esk_teachers'" ) === $t . 'esk_teachers' ) {
	$stats['teachers'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t}esk_teachers" );
}
if ( $wpdb->get_var( "SHOW TABLES LIKE '{$t}esk_admissions'" ) === $t . 'esk_admissions' ) {
	$stats['pending_admissions'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t}esk_admissions WHERE status IN ('submitted','under_review') AND deleted_at IS NULL" );
}
if ( $wpdb->get_var( "SHOW TABLES LIKE '{$t}esk_payments'" ) === $t . 'esk_payments' ) {
	$stats['revenue'] = (float) $wpdb->get_var( "SELECT COALESCE(SUM(paid_amount),0) FROM {$t}esk_payments WHERE payment_status = 'completed' AND deleted_at IS NULL" );
	$stats['dues']    = (float) $wpdb->get_var( "SELECT COALESCE(SUM(due_amount),0) FROM {$t}esk_payments WHERE payment_status <> 'completed' AND deleted_at IS NULL" );
}

$trendBars = array( 8, 8, 8, 8, 8, 8, 8 );
$months    = array(); $revenueData = array(); $expenseData = array();
for ( $i = 11; $i >= 0; $i-- ) { $months[] = gmdate( 'M', strtotime( "-{$i} months" ) ); $revenueData[] = 0; $expenseData[] = 0; }

if ( $wpdb->get_var( "SHOW TABLES LIKE '{$t}esk_attendances'" ) === $t . 'esk_attendances' ) {
	$today = current_time( 'Y-m-d' );
	foreach ( $wpdb->get_results( $wpdb->prepare( "SELECT status, COUNT(*) AS c FROM {$t}esk_attendances WHERE date = %s GROUP BY status", $today ) ) as $row ) {
		if ( 'present' === $row->status ) { $stats['present_today'] = (int) $row->c; }
		if ( 'absent' === $row->status ) { $stats['absent_today'] = (int) $row->c; }
		if ( 'late' === $row->status ) { $stats['late_today'] = (int) $row->c; }
		if ( 'leave' === $row->status ) { $stats['leave_today'] = (int) $row->c; }
	}
	$tt = $stats['present_today'] + $stats['absent_today'] + $stats['late_today'] + $stats['leave_today'];
	$stats['today_rate'] = $tt > 0 ? (int) round( ( $stats['present_today'] + $stats['late_today'] ) / $tt * 100 ) : 0;

	$days = array();
	for ( $i = 6; $i >= 0; $i-- ) { $days[ gmdate( 'Y-m-d', strtotime( "-{$i} days" ) ) ] = 0; }
	foreach ( $wpdb->get_results( "SELECT date, status, COUNT(*) AS c FROM {$t}esk_attendances WHERE date >= '" . gmdate( 'Y-m-d', strtotime( '-6 days' ) ) . "' GROUP BY date, status" ) as $row ) {
		if ( isset( $days[ $row->date ] ) && 'present' === $row->status ) { $days[ $row->date ] += (int) $row->c; }
	}
	$trend = array_values( $days );
	$total7 = array_sum( $trend );
	$stats['rate7'] = $total7 > 0 ? (int) round( $total7 / count( $trend ) ) : 0;
	$trendBars = array();
	foreach ( $trend as $v ) { $trendBars[] = max( 8, min( 100, $v * 4 ) ); }
}

if ( $wpdb->get_var( "SHOW TABLES LIKE '{$t}esk_payments'" ) === $t . 'esk_payments' ) {
	$start = gmdate( 'Y-m-01', strtotime( '-11 months' ) );
	foreach ( $wpdb->get_results( $wpdb->prepare( "SELECT DATE_FORMAT(payment_date, '%%Y-%%m') AS ym, SUM(paid_amount) AS total FROM {$t}esk_payments WHERE payment_status = 'completed' AND payment_date >= %s GROUP BY ym", $start ) ) as $row ) {
		$idx = array_search( $row->ym, array_map( fn( $m ) => gmdate( 'Y-m', strtotime( $m ) ), $months ), true );
		if ( false !== $idx ) { $revenueData[ $idx ] = (float) $row->total; }
	}
}
if ( $wpdb->get_var( "SHOW TABLES LIKE '{$t}esk_expenses'" ) === $t . 'esk_expenses' ) {
	$start = gmdate( 'Y-m-01', strtotime( '-11 months' ) );
	foreach ( $wpdb->get_results( $wpdb->prepare( "SELECT DATE_FORMAT(date, '%%Y-%%m') AS ym, SUM(amount) AS total FROM {$t}esk_expenses WHERE date >= %s GROUP BY ym", $start ) ) as $row ) {
		$idx = array_search( $row->ym, array_map( fn( $m ) => gmdate( 'Y-m', strtotime( $m ) ), $months ), true );
		if ( false !== $idx ) { $expenseData[ $idx ] = (float) $row->total; }
	}
}
$maxY = max( 1, max( array_merge( $revenueData, $expenseData ) ) );

$expenses_total = (float) $wpdb->get_var( "SELECT COALESCE(SUM(amount),0) FROM {$t}esk_expenses" );
$workbench = array(
	array( __( 'Fees collected', 'eskoofy' ), (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t}esk_fee_payments WHERE deleted_at IS NULL" ), esk_dashboard_url( 'esk-fee-payments' ) ),
	array( __( 'Expenses', 'eskoofy' ), number_format_i18n( $expenses_total, 2 ), esk_dashboard_url( 'esk-expenses' ) ),
	array( __( 'Net balance', 'eskoofy' ), number_format_i18n( $stats['revenue'] - $expenses_total, 2 ), esk_dashboard_url( 'esk-income-statement' ) ),
	array( __( 'Pending dues', 'eskoofy' ), number_format_i18n( $stats['dues'], 2 ), esk_dashboard_url( 'esk-fee-payments' ) ),
	array( __( 'Upcoming exams', 'eskoofy' ), (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t}esk_exams WHERE status = 'upcoming' AND deleted_at IS NULL" ), esk_dashboard_url( 'esk-exams' ) ),
	array( __( 'Leave requests', 'eskoofy' ), (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t}esk_leave_requests WHERE status = 'pending'" ), esk_dashboard_url( 'esk-leave-requests' ) ),
);

$rev_path = ''; $exp_path = '';
foreach ( $revenueData as $i => $v ) { $x = ( $i + 0.5 ) * 600 / 12; $y = 200 - ( $v / $maxY * 190 ) - 5; $rev_path .= ( 0 === $i ? 'M' : 'L' ) . round( $x, 1 ) . ' ' . round( $y, 1 ); }
foreach ( $expenseData as $i => $v ) { $x = ( $i + 0.5 ) * 600 / 12; $y = 200 - ( $v / $maxY * 190 ) - 5; $exp_path .= ( 0 === $i ? 'M' : 'L' ) . round( $x, 1 ) . ' ' . round( $y, 1 ); }

$cur = get_option( 'esk_currency', '৳' );
$fmt = static function ( float $n ): string { return number_format( $n, 2 ); };
?>
<div class="wrap esk-admin-wrap">

	<div class="mb-6 flex items-center justify-between">
		<div>
			<h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white"><?php esc_html_e( 'Dashboard', 'eskoofy' ); ?></h1>
			<p class="mt-1 text-sm text-slate-500 dark:text-slate-400"><?php echo esc_html( sprintf( __( 'Welcome back, %s!', 'eskoofy' ), wp_get_current_user()->display_name ) ); ?></p>
		</div>
		<div class="flex gap-2">
			<a href="<?php echo esc_url( esk_dashboard_url( 'esk-bulk' ) ); ?>" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-200 dark:hover:bg-slate-600">
				<span class="dashicons dashicons-upload"></span>
				<?php esc_html_e( 'Import', 'eskoofy' ); ?>
			</a>
			<a href="<?php echo esc_url( esk_dashboard_url( 'esk-reports' ) ); ?>" class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-3 py-2 text-sm font-semibold text-white hover:bg-brand-700">
				<span class="dashicons dashicons-chart-bar"></span>
				<?php esc_html_e( 'Reports', 'eskoofy' ); ?>
			</a>
		</div>
	</div>

	<div class="mb-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
		<a href="<?php echo esc_url( esk_dashboard_url( 'esk-students' ) ); ?>" class="admin-stat-card block">
			<div class="flex items-center justify-between">
				<p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400"><?php esc_html_e( 'Students', 'eskoofy' ); ?></p>
				<span class="rounded-full bg-brand-50 p-1.5 text-brand-600 dark:bg-brand-900/30 dark:text-brand-400"><span class="dashicons dashicons-groups"></span></span>
			</div>
			<p class="mt-2 text-3xl font-bold tracking-tight text-slate-900 dark:text-white"><?php echo esc_html( number_format_i18n( $stats['students'] ) ); ?></p>
			<p class="mt-1 text-xs text-slate-400 dark:text-slate-500"><?php esc_html_e( 'Active enrollment', 'eskoofy' ); ?></p>
			<?php if ( $stats['pending_admissions'] > 0 ) : ?>
				<span class="mt-3 inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-700 hover:bg-amber-100 dark:bg-amber-900/30 dark:text-amber-400"><?php echo esc_html( sprintf( __( '%d pending admissions', 'eskoofy' ), $stats['pending_admissions'] ) ); ?></span>
			<?php endif; ?>
		</a>

		<a href="<?php echo esc_url( esk_dashboard_url( 'esk-teachers' ) ); ?>" class="admin-stat-card block">
			<div class="flex items-center justify-between">
				<p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400"><?php esc_html_e( 'Teachers', 'eskoofy' ); ?></p>
				<span class="rounded-full bg-emerald-50 p-1.5 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400"><span class="dashicons dashicons-welcome-learn-more"></span></span>
			</div>
			<p class="mt-2 text-3xl font-bold tracking-tight text-slate-900 dark:text-white"><?php echo esc_html( number_format_i18n( $stats['teachers'] ) ); ?></p>
			<p class="mt-1 text-xs text-slate-400 dark:text-slate-500"><?php esc_html_e( 'Full-time staff', 'eskoofy' ); ?></p>
		</a>

		<a href="<?php echo esc_url( esk_dashboard_url( 'esk-guardians' ) ); ?>" class="admin-stat-card block">
			<div class="flex items-center justify-between">
				<p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400"><?php esc_html_e( 'Parents', 'eskoofy' ); ?></p>
				<span class="rounded-full bg-amber-50 p-1.5 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400"><span class="dashicons dashicons-universal-access"></span></span>
			</div>
			<p class="mt-2 text-3xl font-bold tracking-tight text-slate-900 dark:text-white"><?php echo esc_html( number_format_i18n( $stats['guardians'] ) ); ?></p>
			<p class="mt-1 text-xs text-slate-400 dark:text-slate-500"><?php esc_html_e( 'Registered guardians', 'eskoofy' ); ?></p>
		</a>

		<a href="<?php echo esc_url( esk_dashboard_url( 'esk-attendance' ) ); ?>" class="admin-stat-card block">
			<div class="flex items-center justify-between">
				<p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400"><?php esc_html_e( 'Attendance', 'eskoofy' ); ?></p>
				<span class="rounded-full bg-sky-50 p-1.5 text-sky-600 dark:bg-sky-900/30 dark:text-sky-400"><span class="dashicons dashicons-calendar-alt"></span></span>
			</div>
			<p class="mt-2 text-3xl font-bold tracking-tight text-slate-900 dark:text-white"><?php echo esc_html( (string) $stats['rate7'] ); ?>%</p>
			<p class="mt-1 text-xs text-slate-400 dark:text-slate-500"><?php esc_html_e( 'Last 7 days', 'eskoofy' ); ?></p>
		</a>

		<a href="<?php echo esc_url( esk_dashboard_url( 'esk-fee-payments' ) ); ?>" class="admin-stat-card block">
			<div class="flex items-center justify-between">
				<p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400"><?php esc_html_e( 'Revenue', 'eskoofy' ); ?></p>
				<span class="rounded-full bg-violet-50 p-1.5 text-violet-600 dark:bg-violet-900/30 dark:text-violet-400"><span class="dashicons dashicons-money-alt"></span></span>
			</div>
			<p class="mt-2 text-3xl font-bold tracking-tight text-slate-900 dark:text-white"><?php echo esc_html( $cur . ' ' . $fmt( $stats['revenue'] ) ); ?></p>
			<p class="mt-1 text-xs text-slate-400 dark:text-slate-500"><?php esc_html_e( 'Total collected', 'eskoofy' ); ?></p>
			<?php if ( $stats['dues'] > 0 ) : ?>
				<span class="mt-3 inline-flex items-center gap-1 rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-200 dark:bg-red-900/30 dark:text-red-400 dark:ring-red-800"><?php echo esc_html( sprintf( __( '%s pending dues', 'eskoofy' ), $cur . ' ' . $fmt( $stats['dues'] ) ) ); ?></span>
			<?php endif; ?>
		</a>
	</div>

	<div class="mb-8 grid gap-6 lg:grid-cols-2">
		<div class="admin-card">
			<div class="admin-card-header">
				<h2 class="text-base font-semibold text-slate-900 dark:text-white"><?php esc_html_e( 'Revenue vs Expenses', 'eskoofy' ); ?></h2>
				<span class="text-xs text-slate-400 dark:text-slate-500"><?php esc_html_e( 'Last 12 months', 'eskoofy' ); ?></span>
			</div>
			<div class="admin-card-body">
				<div class="mb-4 flex items-center gap-4 text-xs text-slate-500 dark:text-slate-400">
					<span class="flex items-center gap-1"><span class="inline-block h-2 w-4 rounded bg-emerald-500"></span> <?php esc_html_e( 'Revenue', 'eskoofy' ); ?></span>
					<span class="flex items-center gap-1"><span class="inline-block h-2 w-4 rounded bg-red-400"></span> <?php esc_html_e( 'Expenses', 'eskoofy' ); ?></span>
				</div>
				<div class="relative h-48">
					<svg viewBox="0 0 600 200" class="h-full w-full" preserveAspectRatio="none">
						<?php foreach ( array( 0, 50, 100, 150, 200 ) as $y ) : ?>
							<line x1="0" y1="<?php echo esc_attr( (string) $y ); ?>" x2="600" y2="<?php echo esc_attr( (string) $y ); ?>" stroke="oklch(0.9 0 0 / 0.3)" stroke-width="0.5"/>
						<?php endforeach; ?>
						<path d="<?php echo esc_attr( $rev_path ); ?> L600 200 L0 200 Z" fill="oklch(0.65 0.18 160 / 0.15)" stroke="oklch(0.55 0.18 160)" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"/>
						<path d="<?php echo esc_attr( $exp_path ); ?> L600 200 L0 200 Z" fill="oklch(0.65 0.18 25 / 0.15)" stroke="oklch(0.55 0.18 25)" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"/>
					</svg>
					<div class="mt-1 flex justify-between text-[0.6rem] text-slate-400 dark:text-slate-500">
						<?php foreach ( $months as $m ) : ?><span><?php echo esc_html( $m ); ?></span><?php endforeach; ?>
					</div>
				</div>
			</div>
		</div>

		<div class="admin-card">
			<div class="admin-card-header">
				<h2 class="text-base font-semibold text-slate-900 dark:text-white"><?php esc_html_e( "Today's Attendance", 'eskoofy' ); ?></h2>
				<span class="text-xs text-slate-400 dark:text-slate-500"><?php esc_html_e( 'Real-time', 'eskoofy' ); ?></span>
			</div>
			<div class="admin-card-body">
				<div class="grid grid-cols-2 gap-3">
					<div class="rounded-lg border border-emerald-100 bg-emerald-50 p-4 dark:border-emerald-900/30 dark:bg-emerald-900/20">
						<p class="text-xs uppercase tracking-wide text-emerald-700 dark:text-emerald-400"><?php esc_html_e( 'Present', 'eskoofy' ); ?></p>
						<p class="mt-1 text-3xl font-bold text-emerald-700 dark:text-emerald-300"><?php echo esc_html( (string) $stats['present_today'] ); ?></p>
					</div>
					<div class="rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-900/30 dark:bg-red-900/20">
						<p class="text-xs uppercase tracking-wide text-red-700 dark:text-red-400"><?php esc_html_e( 'Absent', 'eskoofy' ); ?></p>
						<p class="mt-1 text-3xl font-bold text-red-700 dark:text-red-300"><?php echo esc_html( (string) $stats['absent_today'] ); ?></p>
					</div>
					<div class="rounded-lg border border-amber-100 bg-amber-50 p-4 dark:border-amber-900/30 dark:bg-amber-900/20">
						<p class="text-xs uppercase tracking-wide text-amber-700 dark:text-amber-400"><?php esc_html_e( 'Late', 'eskoofy' ); ?></p>
						<p class="mt-1 text-3xl font-bold text-amber-700 dark:text-amber-300"><?php echo esc_html( (string) $stats['late_today'] ); ?></p>
					</div>
					<div class="rounded-lg border border-sky-100 bg-sky-50 p-4 dark:border-sky-900/30 dark:bg-sky-900/20">
						<p class="text-xs uppercase tracking-wide text-sky-700 dark:text-sky-400"><?php esc_html_e( 'On leave', 'eskoofy' ); ?></p>
						<p class="mt-1 text-3xl font-bold text-sky-700 dark:text-sky-300"><?php echo esc_html( (string) $stats['leave_today'] ); ?></p>
					</div>
				</div>
				<div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3 dark:border-slate-700">
					<span class="text-sm text-slate-600 dark:text-slate-400"><?php esc_html_e( "Today's rate", 'eskoofy' ); ?></span>
					<span class="text-2xl font-bold text-brand-600 dark:text-brand-400"><?php echo esc_html( (string) $stats['today_rate'] ); ?>%</span>
				</div>
			</div>
		</div>
	</div>

	<div class="grid gap-6 lg:grid-cols-3">
		<div class="admin-card lg:col-span-2">
			<div class="admin-card-header">
				<h2 class="text-base font-semibold text-slate-900 dark:text-white"><?php esc_html_e( 'Attendance Trend', 'eskoofy' ); ?></h2>
				<span class="text-xs text-slate-400 dark:text-slate-500"><?php esc_html_e( 'Last 7 days', 'eskoofy' ); ?></span>
			</div>
			<div class="admin-card-body">
				<div class="flex h-44 items-end justify-between gap-2 rounded-lg bg-slate-50 p-4 dark:bg-slate-800/50">
					<?php foreach ( $trendBars as $h ) : ?>
						<div class="group relative w-full">
							<div class="w-full rounded-t-md bg-gradient-to-t from-brand-600 to-brand-400 transition-all duration-300 hover:from-brand-500 hover:to-brand-300" style="height: <?php echo esc_attr( (string) $h ); ?>%"></div>
							<div class="absolute -top-8 left-1/2 -translate-x-1/2 rounded bg-slate-800 px-2 py-1 text-xs text-white opacity-0 transition-opacity group-hover:opacity-100"><?php echo esc_html( (string) $h ); ?>%</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>

		<div class="admin-card">
			<div class="admin-card-header"><h2 class="text-base font-semibold text-slate-900 dark:text-white"><?php esc_html_e( 'Quick Actions', 'eskoofy' ); ?></h2></div>
			<div class="admin-card-body space-y-2">
				<?php
				$q = array(
					array( __( 'Add Student', 'eskoofy' ), esk_dashboard_url( 'esk-student-add' ), 'bg-brand-50 text-brand-600 dark:bg-brand-900/30 dark:text-brand-400', 'dashicons-groups' ),
					array( __( 'Add Teacher', 'eskoofy' ), esk_dashboard_url( 'esk-teacher-add' ), 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400', 'dashicons-welcome-learn-more' ),
					array( __( 'Mark Attendance', 'eskoofy' ), esk_dashboard_url( 'esk-attendance-mark' ), 'bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400', 'dashicons-calendar-alt' ),
					array( __( 'Collect Fees', 'eskoofy' ), esk_dashboard_url( 'esk-fee-payments' ), 'bg-violet-50 text-violet-600 dark:bg-violet-900/30 dark:text-violet-400', 'dashicons-money-alt' ),
					array( __( 'Manage Exams', 'eskoofy' ), esk_dashboard_url( 'esk-exams' ), 'bg-sky-50 text-sky-600 dark:bg-sky-900/30 dark:text-sky-400', 'dashicons-welcome-write-blog' ),
				);
				foreach ( $q as $qa ) : ?>
					<a href="<?php echo esc_url( $qa[1] ); ?>" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-700/50">
						<span class="flex h-8 w-8 items-center justify-center rounded-lg <?php echo esc_attr( $qa[2] ); ?>"><span class="dashicons <?php echo esc_attr( $qa[3] ); ?>"></span></span>
						<?php echo esc_html( $qa[0] ); ?>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	</div>

	<div class="mt-6 admin-card">
		<div class="admin-card-header"><h2 class="text-base font-semibold text-slate-900 dark:text-white"><?php esc_html_e( 'Workbench', 'eskoofy' ); ?></h2></div>
		<div class="admin-card-body grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
			<?php foreach ( $workbench as $wb ) : ?>
				<a href="<?php echo esc_url( $wb[2] ); ?>" class="group rounded-xl border border-slate-200 bg-slate-50 p-4 transition hover:border-brand-300 hover:bg-brand-50/50 dark:border-slate-700 dark:bg-slate-700/50 dark:hover:border-brand-700">
					<p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400"><?php echo esc_html( $wb[0] ); ?></p>
					<p class="mt-2 text-2xl font-bold text-slate-900 dark:text-white"><?php echo esc_html( (string) $wb[1] ); ?></p>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</div>