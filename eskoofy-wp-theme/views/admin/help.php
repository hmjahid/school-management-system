<?php
/**
 * Admin Help & Documentation — mirrors the app's help page.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;

$sections = array(
	'getting_started' => array(
		'title'   => __( 'Getting Started', 'eskoofy' ),
		'desc'    => __( 'Learn the basics of navigating the school dashboard.', 'eskoofy' ),
		'steps'   => array(
			__( 'Open the Dashboard from the sidebar to see an overview of students, fees, attendance and recent activity.', 'eskoofy' ),
			__( 'Add your school name, tagline, logo and contact details from Settings.', 'eskoofy' ),
			__( 'Create classes, sections, subjects and batches before enrolling students.', 'eskoofy' ),
			__( 'Add students and assign them to a class and section.', 'eskoofy' ),
			__( 'Set up fees, income and expense categories to start recording finances.', 'eskoofy' ),
		),
		'color'   => 'brand',
		'icon'    => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>',
	),
	'managing_students' => array(
		'title'   => __( 'Managing Students', 'eskoofy' ),
		'desc'    => __( 'Enroll, search and keep student records up to date.', 'eskoofy' ),
		'steps'   => array(
			__( 'Go to Students from the sidebar and click Add Student to create a new record.', 'eskoofy' ),
			__( 'Use the search box to quickly find a student by name, ID or guardian.', 'eskoofy' ),
			__( 'Open a student to view their profile, guardians, payments and results.', 'eskoofy' ),
			__( 'Add guardian contact details so guardians appear in the Guardians page.', 'eskoofy' ),
		),
		'color'   => 'emerald',
		'icon'    => '<path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3z"/>',
	),
	'managing_teachers' => array(
		'title'   => __( 'Managing Teachers', 'eskoofy' ),
		'desc'    => __( 'Add staff, assign subjects and track payroll.', 'eskoofy' ),
		'steps'   => array(
			__( 'Open Teachers and click Add Teacher to create a staff record.', 'eskoofy' ),
			__( 'Assign teachers to subjects and classes from their profile.', 'eskoofy' ),
			__( 'Record salaries and see totals under Payroll.', 'eskoofy' ),
			__( 'Track staff attendance from the Staff Attendance page.', 'eskoofy' ),
		),
		'color'   => 'brand',
		'icon'    => '<path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z"/>',
	),
	'attendance' => array(
		'title'   => __( 'Attendance', 'eskoofy' ),
		'desc'    => __( 'Mark daily attendance quickly for any class.', 'eskoofy' ),
		'steps'   => array(
			__( 'Pick a class, section and date from the Attendance page.', 'eskoofy' ),
			__( 'Mark each student present or absent, then save.', 'eskoofy' ),
			__( 'Review monthly attendance records from the same page.', 'eskoofy' ),
		),
		'color'   => 'amber',
		'icon'    => '<path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>',
	),
	'exams_results' => array(
		'title'   => __( 'Exams & Results', 'eskoofy' ),
		'desc'    => __( 'Create exams and publish results to students.', 'eskoofy' ),
		'steps'   => array(
			__( 'Create an exam with grading options under Exams.', 'eskoofy' ),
			__( 'Enter marks per student from the gradebook or the exam page.', 'eskoofy' ),
			__( 'Results are computed automatically with ranks and summaries.', 'eskoofy' ),
		),
		'color'   => 'violet',
		'icon'    => '<path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>',
	),
	'fees_payments' => array(
		'title'   => __( 'Fees & Payments', 'eskoofy' ),
		'desc'    => __( 'Collect fees, record expenses and read reports.', 'eskoofy' ),
		'steps'   => array(
			__( 'Define fee structures against fee types in the Fees page.', 'eskoofy' ),
			__( 'Record payments as they arrive under Fee Payments.', 'eskoofy' ),
			__( 'Log day-to-day expenses and allocate them to categories.', 'eskoofy' ),
			__( 'Read Income Statement and Cash Flow reports under Reports.', 'eskoofy' ),
		),
		'color'   => 'teal',
		'icon'    => '<path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>',
	),
	'cms_management' => array(
		'title'   => __( 'CMS & Public Website', 'eskoofy' ),
		'desc'    => __( 'Publish notices, events and gallery photos to the public site.', 'eskoofy' ),
		'steps'   => array(
			__( 'Create pages and posts under CMS to shape the public website.', 'eskoofy' ),
			__( 'Publish notices and announcements that appear on the homepage.', 'eskoofy' ),
			__( 'Add events to the calendar and photos to the gallery.', 'eskoofy' ),
		),
		'color'   => 'rose',
		'icon'    => '<path fill-rule="evenodd" d="M4.083 9h1.946c.089-1.546.383-2.97.837-4.118A6.004 6.004 0 004.083 9zM10 2a8 8 0 100 16 8 8 0 000-16zm0 2c-.076 0-.232.032-.465.262-.238.234-.497.623-.737 1.182-.389.907-.673 2.142-.766 3.556h3.936c-.093-1.414-.377-2.649-.766-3.556-.24-.56-.5-.948-.737-1.182C10.232 4.032 10.076 4 10 4zm3.971 5c-.089-1.546-.383-2.97-.837-4.118A6.004 6.004 0 0115.917 9h-1.946zm-2.003 2H8.032c.093 1.414.377 2.649.766 3.556.24.56.5.948.737 1.182.233.23.389.262.465.262.076 0 .232-.032.465-.262.238-.234.498-.623.737-1.182.389-.907.673-2.142.766-3.556zm1.166 4.118c.454-1.147.748-2.572.837-4.118h1.946a6.004 6.004 0 01-2.783 4.118zm-6.268 0C6.412 13.97 6.118 12.546 6.03 11H4.083a6.004 6.004 0 002.783 4.118z" clip-rule="evenodd"/>',
	),
	'public_website' => array(
		'title'   => __( 'Public Website', 'eskoofy' ),
		'desc'    => __( 'Manage homepage sections like sliders, testimonials and committees.', 'eskoofy' ),
		'steps'   => array(
			__( 'Homepage sections (slider, teachers, results, contact) are powered by the pages below.', 'eskoofy' ),
			__( 'Update slider slides from the Gallery or the theme customizer.', 'eskoofy' ),
			__( 'Testimonials and committee members appear directly on the homepage.', 'eskoofy' ),
		),
		'color'   => 'cyan',
		'icon'    => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>',
	),
);
?>
<div class="wrap esk-admin-wrap">

	<div class="mb-6 flex items-center justify-between">
		<div>
			<h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white"><?php esc_html_e( 'Help & Documentation', 'eskoofy' ); ?></h1>
			<p class="mt-1 text-sm text-slate-500 dark:text-slate-400"><?php esc_html_e( 'Step-by-step guides for every part of the school dashboard.', 'eskoofy' ); ?></p>
		</div>
	</div>

	<div class="mb-8" data-help-search-wrap>
		<div class="relative max-w-xl">
			<svg class="absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
			<input type="search" data-help-search placeholder="<?php esc_attr_e( 'Search the guides…', 'eskoofy' ); ?>"
				class="w-full rounded-xl border border-slate-300 bg-white py-3 pl-12 pr-4 text-sm text-slate-900 placeholder:text-slate-400 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500">
		</div>
	</div>

	<div class="mb-8 flex flex-wrap gap-2" data-help-nav>
		<?php foreach ( $sections as $key => $section ) : ?>
			<a href="#help-<?php echo esc_attr( $key ); ?>" data-help-nav-item data-help-nav-key="<?php echo esc_attr( $key ); ?>"
				class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50 hover:text-brand-600 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-400 dark:hover:text-brand-400">
				<?php echo esc_html( $section['title'] ); ?>
			</a>
		<?php endforeach; ?>
	</div>

	<div class="hidden rounded-xl border border-amber-300 bg-amber-50 p-6 text-center text-sm text-amber-700 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-400" data-help-no-results>
		<?php esc_html_e( 'No guides matched your search. Try a different keyword.', 'eskoofy' ); ?>
	</div>

	<div class="space-y-4" data-help-accordion>
		<?php foreach ( $sections as $key => $section ) : ?>
			<?php
			$c = array(
				'brand'   => array( 'bg' => 'bg-brand-100 dark:bg-brand-900/40', 'text' => 'text-brand-600 dark:text-brand-400' ),
				'emerald' => array( 'bg' => 'bg-emerald-100 dark:bg-emerald-900/40', 'text' => 'text-emerald-600 dark:text-emerald-400' ),
				'amber'   => array( 'bg' => 'bg-amber-100 dark:bg-amber-900/40', 'text' => 'text-amber-600 dark:text-amber-400' ),
				'violet'  => array( 'bg' => 'bg-violet-100 dark:bg-violet-900/40', 'text' => 'text-violet-600 dark:text-violet-400' ),
				'teal'    => array( 'bg' => 'bg-teal-100 dark:bg-teal-900/40', 'text' => 'text-teal-600 dark:text-teal-400' ),
				'rose'    => array( 'bg' => 'bg-rose-100 dark:bg-rose-900/40', 'text' => 'text-rose-600 dark:text-rose-400' ),
				'cyan'    => array( 'bg' => 'bg-cyan-100 dark:bg-cyan-900/40', 'text' => 'text-cyan-600 dark:text-cyan-400' ),
			);
			$colors = $c[ $section['color'] ] ?? array( 'bg' => 'bg-slate-100 dark:bg-slate-700', 'text' => 'text-slate-600 dark:text-slate-400' );
			?>
			<div class="overflow-hidden rounded-xl border border-slate-300 bg-white shadow-sm transition dark:border-slate-700 dark:bg-slate-800" data-help-section data-help-section-key="<?php echo esc_attr( $key ); ?>" id="help-<?php echo esc_attr( $key ); ?>">
				<button type="button" class="flex w-full items-center gap-4 px-6 py-5 text-left transition hover:bg-slate-50 dark:hover:bg-slate-700/50" data-help-toggle>
					<span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg <?php echo esc_attr( $colors['bg'] . ' ' . $colors['text'] ); ?>">
						<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><?php echo $section['icon']; // phpcs:ignore ?></svg>
					</span>
					<span class="min-w-0 flex-1">
						<span class="block text-base font-semibold text-slate-900 dark:text-slate-100"><?php echo esc_html( $section['title'] ); ?></span>
						<span class="mt-0.5 block truncate text-sm text-slate-500 dark:text-slate-400"><?php echo esc_html( $section['desc'] ); ?></span>
					</span>
					<svg class="h-5 w-5 shrink-0 text-slate-400 transition-transform duration-200" data-help-chevron fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
				</button>
				<div class="hidden border-t border-slate-200 px-6 py-5 dark:border-slate-700" data-help-content>
					<ol class="space-y-3">
						<?php foreach ( $section['steps'] as $i => $step ) : ?>
							<li class="flex items-start gap-3" data-help-step>
								<span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-100 text-xs font-bold text-brand-700 dark:bg-brand-900/40 dark:text-brand-400"><?php echo esc_html( $i + 1 ); ?></span>
								<span class="pt-0.5 text-sm text-slate-700 dark:text-slate-300"><?php echo esc_html( $step ); ?></span>
							</li>
						<?php endforeach; ?>
					</ol>
					<div class="mt-6 flex items-center gap-3 border-t border-slate-200 pt-4 dark:border-slate-700">
						<span class="text-xs text-slate-500 dark:text-slate-400"><?php esc_html_e( 'Was this helpful?', 'eskoofy' ); ?></span>
						<button type="button" data-help-feedback="yes" class="rounded-lg border border-slate-300 bg-white px-3 py-1 text-xs font-medium text-slate-600 transition hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-600 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-400 dark:hover:bg-emerald-900/20 dark:hover:text-emerald-400"><?php esc_html_e( 'Yes', 'eskoofy' ); ?></button>
						<button type="button" data-help-feedback="no" class="rounded-lg border border-slate-300 bg-white px-3 py-1 text-xs font-medium text-slate-600 transition hover:border-red-300 hover:bg-red-50 hover:text-red-600 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-400 dark:hover:bg-red-900/20 dark:hover:text-red-400"><?php esc_html_e( 'No', 'eskoofy' ); ?></button>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</div>