<?php
/**
 * Staff attendance.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

if ( isset( $_POST['esk_staff_att_save'] ) ) {
	check_admin_referer( 'esk_staff_att_form' );
	$date     = sanitize_text_field( $_POST['date'] ?? gmdate( 'Y-m-d' ) );
	$statuses = $_POST['status'] ?? array();
	foreach ( $statuses as $uid => $status ) {
		$uid    = absint( $uid );
		$status = sanitize_text_field( $status );
		if ( ! $uid || ! in_array( $status, array( 'present', 'absent', 'late', 'half_day' ), true ) ) {
			continue;
		}
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}esk_staff_attendances WHERE user_id = %d AND date = %s",
				$uid, $date
			)
		);
		$data = array(
			'user_id' => $uid,
			'date'    => $date,
			'status'  => $status,
		);
		if ( $existing ) {
			$wpdb->update( $wpdb->prefix . 'esk_staff_attendances', $data, array( 'id' => $existing ) );
		} else {
			$wpdb->insert( $wpdb->prefix . 'esk_staff_attendances', $data );
		}
	}
	esk_flash( 'success', __( 'Staff attendance saved.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-staff-attendance&date=' . $date ) );
	exit;
}

$date     = sanitize_text_field( $_GET['date'] ?? gmdate( 'Y-m-d' ) );
$staff    = $wpdb->get_results(
	"SELECT t.user_id, u.display_name FROM {$wpdb->prefix}esk_teachers t
	JOIN {$wpdb->prefix}users u ON t.user_id = u.ID ORDER BY u.display_name"
);
$existing = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT user_id, status FROM {$wpdb->prefix}esk_staff_attendances WHERE date = %s",
		$date
	),
	OBJECT_K
);

$flash = esk_get_flash( 'success' );
?>
<div class="wrap esk-admin-wrap">
	<h1><?php esc_html_e( 'Staff Attendance', 'eskoofy' ); ?></h1>

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<div class="esk-card esk-form-card">
		<form method="get" class="esk-inline-form" style="margin-bottom:1rem;">
			<input type="hidden" name="page" value="esk-staff-attendance">
			<label><?php esc_html_e( 'Date', 'eskoofy' ); ?>:</label>
			<input type="date" name="date" value="<?php echo esc_attr( $date ); ?>">
			<button type="submit" class="button"><?php esc_html_e( 'Load', 'eskoofy' ); ?></button>
		</form>

		<form method="post" class="esk-form">
			<?php wp_nonce_field( 'esk_staff_att_form' ); ?>
			<input type="hidden" name="date" value="<?php echo esc_attr( $date ); ?>">
			<table class="wp-list-table widefat striped esk-table">
				<thead><tr>
					<th><?php esc_html_e( 'Staff', 'eskoofy' ); ?></th>
					<th><?php esc_html_e( 'Present', 'eskoofy' ); ?></th>
					<th><?php esc_html_e( 'Absent', 'eskoofy' ); ?></th>
					<th><?php esc_html_e( 'Late', 'eskoofy' ); ?></th>
					<th><?php esc_html_e( 'Half Day', 'eskoofy' ); ?></th>
				</tr></thead>
				<tbody>
					<?php if ( empty( $staff ) ) : ?>
						<tr><td colspan="5"><?php esc_html_e( 'No staff found.', 'eskoofy' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $staff as $s ) :
							$cur = $existing[ $s->user_id ]->status ?? '';
							?>
							<tr>
								<td><?php echo esc_html( $s->display_name ); ?></td>
								<td><input type="radio" name="status[<?php echo esc_attr( $s->user_id ); ?>]" value="present" <?php checked( $cur, 'present' ); ?>></td>
								<td><input type="radio" name="status[<?php echo esc_attr( $s->user_id ); ?>]" value="absent" <?php checked( $cur, 'absent' ); ?>></td>
								<td><input type="radio" name="status[<?php echo esc_attr( $s->user_id ); ?>]" value="late" <?php checked( $cur, 'late' ); ?>></td>
								<td><input type="radio" name="status[<?php echo esc_attr( $s->user_id ); ?>]" value="half_day" <?php checked( $cur, 'half_day' ); ?>></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
			<p class="submit"><button type="submit" name="esk_staff_att_save" class="button button-primary"><?php esc_html_e( 'Save', 'eskoofy' ); ?></button></p>
		</form>
	</div>
</div>