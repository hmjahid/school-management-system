<?php
/**
 * Hostels management.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

if ( isset( $_POST['esk_hostel_save'] ) ) {
	check_admin_referer( 'esk_hostel_form' );
	$wpdb->insert( $wpdb->prefix . 'esk_hostels', array(
		'name'          => sanitize_text_field( $_POST['name'] ?? '' ),
		'address'       => sanitize_textarea_field( $_POST['address'] ?? '' ),
		'description'   => sanitize_textarea_field( $_POST['description'] ?? '' ),
		'total_rooms'   => absint( $_POST['total_rooms'] ?? 0 ),
		'warden_name'   => sanitize_text_field( $_POST['warden_name'] ?? '' ),
		'warden_phone'  => sanitize_text_field( $_POST['warden_phone'] ?? '' ),
		'status'        => 'active',
	) );
	esk_flash( 'success', __( 'Hostel added.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-hostels' ) );
	exit;
}

if ( isset( $_POST['esk_hostel_room_save'] ) ) {
	check_admin_referer( 'esk_hostel_room_form' );
	$wpdb->insert( $wpdb->prefix . 'esk_hostel_rooms', array(
		'hostel_id'    => absint( $_POST['hostel_id'] ?? 0 ),
		'room_number'  => sanitize_text_field( $_POST['room_number'] ?? '' ),
		'room_type'    => sanitize_text_field( $_POST['room_type'] ?? 'double' ),
		'capacity'     => absint( $_POST['capacity'] ?? 2 ),
		'occupied'     => 0,
		'status'       => 'available',
	) );
	esk_flash( 'success', __( 'Room added.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-hostels' ) );
	exit;
}

if ( isset( $_POST['esk_hostel_delete'] ) ) {
	check_admin_referer( 'esk_hostel_delete_' . absint( $_POST['hostel_id'] ?? 0 ) );
	$wpdb->delete( $wpdb->prefix . 'esk_hostels', array( 'id' => absint( $_POST['hostel_id'] ?? 0 ) ) );
	esk_flash( 'success', __( 'Hostel deleted.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-hostels' ) );
	exit;
}

$hostels = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}esk_hostels ORDER BY name" );
$rooms   = $wpdb->get_results(
	"SELECT r.*, h.name AS hostel_name
	FROM {$wpdb->prefix}esk_hostel_rooms r
	JOIN {$wpdb->prefix}esk_hostels h ON r.hostel_id = h.id
	ORDER BY h.name, r.room_number"
);
$flash = esk_get_flash( 'success' );
?>
<div class="wrap esk-admin-wrap">
	<h1><?php esc_html_e( 'Hostels', 'eskoofy' ); ?></h1>

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<div class="esk-dashboard-columns">
		<div class="esk-dashboard-column esk-col-wide">
			<div class="esk-card esk-form-card" style="margin-bottom:1.5rem;">
				<h2><?php esc_html_e( 'Add Hostel', 'eskoofy' ); ?></h2>
				<form method="post" class="esk-form">
					<?php wp_nonce_field( 'esk_hostel_form' ); ?>
					<div class="esk-form-row">
						<div class="esk-form-group"><label><?php esc_html_e( 'Name', 'eskoofy' ); ?> *</label><input type="text" name="name" required></div>
						<div class="esk-form-group"><label><?php esc_html_e( 'Total Rooms', 'eskoofy' ); ?></label><input type="number" name="total_rooms" min="0"></div>
						<div class="esk-form-group"><label><?php esc_html_e( 'Warden Name', 'eskoofy' ); ?></label><input type="text" name="warden_name"></div>
						<div class="esk-form-group"><label><?php esc_html_e( 'Warden Phone', 'eskoofy' ); ?></label><input type="tel" name="warden_phone"></div>
					</div>
					<div class="esk-form-group"><label><?php esc_html_e( 'Address', 'eskoofy' ); ?></label><textarea name="address" rows="2" class="large-text"></textarea></div>
					<div class="esk-form-group"><label><?php esc_html_e( 'Description', 'eskoofy' ); ?></label><textarea name="description" rows="2" class="large-text"></textarea></div>
					<button type="submit" name="esk_hostel_save" class="button button-primary"><?php esc_html_e( 'Add Hostel', 'eskoofy' ); ?></button>
				</form>
			</div>

			<table class="wp-list-table widefat striped esk-table">
				<thead><tr><th><?php esc_html_e( 'Name', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Rooms', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Warden', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Status', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Actions', 'eskoofy' ); ?></th></tr></thead>
				<tbody>
					<?php if ( empty( $hostels ) ) : ?>
						<tr><td colspan="5"><?php esc_html_e( 'No hostels found.', 'eskoofy' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $hostels as $h ) : ?>
							<tr>
								<td><strong><?php echo esc_html( $h->name ); ?></strong></td>
								<td><?php echo esc_html( $h->total_rooms ); ?></td>
								<td><?php echo esc_html( $h->warden_name ); ?></td>
								<td><span class="esk-badge esk-badge-<?php echo esc_attr( $h->status ); ?>"><?php echo esc_html( ucfirst( $h->status ) ); ?></span></td>
								<td>
									<form method="post" style="display:inline;" onsubmit="return confirm('<?php esc_attr_e( 'Delete this hostel?', 'eskoofy' ); ?>');">
										<?php wp_nonce_field( 'esk_hostel_delete_' . $h->id ); ?>
										<input type="hidden" name="hostel_id" value="<?php echo esc_attr( $h->id ); ?>">
										<button type="submit" name="esk_hostel_delete" class="button button-small"><?php esc_html_e( 'Delete', 'eskoofy' ); ?></button>
									</form>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>

		<div class="esk-dashboard-column esk-col-narrow">
			<div class="esk-card esk-form-card" style="margin-bottom:1.5rem;">
				<h2><?php esc_html_e( 'Add Room', 'eskoofy' ); ?></h2>
				<form method="post" class="esk-form">
					<?php wp_nonce_field( 'esk_hostel_room_form' ); ?>
					<div class="esk-form-group">
						<label><?php esc_html_e( 'Hostel', 'eskoofy' ); ?> *</label>
						<select name="hostel_id" required>
							<option value=""><?php esc_html_e( 'Select', 'eskoofy' ); ?></option>
							<?php foreach ( $hostels as $h ) : ?>
								<option value="<?php echo esc_attr( $h->id ); ?>"><?php echo esc_html( $h->name ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="esk-form-group"><label><?php esc_html_e( 'Room Number', 'eskoofy' ); ?> *</label><input type="text" name="room_number" required></div>
					<div class="esk-form-group">
						<label><?php esc_html_e( 'Type', 'eskoofy' ); ?></label>
						<select name="room_type">
							<option value="single"><?php esc_html_e( 'Single', 'eskoofy' ); ?></option>
							<option value="double" selected><?php esc_html_e( 'Double', 'eskoofy' ); ?></option>
							<option value="triple"><?php esc_html_e( 'Triple', 'eskoofy' ); ?></option>
							<option value="dormitory"><?php esc_html_e( 'Dormitory', 'eskoofy' ); ?></option>
						</select>
					</div>
					<div class="esk-form-group"><label><?php esc_html_e( 'Capacity', 'eskoofy' ); ?></label><input type="number" name="capacity" value="2" min="1"></div>
					<button type="submit" name="esk_hostel_room_save" class="button button-primary"><?php esc_html_e( 'Add Room', 'eskoofy' ); ?></button>
				</form>
			</div>

			<table class="wp-list-table widefat striped esk-table">
				<thead><tr><th><?php esc_html_e( 'Hostel', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Room', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Type', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Occupied', 'eskoofy' ); ?></th></tr></thead>
				<tbody>
					<?php if ( empty( $rooms ) ) : ?>
						<tr><td colspan="4"><?php esc_html_e( 'No rooms.', 'eskoofy' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $rooms as $r ) : ?>
							<tr>
								<td><?php echo esc_html( $r->hostel_name ); ?></td>
								<td><?php echo esc_html( $r->room_number ); ?></td>
								<td><?php echo esc_html( ucfirst( $r->room_type ) ); ?></td>
								<td><?php echo esc_html( $r->occupied . '/' . $r->capacity ); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
