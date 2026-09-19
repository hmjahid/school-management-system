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

if ( isset( $_POST['esk_hostel_room_update'] ) ) {
	check_admin_referer( 'esk_hostel_room_form' );
	$room_id = absint( $_POST['room_id'] ?? 0 );
	if ( $room_id ) {
		$wpdb->update( $wpdb->prefix . 'esk_hostel_rooms', array(
			'hostel_id'   => absint( $_POST['hostel_id'] ?? 0 ),
			'room_number' => sanitize_text_field( $_POST['room_number'] ?? '' ),
			'room_type'   => sanitize_text_field( $_POST['room_type'] ?? 'double' ),
			'capacity'    => absint( $_POST['capacity'] ?? 2 ),
		), array( 'id' => $room_id ) );
	}
	esk_flash( 'success', __( 'Room updated.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-hostels' ) );
	exit;
}

if ( isset( $_POST['esk_hostel_allocate'] ) ) {
	check_admin_referer( 'esk_hostel_allocate_form' );
	$student_id = absint( $_POST['student_id'] ?? 0 );
	$room_id    = absint( $_POST['room_id'] ?? 0 );
	$check_in   = sanitize_text_field( $_POST['check_in_date'] ?? gmdate( 'Y-m-d' ) );
	if ( $student_id && $room_id ) {
		$wpdb->insert( $wpdb->prefix . 'esk_hostel_assignments', array(
			'student_id'     => $student_id,
			'room_id'        => $room_id,
			'check_in_date'  => $check_in,
			'status'         => 'active',
		) );
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->prefix}esk_hostel_rooms SET occupied = occupied + 1, status = IF(occupied + 1 >= capacity, 'full', 'available') WHERE id = %d",
				$room_id
			)
		);
	}
	esk_flash( 'success', __( 'Student allocated to room.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-hostels' ) );
	exit;
}

if ( isset( $_POST['esk_hostel_checkout'] ) ) {
	check_admin_referer( 'esk_hostel_checkout_' . absint( $_POST['assignment_id'] ?? 0 ) );
	$assignment_id = absint( $_POST['assignment_id'] ?? 0 );
	if ( $assignment_id ) {
		$assignment = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}esk_hostel_assignments WHERE id = %d", $assignment_id ) );
		if ( $assignment ) {
			$wpdb->update( $wpdb->prefix . 'esk_hostel_assignments', array(
				'check_out_date' => gmdate( 'Y-m-d' ),
				'status'         => 'checked_out',
			), array( 'id' => $assignment_id ) );
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->prefix}esk_hostel_rooms SET occupied = GREATEST(0, occupied - 1), status = IF(occupied - 1 > 0, 'available', 'available') WHERE id = %d",
					$assignment->room_id
				)
			);
		}
	}
	esk_flash( 'success', __( 'Student checked out.', 'eskoofy' ) );
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
$students = $wpdb->get_results(
	"SELECT s.id, u.display_name FROM {$wpdb->prefix}esk_students s JOIN {$wpdb->prefix}users u ON s.user_id = u.ID WHERE s.status = 'active' AND s.deleted_at IS NULL ORDER BY u.display_name"
);
$assignments = $wpdb->get_results(
	"SELECT a.*, u.display_name AS student_name, h.name AS hostel_name, r.room_number
	FROM {$wpdb->prefix}esk_hostel_assignments a
	LEFT JOIN {$wpdb->prefix}esk_students st ON a.student_id = st.id
	LEFT JOIN {$wpdb->prefix}users u ON st.user_id = u.ID
	LEFT JOIN {$wpdb->prefix}esk_hostel_rooms r ON a.room_id = r.id
	LEFT JOIN {$wpdb->prefix}esk_hostels h ON r.hostel_id = h.id
	WHERE a.status = 'active'
	ORDER BY a.check_in_date DESC"
);
$flash = esk_get_flash( 'success' );

$edit_room = null;
if ( isset( $_GET['edit_room'] ) ) {
	$edit_room = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}esk_hostel_rooms WHERE id = %d", absint( $_GET['edit_room'] ) ) );
}
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
				<h2><?php echo $edit_room ? esc_html__( 'Edit Room', 'eskoofy' ) : esc_html__( 'Add Room', 'eskoofy' ); ?></h2>
				<form method="post" class="esk-form">
					<?php wp_nonce_field( 'esk_hostel_room_form' ); ?>
					<?php if ( $edit_room ) : ?>
						<input type="hidden" name="room_id" value="<?php echo esc_attr( $edit_room->id ); ?>">
					<?php endif; ?>
					<div class="esk-form-group">
						<label><?php esc_html_e( 'Hostel', 'eskoofy' ); ?> *</label>
						<select name="hostel_id" required>
							<option value=""><?php esc_html_e( 'Select', 'eskoofy' ); ?></option>
							<?php foreach ( $hostels as $h ) : ?>
								<option value="<?php echo esc_attr( $h->id ); ?>" <?php selected( $edit_room->hostel_id ?? 0, $h->id ); ?>><?php echo esc_html( $h->name ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="esk-form-group"><label><?php esc_html_e( 'Room Number', 'eskoofy' ); ?> *</label><input type="text" name="room_number" required value="<?php echo esc_attr( $edit_room->room_number ?? '' ); ?>"></div>
					<div class="esk-form-group">
						<label><?php esc_html_e( 'Type', 'eskoofy' ); ?></label>
						<select name="room_type">
							<option value="single" <?php selected( $edit_room->room_type ?? '', 'single' ); ?>><?php esc_html_e( 'Single', 'eskoofy' ); ?></option>
							<option value="double" <?php selected( $edit_room->room_type ?? '', 'double' ); ?>><?php esc_html_e( 'Double', 'eskoofy' ); ?></option>
							<option value="triple" <?php selected( $edit_room->room_type ?? '', 'triple' ); ?>><?php esc_html_e( 'Triple', 'eskoofy' ); ?></option>
							<option value="dormitory" <?php selected( $edit_room->room_type ?? '', 'dormitory' ); ?>><?php esc_html_e( 'Dormitory', 'eskoofy' ); ?></option>
						</select>
					</div>
					<div class="esk-form-group"><label><?php esc_html_e( 'Capacity', 'eskoofy' ); ?></label><input type="number" name="capacity" value="<?php echo esc_attr( $edit_room->capacity ?? 2 ); ?>" min="1"></div>
					<button type="submit" name="<?php echo $edit_room ? 'esk_hostel_room_update' : 'esk_hostel_room_save'; ?>" class="button button-primary"><?php echo $edit_room ? esc_html__( 'Update Room', 'eskoofy' ) : esc_html__( 'Add Room', 'eskoofy' ); ?></button>
					<?php if ( $edit_room ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=esk-hostels' ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'eskoofy' ); ?></a>
					<?php endif; ?>
				</form>
			</div>

			<div class="esk-card esk-form-card" style="margin-bottom:1.5rem;">
				<h2><?php esc_html_e( 'Allocate Student', 'eskoofy' ); ?></h2>
				<form method="post" class="esk-form">
					<?php wp_nonce_field( 'esk_hostel_allocate_form' ); ?>
					<div class="esk-form-group">
						<label><?php esc_html_e( 'Student', 'eskoofy' ); ?> *</label>
						<select name="student_id" required>
							<option value=""><?php esc_html_e( 'Select', 'eskoofy' ); ?></option>
							<?php foreach ( $students as $st ) : ?>
								<option value="<?php echo esc_attr( $st->id ); ?>"><?php echo esc_html( $st->display_name ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="esk-form-group">
						<label><?php esc_html_e( 'Room', 'eskoofy' ); ?> *</label>
						<select name="room_id" required>
							<option value=""><?php esc_html_e( 'Select', 'eskoofy' ); ?></option>
							<?php foreach ( $rooms as $r ) : ?>
								<?php if ( $r->occupied < $r->capacity ) : ?>
									<option value="<?php echo esc_attr( $r->id ); ?>"><?php echo esc_html( $r->hostel_name . ' — ' . $r->room_number . ' (' . $r->occupied . '/' . $r->capacity . ')' ); ?></option>
								<?php endif; ?>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="esk-form-group"><label><?php esc_html_e( 'Check-in', 'eskoofy' ); ?></label><input type="date" name="check_in_date" value="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>"></div>
					<button type="submit" name="esk_hostel_allocate" class="button button-primary"><?php esc_html_e( 'Allocate', 'eskoofy' ); ?></button>
				</form>
			</div>

			<table class="wp-list-table widefat striped esk-table">
				<thead><tr><th><?php esc_html_e( 'Hostel', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Room', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Type', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Occupied', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Actions', 'eskoofy' ); ?></th></tr></thead>
				<tbody>
					<?php if ( empty( $rooms ) ) : ?>
						<tr><td colspan="5"><?php esc_html_e( 'No rooms.', 'eskoofy' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $rooms as $r ) : ?>
							<tr>
								<td><?php echo esc_html( $r->hostel_name ); ?></td>
								<td><?php echo esc_html( $r->room_number ); ?></td>
								<td><?php echo esc_html( ucfirst( $r->room_type ) ); ?></td>
								<td><?php echo esc_html( $r->occupied . '/' . $r->capacity ); ?></td>
								<td><a href="<?php echo esc_url( admin_url( 'admin.php?page=esk-hostels&edit_room=' . $r->id ) ); ?>" class="button button-small"><?php esc_html_e( 'Edit', 'eskoofy' ); ?></a></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>

			<table class="wp-list-table widefat striped esk-table" style="margin-top:1.5rem;">
				<thead><tr><th><?php esc_html_e( 'Student', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Hostel / Room', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Check-in', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Actions', 'eskoofy' ); ?></th></tr></thead>
				<tbody>
					<?php if ( empty( $assignments ) ) : ?>
						<tr><td colspan="4"><?php esc_html_e( 'No active allocations.', 'eskoofy' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $assignments as $a ) : ?>
							<tr>
								<td><strong><?php echo esc_html( $a->student_name ); ?></strong></td>
								<td><?php echo esc_html( $a->hostel_name . ' — ' . $a->room_number ); ?></td>
								<td><?php echo esc_html( esk_date_format( $a->check_in_date ) ); ?></td>
								<td>
									<form method="post" style="display:inline;" onsubmit="return confirm('<?php esc_attr_e( 'Check out this student?', 'eskoofy' ); ?>', 'brand')">
										<?php wp_nonce_field( 'esk_hostel_checkout_' . $a->id ); ?>
										<input type="hidden" name="assignment_id" value="<?php echo esc_attr( $a->id ); ?>">
										<button type="submit" name="esk_hostel_checkout" class="button button-small"><?php esc_html_e( 'Check Out', 'eskoofy' ); ?></button>
									</form>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
