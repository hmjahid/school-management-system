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
	$edit_id       = absint( $_POST['teacher_id'] ?? 0 );

	if ( $edit_id ) {
		$teacher = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}esk_teachers WHERE id = %d", $edit_id ) );
		if ( $teacher ) {
			$existing = get_userdata( $teacher->user_id );
			if ( $existing ) {
				wp_update_user( array(
					'ID'           => (int) $teacher->user_id,
					'user_email'   => $email,
					'display_name' => $first_name . ' ' . $last_name,
				) );
			}
			$wpdb->update( $wpdb->prefix . 'esk_teachers', array(
				'qualification' => $qualification,
				'subjects'      => wp_json_encode( $subjects ),
			), array( 'id' => $edit_id ) );
		}
		esk_flash( 'success', __( 'Teacher updated successfully.', 'eskoofy' ) );
		wp_safe_redirect( admin_url( 'admin.php?page=esk-teachers' ) );
		exit;
	}

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

$edit_id = absint( $_GET['id'] ?? 0 );
$teacher = null;
if ( $edit_id ) {
	$teacher = $wpdb->get_row( $wpdb->prepare(
		"SELECT t.*, u.display_name, u.user_email
		FROM {$wpdb->prefix}esk_teachers t
		JOIN {$wpdb->prefix}users u ON t.user_id = u.ID
		WHERE t.id = %d",
		$edit_id
	) );
}
$teacher_subjects = $teacher ? json_decode( (string) $teacher->subjects, true ) : array();

$all_subjects = $wpdb->get_col( "SELECT name FROM {$wpdb->prefix}esk_subjects WHERE deleted_at IS NULL ORDER BY name" );
$flash = esk_get_flash( 'success' );
?>
<div class="wrap esk-admin-wrap">
	<h1><?php echo $teacher ? esc_html__( 'Edit Teacher', 'eskoofy' ) : esc_html__( 'Add New Teacher', 'eskoofy' ); ?></h1>

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<form method="post" class="esk-form">
		<?php wp_nonce_field( 'esk_teacher_form' ); ?>
		<?php if ( $teacher ) : ?>
			<input type="hidden" name="teacher_id" value="<?php echo esc_attr( $teacher->id ); ?>">
		<?php endif; ?>
		<div class="esk-card esk-form-card">
			<table class="form-table">
				<tr>
					<th><label><?php esc_html_e( 'First Name', 'eskoofy' ); ?> *</label></th>
					<td><input type="text" name="first_name" class="regular-text" required value="<?php echo esc_attr( $teacher ? explode( ' ', $teacher->display_name, 2 )[0] : '' ); ?>"></td>
				</tr>
				<tr>
					<th><label><?php esc_html_e( 'Last Name', 'eskoofy' ); ?> *</label></th>
					<td><input type="text" name="last_name" class="regular-text" required value="<?php echo esc_attr( $teacher && false !== strpos( $teacher->display_name, ' ' ) ? explode( ' ', $teacher->display_name, 2 )[1] : '' ); ?>"></td>
				</tr>
				<tr>
					<th><label><?php esc_html_e( 'Email', 'eskoofy' ); ?> *</label></th>
					<td><input type="email" name="email" class="regular-text" required value="<?php echo esc_attr( $teacher->user_email ?? '' ); ?>"></td>
				</tr>
				<tr>
					<th><label><?php esc_html_e( 'Qualification', 'eskoofy' ); ?></label></th>
					<td><input type="text" name="qualification" class="regular-text" value="<?php echo esc_attr( $teacher->qualification ?? '' ); ?>"></td>
				</tr>
				<tr>
					<th><label><?php esc_html_e( 'Subjects', 'eskoofy' ); ?></label></th>
					<td>
						<?php foreach ( $all_subjects as $sub ) : ?>
							<label><input type="checkbox" name="subjects[]" value="<?php echo esc_attr( $sub ); ?>" <?php checked( is_array( $teacher_subjects ) && in_array( $sub, $teacher_subjects, true ), true ); ?>> <?php echo esc_html( $sub ); ?></label><br>
						<?php endforeach; ?>
					</td>
				</tr>
			</table>
		</div>
		<p class="submit">
			<button type="submit" name="esk_teacher_save" class="button button-primary"><?php echo $teacher ? esc_html__( 'Update Teacher', 'eskoofy' ) : esc_html__( 'Add Teacher', 'eskoofy' ); ?></button>
			<?php if ( $teacher ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=esk-teachers' ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'eskoofy' ); ?></a>
			<?php endif; ?>
		</p>
	</form>
</div>
