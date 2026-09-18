<?php
/**
 * Header template — Eskoofy WordPress theme.
 *
 * Top utility bar, announcement ticker, admissions bar, sticky navigation
 * with search + dark-mode toggles, and a mobile slide-in panel. Mirrors the
 * Laravel app's public layout.
 *
 * @package Eskoofy
 */

declare(strict_types=1);

global $wpdb;

$school_name    = esk_school( 'school_name' ) ?: get_bloginfo( 'name' );
$school_tagline = esk_school( 'school_tagline' ) ?: get_bloginfo( 'description', 'display' );
$is_bn          = 'bn_BD' === get_option( 'esk_locale', 'en' ) || 0 === strpos( get_locale(), 'bn' );

$tagline   = (string) get_bloginfo( 'name' );
$initials  = esk_initials( $tagline );
$has_logo  = (bool) get_theme_mod( 'esk_custom_logo', '' );

$phone     = esk_school( 'school_phone' );
$email     = esk_school( 'school_email' );
$address   = esk_school( 'school_address' );

$target_locale    = $is_bn ? 'en' : 'bn_BD';
$target_label     = $is_bn ? 'English' : 'বাংলা';
$portal_url       = home_url( '/portal/' );
$stock_url        = esc_url( (string) esk_school( 'stock_photo' ) );
$is_logged_in     = is_user_logged_in();
$socials          = esk_social_profiles();

$og_description = wp_trim_words( (string) $school_tagline, 30, '…' );

$svgs = array(
	'phone'  => '<svg class="esk-icon" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/></svg>',
	'mail'   => '<svg class="esk-icon" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/><path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/></svg>',
	'pin'    => '<svg class="esk-icon" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M10 2a5 5 0 00-5 5v2a2 2 0 00-2 2v5a2 2 0 002 2h10a2 2 0 002-2v-5a2 2 0 00-2-2V7a5 5 0 00-5-5zm3 7V7a3 3 0 00-6 0v2h6z"/></svg>',
	'user'   => '<svg class="esk-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>',
	'search' => '<svg class="esk-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>',
	'moon'   => '<svg class="esk-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>',
	'arrow'  => '<svg class="esk-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>',
);

/* Announcements for the ticker. */
$announcements = array();
$esk_ann_table = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->prefix . 'esk_announcements' ) );
if ( $esk_ann_table === ( $wpdb->prefix . 'esk_announcements' ) ) {
	$now = current_time( 'mysql' );
	$announcements = (array) $wpdb->get_results( $wpdb->prepare(
		"SELECT title, body FROM {$wpdb->prefix}esk_announcements
		WHERE is_published = 1 AND audience IN ('all', 'public')
		AND ( starts_at IS NULL OR starts_at <= %s )
		AND ( ends_at IS NULL OR ends_at >= %s )
		ORDER BY created_at DESC LIMIT 6",
		$now,
		$now
	) );
}

$admissions_open  = esk_admissions_open();
$ad_bar_title     = str_replace( ':year', (string) gmdate( 'Y' ), (string) esk_site_ui( 'admissions_bar.title', '' ) );
$ad_bar_cta       = (string) esk_site_ui( 'admissions_bar.cta', '' );

$nav_items = array(
	array(
		'label' => 'nav.home',
		'url'   => home_url( '/' ),
	),
	array(
		'label'    => 'nav.group.about',
		'url'      => home_url( '/about/' ),
		'children' => array(
			array( 'label' => 'nav.about',     'url' => home_url( '/about/' ) ),
			array( 'label' => 'nav.faculty',   'url' => home_url( '/faculty/' ) ),
			array( 'label' => 'nav.committee', 'url' => home_url( '/committee/' ) ),
			array( 'label' => 'nav.students',  'url' => home_url( '/students/' ) ),
		),
	),
	array(
		'label'    => 'nav.group.academics',
		'url'      => home_url( '/academics/' ),
		'children' => array(
			array( 'label' => 'nav.academics',  'url' => home_url( '/academics/' ) ),
			array( 'label' => 'nav.routine',    'url' => home_url( '/routine/' ) ),
			array( 'label' => 'nav.admissions', 'url' => home_url( '/admission/' ) ),
			array( 'label' => 'nav.gallery',    'url' => home_url( '/gallery/' ) ),
			array( 'label' => 'nav.results',    'url' => home_url( '/results/' ) ),
		),
	),
	array(
		'label'    => 'nav.group.contact',
		'url'      => home_url( '/contact/' ),
		'children' => array(
			array( 'label' => 'nav.contact',  'url' => home_url( '/contact/' ) ),
			array( 'label' => 'nav.payments', 'url' => home_url( '/fees/' ) ),
		),
	),
	array(
		'label'    => 'nav.group.news',
		'url'      => home_url( '/news/' ),
		'children' => array(
			array( 'label' => 'nav.news',    'url' => home_url( '/news/' ) ),
			array( 'label' => 'nav.notices', 'url' => home_url( '/notices/' ) ),
		),
	),
);

$render_nav = static function ( array $items ) : void {
	foreach ( $items as $item ) {
		if ( empty( $item['children'] ) ) {
			printf(
				'<li><a href="%s">%s</a></li>',
				esc_url( $item['url'] ),
				esc_html( (string) esk_site_ui( $item['label'], '' ) )
			);
			continue;
		}
		echo '<li class="menu-item-has-children">';
		printf(
			'<a href="%s">%s</a>',
			esc_url( $item['url'] ),
			esc_html( (string) esk_site_ui( $item['label'], '' ) )
		);
		echo '<ul class="sub-menu">';
		foreach ( $item['children'] as $child ) {
			printf(
				'<li><a href="%s">%s</a></li>',
				esc_url( $child['url'] ),
				esc_html( (string) esk_site_ui( $child['label'], '' ) )
			);
		}
		echo '</ul></li>';
	}
};

$esc = static fn( string $svg ): string => wp_kses_post( $svg );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> class="scroll-smooth">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="icon" type="image/svg+xml" href="<?php echo esc_url( get_template_directory_uri() . '/assets/favicon.svg' ); ?>">
	<link rel="apple-touch-icon" href="<?php echo esc_url( get_template_directory_uri() . '/assets/icons/apple-touch-icon.png' ); ?>">
	<link rel="manifest" href="<?php echo esc_url( home_url( '/manifest.json' ) ); ?>">
	<meta name="theme-color" content="<?php echo esc_attr( (string) esk_school( 'theme_primary' ) ); ?>">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="default">
	<meta name="apple-mobile-web-app-title" content="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
	<meta property="og:site_name" content="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
	<meta property="og:title" content="<?php echo esc_attr( wp_get_document_title() ); ?>">
	<meta property="og:description" content="<?php echo esc_attr( $og_description ); ?>">
	<meta property="og:type" content="website">
	<meta property="og:url" content="<?php echo esc_url( is_singular() ? get_permalink() : home_url( '/' ) ); ?>">
	<meta property="og:image" content="<?php echo esc_url( (string) ( $stock_url ?: get_template_directory_uri() . '/assets/img/og-default.png' ) ); ?>">
	<meta name="twitter:card" content="summary_large_image">
	<meta name="twitter:title" content="<?php echo esc_attr( wp_get_document_title() ); ?>">
	<meta name="twitter:description" content="<?php echo esc_attr( $og_description ); ?>">
	<meta name="twitter:image" content="<?php echo esc_url( (string) ( $stock_url ?: get_template_directory_uri() . '/assets/img/og-default.png' ) ); ?>">
	<?php wp_head(); ?>
	<script>
		/* Restore dark mode before first paint to avoid a flash. */
		(function () {
			try {
				if ('1' === window.localStorage.getItem('school-dark-mode')) {
					document.documentElement.classList.add('esk-dark-ready');
				}
			} catch (e) {}
		})();
	</script>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link screen-reader-text" href="#esk-main-content">
	<?php echo esc_html( (string) esk_site_ui( 'nav.skip_to_content', '' ) ?: esc_html__( 'Skip to content', 'eskoofy' ) ); ?>
</a>

<div class="esk-loading-bar" id="esk-loading-bar" aria-hidden="true" data-esk-loading-bar></div>

<div class="hidden bg-blue-900 text-sm text-white sm:block">
	<div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-4 py-2 lg:flex-row">
		<div class="flex flex-wrap items-center justify-center gap-x-5 gap-y-1 lg:justify-start">
			<?php if ( $phone ) : ?>
				<span class="inline-flex items-center gap-1.5">
					<svg class="h-3.5 w-3.5 shrink-0 text-blue-300" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/></svg>
					<a href="<?php echo esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', $phone ) ); ?>" class="whitespace-nowrap font-medium hover:text-blue-100"><?php echo esc_html( $phone ); ?></a>
				</span>
			<?php endif; ?>
			<?php if ( $email ) : ?>
				<span class="inline-flex items-center gap-1.5">
					<svg class="h-3.5 w-3.5 shrink-0 text-blue-300" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/><path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/></svg>
					<a href="<?php echo esc_url( 'mailto:' . $email ); ?>" class="max-w-[16rem] truncate font-medium hover:text-blue-100 lg:max-w-none"><?php echo esc_html( $email ); ?></a>
				</span>
			<?php endif; ?>
			<?php if ( $address ) : ?>
				<span class="hidden items-start gap-1.5 xl:inline-flex">
					<svg class="mt-0.5 h-3.5 w-3.5 shrink-0 text-blue-300" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
					<span class="text-blue-100"><?php echo esc_html( wp_trim_words( $address, 12, '…' ) ); ?></span>
				</span>
			<?php endif; ?>
		</div>
		<div class="flex flex-wrap items-center justify-center gap-3 lg:justify-end">
			<a href="<?php echo esc_url( add_query_arg( 'esk_lang', $target_locale ) ); ?>" rel="nofollow" class="inline-flex min-w-[1.75rem] items-center justify-center rounded border border-blue-400/60 px-2 py-0.5 text-[0.7rem] font-bold uppercase tracking-wide text-blue-200 transition hover:border-white hover:text-white"><?php echo esc_html( $target_label ); ?></a>
			<span class="hidden h-4 w-px bg-blue-600 sm:block" aria-hidden="true"></span>
			<div class="flex items-center gap-2 text-blue-200">
				<?php foreach ( $socials as $social ) : ?>
					<a href="<?php echo esc_url( $social['url'] ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( $social['label'] ); ?>" class="text-blue-200 transition hover:text-white"><?php echo $esc( $social['svg'] ); // phpcs:ignore ?></a>
				<?php endforeach; ?>
			</div>
			<span class="hidden h-4 w-px bg-blue-600 sm:block" aria-hidden="true"></span>
			<?php if ( $is_logged_in ) : ?>
				<a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>" class="whitespace-nowrap font-medium text-blue-200 hover:text-white"><?php echo esc_html( (string) esk_site_ui( 'nav.logout', '' ) ); ?></a>
			<?php else : ?>
				<a href="<?php echo esc_url( home_url( '/login/' ) ); ?>" class="whitespace-nowrap font-medium text-blue-200 hover:text-white"><?php echo esc_html( (string) esk_site_ui( 'nav.login', '' ) ); ?></a>
			<?php endif; ?>
		</div>
	</div>
</div>

<?php if ( ! empty( $announcements ) ) : ?>
	<div class="esk-ticker" role="region" aria-label="<?php echo esc_attr( (string) esk_site_ui( 'nav.announcements', '' ) ); ?>">
		<div class="esk-container esk-ticker-inner">
			<span class="esk-ticker-label"><?php echo esc_html( (string) esk_site_ui( 'nav.announcements', '' ) ); ?></span>
			<div class="esk-ticker-bar">
				<div class="esk-ticker-track">
					<?php for ( $i = 0; $i < 2; $i++ ) : ?>
						<?php foreach ( $announcements as $ann ) : ?>
							<span class="esk-ticker-item">
								<b><?php echo esc_html( wp_trim_words( (string) $ann->title, 8 ) ); ?></b>
								<?php if ( $ann->body ) : ?>
									<span aria-hidden="true">·</span>
									<?php echo esc_html( wp_trim_words( wp_strip_all_tags( (string) $ann->body ), 14 ) ); ?>
								<?php endif; ?>
							</span>
						<?php endforeach; ?>
					<?php endfor; ?>
				</div>
			</div>
		</div>
	</div>
<?php endif; ?>

<?php if ( $admissions_open ) : ?>
	<div class="esk-admissions-bar">
		<div class="esk-container esk-admissions-bar-inner">
			<span><?php echo esc_html( $ad_bar_title ); ?></span>
			<a href="<?php echo esc_url( home_url( '/admission/' ) ); ?>"><?php echo esc_html( $ad_bar_cta ); ?> &rarr;</a>
		</div>
	</div>
<?php endif; ?>

<header class="site-header sticky top-0 z-50 w-full border-b border-slate-200/80 bg-white/95 backdrop-blur-md transition-shadow duration-300">
	<div class="mx-auto flex max-w-7xl items-center justify-between gap-2 px-4 py-3 sm:py-4">
		<?php
		$brand_parts = preg_split( '/\s+/', trim( $school_name ), 2 );
		$brand_first = $brand_parts[0] ?? $school_name;
		$brand_rest  = $brand_parts[1] ?? '';
		?>
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="flex min-w-0 items-center gap-2 no-underline sm:gap-3">
			<?php if ( $has_logo ) : ?>
				<img src="<?php echo esc_url( get_theme_mod( 'esk_custom_logo', '' ) ); ?>" alt="<?php echo esc_attr( $school_name ); ?>" width="120" height="48" class="h-9 w-auto max-h-10 max-w-[8rem] shrink-0 object-contain sm:h-10 sm:max-h-12 sm:max-w-[10rem] md:max-w-[12rem]">
			<?php else : ?>
				<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/logo.svg' ); ?>" alt="<?php echo esc_attr( $school_name ); ?>" width="120" height="48" class="h-9 w-auto max-h-10 max-w-[8rem] shrink-0 object-contain sm:h-10 sm:max-h-12 sm:max-w-[10rem] md:max-w-[12rem]">
			<?php endif; ?>
			<span class="truncate text-lg font-bold leading-tight text-blue-700 sm:text-2xl md:text-3xl">
				<?php echo esc_html( $brand_first ); ?><?php if ( $brand_rest ) : ?><span class="text-orange-500"><?php echo esc_html( ' ' . $brand_rest ); ?></span><?php endif; ?>
			</span>
		</a>

		<div class="flex items-center gap-1">
			<button type="button" data-search-open aria-label="Search" class="esk-search-toggle inline-flex items-center justify-center rounded-md border border-gray-200 bg-white p-2 text-gray-500 transition hover:bg-blue-50 hover:text-blue-700 min-[1367px]:hidden">
				<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
			</button>
			<button type="button" data-menu-open aria-controls="site-nav-panel" aria-expanded="false" aria-label="<?php echo esc_attr( (string) esk_site_ui( 'nav.menu', '' ) ); ?>" class="esk-hamburger inline-flex items-center justify-center gap-2 rounded-md border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 min-[1367px]:hidden">
				<svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16M4 12h16M4 17h16"/></svg>
				<span class="hidden sm:inline"><?php echo esc_html( (string) esk_site_ui( 'nav.menu', '' ) ); ?></span>
			</button>
		</div>

		<nav class="hidden items-center gap-1 min-[1367px]:flex" aria-label="<?php echo esc_attr( (string) esk_site_ui( 'nav.menu', '' ) ); ?>">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="rounded-md px-3 py-2 text-sm font-medium transition-colors whitespace-nowrap <?php echo is_front_page() ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-blue-50 hover:text-blue-700'; ?>"><?php echo esc_html( (string) esk_site_ui( 'nav.home', '' ) ); ?></a>

			<?php foreach ( $nav_items as $nav_item ) : if ( empty( $nav_item['children'] ) ) { continue; } ?>
				<div class="relative" data-site-nav-dropdown>
					<button type="button" data-site-nav-dropdown-trigger aria-haspopup="true" aria-expanded="false" class="inline-flex items-center gap-1 rounded-md px-3 py-2 text-sm font-medium transition-colors whitespace-nowrap text-gray-700 hover:bg-blue-50 hover:text-blue-700">
						<span><?php echo esc_html( (string) esk_site_ui( $nav_item['label'], '' ) ); ?></span>
						<svg class="h-3.5 w-3.5 transition-transform duration-200" data-site-nav-caret fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
					</button>
					<div data-site-nav-dropdown-panel class="invisible absolute right-0 top-full z-50 mt-1 min-w-[14rem] origin-top-right translate-y-1 rounded-lg border border-gray-100 bg-white p-2 opacity-0 shadow-lg ring-1 ring-black/5 transition-all duration-150 data-[open=true]:visible data-[open=true]:translate-y-0 data-[open=true]:opacity-100" role="menu" aria-label="<?php echo esc_attr( (string) esk_site_ui( $nav_item['label'], '' ) ); ?>">
						<ul class="space-y-0.5">
							<?php foreach ( $nav_item['children'] as $child ) : ?>
								<li role="none">
									<a href="<?php echo esc_url( $child['url'] ); ?>" role="menuitem" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm transition text-gray-700 hover:bg-blue-50 hover:text-blue-700">
										<svg class="h-4 w-4 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
										<span class="flex-1"><?php echo esc_html( (string) esk_site_ui( $child['label'], '' ) ); ?></span>
									</a>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				</div>
			<?php endforeach; ?>

			<button type="button" data-search-open aria-label="Search" class="esk-search-toggle rounded-md p-2 text-gray-500 transition hover:bg-blue-50 hover:text-blue-700">
				<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
			</button>

			<?php if ( $is_logged_in ) : ?>
				<a href="<?php echo esc_url( current_user_can( 'manage_options' ) ? esk_dashboard_url( 'esk-dashboard' ) : $portal_url ); ?>" class="ml-1 inline-flex items-center justify-center rounded-md border-2 border-blue-600 bg-white px-3 py-2 text-sm font-semibold text-blue-700 shadow-sm transition hover:bg-blue-50 whitespace-nowrap"><?php echo esc_html( (string) esk_site_ui( 'nav.dashboard', __( 'Dashboard', 'eskoofy' ) ) ); ?></a>
				<a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>" class="ml-1 inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-800 shadow-sm transition hover:bg-gray-50 whitespace-nowrap"><?php echo esc_html( (string) esk_site_ui( 'nav.logout', __( 'Log out', 'eskoofy' ) ) ); ?></a>
			<?php else : ?>
				<a href="<?php echo esc_url( home_url( '/login/' ) ); ?>" class="ml-1 inline-flex items-center justify-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 whitespace-nowrap"><?php echo esc_html( (string) esk_site_ui( 'nav.login', __( 'Login', 'eskoofy' ) ) ); ?></a>
				<a href="<?php echo esc_url( $portal_url ); ?>" class="ml-1 inline-flex items-center justify-center rounded-md border-2 border-blue-600 bg-white px-3 py-2 text-sm font-semibold text-blue-700 shadow-sm transition hover:bg-blue-50 whitespace-nowrap"><?php echo esc_html( (string) esk_site_ui( 'nav.portal', __( 'Portal', 'eskoofy' ) ) ); ?></a>
			<?php endif; ?>
		</nav>
	</div>
</header>

<div class="esk-search-overlay" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr( (string) esk_site_ui( 'nav.search_label', '' ) ); ?>">
	<button type="button" class="esk-close-search" aria-label="<?php echo esc_attr__( 'Close search', 'eskoofy' ); ?>" data-search-close>&times;</button>
	<form role="search" method="get" class="esk-search-box" action="<?php echo esc_url( home_url( '/' ) ); ?>">
		<label class="screen-reader-text" for="esk-search-input"><?php echo esc_html( (string) esk_site_ui( 'nav.search_label', '' ) ); ?></label>
		<input id="esk-search-input" type="search" class="esk-search-input" name="s" placeholder="<?php echo esc_attr( (string) esk_site_ui( 'nav.search_placeholder', '' ) ); ?>" autocomplete="off">
		<button type="submit" class="esk-search-submit" aria-label="<?php echo esc_attr( (string) esk_site_ui( 'nav.search_label', '' ) ); ?>">
			<?php echo $esc( $svgs['search'] ); // phpcs:ignore ?>
		</button>
	</form>
</div>

<aside class="esk-mobile-panel" aria-label="<?php echo esc_attr( (string) esk_site_ui( 'nav.menu', '' ) ); ?>">
	<div class="esk-panel-head">
		<span class="esk-brand-name"><?php echo esc_html( $school_name ); ?></span>
		<button type="button" class="esk-panel-close" aria-label="<?php echo esc_attr__( 'Close menu', 'eskoofy' ); ?>">&times;</button>
	</div>
	<?php
	if ( has_nav_menu( 'primary' ) ) {
		wp_nav_menu(
			array(
				'theme_location' => 'primary',
				'container'      => false,
				'menu_class'     => 'esk-panel-menu',
				'fallback_cb'    => false,
			)
		);
	} else {
		echo '<ul class="esk-panel-menu">';
		$render_nav( $nav_items );
		echo '</ul>';
	}
	?>
	<div class="esk-panel-search">
		<form role="search" method="get" class="esk-search-box" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<label class="screen-reader-text" for="esk-mobile-search"><?php echo esc_html( (string) esk_site_ui( 'nav.search_label', '' ) ); ?></label>
			<input id="esk-mobile-search" type="search" class="esk-search-input" name="s" placeholder="<?php echo esc_attr( (string) esk_site_ui( 'nav.search_placeholder', '' ) ); ?>" autocomplete="off">
			<button type="submit" class="esk-search-submit" aria-label="<?php echo esc_attr( (string) esk_site_ui( 'nav.search_label', '' ) ); ?>">
				<?php echo $esc( $svgs['search'] ); // phpcs:ignore ?>
			</button>
		</form>
	</div>
	<?php if ( $phone || $email || $address ) : ?>
		<div class="esk-panel-contact">
			<?php if ( $phone ) : ?>
				<a href="<?php echo esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', $phone ) ); ?>"><?php echo $esc( $svgs['phone'] ); // phpcs:ignore ?> <?php echo esc_html( $phone ); ?></a>
			<?php endif; ?>
			<?php if ( $email ) : ?>
				<a href="<?php echo esc_url( 'mailto:' . $email ); ?>"><?php echo $esc( $svgs['mail'] ); // phpcs:ignore ?> <?php echo esc_html( $email ); ?></a>
			<?php endif; ?>
			<?php if ( $address ) : ?>
				<span><?php echo $esc( $svgs['pin'] ); // phpcs:ignore ?> <?php echo esc_html( $address ); ?></span>
			<?php endif; ?>
		</div>
	<?php endif; ?>
	<div class="esk-panel-actions">
		<?php if ( $is_logged_in ) : ?>
			<a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>" class="esk-btn esk-btn-plain"><?php echo esc_html( (string) esk_site_ui( 'nav.logout', '' ) ); ?></a>
		<?php else : ?>
			<a href="<?php echo esc_url( home_url( '/login/' ) ); ?>" class="esk-btn esk-btn-plain"><?php echo esc_html( (string) esk_site_ui( 'nav.login', '' ) ); ?></a>
		<?php endif; ?>
		<a href="<?php echo esc_url( $portal_url ); ?>" class="esk-btn esk-btn-accent"><?php echo esc_html( (string) esk_site_ui( 'nav.portal', '' ) ); ?></a>
		<a href="<?php echo esc_url( add_query_arg( 'esk_lang', $target_locale ) ); ?>" class="esk-btn esk-btn-plain"><?php echo esc_html( $target_label ); ?></a>
	</div>
</aside>

<div class="esk-overlay-backdrop" data-menu-backdrop></div>