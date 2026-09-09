<?php
/**
 * Template Name: Faculty
 *
 * @package Eskoofy
 */

declare(strict_types=1);

get_header();
?>
<div class="eskoofy-container">
	<?php
	global $wpdb;
	$faculty = $wpdb->get_results(
		"SELECT t.*, u.display_name FROM {$wpdb->prefix}esk_teachers t
		JOIN {$wpdb->prefix}users u ON t.user_id = u.ID ORDER BY u.display_name"
	);
	if ( empty( $faculty ) ) {
		echo '<p>' . esc_html__( 'No faculty listed.', 'eskoofy' ) . '</p>';
	} else {
		echo '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:1rem;">';
		foreach ( $faculty as $f ) {
			echo '<div class="esk-card" style="text-align:center;"><h3>' . esc_html( $f->display_name ) . '</h3>';
			echo '<p>' . esc_html( $f->qualification ) . '</p></div>';
		}
		echo '</div>';
	}
	?>
</div>
<?php get_footer();