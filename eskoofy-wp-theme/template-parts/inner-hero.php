<?php
/**
 * Inner page hero.
 *
 * Expected variables: $title (string), $subtitle (string, optional).
 *
 * @package Eskoofy
 */

declare(strict_types=1);

$args = is_array( $args ) ? $args : array();

$title          = $args['title'] ?? get_the_title();
$subtitle       = $args['subtitle'] ?? '';
$breadcrumb_home = esk_site_ui( 'pages.breadcrumb_home', 'Home' );
?>
<section class="esk-inner-hero">
	<div class="esk-inner-hero-bg" aria-hidden="true"></div>
	<div class="esk-container">
		<nav class="esk-breadcrumb" aria-label="<?php echo esc_attr( $breadcrumb_home ); ?>">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( $breadcrumb_home ); ?></a>
			<span class="esk-breadcrumb-sep" aria-hidden="true">/</span>
			<span aria-current="page"><?php echo esc_html( $title ); ?></span>
		</nav>
		<h1 class="esk-inner-hero-title"><?php echo esc_html( $title ); ?></h1>
		<?php if ( '' !== $subtitle ) : ?>
			<p class="esk-inner-hero-subtitle"><?php echo esc_html( $subtitle ); ?></p>
		<?php endif; ?>
	</div>
</section>