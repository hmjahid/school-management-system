<?php
/**
 * Template Name: Gallery
 *
 * Shows published photos from the esk_galleries table.
 *
 * @package Eskoofy
 */

declare(strict_types=1);

get_header();

$photos = array();
global $wpdb;
$table = $wpdb->prefix . 'esk_galleries';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
$exists = (string) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
if ( '' !== $exists ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$photos = (array) $wpdb->get_results( "SELECT title, description, image_path, category FROM {$table} WHERE is_published = 1 ORDER BY updated_at DESC, id DESC" );
}

$empty = (string) esk_site_ui( 'pages.gallery_empty', '' );

get_template_part(
	'template-parts/inner-hero',
	null,
	array(
		'title'    => (string) esk_site_ui( 'pages.gallery_heading', __( 'Photo gallery', 'eskoofy' ) ),
		'subtitle' => (string) $empty,
	)
);
?>
<div class="esk-page-sections">
	<div class="esk-container">
		<?php if ( empty( $photos ) ) : ?>
			<p class="esk-empty"><?php echo esc_html( $empty ); ?></p>
		<?php else : ?>
			<div class="esk-gallery-grid">
				<?php foreach ( $photos as $photo ) : ?>
					<figure class="esk-gallery-item">
						<a href="<?php echo esc_url( $photo->image_path ); ?>" target="_blank" rel="noopener noreferrer">
							<img src="<?php echo esc_url( $photo->image_path ); ?>" alt="<?php echo esc_attr( $photo->title ); ?>" loading="lazy">
						</a>
						<figcaption class="screen-reader-text"><?php echo esc_html( $photo->title ); ?></figcaption>
					</figure>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</div>
<?php
get_footer();