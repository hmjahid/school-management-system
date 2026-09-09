<?php
/**
 * Search results template — Eskoofy WordPress theme.
 *
 * @package Eskoofy
 */

declare(strict_types=1);

get_header();
?>

<div class="eskoofy-container eskoofy-content-area">
	<header class="eskoofy-page-header">
		<h1 class="eskoofy-search-title">
			<?php
			printf(
				/* translators: %s: search query */
				esc_html__( 'Search results for: %s', 'eskoofy' ),
				'<span class="eskoofy-search-query">' . esc_html( get_search_query() ) . '</span>'
			);
			?>
		</h1>
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
		<p><?php esc_html_e( 'No results found. Try a different search term.', 'eskoofy' ); ?></p>
	<?php endif; ?>
</div>

<?php
get_footer();
