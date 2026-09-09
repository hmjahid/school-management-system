<?php
/**
 * Template Name: Academics
 *
 * @package Eskoofy
 */

declare(strict_types=1);

get_header();
?>
<div class="eskoofy-container">
	<?php
	global $wpdb;
	$rows = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}esk_website_contents WHERE page = 'academics' ORDER BY sort_order, section" );
	foreach ( $rows as $r ) {
		echo '<section style="margin-bottom:2rem;"><h2>' . esc_html( $r->title ) . '</h2>';
		echo wp_kses_post( $r->content );
		echo '</section>';
	}
	if ( empty( $rows ) ) {
		echo '<p>' . esc_html__( 'Academics information coming soon.', 'eskoofy' ) . '</p>';
	}
	?>
</div>
<?php get_footer();