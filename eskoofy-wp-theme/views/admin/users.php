<?php
/**
 * Users management — wraps WP users with role filter.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;

if ( isset( $_POST['esk_user_save'] ) ) {
	check_admin_referer( 'esk_user_form' );
	$username = sanitize_user( $_POST['username'] ?? '' );
	$email    = sanitize_email( $_POST['email'] ?? '' );
	$password = isset( $_POST['password'] ) ? $_POST['password'] : '';
	$role     = sanitize_text_field( $_POST['role'] ?? 'subscriber' );

	$user_id = wp_create_user( $username, $email, $password ? $password : wp_generate_password() );

	if ( ! is_wp_error( $user_id ) ) {
		$user = new WP_User( $user_id );
		$user->set_role( $role );
		esk_flash( 'success', __( 'User created.', 'eskoofy' ) );
	} else {
		esk_flash( 'error', $user_id->get_error_message() );
	}
	wp_safe_redirect( admin_url( 'admin.php?page=esk-users' ) );
	exit;
}

if ( isset( $_POST['esk_user_delete'] ) ) {
	check_admin_referer( 'esk_user_delete_' . absint( $_POST['user_id'] ?? 0 ) );
	$del_user_id = absint( $_POST['user_id'] ?? 0 );
	if ( $del_user_id && $del_user_id !== get_current_user_id() ) {
		require_once ABSPATH . 'wp-admin/includes/user.php';
		wp_delete_user( $del_user_id );
	}
	esk_flash( 'success', __( 'User deleted.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-users' ) );
	exit;
}

$role_filter = sanitize_text_field( $_GET['role'] ?? '' );
$args        = array(
	'number' => 50,
	'orderby' => 'ID',
	'order'   => 'DESC',
);
if ( $role_filter ) {
	$args['role'] = $role_filter;
}

$wp_user_query = new WP_User_Query( $args );
$users         = $wp_user_query->get_results();
$all_roles     = wp_roles()->get_names();
$flash         = esk_get_flash( 'success' );
?>
<div class="wrap esk-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Users', 'eskoofy' ); ?></h1>
	<hr class="wp-header-end">

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<div class="esk-card esk-form-card" style="margin-bottom:1.5rem;">
		<h2><?php esc_html_e( 'Add New User', 'eskoofy' ); ?></h2>
		<form method="post" class="esk-form">
			<?php wp_nonce_field( 'esk_user_form' ); ?>
			<div class="esk-form-row">
				<div class="esk-form-group"><label><?php esc_html_e( 'Username', 'eskoofy' ); ?> *</label><input type="text" name="username" required></div>
				<div class="esk-form-group"><label><?php esc_html_e( 'Email', 'eskoofy' ); ?> *</label><input type="email" name="email" required></div>
				<div class="esk-form-group"><label><?php esc_html_e( 'Password', 'eskoofy' ); ?></label><input type="text" name="password" placeholder="<?php esc_attr_e( 'Auto-generated if empty', 'eskoofy' ); ?>"></div>
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Role', 'eskoofy' ); ?></label>
					<select name="role">
						<?php foreach ( $all_roles as $slug => $label ) : ?>
							<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
			<button type="submit" name="esk_user_save" class="button button-primary"><?php esc_html_e( 'Add User', 'eskoofy' ); ?></button>
		</form>
	</div>

	<div class="esk-toolbar">
		<form method="get" class="esk-filter-form">
			<input type="hidden" name="page" value="esk-users">
			<label><?php esc_html_e( 'Filter by role:', 'eskoofy' ); ?></label>
			<select name="role">
				<option value=""><?php esc_html_e( 'All Roles', 'eskoofy' ); ?></option>
				<?php foreach ( $all_roles as $slug => $label ) : ?>
					<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $role_filter, $slug ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<button type="submit" class="button"><?php esc_html_e( 'Filter', 'eskoofy' ); ?></button>
		</form>
	</div>

	<table class="wp-list-table widefat striped esk-table">
		<thead><tr>
			<th><?php esc_html_e( 'Username', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Name', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Email', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Role', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Actions', 'eskoofy' ); ?></th>
		</tr></thead>
		<tbody>
			<?php if ( empty( $users ) ) : ?>
				<tr><td colspan="5"><?php esc_html_e( 'No users found.', 'eskoofy' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $users as $u ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $u->user_login ); ?></strong></td>
						<td><?php echo esc_html( $u->display_name ); ?></td>
						<td><?php echo esc_html( $u->user_email ); ?></td>
						<td><?php echo esc_html( implode( ', ', $u->roles ) ); ?></td>
						<td>
							<?php if ( $u->ID !== get_current_user_id() ) : ?>
								<form method="post" style="display:inline;" onsubmit="return confirm('<?php esc_attr_e( 'Delete this user?', 'eskoofy' ); ?>');">
									<?php wp_nonce_field( 'esk_user_delete_' . $u->ID ); ?>
									<input type="hidden" name="user_id" value="<?php echo esc_attr( $u->ID ); ?>">
									<button type="submit" name="esk_user_delete" class="button button-small"><?php esc_html_e( 'Delete', 'eskoofy' ); ?></button>
								</form>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
