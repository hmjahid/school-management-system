<?php
/**
 * News archive template — reads from esk_news table.
 *
 * @package Eskoofy
 */

declare(strict_types=1);

get_header();
?>
<div class="eskoofy-container">
	<h1><?php esc_html_e( 'News', 'eskoofy' ); ?></h1>

	<?php
	global $wpdb;
	$news = $wpdb->get_results(
		"SELECT * FROM {$wpdb->prefix}esk_news
		WHERE deleted_at IS NULL ORDER BY published_at DESC, id DESC LIMIT 50"
	);

	if ( empty( $news ) ) {
		echo '<p>' . esc_html__( 'No news yet.', 'eskoofy' ) . '</p>';
	} else {
		foreach ( $news as $item ) {
			echo '<article style="padding:1.5rem 0;border-bottom:1px solid #e5e7eb;">';
			if ( $item->image_url ) {
				echo '<img src="' . esc_url( $item->image_url ) . '" alt="' . esc_attr( $item->title ) . '" style="max-width:400px;height:auto;border-radius:6px;">';
			}
			echo '<h2>' . esc_html( $item->title ) . '</h2>';
			echo '<small>' . esc_html( esk_date_format( $item->published_at ?? $item->created_at ) ) . '</small>';
			echo '<div>' . wp_kses_post( wp_trim_words( $item->content, 50 ) ) . '</div>';
			echo '</article>';
		}
	}
	?>
</div>
<?php get_footer();