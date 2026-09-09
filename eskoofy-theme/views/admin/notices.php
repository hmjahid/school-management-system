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

	$notice_id = $wpdb->insert_id;

	if ( $notice_id && post_type_exists( 'esk_notices' ) ) {
		$post_id = wp_insert_post( array(
			'post_title'   => $title,
			'post_content' => $content,
			'post_type'    => 'esk_notices',
			'post_status'  => 'publish',
		) );
		if ( is_wp_error( $post_id ) ) {
			$post_id = 0;
		}
	}

	esk_flash( 'success', __( 'Notice created.', 'eskoofy' ) );
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
?>
<div class="wrap esk-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Notices', 'eskoofy' ); ?></h1>
	<hr class="wp-header-end">

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<div class="esk-card esk-form-card" style="margin-bottom:1.5rem;">
		<h2><?php esc_html_e( 'Add New Notice', 'eskoofy' ); ?></h2>
		<form method="post" class="esk-form">
			<?php wp_nonce_field( 'esk_notice_form' ); ?>
			<div class="esk-form-group">
				<label><?php esc_html_e( 'Title', 'eskoofy' ); ?> *</label>
				<input type="text" name="title" class="regular-text" required>
			</div>
			<div class="esk-form-group">
				<label><?php esc_html_e( 'Content', 'eskoofy' ); ?> *</label>
				<textarea name="content" class="large-text" rows="5" required></textarea>
			</div>
			<div class="esk-form-group">
				<label><input type="checkbox" name="pinned" value="1"> <?php esc_html_e( 'Pin this notice', 'eskoofy' ); ?></label>
			</div>
			<button type="submit" name="esk_notice_save" class="button button-primary"><?php esc_html_e( 'Publish Notice', 'eskoofy' ); ?></button>
		</form>
	</div>

	<table class="wp-list-table widefat striped esk-table">
		<thead><tr><th><?php esc_html_e( 'Title', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Author', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Pinned', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Date', 'eskoofy' ); ?></th></tr></thead>
		<tbody>
			<?php if ( empty( $notices ) ) : ?>
				<tr><td colspan="4"><?php esc_html_e( 'No notices.', 'eskoofy' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $notices as $n ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $n->title ); ?></strong></td>
						<td><?php echo esc_html( $n->author_name ); ?></td>
						<td><?php echo $n->pinned ? '✓' : '—'; ?></td>
						<td><?php echo esc_html( esk_date_format( $n->created_at ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
