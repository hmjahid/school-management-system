<?php
/**
 * Staff directory — all non-teaching staff users (staff/accountant/librarian).
 *
 * Mirrors the app's dashboard.modules.staff page.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;

$search = sanitize_text_field( $_GET['s'] ?? '' );
$role   = sanitize_text_field( $_GET['role'] ?? '' );

$args = array(
	'number'  => 100,
	'orderby' => 'display_name',
	'order'   => 'ASC',
);

$all_roles = wp_roles()->get_names();
$staff_roles = array( 'staff', 'accountant', 'librarian', 'editor', 'author', 'contributor' );

if ( $role && in_array( $role, $staff_roles, true ) ) {
	$args['role'] = $role;
} elseif ( ! $role ) {
	$args['role__in'] = $staff_roles;
}

if ( $search ) {
	$args['search'] = '*' . $search . '*';
	$args['search_columns'] = array( 'user_login', 'user_email', 'display_name' );
}

$user_query = new WP_User_Query( $args );
$staff      = $user_query->get_results();
$flash      = esk_get_flash( 'success' );
?>
<div class="wrap esk-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Staff Directory', 'eskoofy' ); ?></h1>
	<hr class="wp-header-end">

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<div class="esk-toolbar">
		<form method="get" class="esk-filter-form">
			<input type="hidden" name="page" value="esk-staff-directory">
			<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search staff...', 'eskoofy' ); ?>">
			<select name="role">
				<option value=""><?php esc_html_e( 'All staff roles', 'eskoofy' ); ?></option>
				<?php foreach ( $all_roles as $slug => $label ) : ?>
					<?php if ( in_array( $slug, $staff_roles, true ) ) : ?>
						<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $role, $slug ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endif; ?>
				<?php endforeach; ?>
			</select>
			<button type="submit" class="button"><?php esc_html_e( 'Filter', 'eskoofy' ); ?></button>
		</form>
	</div>

	<div class="esk-table-scroll">
		<table class="wp-list-table widefat fixed striped esk-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Name', 'eskoofy' ); ?></th>
					<th><?php esc_html_e( 'Email', 'eskoofy' ); ?></th>
					<th><?php esc_html_e( 'Role', 'eskoofy' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $staff ) ) : ?>
					<tr><td colspan="3">
						<div class="esk-empty-state">
							<div class="esk-empty-state-icon"><span class="dashicons dashicons-groups"></span></div>
							<p class="esk-empty-state-title"><?php esc_html_e( 'No staff found', 'eskoofy' ); ?></p>
							<p class="esk-empty-state-message"><?php esc_html_e( 'Staff are WordPress users with a staff, accountant or librarian role.', 'eskoofy' ); ?></p>
						</div>
					</td></tr>
				<?php else : ?>
					<?php foreach ( $staff as $u ) : ?>
						<tr>
							<td><strong><?php echo esc_html( $u->display_name ); ?></strong></td>
							<td><?php echo esc_html( $u->user_email ); ?></td>
							<td>
								<?php foreach ( $u->roles as $r ) : ?>
									<span class="esk-badge esk-badge-active"><?php echo esc_html( ucfirst( $r ) ); ?></span>
								<?php endforeach; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>