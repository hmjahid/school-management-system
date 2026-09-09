<?php
/**
 * Single notice.
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
		?>
		<article>
			<h1><?php the_title(); ?></h1>
			<small><?php echo esc_html( get_the_date() ); ?></small>
			<div><?php the_content(); ?></div>
		</article>
		<?php
	endwhile;
	?>
</div>
<?php get_footer();