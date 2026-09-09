<?php
/**
 * Notices archive — reads from esk_notices table.
 *
 * @package Eskoofy
 */

declare(strict_types=1);

get_header();
?>
<div class="eskoofy-container">
	<h1><?php esc_html_e( 'Notices', 'eskoofy' ); ?></h1>

	<?php
	global $wpdb;
	$notices = $wpdb->get_results(
		"SELECT n.*, u.display_name AS author_name
		FROM {$wpdb->prefix}esk_notices n
		JOIN {$wpdb->prefix}users u ON n.created_by = u.ID
		ORDER BY n.pinned DESC, n.id DESC LIMIT 50"
	);

	if ( empty( $notices ) ) {
		echo '<p>' . esc_html__( 'No notices.', 'eskoofy' ) . '</p>';
	} else {
		foreach ( $notices as $n ) {
			echo '<article style="padding:1.5rem 0;border-bottom:1px solid #e5e7eb;' . ( $n->pinned ? 'background:#fffbeb;' : '' ) . '">';
			echo '<h2>' . ( $n->pinned ? '📌 ' : '' ) . esc_html( $n->title ) . '</h2>';
			echo '<small>' . esc_html( esk_date_format( $n->created_at ) );
			if ( $n->author_name ) {
				echo ' &middot; ' . esc_html( $n->author_name );
			}
			echo '</small>';
			echo '<div>' . wp_kses_post( $n->content ) . '</div>';
			echo '</article>';
		}
	}
	?>
</div>
<?php get_footer();