<?php
/**
 * Single post template — Eskoofy WordPress theme.
 *
 * @package Eskoofy
 */

declare(strict_types=1);

get_header();
?>
<div class="esk-page-sections">
	<div class="esk-container">
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article id="post-<?php the_ID(); ?>" <?php post_class( 'esk-page-section' ); ?>>
				<header style="margin-bottom:1.25rem;">
					<nav class="esk-breadcrumb" aria-label="Breadcrumb">
						<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( (string) esk_site_ui( 'pages.breadcrumb_home', __( 'Home', 'eskoofy' ) ) ); ?></a>
						<span class="esk-breadcrumb-sep" aria-hidden="true">/</span>
						<span aria-current="page"><?php echo esc_html( (string) esk_site_ui( 'single.back_to_list', __( 'Post', 'eskoofy' ) ) ); ?></span>
					</nav>
					<h1 class="esk-page-section-title" style="font-size:1.75rem; margin-top:0.75rem;"><?php the_title(); ?></h1>
					<div class="esk-search-meta" style="font-size:0.875rem; color:#64748b; margin-top:0.375rem;">
						<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
						<span aria-hidden="true" style="margin:0 0.25rem;">·</span>
						<span><?php echo esc_html( (string) esk_site_ui( 'single.author', __( 'Author', 'eskoofy' ) ) ); ?>: <?php echo esc_html( get_the_author() ); ?></span>
						<?php if ( has_category() ) : ?>
							<span aria-hidden="true" style="margin:0 0.25rem;">·</span>
							<span><?php echo esc_html( (string) esk_site_ui( 'single.categories', __( 'Categories', 'eskoofy' ) ) ); ?>: <?php the_category( ', ' ); ?></span>
						<?php endif; ?>
					</div>
				</header>

				<div class="esk-page-section-content">
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

				<footer style="margin-top:1.5rem; padding-top:1rem; border-top:1px solid var(--esk-border, rgba(148, 163, 184, 0.3)); font-size:0.875rem;">
					<?php
					$tags_list = get_the_tags();
					if ( ! empty( $tags_list ) ) :
						?>
						<div style="margin-bottom:0.75rem;">
							<span style="font-weight:600;"><?php echo esc_html( (string) esk_site_ui( 'single.tags', __( 'Tags', 'eskoofy' ) ) ); ?>:</span>
							<?php the_tags( '<span style="margin-left:0.25rem;">', ', ', '</span>' ); ?>
						</div>
					<?php endif; ?>
					<?php
					the_post_navigation(
						array(
							'prev_text' => '<span style="display:block; font-size:0.75rem; color:#64748b;">' . esc_html( (string) esk_site_ui( 'single.previous', __( 'Previous', 'eskoofy' ) ) ) . '</span> %title',
							'next_text' => '<span style="display:block; font-size:0.75rem; color:#64748b;">' . esc_html( (string) esk_site_ui( 'single.next', __( 'Next', 'eskoofy' ) ) ) . '</span> %title',
						)
					);
					?>
				</footer>
			</article>
		<?php endwhile; ?>
	</div>
</div>
<?php
get_footer();