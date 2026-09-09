<?php
/**
 * Guardians management.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

if ( isset( $_POST['esk_guardian_save'] ) ) {
	check_admin_referer( 'esk_guardian_form' );
	$name  = sanitize_text_field( $_POST['name'] ?? '' );
	$email = sanitize_email( $_POST['email'] ?? '' );
	$phone = sanitize_text_field( $_POST['phone'] ?? '' );

	$user_id = wp_insert_user( array(
		'user_login'   => $email,
		'user_email'   => $email,
		'display_name' => $name,
		'user_pass'    => wp_generate_password(),
		'role'         => 'subscriber',
	) );

	if ( ! is_wp_error( $user_id ) ) {
		$wpdb->insert( $wpdb->prefix . 'esk_guardians', array(
			'user_id' => $user_id,
		) );
		esk_flash( 'success', __( 'Guardian added.', 'eskoofy' ) );
	} else {
		esk_flash( 'error', $user_id->get_error_message() );
	}
	wp_safe_redirect( admin_url( 'admin.php?page=esk-guardians' ) );
	exit;
}

if ( isset( $_POST['esk_guardian_delete'] ) ) {
	check_admin_referer( 'esk_guardian_delete_' . absint( $_POST['guardian_id'] ?? 0 ) );
	$guardian_id = absint( $_POST['guardian_id'] ?? 0 );
	if ( $guardian_id ) {
		$wpdb->update( $wpdb->prefix . 'esk_guardians', array( 'deleted_at' => current_time( 'mysql' ) ), array( 'id' => $guardian_id ) );
	}
	esk_flash( 'success', __( 'Guardian deleted.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-guardians' ) );
	exit;
}

$guardians = $wpdb->get_results(
	"SELECT g.*, u.display_name, u.user_email
	FROM {$wpdb->prefix}esk_guardians g
	JOIN {$wpdb->prefix}users u ON g.user_id = u.ID
	WHERE g.deleted_at IS NULL
	ORDER BY u.display_name"
);
$flash = esk_get_flash( 'success' );
?>
<div class="wrap esk-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Guardians', 'eskoofy' ); ?></h1>
	<hr class="wp-header-end">

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<div class="esk-card esk-form-card" style="margin-bottom:1.5rem;">
		<h2><?php esc_html_e( 'Add New Guardian', 'eskoofy' ); ?></h2>
		<form method="post" class="esk-form esk-inline-form">
			<?php wp_nonce_field( 'esk_guardian_form' ); ?>
			<label><?php esc_html_e( 'Name', 'eskoofy' ); ?>:</label>
			<input type="text" name="name" required>
			<label><?php esc_html_e( 'Email', 'eskoofy' ); ?>:</label>
			<input type="email" name="email" required>
			<label><?php esc_html_e( 'Phone', 'eskoofy' ); ?>:</label>
			<input type="tel" name="phone">
			<button type="submit" name="esk_guardian_save" class="button button-primary"><?php esc_html_e( 'Add Guardian', 'eskoofy' ); ?></button>
		</form>
	</div>

	<table class="wp-list-table widefat striped esk-table">
		<thead><tr>
			<th><?php esc_html_e( 'Name', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Email', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Actions', 'eskoofy' ); ?></th>
		</tr></thead>
		<tbody>
			<?php if ( empty( $guardians ) ) : ?>
				<tr><td colspan="3"><?php esc_html_e( 'No guardians found.', 'eskoofy' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $guardians as $g ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $g->display_name ); ?></strong></td>
						<td><?php echo esc_html( $g->user_email ); ?></td>
						<td>
							<form method="post" style="display:inline;" onsubmit="return confirm('<?php esc_attr_e( 'Delete this guardian?', 'eskoofy' ); ?>');">
								<?php wp_nonce_field( 'esk_guardian_delete_' . $g->id ); ?>
								<input type="hidden" name="guardian_id" value="<?php echo esc_attr( $g->id ); ?>">
								<button type="submit" name="esk_guardian_delete" class="button button-small"><?php esc_html_e( 'Delete', 'eskoofy' ); ?></button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
