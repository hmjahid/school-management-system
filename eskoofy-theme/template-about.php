<?php
/**
 * Template Name: About
 *
 * Reads content from esk_website_contents table for the "about" page.
 *
 * @package Eskoofy
 */

declare(strict_types=1);

get_header();
?>
<div class="eskoofy-container">
	<?php
	global $wpdb;
	$rows = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}esk_website_contents WHERE page = 'about' ORDER BY sort_order, section" );
	if ( empty( $rows ) ) {
		echo wp_kses_post( get_option( 'esk_about_page_content', '' ) );
	} else {
		foreach ( $rows as $r ) {
			echo '<section style="margin-bottom:2rem;"><h2>' . esc_html( $r->title ) . '</h2>';
			echo wp_kses_post( $r->content );
			echo '</section>';
		}
	}
	?>
</div>
<?php
get_footer();