<?php
/**
 * Page template — Eskoofy WordPress theme.
 *
 * @package Eskoofy
 */

declare(strict_types=1);

get_header();

while ( have_posts() ) :
	the_post();

	get_template_part(
		'template-parts/inner-hero',
		null,
		array(
			'title'    => get_the_title(),
			'subtitle' => get_the_excerpt(),
		)
	);
	?>
	<div class="esk-page-sections">
		<div class="esk-container">
			<article id="page-<?php the_ID(); ?>" <?php post_class( 'esk-page-section' ); ?>>
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
			</article>
		</div>
	</div>
<?php endwhile; ?>

<?php
get_footer();