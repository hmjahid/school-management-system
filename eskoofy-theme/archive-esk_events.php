<?php
/**
 * Events archive.
 *
 * @package Eskoofy
 */

declare(strict_types=1);

get_header();
?>
<div class="eskoofy-container">
	<h1><?php esc_html_e( 'Events', 'eskoofy' ); ?></h1>

	<?php
	global $wpdb;
	$events = $wpdb->get_results(
		"SELECT * FROM {$wpdb->prefix}esk_events
		WHERE status = 'published' AND deleted_at IS NULL ORDER BY start_date ASC LIMIT 50"
	);

	if ( empty( $events ) ) {
		echo '<p>' . esc_html__( 'No events.', 'eskoofy' ) . '</p>';
	} else {
		foreach ( $events as $e ) {
			echo '<article style="padding:1.5rem 0;border-bottom:1px solid #e5e7eb;">';
			echo '<h2>' . esc_html( $e->title ) . '</h2>';
			echo '<p><strong>' . esc_html( esk_date_format( $e->start_date, 'd M, Y g:i A' ) ) . '</strong>';
			if ( $e->location ) {
				echo ' &middot; ' . esc_html( $e->location );
			}
			echo '</p>';
			echo '<div>' . wp_kses_post( wp_trim_words( $e->description, 50 ) ) . '</div>';
			echo '</article>';
		}
	}
	?>
</div>
<?php get_footer();