<?php
/**
 * Template Name: Gallery
 *
 * @package Eskoofy
 */

declare(strict_types=1);

get_header();
?>
<div class="eskoofy-container">
	<?php
	$category = isset( $_GET['category'] ) ? sanitize_text_field( $_GET['category'] ) : '';
	echo do_shortcode( '[eskoofy_gallery category="' . esc_attr( $category ) . '"]' );
	?>
</div>
<?php
get_footer();
