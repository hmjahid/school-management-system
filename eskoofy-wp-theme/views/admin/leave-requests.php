<?php
/**
 * Leave requests admin view.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

if ( isset( $_POST['esk_lr_action'] ) ) {
	check_admin_referer( 'esk_lr_action_' . absint( $_POST['leave_id'] ?? 0 ) );
	$action = sanitize_text_field( $_POST['esk_lr_action'] );
	$id     = absint( $_POST['leave_id'] ?? 0 );
	if ( $id && in_array( $action, array( 'approve', 'reject' ), true ) ) {
		$wpdb->update(
			$wpdb->prefix . 'esk_leave_requests',
			array(
				'status'      => 'approve' === $action ? 'approved' : 'rejected',
				'approved_by' => get_current_user_id(),
				'approved_at' => current_time( 'mysql' ),
			),
			array( 'id' => $id )
		);
		esk_flash( 'success', __( 'Leave request updated.', 'eskoofy' ) );
	}
	wp_safe_redirect( admin_url( 'admin.php?page=esk-leave-requests' ) );
	exit;
}

$requests = $wpdb->get_results(
	"SELECT lr.*, u.display_name, lt.name AS leave_type_name
	FROM {$wpdb->prefix}esk_leave_requests lr
	JOIN {$wpdb->prefix}users u ON lr.user_id = u.ID
	LEFT JOIN {$wpdb->prefix}esk_leave_types lt ON lr.leave_type_id = lt.id
	ORDER BY lr.created_at DESC"
);

$flash = esk_get_flash( 'success' );
?>
<div class="wrap esk-admin-wrap">
	<h1><?php esc_html_e( 'Leave Requests', 'eskoofy' ); ?></h1>

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<table class="wp-list-table widefat striped esk-table">
		<thead><tr>
			<th><?php esc_html_e( 'Staff', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Type', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'From', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'To', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Days', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Reason', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Status', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Actions', 'eskoofy' ); ?></th>
		</tr></thead>
		<tbody>
			<?php if ( empty( $requests ) ) : ?>
				<tr><td colspan="8"><?php esc_html_e( 'No leave requests.', 'eskoofy' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $requests as $r ) : ?>
					<tr>
						<td><?php echo esc_html( $r->display_name ); ?></td>
						<td><?php echo esc_html( $r->leave_type_name ); ?></td>
						<td><?php echo esc_html( esk_date_format( $r->start_date ) ); ?></td>
						<td><?php echo esc_html( esk_date_format( $r->end_date ) ); ?></td>
						<td><?php echo esc_html( $r->days ); ?></td>
						<td><?php echo esc_html( wp_trim_words( $r->reason, 8 ) ); ?></td>
						<td><span class="esk-badge esk-badge-<?php echo esc_attr( $r->status ); ?>"><?php echo esc_html( ucfirst( $r->status ) ); ?></span></td>
						<td>
							<?php if ( 'pending' === $r->status ) : ?>
								<form method="post" style="display:inline;">
									<?php wp_nonce_field( 'esk_lr_action_' . $r->id ); ?>
									<input type="hidden" name="leave_id" value="<?php echo esc_attr( $r->id ); ?>">
									<button type="submit" name="esk_lr_action" value="approve" class="button button-small"><?php esc_html_e( 'Approve', 'eskoofy' ); ?></button>
								</form>
								<form method="post" style="display:inline;">
									<?php wp_nonce_field( 'esk_lr_action_' . $r->id ); ?>
									<input type="hidden" name="leave_id" value="<?php echo esc_attr( $r->id ); ?>">
									<button type="submit" name="esk_lr_action" value="reject" class="button button-small"><?php esc_html_e( 'Reject', 'eskoofy' ); ?></button>
								</form>
							<?php else : ?>—<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>