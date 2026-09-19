<?php
/**
 * Documents library.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

if ( isset( $_POST['esk_doc_upload'] ) ) {
	check_admin_referer( 'esk_doc_form' );
	$title    = sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) );
	$category = sanitize_text_field( wp_unslash( $_POST['category'] ?? '' ) );
	$desc     = sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) );

	if ( ! empty( $_FILES['file']['tmp_name'] ) && is_uploaded_file( $_FILES['file']['tmp_name'] ) ) {
		$upload = esk_upload_file( $_FILES['file'], 'eskoofy/documents' );
		if ( $upload ) {
			$wpdb->insert( $wpdb->prefix . 'esk_documents', array(
				'title'       => $title ?: pathinfo( $upload['filename'], PATHINFO_FILENAME ),
				'category'    => $category,
				'file_path'   => $upload['url'],
				'file_type'   => $upload['type'],
				'file_size'   => $upload['size'],
				'description' => $desc,
				'uploaded_by' => get_current_user_id(),
			) );
			esk_flash( 'success', __( 'Document uploaded.', 'eskoofy' ) );
		}
	}
	wp_safe_redirect( admin_url( 'admin.php?page=esk-documents' ) );
	exit;
}

if ( isset( $_GET['action'] ) && 'delete' === $_GET['action'] && ! empty( $_GET['id'] ) ) {
	$id = absint( $_GET['id'] );
	check_admin_referer( 'esk_doc_delete_' . $id );
	$wpdb->delete( $wpdb->prefix . 'esk_documents', array( 'id' => $id ) );
	esk_flash( 'success', __( 'Document deleted.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-documents' ) );
	exit;
}

$docs = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}esk_documents ORDER BY created_at DESC" );
$flash = esk_get_flash( 'success' );
?>
<div class="wrap esk-admin-wrap">
	<h1><?php esc_html_e( 'Documents', 'eskoofy' ); ?></h1>

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<div class="esk-card esk-form-card" style="margin-bottom:1.5rem;">
		<h2><?php esc_html_e( 'Upload Document', 'eskoofy' ); ?></h2>
		<form method="post" enctype="multipart/form-data" class="esk-form">
			<?php wp_nonce_field( 'esk_doc_form' ); ?>
			<div class="esk-form-row">
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Title', 'eskoofy' ); ?></label>
					<input type="text" name="title">
				</div>
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Category', 'eskoofy' ); ?></label>
					<input type="text" name="category" placeholder="<?php esc_attr_e( 'e.g. Policy, Syllabus', 'eskoofy' ); ?>">
				</div>
				<div class="esk-form-group">
					<label><?php esc_html_e( 'File', 'eskoofy' ); ?> *</label>
					<input type="file" name="file" required accept=".pdf,.doc,.docx,.xls,.xlsx,.txt">
				</div>
			</div>
			<div class="esk-form-group">
				<label><?php esc_html_e( 'Description', 'eskoofy' ); ?></label>
				<textarea name="description" rows="2" class="large-text"></textarea>
			</div>
			<button type="submit" name="esk_doc_upload" class="button button-primary"><?php esc_html_e( 'Upload', 'eskoofy' ); ?></button>
		</form>
	</div>

	<table class="wp-list-table widefat striped esk-table">
		<thead><tr>
			<th><?php esc_html_e( 'Title', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Category', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Type', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Size', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Date', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Actions', 'eskoofy' ); ?></th>
		</tr></thead>
		<tbody>
			<?php if ( empty( $docs ) ) : ?>
				<tr><td colspan="6"><?php esc_html_e( 'No documents yet.', 'eskoofy' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $docs as $d ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $d->title ); ?></strong></td>
						<td><?php echo esc_html( $d->category ); ?></td>
						<td><?php echo esc_html( $d->file_type ); ?></td>
						<td><?php echo esc_html( size_format( (int) $d->file_size ) ); ?></td>
						<td><?php echo esc_html( esk_date_format( $d->created_at ) ); ?></td>
						<td>
							<a href="<?php echo esc_url( $d->file_path ); ?>" target="_blank" class="button button-small"><?php esc_html_e( 'Download', 'eskoofy' ); ?></a>
							<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=esk-documents&action=delete&id=' . $d->id ), 'esk_doc_delete_' . $d->id ) ); ?>" class="button button-small" onclick="return confirm('Delete?');"><?php esc_html_e( 'Delete', 'eskoofy' ); ?></a>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>