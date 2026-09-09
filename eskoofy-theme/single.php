<?php
/**
 * Single post template — Eskoofy WordPress theme.
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
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'eskoofy-single' ); ?>>
			<header class="eskoofy-entry-header">
				<h1 class="eskoofy-entry-title"><?php the_title(); ?></h1>
				<div class="eskoofy-entry-meta">
					<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
						<?php echo esc_html( get_the_date() ); ?>
					</time>
					<span class="eskoofy-meta-sep" aria-hidden="true">&middot;</span>
					<span class="eskoofy-meta-author">
						<?php echo esc_html( get_the_author() ); ?>
					</span>
					<?php if ( has_category() ) : ?>
						<span class="eskoofy-meta-sep" aria-hidden="true">&middot;</span>
						<span class="eskoofy-meta-cats"><?php the_category( ', ' ); ?></span>
					<?php endif; ?>
				</div>
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

			<footer class="eskoofy-entry-footer">
				<?php the_tags( '<div class="eskoofy-tags">', ', ', '</div>' ); ?>
				<?php
				the_post_navigation(
					array(
						'prev_text' => '<span class="eskoofy-nav-label">' . esc_html__( 'Previous', 'eskoofy' ) . '</span> %title',
						'next_text' => '<span class="eskoofy-nav-label">' . esc_html__( 'Next', 'eskoofy' ) . '</span> %title',
					)
				);
				?>
			</footer>
		</article>

		<?php if ( comments_open() || get_comments_number() ) : ?>
			<?php comments_template(); ?>
		<?php endif; ?>
	<?php endwhile; ?>
</div>

<?php
get_sidebar();
get_footer();
