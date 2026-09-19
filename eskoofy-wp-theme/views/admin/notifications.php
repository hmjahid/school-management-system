<?php
/**
 * Notifications inbox.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

if ( isset( $_GET['action'] ) && 'mark_read' === $_GET['action'] && ! empty( $_GET['id'] ) ) {
	$id = absint( $_GET['id'] );
	check_admin_referer( 'esk_notif_read_' . $id );
	$wpdb->update( $wpdb->prefix . 'esk_notifications', array( 'read_at' => current_time( 'mysql' ) ), array( 'id' => $id ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-notifications' ) );
	exit;
}

$notes = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}esk_notifications ORDER BY created_at DESC LIMIT 100" );
?>
<div class="wrap esk-admin-wrap">
	<h1><?php esc_html_e( 'Notifications', 'eskoofy' ); ?></h1>

	<table class="wp-list-table widefat striped esk-table">
		<thead><tr>
			<th><?php esc_html_e( 'Title', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Message', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Type', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Date', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Status', 'eskoofy' ); ?></th>
		</tr></thead>
		<tbody>
			<?php if ( empty( $notes ) ) : ?>
				<tr><td colspan="5"><?php esc_html_e( 'No notifications.', 'eskoofy' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $notes as $n ) : ?>
					<tr <?php echo $n->read_at ? 'style="opacity:.6;"' : ''; ?>>
						<td><strong><?php echo esc_html( $n->title ); ?></strong></td>
						<td><?php echo esc_html( wp_trim_words( $n->message, 15 ) ); ?></td>
						<td><?php echo esc_html( $n->type ); ?></td>
						<td><?php echo esc_html( esk_date_format( $n->created_at ) ); ?></td>
						<td>
							<?php if ( $n->read_at ) : ?>
								<span class="esk-badge esk-badge-completed"><?php esc_html_e( 'Read', 'eskoofy' ); ?></span>
							<?php else : ?>
								<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=esk-notifications&action=mark_read&id=' . $n->id ), 'esk_notif_read_' . $n->id ) ); ?>" class="button button-small"><?php esc_html_e( 'Mark Read', 'eskoofy' ); ?></a>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>