<?php
/**
 * Archive template — Eskoofy WordPress theme.
 *
 * @package Eskoofy
 */

declare(strict_types=1);

get_header();

$archive_title = '';
if ( is_category() ) {
	$archive_title = single_cat_title( '', false );
} elseif ( is_tag() ) {
	$archive_title = single_tag_title( '', false );
} elseif ( is_author() ) {
	$archive_title = get_the_author();
} elseif ( is_year() ) {
	$archive_title = get_the_date( 'Y' );
} elseif ( is_month() ) {
	$archive_title = get_the_date( 'F Y' );
} elseif ( is_day() ) {
	$archive_title = get_the_date( 'F j, Y' );
} elseif ( is_search() ) {
	$archive_title = (string) esk_site_ui( 'search.heading', __( 'Search', 'eskoofy' ) );
} elseif ( is_post_type_archive() ) {
	$archive_title = post_type_archive_title( '', false );
} else {
	$archive_title = (string) esk_site_ui( 'single.back_to_list', __( 'Archives', 'eskoofy' ) );
}

get_template_part(
	'template-parts/inner-hero',
	null,
	array(
		'title'    => $archive_title,
		'subtitle' => '',
	)
);
?>
<div class="esk-page-sections">
	<div class="esk-container">
		<?php if ( have_posts() ) : ?>
			<div class="esk-posts">
				<?php
				while ( have_posts() ) :
					the_post();
					?>
					<article id="post-<?php the_ID(); ?>" <?php post_class( 'esk-page-section esk-search-item' ); ?>>
						<a href="<?php the_permalink(); ?>" class="esk-search-link">
							<span class="esk-search-date"><?php echo esc_html( get_the_date() ); ?></span>
							<h2 class="esk-search-title" style="font-size:1.125rem;"><?php the_title(); ?></h2>
							<div class="esk-search-excerpt"><?php the_excerpt(); ?></div>
						</a>
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
			<p class="esk-empty"><?php echo esc_html( (string) esk_site_ui( 'archive.no_posts', __( 'No posts found.', 'eskoofy' ) ) ); ?></p>
		<?php endif; ?>
	</div>
</div>
<?php
get_footer();