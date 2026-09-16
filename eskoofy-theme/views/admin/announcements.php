<?php
/**
 * Announcements management.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

if ( isset( $_POST['esk_announcement_save'] ) ) {
	check_admin_referer( 'esk_announcement_form' );
	$wpdb->insert( $wpdb->prefix . 'esk_announcements', array(
		'title'           => sanitize_text_field( $_POST['title'] ?? '' ),
		'body'            => wp_kses_post( wp_unslash( $_POST['body'] ?? '' ) ),
		'audience'        => sanitize_text_field( $_POST['audience'] ?? 'all' ),
		'is_published'    => isset( $_POST['is_published'] ) ? 1 : 0,
		'display_target'  => sanitize_text_field( $_POST['display_target'] ?? 'all' ),
		'starts_at'       => sanitize_text_field( $_POST['starts_at'] ?? '' ) ?: null,
		'ends_at'         => sanitize_text_field( $_POST['ends_at'] ?? '' ) ?: null,
	) );
	esk_flash( 'success', __( 'Announcement added.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-announcements' ) );
	exit;
}

if ( isset( $_POST['esk_announcement_update'] ) ) {
	check_admin_referer( 'esk_announcement_form' );
	$announcement_id = absint( $_POST['announcement_id'] ?? 0 );
	if ( $announcement_id ) {
		$wpdb->update( $wpdb->prefix . 'esk_announcements', array(
			'title'          => sanitize_text_field( $_POST['title'] ?? '' ),
			'body'           => wp_kses_post( wp_unslash( $_POST['body'] ?? '' ) ),
			'audience'       => sanitize_text_field( $_POST['audience'] ?? 'all' ),
			'is_published'   => isset( $_POST['is_published'] ) ? 1 : 0,
			'display_target' => sanitize_text_field( $_POST['display_target'] ?? 'all' ),
			'starts_at'      => sanitize_text_field( $_POST['starts_at'] ?? '' ) ?: null,
			'ends_at'        => sanitize_text_field( $_POST['ends_at'] ?? '' ) ?: null,
		), array( 'id' => $announcement_id ) );
	}
	esk_flash( 'success', __( 'Announcement updated.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-announcements' ) );
	exit;
}

if ( isset( $_POST['esk_announcement_delete'] ) ) {
	check_admin_referer( 'esk_announcement_delete_' . absint( $_POST['announcement_id'] ?? 0 ) );
	$wpdb->delete( $wpdb->prefix . 'esk_announcements', array( 'id' => absint( $_POST['announcement_id'] ?? 0 ) ) );
	esk_flash( 'success', __( 'Announcement deleted.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-announcements' ) );
	exit;
}

$announcements = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}esk_announcements ORDER BY id DESC LIMIT 100" );
$flash         = esk_get_flash( 'success' );

$edit_announcement = null;
if ( isset( $_GET['edit_announcement'] ) ) {
	$edit_announcement = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}esk_announcements WHERE id = %d", absint( $_GET['edit_announcement'] ) ) );
}
?>
<div class="wrap esk-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Announcements', 'eskoofy' ); ?></h1>
	<hr class="wp-header-end">

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<div class="esk-card esk-form-card" style="margin-bottom:1.5rem;">
		<h2><?php echo $edit_announcement ? esc_html__( 'Edit Announcement', 'eskoofy' ) : esc_html__( 'Add Announcement', 'eskoofy' ); ?></h2>
		<form method="post" class="esk-form">
			<?php wp_nonce_field( 'esk_announcement_form' ); ?>
			<?php if ( $edit_announcement ) : ?>
				<input type="hidden" name="announcement_id" value="<?php echo esc_attr( $edit_announcement->id ); ?>">
			<?php endif; ?>
			<div class="esk-form-group"><label><?php esc_html_e( 'Title', 'eskoofy' ); ?> *</label><input type="text" name="title" class="regular-text" required value="<?php echo esc_attr( $edit_announcement->title ?? '' ); ?>"></div>
			<div class="esk-form-group"><label><?php esc_html_e( 'Body', 'eskoofy' ); ?> *</label><textarea name="body" class="large-text" rows="4" required><?php echo esc_textarea( $edit_announcement->body ?? '' ); ?></textarea></div>
			<div class="esk-form-row">
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Audience', 'eskoofy' ); ?></label>
					<select name="audience">
						<option value="all" <?php selected( $edit_announcement->audience ?? '', 'all' ); ?>><?php esc_html_e( 'All', 'eskoofy' ); ?></option>
						<option value="students" <?php selected( $edit_announcement->audience ?? '', 'students' ); ?>><?php esc_html_e( 'Students', 'eskoofy' ); ?></option>
						<option value="teachers" <?php selected( $edit_announcement->audience ?? '', 'teachers' ); ?>><?php esc_html_e( 'Teachers', 'eskoofy' ); ?></option>
						<option value="parents" <?php selected( $edit_announcement->audience ?? '', 'parents' ); ?>><?php esc_html_e( 'Parents', 'eskoofy' ); ?></option>
					</select>
				</div>
				<div class="esk-form-group"><label><input type="checkbox" name="is_published" value="1" <?php checked( $edit_announcement->is_published ?? 1, 1 ); ?>> <?php esc_html_e( 'Published', 'eskoofy' ); ?></label></div>
				<div class="esk-form-group"><label><?php esc_html_e( 'Starts', 'eskoofy' ); ?></label><input type="datetime-local" name="starts_at" value="<?php echo esc_attr( $edit_announcement->starts_at ?? '' ); ?>"></div>
				<div class="esk-form-group"><label><?php esc_html_e( 'Ends', 'eskoofy' ); ?></label><input type="datetime-local" name="ends_at" value="<?php echo esc_attr( $edit_announcement->ends_at ?? '' ); ?>"></div>
			</div>
			<button type="submit" name="<?php echo $edit_announcement ? 'esk_announcement_update' : 'esk_announcement_save'; ?>" class="button button-primary"><?php echo $edit_announcement ? esc_html__( 'Update Announcement', 'eskoofy' ) : esc_html__( 'Add Announcement', 'eskoofy' ); ?></button>
			<?php if ( $edit_announcement ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=esk-announcements' ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'eskoofy' ); ?></a>
			<?php endif; ?>
		</form>
	</div>

	<table class="wp-list-table widefat striped esk-table">
		<thead><tr>
			<th><?php esc_html_e( 'Title', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Audience', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Published', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Actions', 'eskoofy' ); ?></th>
		</tr></thead>
		<tbody>
			<?php if ( empty( $announcements ) ) : ?>
				<tr><td colspan="4"><?php esc_html_e( 'No announcements.', 'eskoofy' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $announcements as $a ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $a->title ); ?></strong></td>
						<td><?php echo esc_html( ucfirst( $a->audience ) ); ?></td>
						<td><?php echo $a->is_published ? '✓' : '—'; ?></td>
						<td>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=esk-announcements&edit_announcement=' . $a->id ) ); ?>" class="button button-small"><?php esc_html_e( 'Edit', 'eskoofy' ); ?></a>
							<form method="post" style="display:inline;" onsubmit="return confirm('<?php esc_attr_e( 'Delete this announcement?', 'eskoofy' ); ?>');">
								<?php wp_nonce_field( 'esk_announcement_delete_' . $a->id ); ?>
								<input type="hidden" name="announcement_id" value="<?php echo esc_attr( $a->id ); ?>">
								<button type="submit" name="esk_announcement_delete" class="button button-small"><?php esc_html_e( 'Delete', 'eskoofy' ); ?></button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
