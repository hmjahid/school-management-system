<?php
/**
 * Single news template — reads from esk_news by slug.
 *
 * @package Eskoofy
 */

declare(strict_types=1);

get_header();
?>
<div class="eskoofy-container">
	<?php
	global $wpdb;
	$slug = get_query_var( 'esk_news' );
	if ( ! $slug && is_singular( 'esk_news' ) ) {
		$slug = get_post_field( 'post_name', get_queried_object_id() );
	}
	$news = $slug ? $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM {$wpdb->prefix}esk_news WHERE slug = %s AND deleted_at IS NULL",
		$slug
	) ) : null;

	if ( $news ) {
		echo '<article>';
		echo '<h1>' . esc_html( $news->title ) . '</h1>';
		echo '<small>' . esc_html( esk_date_format( $news->published_at ?? $news->created_at ) );
		if ( $news->author_name ) {
			echo ' &middot; ' . esc_html( $news->author_name );
		}
		echo '</small>';
		if ( $news->image_url ) {
			echo '<img src="' . esc_url( $news->image_url ) . '" alt="' . esc_attr( $news->title ) . '" style="max-width:100%;border-radius:6px;">';
		}
		echo '<div>' . wp_kses_post( $news->content ) . '</div>';
		echo '</article>';
	} else {
		echo '<p>' . esc_html__( 'News item not found.', 'eskoofy' ) . '</p>';
	}
	?>
</div>
<?php get_footer();