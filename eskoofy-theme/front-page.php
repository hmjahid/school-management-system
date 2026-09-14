<?php
/**
 * Front page template — Eskoofy WordPress theme.
 *
 * Feature-equivalent port of the Laravel app's public homepage:
 * hero (6 CMS designs + notices panel), features, stats, principal's
 * message, teachers slider, committee slider, testimonials, remarkable
 * students, photo slider, events, news, highlights, CTA banner and the
 * partner/affiliation strip. All user-facing strings come from
 * esk_site_ui() (languages/{locale}/site_ui.php), mirroring the app's
 * `site_ui()` helper.
 *
 * @package Eskoofy
 */

declare(strict_types=1);

get_header();

global $wpdb;

/* ─── Shared data ──────────────────────────────────────────────────────── */

$is_bn            = 'bn_BD' === get_option( 'esk_locale', 'en' ) || 0 === strpos( get_locale(), 'bn' );
$school_name      = esk_school( 'school_name' ) ?: get_bloginfo( 'name' );
$school_tagline   = esk_school( 'school_tagline' ) ?: get_bloginfo( 'description', 'display' );
$admissions_open  = esk_admissions_open();
$hidden_sections  = array_filter( array_map( 'trim', explode( ',', (string) get_theme_mod( 'esk_section_visibility', '' ) ) ) );
$show             = static fn( string $key ): bool => ! in_array( $key, $hidden_sections, true );

$links = array(
	'admission' => home_url( '/admission/' ),
	'apply'     => home_url( '/admission/' ),
	'about'     => home_url( '/about/' ),
	'contact'   => home_url( '/contact/' ),
	'faculty'   => home_url( '/faculty/' ),
	'committee' => home_url( '/committee/' ),
	'news'      => home_url( '/news/' ),
	'notices'   => home_url( '/notices/' ),
	'gallery'   => home_url( '/gallery/' ),
	'portal'    => home_url( '/portal/' ),
	'results'   => home_url( '/results/' ),
	'fees'      => home_url( '/fees/' ),
);

/* ─── Hero data ───────────────────────────────────────────────────────── */

$hero_sec       = esk_home_section( 'hero' );
$hero_meta      = $hero_sec && $hero_sec->meta ? json_decode( $hero_sec->meta, true ) : null;
$hero_design    = is_array( $hero_meta ) && isset( $hero_meta['hero_design'] ) ? (string) $hero_meta['hero_design'] : (string) get_theme_mod( 'esk_hero_design', (string) get_option( 'esk_hero_design', 'design-1' ) );
if ( ! in_array( $hero_design, array( 'design-1', 'design-2', 'design-3', 'design-4', 'design-5', 'design-6' ), true ) ) {
	$hero_design = 'design-1';
}
$hero_image     = get_theme_mod( 'esk_hero_background', '' );
if ( ! $hero_image && $hero_sec && $hero_sec->image ) {
	$hero_image = $hero_sec->image;
}
$hero_title     = esk_school( 'hero_title' ) ?: (string) ( is_array( $hero_meta ) && ! empty( $hero_meta['headline'] ) ? $hero_meta['headline'] : esk_site_ui( 'home.hero_headline', '' ) );
$hero_subtitle  = esk_school( 'hero_tagline' ) ?: (string) ( is_array( $hero_meta ) && ! empty( $hero_meta['subtitle'] ) ? $hero_meta['subtitle'] : esk_site_ui( 'home.hero_subtitle', '' ) );
$hero_cta_text  = esk_school( 'hero_cta_text' ) ?: (string) esk_site_ui( 'home.hero_cta_primary', '' );

/* ─── Notices ────────────────────────────────────────────────────────────── */

$notices = array();
$table_exists = array();
$tables  = array( 'esk_notices', 'esk_students', 'esk_teachers', 'esk_committee_members', 'esk_testimonials', 'esk_events', 'esk_news', 'esk_galleries', 'esk_announcements', 'esk_website_contents', 'esk_admission_settings' );
foreach ( $tables as $t ) {
	$full = $wpdb->prefix . $t;
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $full ) ) === $full ) {
		$table_exists[ $t ] = true;
	}
}
if ( ! empty( $table_exists['esk_notices'] ) ) {
	$notices = $wpdb->get_results(
		"SELECT * FROM {$wpdb->prefix}esk_notices ORDER BY pinned DESC, created_at DESC, id DESC LIMIT 5"
	);
}

/* ─── Stats ───────────────────────────────────────────────────────────── */

$stats = array(
	'students' => ! empty( $table_exists['esk_students'] ) ? (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}esk_students WHERE deleted_at IS NULL" ) : 0,
	'teachers' => ! empty( $table_exists['esk_teachers'] ) ? (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}esk_teachers" ) : 0,
	'years'    => 0,
	'awards'   => (int) get_option( 'esk_stats_awards', 0 ),
);
$established_year = (int) ( esk_school( 'established_year' ) ?: get_theme_mod( 'esk_established_year', get_option( 'esk_established_year', 0 ) ) );
if ( $established_year > 0 ) {
	$stats['years'] = max( 0, (int) gmdate( 'Y' ) - $established_year );
}
$stats['awards'] = max( $stats['awards'], (int) get_theme_mod( 'esk_stats_awards', get_option( 'esk_stats_awards', 0 ) ) );

/* ─── Slider slides (photo slider + design-6 hero slides) ─────────────── */

$slides = array();
$slider_row = esk_home_section( 'slider' );
if ( $slider_row ) {
	foreach ( array( 'meta', 'content' ) as $field ) {
		$decoded = $slider_row->{$field} ? json_decode( $slider_row->{$field}, true ) : null;
		if ( is_array( $decoded ) && ! empty( $decoded ) ) {
			foreach ( $decoded as $slide ) {
				if ( is_array( $slide ) && ! empty( $slide['image'] ) ) {
					$slides[] = array(
						'image'   => (string) $slide['image'],
						'title'   => isset( $slide['title'] ) ? (string) $slide['title'] : '',
						'caption' => isset( $slide['caption'] ) ? (string) $slide['caption'] : '',
						'link'    => isset( $slide['link'] ) ? (string) $slide['link'] : '',
					);
				}
			}
			break;
		}
	}
}
if ( empty( $slides ) && ! empty( $table_exists['esk_events'] ) ) {
	$event_slides = $wpdb->get_results(
		"SELECT title, image, start_date FROM {$wpdb->prefix}esk_events WHERE status = 'published' AND image IS NOT NULL AND image != '' AND deleted_at IS NULL ORDER BY start_date DESC LIMIT 6"
	);
	foreach ( (array) $event_slides as $es ) {
		$slides[] = array(
			'image'   => (string) $es->image,
			'title'   => (string) $es->title,
			'caption' => esk_date_format( (string) $es->start_date ),
			'link'    => $links['news'],
		);
	}
}

/* ─── Features ────────────────────────────────────────────────────────── */

$features = (array) esk_site_ui( 'home.features_default', array() );

/* ─── Principal's message ─────────────────────────────────────────────── */

$principal_row   = esk_home_section( 'principal' );
$principal       = array(
	'name'        => $principal_row ? (string) $principal_row->title : (string) esk_site_ui( 'home.principal_fallback', '' ),
	'designation' => (string) esk_site_ui( 'home.principal_fallback', '' ),
	'photo'       => $principal_row && $principal_row->image ? (string) $principal_row->image : '',
	'message'     => $principal_row && $principal_row->content ? (string) $principal_row->content : (string) esk_site_ui( 'home.principal_message_default', '' ),
);

/* ─── Teachers ────────────────────────────────────────────────────────── */

$teachers = array();
if ( ! empty( $table_exists['esk_teachers'] ) ) {
	$teachers = (array) $wpdb->get_results(
		"SELECT t.id, t.qualification, t.subjects, u.ID AS user_id, u.display_name
		FROM {$wpdb->prefix}esk_teachers t
		INNER JOIN {$wpdb->users} u ON t.user_id = u.ID
		ORDER BY t.id DESC LIMIT 8"
	);
}

/* ─── Committee ───────────────────────────────────────────────────────── */

$committee = array();
if ( ! empty( $table_exists['esk_committee_members'] ) ) {
	$committee = (array) $wpdb->get_results(
		"SELECT * FROM {$wpdb->prefix}esk_committee_members WHERE is_active = 1 ORDER BY sort_order ASC, id ASC LIMIT 20"
	);
}

/* ─── Testimonials ────────────────────────────────────────────────────── */

$testimonials = array();
if ( ! empty( $table_exists['esk_testimonials'] ) ) {
	$rows = (array) $wpdb->get_results(
		"SELECT * FROM {$wpdb->prefix}esk_testimonials WHERE is_visible = 1 ORDER BY sort_order ASC, id DESC LIMIT 6"
	);
	foreach ( $rows as $row ) {
		$testimonials[] = array(
			'quote' => (string) $row->content,
			'name'  => (string) $row->author_name,
			'role'  => (string) $row->author_designation,
			'photo' => (string) $row->photo,
		);
	}
}
if ( empty( $testimonials ) ) {
	$testimonials = (array) esk_site_ui( 'home.testimonials_default', array() );
}

/* ─── Events & news ───────────────────────────────────────────────────── */

$events = array();
if ( ! empty( $table_exists['esk_events'] ) ) {
	$events = (array) $wpdb->get_results(
		"SELECT * FROM {$wpdb->prefix}esk_events WHERE status = 'published' AND start_date >= NOW() AND deleted_at IS NULL ORDER BY start_date ASC LIMIT 6"
	);
}
$news_items = array();
if ( ! empty( $table_exists['esk_news'] ) ) {
	$news_items = (array) $wpdb->get_results(
		"SELECT * FROM {$wpdb->prefix}esk_news WHERE is_published = 1 AND deleted_at IS NULL ORDER BY published_at DESC, id DESC LIMIT 6"
	);
}

/* ─── Highlights & partners ───────────────────────────────────────────── */

$highlights = (array) esk_site_ui( 'home.highlights_default', array() );

$partner_labels = array(
	(string) esk_site_ui( 'home.partner_education_ministry', '' ),
	(string) esk_site_ui( 'home.partner_primary_education', '' ),
	(string) esk_site_ui( 'home.partner_secondary_board', '' ),
	(string) esk_site_ui( 'home.partner_national_board', '' ),
	(string) esk_site_ui( 'home.partner_naem', '' ),
);
$ministry_links = (array) esk_site_ui( 'footer.ministry_links', array() );
$partners       = array();
foreach ( $partner_labels as $label ) {
	if ( '' === $label ) {
		continue;
	}
	$url = '';
	foreach ( $ministry_links as $entry ) {
		$parts = explode( '|', (string) $entry );
		if ( isset( $parts[0] ) && ( $parts[0] === $label || false !== strpos( $label, $parts[0] ) ) && isset( $parts[1] ) ) {
			$url = $parts[1];
			break;
		}
	}
	$partners[] = array( 'name' => $label, 'url' => $url );
}

/* ─── SVG helpers ─────────────────────────────────────────────────────── */

$svg = array(
	'arrow'      => '<svg class="esk-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>',
	'chev-l'     => '<svg class="esk-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>',
	'chev-r'     => '<svg class="esk-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>',
	'phone'      => '<svg class="esk-icon" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/></svg>',
	'pin'        => '<svg class="esk-icon" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M10 2a5 5 0 00-5 5v2a2 2 0 00-2 2v5a2 2 0 002 2h10a2 2 0 002-2v-5a2 2 0 00-2-2V7a5 5 0 00-5-5zm3 7V7a3 3 0 00-6 0v2h6z"/></svg>',
	'user'       => '<svg class="esk-icon" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>',
	'quote'      => '<svg class="esk-icon" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10H14.017zM0 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151C7.544 6.068 5.982 8.79 5.982 11H10v10H0z"/></svg>',
	'cal'        => '<svg class="esk-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>',
	'loc'        => '<svg class="esk-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
	'check'      => '<svg class="esk-icon" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>',
	'image'      => '<svg class="esk-icon" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/></svg>',
	'swipe'      => '<svg class="esk-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>',
);
$esc_svg = array_map( 'wp_kses_post', $svg );

$esc = static function ( string $key = '' ) use ( $esc_svg, $svg ) {
	return $esc_svg[ $key ] ?? '';
};

/* Helper: localized field. */
$t_loc = static function ( string $en, string $bn ) use ( $is_bn ): string {
	return $is_bn && '' !== $bn ? $bn : $en;
};
?>

<div id="page" class="esk-home">

	<?php if ( $show( 'hero' ) ) : ?>

	<?php
	$primary_href = $admissions_open ? $links['admission'] : $links['contact'];
	$primary_text = $admissions_open ? (string) ( $hero_cta_text ?: esk_site_ui( 'home.hero_cta_primary', '' ) ) : (string) esk_site_ui( 'home.cta_contact', '' );
	$secondary_href = $links['about'];
	$secondary_text = (string) esk_site_ui( 'home.hero_cta_secondary', '' );
	?>

	<?php if ( 'design-1' === $hero_design ) : ?>
		<section class="esk-hero esk-hero-1">
			<?php if ( $hero_image ) : ?>
				<div class="esk-hero-bg">
					<img src="<?php echo esc_url( $hero_image ); ?>" alt="">
					<div class="esk-hero-overlay"></div>
				</div>
			<?php endif; ?>
			<div class="esk-hero-blob esk-hero-blob-orange"></div>
			<div class="esk-hero-blob esk-hero-blob-indigo"></div>

			<div class="esk-container esk-hero-body">
				<div class="esk-hero-grid">
					<div class="esk-hero-copy">
						<h1 class="esk-hero-title"><?php echo esc_html( $hero_title ); ?></h1>
						<p class="esk-hero-subtitle"><?php echo esc_html( $hero_subtitle ); ?></p>
						<div class="esk-hero-ctas">
							<a href="<?php echo esc_url( $primary_href ); ?>" class="esk-btn esk-btn-accent"><?php echo esc_html( $primary_text ); ?> <?php echo $esc( 'arrow' ); // phpcs:ignore ?></a>
							<a href="<?php echo esc_url( $secondary_href ); ?>" class="esk-btn esk-btn-ghost"><?php echo esc_html( $secondary_text ); ?></a>
						</div>
					</div>

					<?php if ( $show( 'urgent_notices' ) && ! empty( $notices ) ) : ?>
						<div class="esk-hero-notices">
							<div class="esk-card esk-notices-card">
								<div class="esk-notices-head">
									<span class="esk-notices-icon"><?php echo $esc( 'pin' ); // phpcs:ignore ?></span>
									<h3><?php echo esc_html( (string) esk_site_ui( 'home.latest_notices', '' ) ); ?></h3>
								</div>
								<?php
								$visible   = 4;
								$n_height  = 88;
								$n_gap     = 10;
								$n_all     = count( $notices );
								$visible_h = ( $visible * $n_height ) + ( ( $visible - 1 ) * $n_gap );
								$duration  = max( 8, $n_all * 3 );
								?>
								<div class="esk-notices-scroll-wrap">
									<div class="esk-notices-fade esk-notices-fade-top"></div>
									<div class="esk-notices-fade esk-notices-fade-bottom"></div>
									<div class="notice-scroll-container esk-notices-scroll" style="height: <?php echo esc_attr( (string) $visible_h ); ?>px;" data-scroll-speed="<?php echo esc_attr( (string) $duration ); ?>">
										<div class="notice-scroll-content">
											<?php for ( $copy = 0; $copy < 2; $copy++ ) : ?>
												<?php foreach ( $notices as $notice ) : ?>
													<div class="esk-notice-item">
														<div class="esk-notice-item-inner">
															<?php if ( ! empty( $notice->pinned ) ) : ?>
																<?php echo $esc( 'pin' ); // phpcs:ignore ?>
															<?php else : ?>
																<span class="esk-dot"></span>
															<?php endif; ?>
															<div class="esk-notice-item-copy">
																<h4><?php echo esc_html( $t_loc( (string) $notice->title, (string) $notice->title_bn ) ); ?></h4>
																<?php $n_clean = trim( wp_strip_all_tags( $t_loc( (string) $notice->content, (string) $notice->content_bn ) ) ); ?>
																<?php if ( '' !== $n_clean ) : ?>
																	<p><?php echo esc_html( wp_trim_words( $n_clean, 14 ) ); ?></p>
																<?php endif; ?>
															</div>
														</div>
													</div>
												<?php endforeach; ?>
											<?php endfor; ?>
										</div>
									</div>
								</div>
								<div class="esk-notices-footer">
									<a href="<?php echo esc_url( $links['notices'] ); ?>"><?php echo esc_html( (string) esk_site_ui( 'home.view_all_notices', '' ) ); ?> <?php echo $esc( 'arrow' ); // phpcs:ignore ?></a>
								</div>
							</div>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</section>

	<?php elseif ( 'design-2' === $hero_design ) : ?>
		<section class="esk-hero esk-hero-2">
			<?php if ( $hero_image ) : ?>
				<img src="<?php echo esc_url( $hero_image ); ?>" alt="" class="esk-hero-abs-img">
				<div class="esk-hero-overlay-dark"></div>
			<?php endif; ?>
			<div class="esk-hero-dotgrid" aria-hidden="true"></div>
			<div class="esk-container esk-hero-center">
				<span class="esk-hero-badge"><?php echo esc_html( $school_name ); ?></span>
				<h1 class="esk-hero-title-lg"><?php echo esc_html( $hero_title ); ?></h1>
				<p class="esk-hero-subtitle"><?php echo esc_html( $hero_subtitle ); ?></p>
				<div class="esk-hero-ctas">
					<a href="<?php echo esc_url( $primary_href ); ?>" class="esk-btn esk-btn-accent"><?php echo esc_html( $primary_text ); ?> <?php echo $esc( 'arrow' ); // phpcs:ignore ?></a>
					<a href="<?php echo esc_url( $secondary_href ); ?>" class="esk-btn esk-btn-ghost"><?php echo esc_html( $secondary_text ); ?></a>
				</div>
				<div class="esk-hero-divider"></div>
			</div>
		</section>

	<?php elseif ( 'design-3' === $hero_design ) : ?>
		<section class="esk-hero esk-hero-3">
			<div class="esk-hero-blob esk-hero-blob-blue"></div>
			<div class="esk-hero-blob esk-hero-blob-coral"></div>
			<div class="esk-container esk-hero-split">
				<div class="esk-hero-copy">
					<span class="esk-hero-pill"><?php echo esc_html( $school_name ); ?></span>
					<h1 class="esk-hero-title-dark"><?php echo esc_html( $hero_title ); ?></h1>
					<p class="esk-hero-subtitle-dark"><?php echo esc_html( $hero_subtitle ); ?></p>
					<div class="esk-hero-ctas">
						<a href="<?php echo esc_url( $primary_href ); ?>" class="esk-btn esk-btn-primary"><?php echo esc_html( $primary_text ); ?> <?php echo $esc( 'arrow' ); // phpcs:ignore ?></a>
						<a href="<?php echo esc_url( $secondary_href ); ?>" class="esk-btn esk-btn-plain"><?php echo esc_html( $secondary_text ); ?></a>
					</div>
					<div class="esk-hero-metrics">
						<div>
							<div class="esk-hero-metric-value">100%</div>
							<div class="esk-hero-metric-label"><?php echo esc_html( (string) esk_site_ui( 'home.focused_learning', '' ) ); ?></div>
						</div>
						<div class="esk-hero-metric-divider"></div>
						<div>
							<div class="esk-hero-metric-value"><?php echo esc_html( (string) $stats['years'] ); ?>+</div>
							<div class="esk-hero-metric-label"><?php echo esc_html( (string) esk_site_ui( 'home.stats_years', '' ) ); ?></div>
						</div>
					</div>
				</div>
				<div class="esk-hero-media">
					<?php if ( $hero_image ) : ?>
						<img src="<?php echo esc_url( $hero_image ); ?>" alt="" class="esk-hero-photo">
					<?php else : ?>
						<div class="esk-hero-photo-placeholder"><?php echo $esc( 'user' ); // phpcs:ignore ?></div>
					<?php endif; ?>
					<div class="esk-hero-deco esk-hero-deco-orange"></div>
					<div class="esk-hero-deco esk-hero-deco-blue"></div>
				</div>
			</div>
		</section>

	<?php elseif ( 'design-4' === $hero_design ) : ?>
		<section class="esk-hero esk-hero-4">
			<div class="esk-hero-4-bg"></div>
			<?php if ( $hero_image ) : ?>
				<img src="<?php echo esc_url( $hero_image ); ?>" alt="" class="esk-hero-abs-img esk-hero-abs-img-soft">
			<?php endif; ?>
			<div class="esk-hero-blob esk-hero-blob-white"></div>
			<div class="esk-hero-blob esk-hero-blob-black"></div>
			<div class="esk-container esk-hero-4-body">
				<span class="esk-hero-eyebrow"><?php echo esc_html( $school_name ); ?></span>
				<h1 class="esk-hero-title-4"><?php echo esc_html( $hero_title ); ?></h1>
				<p class="esk-hero-subtitle"><?php echo esc_html( $hero_subtitle ); ?></p>
				<div class="esk-hero-ctas">
					<a href="<?php echo esc_url( $primary_href ); ?>" class="esk-btn esk-btn-white"><?php echo esc_html( $primary_text ); ?> <?php echo $esc( 'arrow' ); // phpcs:ignore ?></a>
					<a href="<?php echo esc_url( $secondary_href ); ?>" class="esk-btn esk-btn-ghost"><?php echo esc_html( $secondary_text ); ?></a>
				</div>
			</div>
			<div class="esk-hero-4-fade"></div>
		</section>

	<?php elseif ( 'design-5' === $hero_design ) : ?>
		<section class="esk-hero esk-hero-5">
			<?php if ( $hero_image ) : ?>
				<img src="<?php echo esc_url( $hero_image ); ?>" alt="" class="esk-hero-abs-img">
				<div class="esk-hero-overlay-up"></div>
			<?php endif; ?>
			<div class="esk-container esk-hero-center">
				<h1 class="esk-hero-title-5"><?php echo esc_html( $school_name ); ?></h1>
				<?php if ( $hero_title ) : ?>
					<p class="esk-hero-subtitle-strong"><?php echo esc_html( $hero_title ); ?></p>
				<?php endif; ?>
				<?php if ( $hero_subtitle ) : ?>
					<p class="esk-hero-subtitle"><?php echo esc_html( $hero_subtitle ); ?></p>
				<?php endif; ?>
				<div class="esk-hero-ctas">
					<a href="<?php echo esc_url( $primary_href ); ?>" class="esk-btn esk-btn-accent"><?php echo esc_html( $primary_text ); ?> <?php echo $esc( 'arrow' ); // phpcs:ignore ?></a>
					<a href="<?php echo esc_url( $secondary_href ); ?>" class="esk-btn esk-btn-ghost"><?php echo esc_html( $secondary_text ); ?></a>
				</div>
			</div>
		</section>

	<?php elseif ( 'design-6' === $hero_design ) : ?>
		<?php if ( ! empty( $slides ) ) : ?>
			<style>
				@keyframes hero6-fade {
					0%, 16.66% { opacity: 1; }
					20%, 100% { opacity: 0; }
				}
			</style>
		<?php endif; ?>
		<section class="esk-hero esk-hero-6">
			<div class="esk-hero-6-slides">
				<?php if ( ! empty( $slides ) ) : ?>
					<?php $count = count( $slides ); ?>
					<?php foreach ( $slides as $i => $slide ) : ?>
						<div class="esk-hero-6-slide<?php echo 0 === $i ? ' esk-hero-6-active' : ''; ?>"
							<?php if ( $i > 0 ) : ?>style="animation: hero6-fade <?php echo esc_attr( (string) ( $count * 4 ) ); ?>s <?php echo esc_attr( (string) ( $i * 4 ) ); ?>s infinite;"<?php endif; ?>>
							<?php if ( ! empty( $slide['image'] ) ) : ?>
								<img src="<?php echo esc_url( $slide['image'] ); ?>" alt="<?php echo esc_attr( $slide['title'] ); ?>">
							<?php else : ?>
								<div class="esk-hero-6-fallback"></div>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
					<div class="esk-hero-6-overlay"></div>
				<?php else : ?>
					<div class="esk-hero-6-fallback-only"></div>
				<?php endif; ?>
			</div>
			<div class="esk-container esk-hero-6-body">
				<span class="esk-hero-badge"><?php echo esc_html( $school_name ); ?></span>
				<h1 class="esk-hero-title-6"><?php echo esc_html( $hero_title ); ?></h1>
				<p class="esk-hero-subtitle"><?php echo esc_html( $hero_subtitle ); ?></p>
				<div class="esk-hero-ctas">
					<a href="<?php echo esc_url( $primary_href ); ?>" class="esk-btn esk-btn-accent"><?php echo esc_html( $primary_text ); ?> <?php echo $esc( 'arrow' ); // phpcs:ignore ?></a>
					<a href="<?php echo esc_url( $secondary_href ); ?>" class="esk-btn esk-btn-ghost"><?php echo esc_html( $secondary_text ); ?></a>
				</div>
			</div>
		</section>
	<?php endif; ?>
	<?php endif; ?>

	<?php if ( $show( 'features' ) && ! empty( $features ) ) : ?>
		<section class="esk-section esk-section-white">
			<div class="esk-container">
				<div class="esk-section-head esk-section-head-center reveal">
					<h2 class="esk-section-title"><?php echo esc_html( (string) esk_site_ui( 'home.features_title', '' ) ); ?></h2>
					<div class="esk-section-rule esk-rule-blue"></div>
					<p class="esk-section-intro"><?php echo esc_html( (string) esk_site_ui( 'home.features_intro', '' ) ); ?></p>
				</div>
				<div class="esk-grid esk-grid-4">
					<?php foreach ( $features as $index => $feature ) : ?>
						<?php if ( ! is_array( $feature ) ) { continue; } ?>
						<div class="esk-card esk-feature-card reveal">
							<div class="esk-feature-num"><?php echo esc_html( (string) ( $index + 1 ) ); ?></div>
							<h3><?php echo esc_html( (string) ( $feature['title'] ?? '' ) ); ?></h3>
							<p><?php echo esc_html( (string) ( $feature['description'] ?? '' ) ); ?></p>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $show( 'stats' ) ) : ?>
		<section class="esk-section esk-section-stats">
			<div class="esk-container">
				<div class="esk-grid esk-grid-4 esk-stats-grid reveal">
					<div class="esk-stat">
						<div class="esk-stat-value" data-countup data-target="<?php echo esc_attr( (string) $stats['students'] ); ?>" data-suffix="+">0</div>
						<div class="esk-stat-label"><?php echo esc_html( (string) esk_site_ui( 'home.stats_students', '' ) ); ?></div>
					</div>
					<div class="esk-stat">
						<div class="esk-stat-value" data-countup data-target="<?php echo esc_attr( (string) $stats['teachers'] ); ?>" data-suffix="+">0</div>
						<div class="esk-stat-label"><?php echo esc_html( (string) esk_site_ui( 'home.stats_faculty', '' ) ); ?></div>
					</div>
					<div class="esk-stat">
						<div class="esk-stat-value" data-countup data-target="<?php echo esc_attr( (string) $stats['years'] ); ?>" data-suffix="+">0</div>
						<div class="esk-stat-label"><?php echo esc_html( (string) esk_site_ui( 'home.stats_years', '' ) ); ?></div>
					</div>
					<div class="esk-stat">
						<div class="esk-stat-value" data-countup data-target="<?php echo esc_attr( (string) $stats['awards'] ); ?>" data-suffix="+">0</div>
						<div class="esk-stat-label"><?php echo esc_html( (string) esk_site_ui( 'home.stats_awards', '' ) ); ?></div>
					</div>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $show( 'principal' ) && ! empty( $principal['message'] ) ) : ?>
		<section class="esk-section esk-section-white">
			<div class="esk-container">
				<div class="esk-section-head esk-section-head-center reveal">
					<h2 class="esk-section-title"><?php echo esc_html( (string) esk_site_ui( 'home.principal_title', '' ) ); ?></h2>
					<div class="esk-section-rule esk-rule-orange"></div>
				</div>
				<div class="esk-principal-grid reveal">
					<div class="esk-principal-photo">
						<?php if ( $principal['photo'] ) : ?>
							<img src="<?php echo esc_url( $principal['photo'] ); ?>" alt="<?php echo esc_attr( $principal['name'] ); ?>">
						<?php else : ?>
							<div class="esk-principal-placeholder"><?php echo $esc( 'user' ); // phpcs:ignore ?></div>
						<?php endif; ?>
						<?php if ( $principal['name'] ) : ?>
							<div class="esk-principal-name">
								<?php echo esc_html( $principal['name'] ); ?>
								<?php if ( $principal['designation'] ) : ?>
									<span>· <?php echo esc_html( $principal['designation'] ); ?></span>
								<?php endif; ?>
							</div>
						<?php endif; ?>
					</div>
					<div class="esk-principal-message">
						<div class="esk-quote-mark"><?php echo $esc( 'quote' ); // phpcs:ignore ?></div>
						<blockquote><?php echo esc_html( $principal['message'] ); ?></blockquote>
						<div class="esk-principal-sign">
							<span class="esk-sign-bar"></span>
							<p><?php echo esc_html( $principal['designation'] ); ?></p>
						</div>
					</div>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $show( 'teachers' ) && ! empty( $teachers ) ) : ?>
		<section class="esk-section esk-section-muted">
			<div class="esk-container">
				<div class="esk-section-head esk-section-head-center reveal">
					<h2 class="esk-section-title"><?php echo esc_html( (string) esk_site_ui( 'home.teachers_title', '' ) ); ?></h2>
					<div class="esk-section-rule esk-rule-orange"></div>
					<p class="esk-section-intro"><?php echo esc_html( (string) esk_site_ui( 'home.teachers_intro', '' ) ); ?></p>
				</div>
				<div class="esk-slider" data-teachers-slider>
					<button type="button" class="esk-slider-btn esk-slider-prev" data-teachers-prev aria-label="<?php echo esc_attr( (string) esk_site_ui( 'home.slider_prev', '' ) ); ?>"><?php echo $esc( 'chev-l' ); // phpcs:ignore ?></button>
					<button type="button" class="esk-slider-btn esk-slider-next" data-teachers-next aria-label="<?php echo esc_attr( (string) esk_site_ui( 'home.slider_next', '' ) ); ?>"><?php echo $esc( 'chev-r' ); // phpcs:ignore ?></button>
					<div class="esk-slider-track" data-teachers-track>
						<?php foreach ( $teachers as $teacher ) : ?>
							<?php
							$t_name   = $teacher->display_name ? (string) $teacher->display_name : (string) esk_site_ui( 'home.teacher_fallback', '' );
							$t_avatar = $teacher->user_id ? get_avatar( (int) $teacher->user_id, 96 ) : '';
							?>
							<div class="esk-slide-card">
								<?php if ( $t_avatar ) : ?>
									<div class="esk-avatar-wrap"><?php echo $t_avatar; // phpcs:ignore ?></div>
								<?php else : ?>
									<div class="esk-avatar esk-avatar-circle"><?php echo esc_html( esk_initials( $t_name ) ); ?></div>
								<?php endif; ?>
								<h3><?php echo esc_html( $t_name ); ?></h3>
								<p class="esk-text-muted"><?php echo esc_html( $teacher->qualification ? (string) $teacher->qualification : (string) esk_site_ui( 'home.teacher_fallback', '' ) ); ?></p>
								<?php if ( $teacher->subjects ) : ?>
									<p class="esk-text-subtle"><?php echo esc_html( (string) $teacher->subjects ); ?></p>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>
					<p class="esk-swipe-hint"><?php echo $esc( 'swipe' ); // phpcs:ignore ?> <?php echo esc_html( (string) esk_site_ui( 'home.swipe_hint', '' ) ); ?></p>
				</div>
				<div class="esk-section-footer reveal">
					<a href="<?php echo esc_url( $links['faculty'] ); ?>" class="esk-link"><?php echo esc_html( (string) esk_site_ui( 'home.teachers_view_all', '' ) ); ?> <?php echo $esc( 'arrow' ); // phpcs:ignore ?></a>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $show( 'committee_members' ) ) : ?>
		<section class="esk-section esk-section-white">
			<div class="esk-container">
				<div class="esk-section-head esk-section-head-center reveal">
					<h2 class="esk-section-title"><?php echo esc_html( (string) esk_site_ui( 'home.committee_title', '' ) ); ?></h2>
					<div class="esk-section-rule esk-rule-blue"></div>
					<p class="esk-section-intro"><?php echo esc_html( (string) esk_site_ui( 'home.committee_intro', '' ) ); ?></p>
				</div>
				<?php if ( ! empty( $committee ) ) : ?>
					<div class="esk-slider" data-committee-slider>
						<button type="button" class="esk-slider-btn esk-slider-prev" data-committee-prev aria-label="<?php echo esc_attr( (string) esk_site_ui( 'home.slider_prev', '' ) ); ?>"><?php echo $esc( 'chev-l' ); // phpcs:ignore ?></button>
						<button type="button" class="esk-slider-btn esk-slider-next" data-committee-next aria-label="<?php echo esc_attr( (string) esk_site_ui( 'home.slider_next', '' ) ); ?>"><?php echo $esc( 'chev-r' ); // phpcs:ignore ?></button>
						<div class="esk-slider-track" data-committee-track>
							<?php foreach ( $committee as $member ) : ?>
								<?php
								$c_name        = $t_loc( (string) $member->name, (string) $member->name_bn );
								$c_designation = $t_loc( (string) $member->designation, (string) $member->designation_bn );
								?>
								<div class="esk-slide-card esk-slide-card-snap">
									<?php if ( $member->photo ) : ?>
										<div class="esk-avatar-wrap"><img src="<?php echo esc_url( $member->photo ); ?>" alt="<?php echo esc_attr( $c_name ); ?>"></div>
									<?php else : ?>
										<div class="esk-avatar esk-avatar-circle"><?php echo esc_html( esk_initials( $c_name ) ); ?></div>
									<?php endif; ?>
									<h3><?php echo esc_html( $c_name ); ?></h3>
									<p class="esk-committee-role"><?php echo esc_html( $c_designation ); ?></p>
									<?php if ( $member->phone ) : ?>
										<p class="esk-text-subtle"><?php echo $esc( 'phone' ); // phpcs:ignore ?> <?php echo esc_html( (string) $member->phone ); ?></p>
									<?php endif; ?>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				<?php else : ?>
					<div class="esk-empty">
						<?php echo $esc( 'user' ); // phpcs:ignore ?>
						<p><?php echo esc_html( (string) esk_site_ui( 'home.committee_empty', '' ) ); ?></p>
					</div>
				<?php endif; ?>
				<div class="esk-section-footer reveal">
					<a href="<?php echo esc_url( $links['committee'] ); ?>" class="esk-link"><?php echo esc_html( (string) esk_site_ui( 'home.committee_view_all', '' ) ); ?> <?php echo $esc( 'arrow' ); // phpcs:ignore ?></a>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $show( 'testimonials' ) && ! empty( $testimonials ) ) : ?>
		<section class="esk-section esk-section-muted">
			<div class="esk-container">
				<div class="esk-section-head esk-section-head-center reveal">
					<h2 class="esk-section-title"><?php echo esc_html( (string) esk_site_ui( 'home.testimonials_title', '' ) ); ?></h2>
					<div class="esk-section-rule esk-rule-blue"></div>
				</div>
				<div class="esk-grid esk-grid-2 esk-testimonials">
					<?php foreach ( $testimonials as $t ) : ?>
						<?php if ( ! is_array( $t ) ) { continue; } ?>
						<div class="esk-card esk-testimonial-card reveal">
							<div class="esk-quote-mark"><?php echo $esc( 'quote' ); // phpcs:ignore ?></div>
							<p class="esk-testimonial-quote"><?php echo esc_html( (string) ( $t['quote'] ?? '' ) ); ?></p>
							<div class="esk-testimonial-author">
								<div class="esk-avatar esk-avatar-circle"><?php echo esc_html( esk_initials( (string) ( $t['name'] ?? 'A' ) ) ); ?></div>
								<div>
									<h4><?php echo esc_html( (string) ( $t['name'] ?? '' ) ); ?></h4>
									<p class="esk-text-muted"><?php echo esc_html( (string) ( $t['role'] ?? '' ) ); ?></p>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $show( 'remarkable_students' ) ) : ?>
		<section class="esk-section esk-section-white">
			<div class="esk-container">
				<div class="esk-section-head esk-section-head-center reveal">
					<h2 class="esk-section-title"><?php echo esc_html( (string) esk_site_ui( 'home.remarkable_students_title', '' ) ); ?></h2>
					<div class="esk-section-rule esk-rule-orange"></div>
					<p class="esk-section-intro"><?php echo esc_html( (string) esk_site_ui( 'home.remarkable_students_intro', '' ) ); ?></p>
				</div>
				<?php
				$bright = do_shortcode( '[eskoofy_bright_students count="8"]' );
				$bright = preg_replace( '/<h3>.*?<\/h3>/', '', (string) $bright );
				echo $bright; // phpcs:ignore
				?>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $show( 'slider' ) && ! empty( $slides ) ) : ?>
		<section class="esk-section esk-section-gallery">
			<div class="esk-container">
				<div class="esk-section-head esk-section-head-between reveal">
					<div>
						<h2 class="esk-section-title"><?php echo esc_html( (string) esk_site_ui( 'home.slider_title', '' ) ); ?></h2>
						<div class="esk-section-rule esk-rule-blue"></div>
						<p class="esk-section-intro"><?php echo esc_html( (string) esk_site_ui( 'home.slider_intro', '' ) ); ?></p>
					</div>
				</div>
				<div class="esk-slider" data-slider-carousel>
					<button type="button" class="esk-slider-btn esk-slider-prev" data-slider-prev aria-label="<?php echo esc_attr( (string) esk_site_ui( 'home.slider_prev', '' ) ); ?>"><?php echo $esc( 'chev-l' ); // phpcs:ignore ?></button>
					<button type="button" class="esk-slider-btn esk-slider-next" data-slider-next aria-label="<?php echo esc_attr( (string) esk_site_ui( 'home.slider_next', '' ) ); ?>"><?php echo $esc( 'chev-r' ); // phpcs:ignore ?></button>
					<div class="esk-slider-track esk-photo-track" data-slider-track>
						<?php foreach ( $slides as $slide ) : ?>
							<?php if ( empty( $slide['image'] ) ) { continue; } ?>
							<div class="esk-slide-photo">
								<img src="<?php echo esc_url( $slide['image'] ); ?>" alt="<?php echo esc_attr( (string) ( $slide['title'] ?? '' ) ); ?>" loading="lazy">
								<div class="esk-slide-photo-overlay"></div>
								<div class="esk-slide-photo-caption">
									<?php if ( ! empty( $slide['title'] ) ) : ?>
										<h3><?php echo esc_html( (string) $slide['title'] ); ?></h3>
									<?php endif; ?>
									<?php if ( ! empty( $slide['caption'] ) ) : ?>
										<p><?php echo esc_html( (string) $slide['caption'] ); ?></p>
									<?php endif; ?>
								</div>
								<?php if ( ! empty( $slide['link'] ) ) : ?>
									<a href="<?php echo esc_url( (string) $slide['link'] ); ?>" class="esk-slide-photo-link" aria-label="<?php echo esc_attr( (string) ( $slide['title'] ?? '' ) ); ?>"></a>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $show( 'events' ) && ! empty( $events ) ) : ?>
		<section class="esk-section esk-section-white">
			<div class="esk-container">
				<div class="esk-section-head esk-section-head-between reveal">
					<div>
						<h2 class="esk-section-title"><?php echo esc_html( (string) esk_site_ui( 'home.events_title', '' ) ); ?></h2>
						<div class="esk-section-rule esk-rule-orange"></div>
					</div>
					<a href="<?php echo esc_url( $links['news'] ); ?>" class="esk-link"><?php echo esc_html( (string) esk_site_ui( 'home.events_view_all', '' ) ); ?> <?php echo $esc( 'arrow' ); // phpcs:ignore ?></a>
				</div>
				<div class="esk-grid esk-grid-3">
					<?php foreach ( $events as $ev ) : ?>
						<div class="esk-card esk-event-card reveal">
							<span class="esk-event-date"><?php echo $esc( 'cal' ); // phpcs:ignore ?> <time datetime="<?php echo esc_attr( (string) $ev->start_date ); ?>"><?php echo esc_html( esk_date_format( (string) $ev->start_date, 'M j, Y' ) ); ?></time></span>
							<h3><?php echo esc_html( (string) $ev->title ); ?></h3>
							<?php if ( $ev->location ) : ?>
								<p class="esk-text-muted"><?php echo $esc( 'loc' ); // phpcs:ignore ?> <?php echo esc_html( (string) $ev->location ); ?></p>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $show( 'news' ) && ! empty( $news_items ) ) : ?>
		<section class="esk-section esk-section-muted">
			<div class="esk-container">
				<div class="esk-section-head esk-section-head-between reveal">
					<div>
						<h2 class="esk-section-title"><?php echo esc_html( (string) esk_site_ui( 'home.news_title', '' ) ); ?></h2>
						<div class="esk-section-rule esk-rule-blue"></div>
					</div>
					<a href="<?php echo esc_url( $links['news'] ); ?>" class="esk-link"><?php echo esc_html( (string) esk_site_ui( 'home.news_view_all', '' ) ); ?> <?php echo $esc( 'arrow' ); // phpcs:ignore ?></a>
				</div>
				<div class="esk-grid esk-grid-3">
					<?php foreach ( $news_items as $item ) : ?>
						<div class="esk-card esk-news-card reveal">
							<div class="esk-news-cover">
								<?php if ( $item->image_url ) : ?>
									<img src="<?php echo esc_url( $item->image_url ); ?>" alt="" loading="lazy">
								<?php else : ?>
									<div class="esk-news-cover-placeholder"><?php echo $esc( 'image' ); // phpcs:ignore ?></div>
								<?php endif; ?>
								<span class="esk-news-badge"><?php echo esc_html( $item->category ? (string) $item->category : (string) esk_site_ui( 'home.news_badge', '' ) ); ?></span>
							</div>
							<div class="esk-news-body">
								<?php if ( $item->published_at ) : ?>
									<div class="esk-text-subtle"><?php echo esc_html( esk_date_format( (string) $item->published_at ) ); ?></div>
								<?php endif; ?>
								<h3><?php echo esc_html( (string) $item->title ); ?></h3>
								<p><?php echo esc_html( wp_trim_words( wp_strip_all_tags( (string) $item->content ), 26 ) ); ?></p>
								<a href="<?php echo esc_url( home_url( '/news/' . rawurlencode( (string) $item->slug ) . '/' ) ); ?>" class="esk-link"><?php echo esc_html( (string) esk_site_ui( 'home.read_more', '' ) ); ?> <?php echo $esc( 'arrow' ); // phpcs:ignore ?></a>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $show( 'highlights' ) && ! empty( $highlights ) ) : ?>
		<section class="esk-section esk-section-white esk-section-tight">
			<div class="esk-container">
				<h2 class="esk-highlights-title reveal"><?php echo esc_html( (string) esk_site_ui( 'home.highlights_title', '' ) ); ?></h2>
				<div class="esk-section-rule esk-rule-orange esk-center reveal"></div>
				<ul class="esk-highlights-list reveal">
					<?php foreach ( $highlights as $h ) : ?>
						<?php if ( ! is_string( $h ) ) { continue; } ?>
						<li><?php echo $esc( 'check' ); // phpcs:ignore ?> <?php echo esc_html( $h ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $show( 'cta' ) ) : ?>
		<section class="esk-section esk-section-cta">
			<div class="esk-cta-blobs"></div>
			<div class="esk-container esk-cta-body reveal">
				<h2 class="esk-cta-title"><?php echo esc_html( (string) esk_site_ui( 'home.cta_banner_title', '' ) ); ?></h2>
				<p class="esk-cta-intro"><?php echo esc_html( (string) esk_site_ui( 'home.cta_banner_intro', '' ) ); ?></p>
				<div class="esk-cta-actions">
					<a href="<?php echo esc_url( $links['admission'] ); ?>" class="esk-btn esk-btn-light"><?php echo esc_html( (string) esk_site_ui( 'home.cta_apply', '' ) ); ?> <?php echo $esc( 'arrow' ); // phpcs:ignore ?></a>
					<a href="<?php echo esc_url( $links['contact'] ); ?>" class="esk-btn esk-btn-outline-light"><?php echo esc_html( (string) esk_site_ui( 'home.cta_contact', '' ) ); ?></a>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $show( 'partners' ) && ! empty( $partners ) ) : ?>
		<section class="esk-section esk-section-partners">
			<div class="esk-container">
				<p class="esk-partners-label reveal"><?php echo esc_html( (string) esk_site_ui( 'home.our_partners', '' ) ); ?></p>
				<div class="esk-partners-strip reveal">
					<?php foreach ( $partners as $partner ) : ?>
						<?php if ( $partner['url'] ) : ?>
							<a href="<?php echo esc_url( $partner['url'] ); ?>" target="_blank" rel="noopener noreferrer" class="esk-partner" title="<?php echo esc_attr( $partner['name'] ); ?>"><?php echo esc_html( $partner['name'] ); ?></a>
						<?php else : ?>
							<span class="esk-partner"><?php echo esc_html( $partner['name'] ); ?></span>
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

</main>

<?php
get_footer();