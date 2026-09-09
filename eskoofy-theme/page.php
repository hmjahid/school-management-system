<?php
/**
 * Page template — Eskoofy WordPress theme.
 *
 * @package Eskoofy
 */

declare(strict_types=1);

get_header();
?>

<div class="eskoofy-container eskoofy-content-area">
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<article id="page-<?php the_ID(); ?>" <?php post_class( 'eskoofy-page' ); ?>>
			<header class="eskoofy-entry-header">
				<h1 class="eskoofy-entry-title"><?php the_title(); ?></h1>
			</header>

			<div class="eskoofy-entry-content">
				<?php the_content(); ?>
				<?php
				wp_link_pages(
					array(
						'before' => '<div class="eskoofy-page-links">',
						'after'  => '</div>',
					)
				);
				?>
			</div>
		</article>

		<?php if ( comments_open() || get_comments_number() ) : ?>
			<?php comments_template(); ?>
		<?php endif; ?>
	<?php endwhile; ?>
</div>

<?php
get_sidebar();
get_footer();
