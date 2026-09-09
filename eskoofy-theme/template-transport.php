<?php
/**
 * Template Name: Transport
 *
 * @package Eskoofy
 */

declare(strict_types=1);

get_header();
?>
<div class="eskoofy-container">
	<?php
	global $wpdb;
	$vehicles = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}esk_vehicles ORDER BY number" );
	$routes   = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}esk_transport_routes WHERE is_active = 1 ORDER BY name" );
	?>
	<h2><?php esc_html_e( 'Vehicles', 'eskoofy' ); ?></h2>
	<table class="esk-table esk-table-striped">
		<thead><tr><th><?php esc_html_e( 'Number', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Type', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Capacity', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Driver', 'eskoofy' ); ?></th></tr></thead>
		<tbody>
			<?php if ( empty( $vehicles ) ) : ?>
				<tr><td colspan="4"><?php esc_html_e( 'No vehicles.', 'eskoofy' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $vehicles as $v ) : ?>
					<tr>
						<td><?php echo esc_html( $v->number ); ?></td>
						<td><?php echo esc_html( $v->type ); ?></td>
						<td><?php echo esc_html( $v->capacity ); ?></td>
						<td><?php echo esc_html( $v->driver_name . ' (' . $v->driver_phone . ')' ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>

	<h2 style="margin-top:2rem;"><?php esc_html_e( 'Routes', 'eskoofy' ); ?></h2>
	<table class="esk-table esk-table-striped">
		<thead><tr><th><?php esc_html_e( 'Name', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Code', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Fare', 'eskoofy' ); ?></th></tr></thead>
		<tbody>
			<?php if ( empty( $routes ) ) : ?>
				<tr><td colspan="3"><?php esc_html_e( 'No routes.', 'eskoofy' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $routes as $r ) : ?>
					<tr><td><?php echo esc_html( $r->name ); ?></td><td><?php echo esc_html( $r->code ); ?></td><td><?php echo esc_html( esk_format_currency( $r->fare ) ); ?></td></tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
<?php get_footer();