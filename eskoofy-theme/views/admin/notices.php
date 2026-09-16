<?php
/**
 * Notices management.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

if ( isset( $_POST['esk_notice_save'] ) ) {
	check_admin_referer( 'esk_notice_form' );
	$title   = sanitize_text_field( $_POST['title'] ?? '' );
	$content = wp_kses_post( wp_unslash( $_POST['content'] ?? '' ) );
	$pinned  = isset( $_POST['pinned'] ) ? 1 : 0;

	$wpdb->insert( $wpdb->prefix . 'esk_notices', array(
		'title'      => $title,
		'content'    => $content,
		'pinned'     => $pinned,
		'created_by' => get_current_user_id(),
	) );

	esk_flash( 'success', __( 'Notice created.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-notices' ) );
	exit;
}

if ( isset( $_POST['esk_notice_update'] ) ) {
	check_admin_referer( 'esk_notice_form' );
	$notice_id = absint( $_POST['notice_id'] ?? 0 );
	if ( $notice_id ) {
		$wpdb->update( $wpdb->prefix . 'esk_notices', array(
			'title'   => sanitize_text_field( $_POST['title'] ?? '' ),
			'content' => wp_kses_post( wp_unslash( $_POST['content'] ?? '' ) ),
			'pinned'  => isset( $_POST['pinned'] ) ? 1 : 0,
		), array( 'id' => $notice_id ) );
	}
	esk_flash( 'success', __( 'Notice updated.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-notices' ) );
	exit;
}

if ( isset( $_POST['esk_notice_delete'] ) ) {
	check_admin_referer( 'esk_notice_delete_' . absint( $_POST['notice_id'] ?? 0 ) );
	$wpdb->delete( $wpdb->prefix . 'esk_notices', array( 'id' => absint( $_POST['notice_id'] ?? 0 ) ) );
	esk_flash( 'success', __( 'Notice deleted.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-notices' ) );
	exit;
}

$notices = $wpdb->get_results(
	"SELECT n.*, u.display_name AS author_name
	FROM {$wpdb->prefix}esk_notices n
	JOIN {$wpdb->prefix}users u ON n.created_by = u.ID
	ORDER BY n.pinned DESC, n.id DESC"
);

$flash = esk_get_flash( 'success' );

$edit_notice = null;
if ( isset( $_GET['edit_notice'] ) ) {
	$edit_notice = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}esk_notices WHERE id = %d", absint( $_GET['edit_notice'] ) ) );
}
?>
<div class="wrap esk-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Notices', 'eskoofy' ); ?></h1>
	<hr class="wp-header-end">

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<div class="esk-card esk-form-card" style="margin-bottom:1.5rem;">
		<h2><?php echo $edit_notice ? esc_html__( 'Edit Notice', 'eskoofy' ) : esc_html__( 'Add New Notice', 'eskoofy' ); ?></h2>
		<form method="post" class="esk-form">
			<?php wp_nonce_field( 'esk_notice_form' ); ?>
			<?php if ( $edit_notice ) : ?>
				<input type="hidden" name="notice_id" value="<?php echo esc_attr( $edit_notice->id ); ?>">
			<?php endif; ?>
			<div class="esk-form-group">
				<label><?php esc_html_e( 'Title', 'eskoofy' ); ?> *</label>
				<input type="text" name="title" class="regular-text" required value="<?php echo esc_attr( $edit_notice->title ?? '' ); ?>">
			</div>
			<div class="esk-form-group">
				<label><?php esc_html_e( 'Content', 'eskoofy' ); ?> *</label>
				<textarea name="content" class="large-text" rows="5" required><?php echo esc_textarea( $edit_notice->content ?? '' ); ?></textarea>
			</div>
			<div class="esk-form-group">
				<label><input type="checkbox" name="pinned" value="1" <?php checked( $edit_notice->pinned ?? 0, 1 ); ?>> <?php esc_html_e( 'Pin this notice', 'eskoofy' ); ?></label>
			</div>
			<button type="submit" name="<?php echo $edit_notice ? 'esk_notice_update' : 'esk_notice_save'; ?>" class="button button-primary"><?php echo $edit_notice ? esc_html__( 'Update Notice', 'eskoofy' ) : esc_html__( 'Publish Notice', 'eskoofy' ); ?></button>
			<?php if ( $edit_notice ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=esk-notices' ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'eskoofy' ); ?></a>
			<?php endif; ?>
		</form>
	</div>

	<table class="wp-list-table widefat striped esk-table">
		<thead><tr><th><?php esc_html_e( 'Title', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Author', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Pinned', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Date', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Actions', 'eskoofy' ); ?></th></tr></thead>
		<tbody>
			<?php if ( empty( $notices ) ) : ?>
				<tr><td colspan="5"><?php esc_html_e( 'No notices.', 'eskoofy' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $notices as $n ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $n->title ); ?></strong></td>
						<td><?php echo esc_html( $n->author_name ); ?></td>
						<td><?php echo $n->pinned ? '✓' : '—'; ?></td>
						<td><?php echo esc_html( esk_date_format( $n->created_at ) ); ?></td>
						<td>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=esk-notices&edit_notice=' . $n->id ) ); ?>" class="button button-small"><?php esc_html_e( 'Edit', 'eskoofy' ); ?></a>
							<form method="post" style="display:inline;" onsubmit="return confirm('<?php esc_attr_e( 'Delete this notice?', 'eskoofy' ); ?>');">
								<?php wp_nonce_field( 'esk_notice_delete_' . $n->id ); ?>
								<input type="hidden" name="notice_id" value="<?php echo esc_attr( $n->id ); ?>">
								<button type="submit" name="esk_notice_delete" class="button button-small"><?php esc_html_e( 'Delete', 'eskoofy' ); ?></button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
