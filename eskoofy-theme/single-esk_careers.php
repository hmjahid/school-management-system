<?php
/**
 * Single career.
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
			<div><?php the_content(); ?></div>
			<p><a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>" class="esk-button esk-button-primary"><?php esc_html_e( 'Apply Now', 'eskoofy' ); ?></a></p>
		</article>
		<?php
	endwhile;
	?>
</div>
<?php get_footer();