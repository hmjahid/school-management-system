<?php
/**
 * Template Name: Search Results
 *
 * @package Eskoofy
 */

declare(strict_types=1);

get_header();
?>
<div class="eskoofy-container">
	<h1><?php esc_html_e( 'Search', 'eskoofy' ); ?></h1>
	<form method="get" role="search" class="esk-form">
		<input type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Search...', 'eskoofy' ); ?>" style="min-width:300px;">
		<button type="submit" class="esk-button esk-button-primary"><?php esc_html_e( 'Search', 'eskoofy' ); ?></button>
	</form>

	<?php if ( have_posts() ) : ?>
		<div class="esk-search-results" style="margin-top:1.5rem;">
			<?php while ( have_posts() ) : the_post(); ?>
				<article style="padding:1rem;border-bottom:1px solid #e5e7eb;">
					<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
					<small><?php echo esc_html( get_the_date() ); ?></small>
					<?php the_excerpt(); ?>
				</article>
			<?php endwhile; ?>
		</div>
	<?php else : ?>
		<p><?php esc_html_e( 'Nothing found.', 'eskoofy' ); ?></p>
	<?php endif; ?>
</div>
<?php get_footer();