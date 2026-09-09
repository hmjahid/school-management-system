<?php
/**
 * Teacher add/edit form.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

if ( isset( $_POST['esk_teacher_save'] ) ) {
	check_admin_referer( 'esk_teacher_form' );

	$email        = sanitize_email( $_POST['email'] ?? '' );
	$first_name   = sanitize_text_field( $_POST['first_name'] ?? '' );
	$last_name    = sanitize_text_field( $_POST['last_name'] ?? '' );
	$qualification = sanitize_text_field( $_POST['qualification'] ?? '' );
	$subjects      = isset( $_POST['subjects'] ) ? array_map( 'sanitize_text_field', (array) $_POST['subjects'] ) : array();

	$user_id = wp_insert_user( array(
		'user_login'   => $email,
		'user_email'   => $email,
		'display_name' => $first_name . ' ' . $last_name,
		'user_pass'    => wp_generate_password(),
		'role'         => 'editor',
	) );

	if ( is_wp_error( $user_id ) ) {
		add_settings_error( 'esk', 'user_error', $user_id->get_error_message(), 'error' );
	} else {
		$wpdb->insert( $wpdb->prefix . 'esk_teachers', array(
			'user_id'       => $user_id,
			'qualification' => $qualification,
			'subjects'      => wp_json_encode( $subjects ),
		) );
		esk_flash( 'success', __( 'Teacher added successfully.', 'eskoofy' ) );
		wp_safe_redirect( admin_url( 'admin.php?page=esk-teachers' ) );
		exit;
	}
}

$all_subjects = $wpdb->get_col( "SELECT name FROM {$wpdb->prefix}esk_subjects WHERE deleted_at IS NULL ORDER BY name" );
$flash = esk_get_flash( 'success' );
?>
<div class="wrap esk-admin-wrap">
	<h1><?php esc_html_e( 'Add New Teacher', 'eskoofy' ); ?></h1>

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<form method="post" class="esk-form">
		<?php wp_nonce_field( 'esk_teacher_form' ); ?>
		<div class="esk-card esk-form-card">
			<table class="form-table">
				<tr>
					<th><label><?php esc_html_e( 'First Name', 'eskoofy' ); ?> *</label></th>
					<td><input type="text" name="first_name" class="regular-text" required></td>
				</tr>
				<tr>
					<th><label><?php esc_html_e( 'Last Name', 'eskoofy' ); ?> *</label></th>
					<td><input type="text" name="last_name" class="regular-text" required></td>
				</tr>
				<tr>
					<th><label><?php esc_html_e( 'Email', 'eskoofy' ); ?> *</label></th>
					<td><input type="email" name="email" class="regular-text" required></td>
				</tr>
				<tr>
					<th><label><?php esc_html_e( 'Qualification', 'eskoofy' ); ?></label></th>
					<td><input type="text" name="qualification" class="regular-text"></td>
				</tr>
				<tr>
					<th><label><?php esc_html_e( 'Subjects', 'eskoofy' ); ?></label></th>
					<td>
						<?php foreach ( $all_subjects as $sub ) : ?>
							<label><input type="checkbox" name="subjects[]" value="<?php echo esc_attr( $sub ); ?>"> <?php echo esc_html( $sub ); ?></label><br>
						<?php endforeach; ?>
					</td>
				</tr>
			</table>
		</div>
		<p class="submit">
			<button type="submit" name="esk_teacher_save" class="button button-primary"><?php esc_html_e( 'Add Teacher', 'eskoofy' ); ?></button>
		</p>
	</form>
</div>
