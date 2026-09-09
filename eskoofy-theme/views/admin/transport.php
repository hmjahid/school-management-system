<?php
/**
 * Transport management — vehicles, routes, assignments.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

if ( isset( $_POST['esk_vehicle_save'] ) ) {
	check_admin_referer( 'esk_vehicle_form' );
	$wpdb->insert( $wpdb->prefix . 'esk_vehicles', array(
		'number'       => sanitize_text_field( $_POST['number'] ?? '' ),
		'type'         => sanitize_text_field( $_POST['type'] ?? '' ),
		'capacity'     => absint( $_POST['capacity'] ?? 0 ),
		'driver_name'  => sanitize_text_field( $_POST['driver_name'] ?? '' ),
		'driver_phone' => sanitize_text_field( $_POST['driver_phone'] ?? '' ),
		'is_active'    => 1,
	) );
	esk_flash( 'success', __( 'Vehicle added.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-transport' ) );
	exit;
}

if ( isset( $_POST['esk_route_save'] ) ) {
	check_admin_referer( 'esk_route_form' );
	$wpdb->insert( $wpdb->prefix . 'esk_transport_routes', array(
		'name'       => sanitize_text_field( $_POST['name'] ?? '' ),
		'code'       => sanitize_text_field( $_POST['code'] ?? '' ),
		'fare'       => (float) ( $_POST['fare'] ?? 0 ),
		'vehicle_id' => absint( $_POST['vehicle_id'] ?? 0 ) ?: null,
		'is_active'  => 1,
	) );
	esk_flash( 'success', __( 'Route added.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-transport' ) );
	exit;
}

if ( isset( $_POST['esk_transport_delete'] ) ) {
	check_admin_referer( 'esk_transport_delete_' . absint( $_POST['item_id'] ?? 0 ) . '_' . sanitize_text_field( $_POST['item_type'] ?? '' ) );
	$item_type = sanitize_text_field( $_POST['item_type'] ?? '' );
	$item_id   = absint( $_POST['item_id'] ?? 0 );
	if ( $item_id && in_array( $item_type, array( 'vehicle', 'route' ), true ) ) {
		$table = 'vehicle' === $item_type ? 'esk_vehicles' : 'esk_transport_routes';
		$wpdb->delete( $wpdb->prefix . $table, array( 'id' => $item_id ) );
	}
	esk_flash( 'success', __( 'Item deleted.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-transport' ) );
	exit;
}

$vehicles = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}esk_vehicles ORDER BY number" );
$routes   = $wpdb->get_results(
	"SELECT r.*, v.number AS vehicle_number
	FROM {$wpdb->prefix}esk_transport_routes r
	LEFT JOIN {$wpdb->prefix}esk_vehicles v ON r.vehicle_id = v.id
	ORDER BY r.name"
);
$flash = esk_get_flash( 'success' );
$tab   = sanitize_text_field( $_GET['tab'] ?? 'vehicles' );
?>
<div class="wrap esk-admin-wrap">
	<h1><?php esc_html_e( 'Transport', 'eskoofy' ); ?></h1>

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<nav class="nav-tab-wrapper esk-tabs">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=esk-transport&tab=vehicles' ) ); ?>" class="nav-tab <?php echo 'vehicles' === $tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Vehicles', 'eskoofy' ); ?></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=esk-transport&tab=routes' ) ); ?>" class="nav-tab <?php echo 'routes' === $tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Routes', 'eskoofy' ); ?></a>
	</nav>

	<?php if ( 'vehicles' === $tab ) : ?>
		<div class="esk-card esk-form-card" style="margin-bottom:1.5rem;">
			<h2><?php esc_html_e( 'Add Vehicle', 'eskoofy' ); ?></h2>
			<form method="post" class="esk-form">
				<?php wp_nonce_field( 'esk_vehicle_form' ); ?>
				<div class="esk-form-row">
					<div class="esk-form-group">
						<label><?php esc_html_e( 'Number', 'eskoofy' ); ?> *</label>
						<input type="text" name="number" required>
					</div>
					<div class="esk-form-group">
						<label><?php esc_html_e( 'Type', 'eskoofy' ); ?></label>
						<input type="text" name="type" placeholder="<?php esc_attr_e( 'Bus / Van', 'eskoofy' ); ?>">
					</div>
					<div class="esk-form-group">
						<label><?php esc_html_e( 'Capacity', 'eskoofy' ); ?></label>
						<input type="number" name="capacity" min="0" style="width:80px;">
					</div>
					<div class="esk-form-group">
						<label><?php esc_html_e( 'Driver Name', 'eskoofy' ); ?></label>
						<input type="text" name="driver_name">
					</div>
					<div class="esk-form-group">
						<label><?php esc_html_e( 'Driver Phone', 'eskoofy' ); ?></label>
						<input type="tel" name="driver_phone">
					</div>
				</div>
				<button type="submit" name="esk_vehicle_save" class="button button-primary"><?php esc_html_e( 'Add Vehicle', 'eskoofy' ); ?></button>
			</form>
		</div>

		<table class="wp-list-table widefat striped esk-table">
			<thead><tr>
				<th><?php esc_html_e( 'Number', 'eskoofy' ); ?></th>
				<th><?php esc_html_e( 'Type', 'eskoofy' ); ?></th>
				<th><?php esc_html_e( 'Capacity', 'eskoofy' ); ?></th>
				<th><?php esc_html_e( 'Driver', 'eskoofy' ); ?></th>
				<th><?php esc_html_e( 'Phone', 'eskoofy' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'eskoofy' ); ?></th>
			</tr></thead>
			<tbody>
				<?php if ( empty( $vehicles ) ) : ?>
					<tr><td colspan="6"><?php esc_html_e( 'No vehicles found.', 'eskoofy' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $vehicles as $v ) : ?>
						<tr>
							<td><strong><?php echo esc_html( $v->number ); ?></strong></td>
							<td><?php echo esc_html( $v->type ); ?></td>
							<td><?php echo esc_html( $v->capacity ); ?></td>
							<td><?php echo esc_html( $v->driver_name ); ?></td>
							<td><?php echo esc_html( $v->driver_phone ); ?></td>
							<td>
								<form method="post" style="display:inline;" onsubmit="return confirm('<?php esc_attr_e( 'Delete this vehicle?', 'eskoofy' ); ?>');">
									<?php wp_nonce_field( 'esk_transport_delete_' . $v->id . '_vehicle' ); ?>
									<input type="hidden" name="item_id" value="<?php echo esc_attr( $v->id ); ?>">
									<input type="hidden" name="item_type" value="vehicle">
									<button type="submit" name="esk_transport_delete" class="button button-small"><?php esc_html_e( 'Delete', 'eskoofy' ); ?></button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>

	<?php else : ?>
		<div class="esk-card esk-form-card" style="margin-bottom:1.5rem;">
			<h2><?php esc_html_e( 'Add Route', 'eskoofy' ); ?></h2>
			<form method="post" class="esk-form">
				<?php wp_nonce_field( 'esk_route_form' ); ?>
				<div class="esk-form-row">
					<div class="esk-form-group">
						<label><?php esc_html_e( 'Name', 'eskoofy' ); ?> *</label>
						<input type="text" name="name" required>
					</div>
					<div class="esk-form-group">
						<label><?php esc_html_e( 'Code', 'eskoofy' ); ?> *</label>
						<input type="text" name="code" required>
					</div>
					<div class="esk-form-group">
						<label><?php esc_html_e( 'Fare', 'eskoofy' ); ?></label>
						<input type="number" name="fare" step="0.01" min="0" style="width:100px;">
					</div>
					<div class="esk-form-group">
						<label><?php esc_html_e( 'Vehicle', 'eskoofy' ); ?></label>
						<select name="vehicle_id">
							<option value=""><?php esc_html_e( 'None', 'eskoofy' ); ?></option>
							<?php foreach ( $vehicles as $v ) : ?>
								<option value="<?php echo esc_attr( $v->id ); ?>"><?php echo esc_html( $v->number ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
				<button type="submit" name="esk_route_save" class="button button-primary"><?php esc_html_e( 'Add Route', 'eskoofy' ); ?></button>
			</form>
		</div>

		<table class="wp-list-table widefat striped esk-table">
			<thead><tr>
				<th><?php esc_html_e( 'Name', 'eskoofy' ); ?></th>
				<th><?php esc_html_e( 'Code', 'eskoofy' ); ?></th>
				<th><?php esc_html_e( 'Fare', 'eskoofy' ); ?></th>
				<th><?php esc_html_e( 'Vehicle', 'eskoofy' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'eskoofy' ); ?></th>
			</tr></thead>
			<tbody>
				<?php if ( empty( $routes ) ) : ?>
					<tr><td colspan="5"><?php esc_html_e( 'No routes found.', 'eskoofy' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $routes as $r ) : ?>
						<tr>
							<td><strong><?php echo esc_html( $r->name ); ?></strong></td>
							<td><?php echo esc_html( $r->code ); ?></td>
							<td><?php echo esc_html( esk_format_currency( $r->fare ) ); ?></td>
							<td><?php echo esc_html( $r->vehicle_number ?? '—' ); ?></td>
							<td>
								<form method="post" style="display:inline;" onsubmit="return confirm('<?php esc_attr_e( 'Delete this route?', 'eskoofy' ); ?>');">
									<?php wp_nonce_field( 'esk_transport_delete_' . $r->id . '_route' ); ?>
									<input type="hidden" name="item_id" value="<?php echo esc_attr( $r->id ); ?>">
									<input type="hidden" name="item_type" value="route">
									<button type="submit" name="esk_transport_delete" class="button button-small"><?php esc_html_e( 'Delete', 'eskoofy' ); ?></button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
