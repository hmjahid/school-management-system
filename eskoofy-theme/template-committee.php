<?php
/**
 * Template Name: Committee
 *
 * @package Eskoofy
 */

declare(strict_types=1);

get_header();
?>
<div class="eskoofy-container">
	<?php
	global $wpdb;
	$members = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}esk_committee_members WHERE is_active = 1 ORDER BY sort_order" );
	if ( empty( $members ) ) {
		echo '<p>' . esc_html__( 'No committee members listed.', 'eskoofy' ) . '</p>';
	} else {
		echo '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:1rem;">';
		foreach ( $members as $m ) {
			echo '<div class="esk-card" style="text-align:center;">';
			if ( $m->photo ) {
				echo '<img src="' . esc_url( $m->photo ) . '" alt="' . esc_attr( $m->name ) . '" style="width:100px;height:100px;border-radius:50%;object-fit:cover;">';
			}
			echo '<h3>' . esc_html( $m->name ) . '</h3><p>' . esc_html( $m->designation ) . '</p>';
			if ( $m->bio ) {
				echo '<p>' . esc_html( wp_trim_words( $m->bio, 20 ) ) . '</p>';
			}
			echo '</div>';
		}
		echo '</div>';
	}
	?>
</div>
<?php get_footer();