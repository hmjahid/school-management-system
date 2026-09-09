<?php
/**
 * Front page template — Eskoofy WordPress theme.
 *
 * Used when "Static page" is set as the front page in Settings > Reading.
 * Provides a flexible landing page with hero + optional content blocks.
 *
 * @package Eskoofy
 */

declare(strict_types=1);

get_header();
?>

<section class="eskoofy-hero">
	<div class="eskoofy-container">
		<h1 class="eskoofy-hero-title">
			<?php echo esc_html( get_bloginfo( 'name' ) ); ?>
		</h1>
		<?php $eskoofy_tagline = get_bloginfo( 'description', 'display' ); ?>
		<?php if ( $eskoofy_tagline ) : ?>
			<p class="eskoofy-hero-tagline"><?php echo esc_html( $eskoofy_tagline ); ?></p>
		<?php endif; ?>
	</div>
</section>

<div class="eskoofy-container eskoofy-content-area">
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<div class="eskoofy-entry-content">
			<?php the_content(); ?>
		</div>
	<?php endwhile; ?>
</div>

<?php
get_footer();
