<?php
/**
 * 404 error page — Eskoofy WordPress theme.
 *
 * @package Eskoofy
 */

declare(strict_types=1);

get_header();
?>

<div class="eskoofy-container eskoofy-content-area">
	<section class="eskoofy-404">
		<h1 class="eskoofy-404-title"><?php esc_html_e( 'Page not found', 'eskoofy' ); ?></h1>
		<p class="eskoofy-404-text">
			<?php esc_html_e( 'The page you are looking for might have been moved or does not exist.', 'eskoofy' ); ?>
		</p>
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="eskoofy-button">
			<?php esc_html_e( 'Back to home', 'eskoofy' ); ?>
		</a>
	</section>
</div>

<?php
get_footer();
