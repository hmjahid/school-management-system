<?php
/**
 * Gallery archive — reads from esk_galleries table.
 *
 * @package Eskoofy
 */

declare(strict_types=1);

get_header();
?>
<div class="eskoofy-container">
	<h1><?php esc_html_e( 'Gallery', 'eskoofy' ); ?></h1>

	<?php
	global $wpdb;
	$items = $wpdb->get_results(
		"SELECT * FROM {$wpdb->prefix}esk_galleries WHERE is_published = 1 ORDER BY id DESC"
	);

	if ( empty( $items ) ) {
		echo '<p>' . esc_html__( 'No images yet.', 'eskoofy' ) . '</p>';
	} else {
		echo '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:1rem;">';
		foreach ( $items as $img ) {
			echo '<div style="border-radius:6px;overflow:hidden;background:#f9fafb;">';
			echo '<img src="' . esc_url( $img->image_path ) . '" alt="' . esc_attr( $img->title ) . '" style="width:100%;height:200px;object-fit:cover;">';
			echo '<div style="padding:0.5rem;"><strong>' . esc_html( $img->title ) . '</strong></div>';
			echo '</div>';
		}
		echo '</div>';
	}
	?>
</div>
<?php get_footer();