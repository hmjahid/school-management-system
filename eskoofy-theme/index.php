<?php
/**
 * Main index template — fallback for any request not matched by a more specific template.
 *
 * @package Eskoofy
 */

get_header();

if ( is_home() || is_front_page() ) {
	the_post();
	the_title( '<h1>', '</h1>' );
	the_content();
} elseif ( have_posts() ) {
	while ( have_posts() ) {
		the_post();
		the_title( '<h2>', '</h2>' );
		the_content();
	}
} else {
	echo '<p>' . esc_html__( 'Nothing found.', 'eskoofy' ) . '</p>';
}

get_footer();