<?php
/**
 * Template Name: Admission Page
 *
 * Hero, opening status, quick actions, admission process timeline, fee
 * structure table (from esk_fees), FAQ accordion, and CTA.
 *
 * @package Eskoofy
 */

declare(strict_types=1);

get_header();

global $wpdb;

$open    = esk_admissions_open();
$heading = (string) esk_site_ui( 'pages.admissions_heading', __( 'Admissions', 'eskoofy' ) );

get_template_part(
	'template-parts/inner-hero',
	null,
	array(
		'title'    => $heading,
		'subtitle' => $open ? (string) esk_site_ui( 'pages.apply_online', __( 'Start your application', 'eskoofy' ) ) : (string) esk_site_ui( 'pages.admissions_closed_title', '' ),
	)
);

$fee_rows = array();
$fees_table = $wpdb->prefix . 'esk_fees';
if ( (string) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $fees_table ) ) === $fees_table ) {
	$fee_rows = (array) $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		"SELECT f.name, f.amount, f.fee_type, c.name AS class_name
		FROM {$fees_table} f
		LEFT JOIN {$wpdb->prefix}esk_classes c ON f.class_id = c.id
		WHERE f.deleted_at IS NULL AND f.status = 'active'
		ORDER BY c.name, f.fee_type"
	);
}

$process_steps = array(
	array(
		'num'  => 1,
		'title' => esc_html__( 'Submit Application', 'eskoofy' ),
		'desc'  => esc_html__( 'Fill in the online form with student and guardian details.', 'eskoofy' ),
	),
	array(
		'num'  => 2,
		'title' => esc_html__( 'Document Review', 'eskoofy' ),
		'desc'  => esc_html__( 'Upload required documents for verification by our team.', 'eskoofy' ),
	),
	array(
		'num'  => 3,
		'title' => esc_html__( 'Entrance Test', 'eskoofy' ),
		'desc'  => esc_html__( 'Candidates may be called for a written test and interview.', 'eskoofy' ),
	),
	array(
		'num'  => 4,
		'title' => esc_html__( 'Confirmation', 'eskoofy' ),
		'desc'  => esc_html__( 'Pay fees and confirm admission. Welcome to the family!', 'eskoofy' ),
	),
);

$faqs = array(
	array(
		'q' => esc_html__( 'What is the minimum age for admission?', 'eskoofy' ),
		'a' => esc_html__( 'For Play/Nursery, the minimum age is 3 years as of January 1 of the admission year. For KG-1 it is 4 years, and for KG-2 it is 5 years.', 'eskoofy' ),
	),
	array(
		'q' => esc_html__( 'Is there an entrance test?', 'eskoofy' ),
		'a' => esc_html__( 'Yes, students applying for Class 1 and above must take a written entrance test in English, Mathematics, and Bengali. An oral interview may also be conducted.', 'eskoofy' ),
	),
	array(
		'q' => esc_html__( 'Can I apply for a scholarship?', 'eskoofy' ),
		'a' => esc_html__( 'Merit-based and need-based scholarships are available. Please contact the admissions office or fill out the inquiry form on the contact page.', 'eskoofy' ),
	),
);


?>
<div class="esk-page-sections">
	<div class="esk-container">
		<?php if ( $open ) : ?>
			<div class="esk-message-box esk-message-success">
				<strong><?php echo esc_html( (string) esk_site_ui( 'admissions_bar.title', __( 'Admissions Open', 'eskoofy' ) ) ); ?></strong>
			</div>

			<div class="esk-panel-grid">
				<div class="esk-panel-card esk-card">
					<span class="esk-panel-icon" aria-hidden="true">&#128196;</span>
					<h2 class="esk-card-title"><?php echo esc_html( esk_site_ui( 'pages.apply_online', '' ) ); ?></h2>
					<p class="esk-card-text"><?php esc_html_e( 'Start a new admission application.', 'eskoofy' ); ?></p>
					<a class="esk-btn" href="<?php echo esc_url( home_url( '/admissions/' ) ); ?>"><?php echo esc_html( esk_site_ui( 'pages.apply_online', '' ) ); ?></a>
				</div>
				<div class="esk-panel-card esk-card">
					<span class="esk-panel-icon" aria-hidden="true">&#128270;</span>
					<h2 class="esk-card-title"><?php echo esc_html( esk_site_ui( 'pages.check_status', '' ) ); ?></h2>
					<p class="esk-card-text"><?php esc_html_e( 'Track your submitted application.', 'eskoofy' ); ?></p>
					<a class="esk-btn esk-btn-secondary" href="<?php echo esc_url( home_url( '/portal/' ) ); ?>"><?php echo esc_html( esk_site_ui( 'pages.check_status', '' ) ); ?></a>
				</div>
				<div class="esk-panel-card esk-card">
					<span class="esk-panel-icon" aria-hidden="true">&#128232;</span>
					<h2 class="esk-card-title"><?php echo esc_html( esk_site_ui( 'pages.check_status', '' ) ); ?></h2>
					<p class="esk-card-text"><?php esc_html_e( 'For questions, reach the admissions office.', 'eskoofy' ); ?></p>
					<a class="esk-btn esk-btn-secondary" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><?php esc_html_e( 'Contact us', 'eskoofy' ); ?></a>
				</div>
			</div>

			<section style="margin-top:3rem;">
				<h2 style="font-size:1.5rem; font-weight:800; color:var(--esk-ink); text-align:center; margin-bottom:0.5rem;"><?php esc_html_e( 'Admission Process', 'eskoofy' ); ?></h2>
				<div style="width:80px; height:4px; border-radius:999px; margin:0 auto 2rem; background:linear-gradient(90deg, #f97316, #ea580c);"></div>
				<div class="esk-grid esk-grid-4">
					<?php foreach ( $process_steps as $step ) : ?>
						<div style="text-align:center; position:relative; padding:0 0.5rem;">
							<div style="width:4rem; height:4rem; margin:0 auto; display:flex; align-items:center; justify-content:center; border-radius:1rem; background:linear-gradient(135deg, rgba(249,115,22,0.12), rgba(249,115,22,0.18)); color:#c2410c; font-size:1.4rem; font-weight:800; box-shadow:var(--esk-shadow);">
								<?php echo esc_html( (string) $step['num'] ); ?>
							</div>
							<h3 style="margin:1rem 0 0.35rem; font-size:1.05rem; font-weight:700; color:var(--esk-ink);"><?php echo esc_html( $step['title'] ); ?></h3>
							<p style="font-size:0.875rem; color:var(--esk-muted); line-height:1.6; margin:0;"><?php echo esc_html( $step['desc'] ); ?></p>
						</div>
					<?php endforeach; ?>
				</div>
			</section>

			<?php if ( ! empty( $fee_rows ) ) : ?>
				<section style="margin-top:3rem;">
					<h2 style="font-size:1.5rem; font-weight:800; color:var(--esk-ink); text-align:center; margin-bottom:0.5rem;"><?php esc_html_e( 'Fee Structure', 'eskoofy' ); ?></h2>
					<div style="width:80px; height:4px; border-radius:999px; margin:0 auto 1.5rem; background:linear-gradient(90deg, var(--esk-accent), var(--esk-accent-dark));"></div>
					<div style="overflow-x:auto;">
						<table class="esk-table esk-table-striped">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Class', 'eskoofy' ); ?></th>
									<th><?php esc_html_e( 'Fee', 'eskoofy' ); ?></th>
									<th><?php esc_html_e( 'Type', 'eskoofy' ); ?></th>
									<th><?php esc_html_e( 'Amount', 'eskoofy' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $fee_rows as $fee ) : ?>
									<tr>
										<td><?php echo esc_html( $fee->class_name ?: __( 'General', 'eskoofy' ) ); ?></td>
										<td><?php echo esc_html( $fee->name ); ?></td>
										<td><?php echo esc_html( ucfirst( str_replace( '_', ' ', (string) $fee->fee_type ) ) ); ?></td>
										<td><?php echo esc_html( esk_format_currency( $fee->amount ) ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</section>
			<?php endif; ?>

			<section class="esk-faq" style="margin-top:3rem; max-width:48rem; margin-left:auto; margin-right:auto;">
				<h2 style="font-size:1.5rem; font-weight:800; color:var(--esk-ink); text-align:center; margin-bottom:1.5rem;"><?php esc_html_e( 'Admission FAQs', 'eskoofy' ); ?></h2>
				<?php foreach ( $faqs as $faq ) : ?>
					<details>
						<summary><?php echo esc_html( $faq['q'] ); ?></summary>
						<p><?php echo esc_html( $faq['a'] ); ?></p>
					</details>
				<?php endforeach; ?>
			</section>

			<section class="esk-cta-panel" style="margin-top:3rem;">
				<h2><?php esc_html_e( 'Ready to Join Us?', 'eskoofy' ); ?></h2>
				<p><?php esc_html_e( 'Take the first step towards quality education. Apply online today.', 'eskoofy' ); ?></p>
				<a class="esk-btn" style="background:#fff; color:#ea580c;" href="<?php echo esc_url( home_url( '/admissions/' ) ); ?>"><?php echo esc_html( esk_site_ui( 'pages.apply_online', __( 'Apply Now', 'eskoofy' ) ) ); ?> &rarr;</a>
				<a class="esk-btn esk-btn-outline-light" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><?php esc_html_e( 'Contact Admissions Office', 'eskoofy' ); ?></a>
			</section>
		<?php else : ?>
			<div class="esk-message-box esk-message-error">
				<strong><?php echo esc_html( esk_site_ui( 'pages.admissions_closed_title', '' ) ); ?></strong>
				<p><?php echo esc_html( esk_site_ui( 'pages.admissions_closed_message', '' ) ); ?></p>
			</div>
			<section style="max-width:48rem; margin-left:auto; margin-right:auto;">
				<h2 style="font-size:1.5rem; font-weight:800; color:var(--esk-ink); text-align:center; margin-bottom:1.5rem;"><?php esc_html_e( 'Admission FAQs', 'eskoofy' ); ?></h2>
				<div class="esk-faq">
					<?php foreach ( $faqs as $faq ) : ?>
						<details>
							<summary><?php echo esc_html( $faq['q'] ); ?></summary>
							<p><?php echo esc_html( $faq['a'] ); ?></p>
						</details>
					<?php endforeach; ?>
				</div>
			</section>
			<div style="text-align:center; margin-top:2rem;">
				<a class="esk-btn esk-btn-secondary" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><?php esc_html_e( 'Contact us', 'eskoofy' ); ?></a>
			</div>
		<?php endif; ?>
	</div>
</div>
<?php
get_template_part( 'template-parts/page-sections', null, array( 'page' => 'admission', 'fallback_option' => '' ) );
get_footer();