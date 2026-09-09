<?php
/**
 * Template Name: Terms & Conditions
 *
 * @package Eskoofy
 */

declare(strict_types=1);

get_header();
?>
<div class="eskoofy-container">
	<?php
	$terms = get_option( 'esk_default_terms', '' );
	if ( $terms ) {
		echo wp_kses_post( $terms );
	} else {
		echo '<p>' . esc_html__( 'Terms & Conditions will be published soon.', 'eskoofy' ) . '</p>';
	}
	?>
</div>
<?php get_footer();