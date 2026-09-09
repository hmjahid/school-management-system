<?php
/**
 * Single gallery item.
 *
 * @package Eskoofy
 */

declare(strict_types=1);

get_header();
?>
<div class="eskoofy-container">
	<?php
	while ( have_posts() ) :
		the_post();
		if ( has_post_thumbnail() ) {
			the_post_thumbnail( 'large' );
		}
		?>
		<article>
			<h1><?php the_title(); ?></h1>
			<div><?php the_content(); ?></div>
		</article>
		<?php
	endwhile;
	?>
</div>
<?php get_footer();