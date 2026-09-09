<?php
/**
 * Media library.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

if ( isset( $_POST['esk_media_upload'] ) ) {
	check_admin_referer( 'esk_media_form' );
	$title = sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) );
	$alt   = sanitize_text_field( wp_unslash( $_POST['alt_text'] ?? '' ) );

	if ( ! empty( $_FILES['file']['tmp_name'] ) && is_uploaded_file( $_FILES['file']['tmp_name'] ) ) {
		$upload = esk_upload_file( $_FILES['file'], 'eskoofy/media' );
		if ( $upload ) {
			$wpdb->insert( $wpdb->prefix . 'esk_media', array(
				'title'       => $title ?: pathinfo( $upload['filename'], PATHINFO_FILENAME ),
				'file_path'   => $upload['url'],
				'file_type'   => $upload['type'],
				'file_size'   => $upload['size'],
				'alt_text'    => $alt,
				'uploaded_by' => get_current_user_id(),
			) );
			esk_flash( 'success', __( 'Media uploaded.', 'eskoofy' ) );
		}
	}
	wp_safe_redirect( admin_url( 'admin.php?page=esk-media' ) );
	exit;
}

if ( isset( $_GET['action'] ) && 'delete' === $_GET['action'] && ! empty( $_GET['id'] ) ) {
	$id = absint( $_GET['id'] );
	check_admin_referer( 'esk_media_delete_' . $id );
	$wpdb->delete( $wpdb->prefix . 'esk_media', array( 'id' => $id ) );
	esk_flash( 'success', __( 'Media deleted.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-media' ) );
	exit;
}

$items = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}esk_media ORDER BY created_at DESC" );
$flash = esk_get_flash( 'success' );
?>
<div class="wrap esk-admin-wrap">
	<h1><?php esc_html_e( 'Media Library', 'eskoofy' ); ?></h1>

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<div class="esk-card esk-form-card" style="margin-bottom:1.5rem;">
		<h2><?php esc_html_e( 'Upload Image', 'eskoofy' ); ?></h2>
		<form method="post" enctype="multipart/form-data" class="esk-form">
			<?php wp_nonce_field( 'esk_media_form' ); ?>
			<div class="esk-form-row">
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Title', 'eskoofy' ); ?></label>
					<input type="text" name="title">
				</div>
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Alt Text', 'eskoofy' ); ?></label>
					<input type="text" name="alt_text">
				</div>
				<div class="esk-form-group">
					<label><?php esc_html_e( 'File', 'eskoofy' ); ?> *</label>
					<input type="file" name="file" required accept="image/*">
				</div>
			</div>
			<button type="submit" name="esk_media_upload" class="button button-primary"><?php esc_html_e( 'Upload', 'eskoofy' ); ?></button>
		</form>
	</div>

	<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1rem;">
		<?php if ( empty( $items ) ) : ?>
			<p><?php esc_html_e( 'No media yet.', 'eskoofy' ); ?></p>
		<?php else : ?>
			<?php foreach ( $items as $m ) : ?>
				<div class="esk-card" style="padding:0.5rem;">
					<img src="<?php echo esc_url( $m->file_path ); ?>" alt="<?php echo esc_attr( $m->alt_text ); ?>" style="width:100%;height:150px;object-fit:cover;border-radius:4px;">
					<p style="margin:0.5rem 0;font-size:.85rem;"><strong><?php echo esc_html( $m->title ); ?></strong></p>
					<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=esk-media&action=delete&id=' . $m->id ), 'esk_media_delete_' . $m->id ) ); ?>" class="button button-small" onclick="return confirm('Delete?');"><?php esc_html_e( 'Delete', 'eskoofy' ); ?></a>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
</div>