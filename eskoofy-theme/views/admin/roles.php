<?php
/**
 * Roles & Permissions management.
 *
 * Maps WordPress roles to esk capability keys (stored in `esk_role_caps`).
 * Administrators always pass every capability.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;

$capabilities = array(
	'dashboard'   => __( 'Dashboard access', 'eskoofy' ),
	'students'    => __( 'Students', 'eskoofy' ),
	'teachers'    => __( 'Teachers', 'eskoofy' ),
	'guardians'   => __( 'Parents / Guardians', 'eskoofy' ),
	'academics'   => __( 'Classes, Exams, Results, Assignments', 'eskoofy' ),
	'admissions'  => __( 'Admissions', 'eskoofy' ),
	'attendance'  => __( 'Attendance', 'eskoofy' ),
	'finance'     => __( 'Fees, Payments, Expenses', 'eskoofy' ),
	'hr'          => __( 'Leaves, Payroll', 'eskoofy' ),
	'documents'   => __( 'Admit / ID / Certificates', 'eskoofy' ),
	'library'     => __( 'Library', 'eskoofy' ),
	'events'      => __( 'Events', 'eskoofy' ),
	'transport'   => __( 'Transport', 'eskoofy' ),
	'hostels'     => __( 'Hostels', 'eskoofy' ),
	'sms'         => __( 'SMS / Communications', 'eskoofy' ),
	'content'     => __( 'Website CMS', 'eskoofy' ),
	'users'       => __( 'Users & Roles', 'eskoofy' ),
	'settings'    => __( 'Settings / Configuration', 'eskoofy' ),
	'reports'     => __( 'Reports', 'eskoofy' ),
);

if ( isset( $_POST['esk_roles_save'] ) ) {
	check_admin_referer( 'esk_roles_form' );
	$map = array();
	$all_roles = array_keys( wp_roles()->get_names() );
	foreach ( $all_roles as $role_slug ) {
		$granted = isset( $_POST['caps'][ $role_slug ] ) && is_array( $_POST['caps'][ $role_slug ] )
			? array_map( 'sanitize_key', array_keys( $_POST['caps'][ $role_slug ] ) )
			: array();
		$map[ $role_slug ] = $granted;
	}
	update_option( 'esk_role_caps', $map );
	esk_flash( 'success', __( 'Role permissions saved.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-roles' ) );
	exit;
}

$all_roles = wp_roles()->get_names();
$map       = (array) get_option( 'esk_role_caps', array() );
$flash     = esk_get_flash( 'success' );
?>
<div class="wrap esk-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Roles & Permissions', 'eskoofy' ); ?></h1>
	<hr class="wp-header-end">

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<p style="max-width:60rem;"><?php esc_html_e( 'Grant dashboard capabilities to each WordPress role. The Administrator role always has full access. Only roles with at least one capability can sign in to the management dashboard.', 'eskoofy' ); ?></p>

	<form method="post">
		<?php wp_nonce_field( 'esk_roles_form' ); ?>
		<div class="esk-table-scroll">
			<table class="wp-list-table widefat striped esk-table">
				<thead>
					<tr>
						<th style="min-width:12rem;"><?php esc_html_e( 'Role', 'eskoofy' ); ?></th>
						<?php foreach ( $capabilities as $cap_key => $cap_label ) : ?>
							<th style="text-align:center;font-size:0.72rem;line-height:1.3;"><?php echo esc_html( $cap_label ); ?></th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $all_roles as $role_slug => $role_label ) : ?>
						<?php $granted = (array) ( $map[ $role_slug ] ?? array() ); ?>
						<tr>
							<td>
								<strong><?php echo esc_html( $role_label ); ?></strong>
								<small class="description" style="display:block;"><?php echo esc_html( $role_slug ); ?></small>
							</td>
							<?php foreach ( $capabilities as $cap_key => $cap_label ) : ?>
								<td style="text-align:center;">
									<input type="checkbox" name="caps[<?php echo esc_attr( $role_slug ); ?>][<?php echo esc_attr( $cap_key ); ?>]" value="1"
										<?php checked( in_array( $cap_key, $granted, true ), true ); ?>
										<?php disabled( 'administrator' === $role_slug ); ?>>
								</td>
							<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<p class="submit"><button type="submit" name="esk_roles_save" class="button button-primary"><?php esc_html_e( 'Save Permissions', 'eskoofy' ); ?></button></p>
	</form>
</div>