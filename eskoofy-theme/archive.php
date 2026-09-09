<?php
/**
 * Archive template — Eskoofy WordPress theme.
 *
 * Used for category, tag, date, author, and custom post type archives.
 *
 * @package Eskoofy
 */

declare(strict_types=1);

get_header();
?>

<div class="eskoofy-container eskoofy-content-area">
	<header class="eskoofy-page-header">
		<?php the_archive_title( '<h1 class="eskoofy-archive-title">', '</h1>' ); ?>
		<?php the_archive_description( '<div class="eskoofy-archive-desc">', '</div>' ); ?>
	</header>

	<?php if ( have_posts() ) : ?>
		<div class="eskoofy-posts">
			<?php
			while ( have_posts() ) :
				the_post();
				?>
				<article id="post-<?php the_ID(); ?>" <?php post_class( 'eskoofy-post-summary' ); ?>>
					<h2 class="eskoofy-post-title">
						<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
					</h2>
					<div class="eskoofy-post-meta">
						<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
							<?php echo esc_html( get_the_date() ); ?>
						</time>
					</div>
					<div class="eskoofy-post-excerpt">
						<?php the_excerpt(); ?>
					</div>
				</article>
			<?php endwhile; ?>
		</div>

		<div class="eskoofy-pagination">
			<?php
			the_posts_pagination(
				array(
					'prev_text' => esc_html__( '&laquo; Previous', 'eskoofy' ),
					'next_text' => esc_html__( 'Next &raquo;', 'eskoofy' ),
				)
			);
			?>
		</div>
	<?php else : ?>
		<p><?php esc_html_e( 'No posts found.', 'eskoofy' ); ?></p>
	<?php endif; ?>
</div>

<?php
get_sidebar();
get_footer();
