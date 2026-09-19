<?php
/**
 * Gallery management (esk_galleries table).
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

if ( isset( $_POST['esk_gallery_save'] ) ) {
	check_admin_referer( 'esk_gallery_form' );
	$wpdb->insert( $wpdb->prefix . 'esk_galleries', array(
		'title'        => sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) ),
		'description'  => sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) ),
		'image_path'   => esc_url_raw( wp_unslash( $_POST['image_path'] ?? '' ) ),
		'category'     => sanitize_text_field( wp_unslash( $_POST['category'] ?? 'general' ) ),
		'is_published' => isset( $_POST['is_published'] ) ? 1 : 0,
	) );
	esk_flash( 'success', __( 'Gallery item added.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-gallery' ) );
	exit;
}

if ( isset( $_POST['esk_gallery_update'] ) ) {
	check_admin_referer( 'esk_gallery_form' );
	$gallery_id = absint( $_POST['gallery_id'] ?? 0 );
	if ( $gallery_id ) {
		$wpdb->update( $wpdb->prefix . 'esk_galleries', array(
			'title'        => sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) ),
			'description'  => sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) ),
			'image_path'   => esc_url_raw( wp_unslash( $_POST['image_path'] ?? '' ) ),
			'category'     => sanitize_text_field( wp_unslash( $_POST['category'] ?? 'general' ) ),
			'is_published' => isset( $_POST['is_published'] ) ? 1 : 0,
		), array( 'id' => $gallery_id ) );
	}
	esk_flash( 'success', __( 'Gallery item updated.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-gallery' ) );
	exit;
}

if ( isset( $_POST['esk_gallery_delete'] ) ) {
	check_admin_referer( 'esk_gallery_delete_' . absint( $_POST['gallery_id'] ?? 0 ) );
	$wpdb->delete( $wpdb->prefix . 'esk_galleries', array( 'id' => absint( $_POST['gallery_id'] ?? 0 ) ) );
	esk_flash( 'success', __( 'Gallery item removed.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-gallery' ) );
	exit;
}

$galleries = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}esk_galleries ORDER BY id DESC" );
$flash     = esk_get_flash( 'success' );

$edit_gallery = null;
if ( isset( $_GET['edit_gallery'] ) ) {
	$edit_gallery = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}esk_galleries WHERE id = %d", absint( $_GET['edit_gallery'] ) ) );
}
?>
<div class="wrap esk-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Gallery', 'eskoofy' ); ?></h1>
	<hr class="wp-header-end">

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<div class="esk-card esk-form-card" style="margin-bottom:1.5rem;">
		<h2><?php echo $edit_gallery ? esc_html__( 'Edit Gallery Item', 'eskoofy' ) : esc_html__( 'Add Gallery Item', 'eskoofy' ); ?></h2>
		<form method="post" class="esk-form">
			<?php wp_nonce_field( 'esk_gallery_form' ); ?>
			<?php if ( $edit_gallery ) : ?>
				<input type="hidden" name="gallery_id" value="<?php echo esc_attr( $edit_gallery->id ); ?>">
			<?php endif; ?>
			<div class="esk-form-row">
				<div class="esk-form-group"><label><?php esc_html_e( 'Title', 'eskoofy' ); ?> *</label><input type="text" name="title" required value="<?php echo esc_attr( $edit_gallery->title ?? '' ); ?>"></div>
				<div class="esk-form-group"><label><?php esc_html_e( 'Category', 'eskoofy' ); ?></label><input type="text" name="category" class="regular-text" value="<?php echo esc_attr( $edit_gallery->category ?? 'general' ); ?>"></div>
			</div>
			<div class="esk-form-group"><label><?php esc_html_e( 'Image URL', 'eskoofy' ); ?> *</label><input type="url" name="image_path" class="large-text" required value="<?php echo esc_attr( $edit_gallery->image_path ?? '' ); ?>"></div>
			<div class="esk-form-group"><label><?php esc_html_e( 'Description', 'eskoofy' ); ?></label><textarea name="description" rows="2" class="large-text"><?php echo esc_textarea( $edit_gallery->description ?? '' ); ?></textarea></div>
			<div class="esk-form-group"><label><input type="checkbox" name="is_published" value="1" <?php checked( $edit_gallery->is_published ?? 1, 1 ); ?>> <?php esc_html_e( 'Published', 'eskoofy' ); ?></label></div>
			<button type="submit" name="<?php echo $edit_gallery ? 'esk_gallery_update' : 'esk_gallery_save'; ?>" class="button button-primary"><?php echo $edit_gallery ? esc_html__( 'Update Item', 'eskoofy' ) : esc_html__( 'Add Item', 'eskoofy' ); ?></button>
			<?php if ( $edit_gallery ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=esk-gallery' ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'eskoofy' ); ?></a>
			<?php endif; ?>
		</form>
	</div>

	<table class="wp-list-table widefat striped esk-table">
		<thead><tr>
			<th><?php esc_html_e( 'Title', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Category', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Image', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Published', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Actions', 'eskoofy' ); ?></th>
		</tr></thead>
		<tbody>
			<?php if ( empty( $galleries ) ) : ?>
				<tr><td colspan="5"><?php esc_html_e( 'No gallery items.', 'eskoofy' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $galleries as $g ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $g->title ); ?></strong></td>
						<td><?php echo esc_html( $g->category ); ?></td>
						<td><?php if ( $g->image_path ) : ?><a href="<?php echo esc_url( $g->image_path ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View', 'eskoofy' ); ?></a><?php else : ?>—<?php endif; ?></td>
						<td><?php echo $g->is_published ? '<span class="esk-badge esk-badge-active">' . esc_html__( 'Yes', 'eskoofy' ) . '</span>' : '<span class="esk-badge esk-badge-pending">' . esc_html__( 'No', 'eskoofy' ) . '</span>'; ?></td>
						<td>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=esk-gallery&edit_gallery=' . $g->id ) ); ?>" class="button button-small"><?php esc_html_e( 'Edit', 'eskoofy' ); ?></a>
							<form method="post" style="display:inline;" onsubmit="return confirm('<?php esc_attr_e( 'Delete this gallery item?', 'eskoofy' ); ?>');">
								<?php wp_nonce_field( 'esk_gallery_delete_' . $g->id ); ?>
								<input type="hidden" name="gallery_id" value="<?php echo esc_attr( $g->id ); ?>">
								<button type="submit" name="esk_gallery_delete" class="button button-small"><?php esc_html_e( 'Delete', 'eskoofy' ); ?></button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>