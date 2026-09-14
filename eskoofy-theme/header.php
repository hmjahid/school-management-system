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
$is_logged_in     = is_user_logged_in();

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
		'label'    => 'nav.group.news',
		'url'      => home_url( '/news/' ),
		'children' => array(
			array( 'label' => 'nav.news',    'url' => home_url( '/news/' ) ),
			array( 'label' => 'nav.notices', 'url' => home_url( '/notices/' ) ),
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
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="icon" type="image/svg+xml" href="<?php echo esc_url( get_template_directory_uri() . '/assets/favicon.svg' ); ?>">
	<link rel="apple-touch-icon" href="<?php echo esc_url( get_template_directory_uri() . '/assets/icons/apple-touch-icon.png' ); ?>">
	<link rel="manifest" href="<?php echo esc_url( home_url( '/manifest.json' ) ); ?>">
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

<?php if ( $phone || $email || $address ) : ?>
	<div class="esk-topbar">
		<div class="esk-container esk-topbar-inner">
			<div class="esk-topbar-contact">
				<?php if ( $phone ) : ?>
					<a href="<?php echo esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', $phone ) ); ?>"><?php echo $esc( $svgs['phone'] ); // phpcs:ignore ?> <?php echo esc_html( $phone ); ?></a>
				<?php endif; ?>
				<?php if ( $email ) : ?>
					<a href="<?php echo esc_url( 'mailto:' . $email ); ?>"><?php echo $esc( $svgs['mail'] ); // phpcs:ignore ?> <?php echo esc_html( $email ); ?></a>
				<?php endif; ?>
				<?php if ( $address ) : ?>
					<span class="esk-topbar-address"><?php echo $esc( $svgs['pin'] ); // phpcs:ignore ?> <?php echo esc_html( $address ); ?></span>
				<?php endif; ?>
			</div>
			<div class="esk-topbar-meta">
				<a href="<?php echo esc_url( $portal_url ); ?>"><?php echo $esc( $svgs['user'] ); // phpcs:ignore ?> <?php echo esc_html( (string) esk_site_ui( 'nav.portal', '' ) ); ?></a>
				<?php if ( $is_logged_in ) : ?>
					<a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>"><?php echo esc_html( (string) esk_site_ui( 'nav.logout', '' ) ); ?></a>
				<?php else : ?>
					<a href="<?php echo esc_url( home_url( '/login/' ) ); ?>"><?php echo esc_html( (string) esk_site_ui( 'nav.login', '' ) ); ?></a>
				<?php endif; ?>
				<a class="esk-lang-toggle" href="<?php echo esc_url( add_query_arg( 'esk_lang', $target_locale ) ); ?>" rel="nofollow"><?php echo esc_html( $target_label ); ?></a>
			</div>
		</div>
	</div>
<?php endif; ?>

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

<header id="masthead" class="esk-header" role="banner">
	<div class="esk-container esk-header-inner">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="esk-brand" rel="home">
			<?php if ( $has_logo ) : ?>
				<img src="<?php echo esc_url( get_theme_mod( 'esk_custom_logo', '' ) ); ?>" alt="<?php echo esc_attr( $school_name ); ?>" class="esk-brand-logo-img">
			<?php else : ?>
				<span class="esk-brand-logo" aria-hidden="true"><?php echo esc_html( $initials ); ?></span>
			<?php endif; ?>
			<span class="esk-brand-text">
				<span class="esk-brand-name"><?php echo esc_html( $school_name ); ?></span>
				<?php if ( $school_tagline ) : ?>
					<span class="esk-brand-tagline"><?php echo esc_html( $school_tagline ); ?></span>
				<?php endif; ?>
			</span>
		</a>

		<nav class="esk-nav" role="navigation" aria-label="<?php echo esc_attr( (string) esk_site_ui( 'nav.menu', '' ) ); ?>">
			<?php
			if ( has_nav_menu( 'primary' ) ) {
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'container'      => false,
						'menu_class'     => 'esk-nav-menu',
						'fallback_cb'    => false,
					)
				);
			} else {
				echo '<ul class="esk-nav-menu">';
				$render_nav( $nav_items );
				echo '</ul>';
			}
			?>
		</nav>

		<div class="esk-header-actions">
			<button type="button" class="esk-icon-btn esk-search-toggle" aria-label="<?php echo esc_attr( (string) esk_site_ui( 'nav.search_label', '' ) ); ?>" data-search-open>
				<?php echo $esc( $svgs['search'] ); // phpcs:ignore ?>
			</button>
			<button type="button" class="esk-icon-btn esk-dark-toggle" aria-label="<?php echo esc_attr__( 'Toggle dark mode', 'eskoofy' ); ?>">
				<?php echo $esc( $svgs['moon'] ); // phpcs:ignore ?>
			</button>
			<button type="button" class="esk-icon-btn esk-hamburger" aria-label="<?php echo esc_attr( (string) esk_site_ui( 'nav.menu', '' ) ); ?>" aria-expanded="false" data-menu-open>
				<span></span><span></span><span></span>
			</button>
		</div>
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
	<div class="esk-panel-actions">
		<a href="<?php echo esc_url( $portal_url ); ?>" class="esk-btn esk-btn-accent"><?php echo esc_html( (string) esk_site_ui( 'nav.portal', '' ) ); ?></a>
		<a href="<?php echo esc_url( add_query_arg( 'esk_lang', $target_locale ) ); ?>" class="esk-btn esk-btn-plain"><?php echo esc_html( $target_label ); ?></a>
	</div>
</aside>

<div class="esk-overlay-backdrop" data-menu-backdrop></div>