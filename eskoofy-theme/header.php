<?php
/**
 * Header template — Eskoofy WordPress theme.
 *
 * @package Eskoofy
 */

declare(strict_types=1);
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="eskoofy-site-header" role="banner">
	<div class="eskoofy-container">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="eskoofy-site-logo" rel="home">
			<?php bloginfo( 'name' ); ?>
		</a>

		<nav class="eskoofy-main-nav" role="navigation" aria-label="<?php esc_attr_e( 'Primary menu', 'eskoofy' ); ?>">
			<?php
			if ( has_nav_menu( 'primary' ) ) {
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'container'      => false,
						'menu_class'     => 'eskoofy-menu',
						'fallback_cb'    => false,
					)
				);
			}
			?>
		</nav>
	</div>
</header>

<main class="eskoofy-site-main" role="main">
