<?php
/**
 * Front page template — Eskoofy WordPress theme.
 *
 * Used when "Static page" is set as the front page in Settings > Reading.
 * Provides a flexible landing page with hero + news, events, gallery blocks.
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
		<p class="eskoofy-hero-cta">
			<a href="<?php echo esc_url( home_url( '/admission/' ) ); ?>" class="esk-button esk-button-primary"><?php esc_html_e( 'Apply for Admission', 'eskoofy' ); ?></a>
		</p>
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

	<div class="eskoofy-blocks">
		<section class="eskoofy-block eskoofy-block-news">
			<h2><?php esc_html_e( 'Latest News', 'eskoofy' ); ?></h2>
			<?php echo do_shortcode( '[eskoofy_news_list count="3"]' ); ?>
		</section>

		<section class="eskoofy-block eskoofy-block-events">
			<h2><?php esc_html_e( 'Upcoming Events', 'eskoofy' ); ?></h2>
			<?php echo do_shortcode( '[eskoofy_events_list count="3"]' ); ?>
		</section>

		<section class="eskoofy-block eskoofy-block-gallery">
			<h2><?php esc_html_e( 'Gallery', 'eskoofy' ); ?></h2>
			<?php echo do_shortcode( '[eskoofy_gallery]' ); ?>
		</section>

		<section class="eskoofy-block eskoofy-block-contact">
			<h2><?php esc_html_e( 'Contact Us', 'eskoofy' ); ?></h2>
			<?php echo do_shortcode( '[eskoofy_contact_form]' ); ?>
		</section>
	</div>
</div>

<?php
get_footer();