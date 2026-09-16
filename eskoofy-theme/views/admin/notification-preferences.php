<?php
/**
 * Notification preferences (current user).
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

$me        = get_current_user_id();
$pref_keys = array( 'admission_approved', 'admission_rejected', 'fee_due', 'fee_receipt', 'exam_result', 'notice', 'event', 'assignment', 'attendance', 'leave', 'general' );
$channels  = array( 'email', 'sms', 'push', 'in_app' );

if ( isset( $_POST['esk_notif_prefs_save'] ) ) {
	check_admin_referer( 'esk_notif_prefs_form' );
	foreach ( $pref_keys as $type ) {
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}esk_notification_preferences WHERE user_id = %d AND notification_type = %s", $me, $type ) );
		$data = array();
		foreach ( $channels as $ch ) {
			$data[ $ch ] = isset( $_POST['channels'][ $type ][ $ch ] ) ? 1 : 0;
		}
		if ( $row ) {
			$wpdb->update( $wpdb->prefix . 'esk_notification_preferences', $data, array( 'id' => $row->id ) );
		} else {
			$wpdb->insert( $wpdb->prefix . 'esk_notification_preferences', array_merge( array(
				'user_id'          => $me,
				'notification_type' => $type,
			), $data ) );
		}
	}
	esk_flash( 'success', __( 'Notification preferences saved.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-notification-preferences' ) );
	exit;
}

$prefs = array();
$rows  = $wpdb->get_results( $wpdb->prepare(
	"SELECT * FROM {$wpdb->prefix}esk_notification_preferences WHERE user_id = %d",
	$me
) );
foreach ( $rows as $r ) {
	$prefs[ $r->notification_type ] = $r;
}

$flash = esk_get_flash( 'success' );
?>
<div class="wrap esk-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Notification Preferences', 'eskoofy' ); ?></h1>
	<hr class="wp-header-end">

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<p><?php esc_html_e( 'Choose which channels you want to receive for each notification type.', 'eskoofy' ); ?></p>

	<form method="post">
		<?php wp_nonce_field( 'esk_notif_prefs_form' ); ?>
		<div class="esk-table-scroll">
			<table class="wp-list-table widefat striped esk-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Notification Type', 'eskoofy' ); ?></th>
						<?php foreach ( $channels as $ch ) : ?>
							<th style="text-align:center;"><?php echo esc_html( ucfirst( $ch ) ); ?></th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $pref_keys as $type ) : ?>
						<?php $row = $prefs[ $type ] ?? null; ?>
						<tr>
							<td><strong><?php echo esc_html( str_replace( '_', ' ', $type ) ); ?></strong></td>
							<?php foreach ( $channels as $ch ) : ?>
								<td style="text-align:center;">
									<input type="checkbox" name="channels[<?php echo esc_attr( $type ); ?>][<?php echo esc_attr( $ch ); ?>]" value="1"
										<?php checked( $row ? (int) $row->{$ch} : ( 'sms' === $ch ? 0 : 1 ), 1 ); ?>>
								</td>
							<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<p class="submit"><button type="submit" name="esk_notif_prefs_save" class="button button-primary"><?php esc_html_e( 'Save Preferences', 'eskoofy' ); ?></button></p>
	</form>
</div>