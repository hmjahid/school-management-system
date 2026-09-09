<?php
/**
 * Student add/edit form.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

$edit_id    = absint( $_GET['id'] ?? 0 );
$is_edit    = $edit_id > 0;
$student    = null;
$all_classes = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}esk_classes ORDER BY name" );
$all_sections = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}esk_sections ORDER BY name" );

if ( $is_edit ) {
	$student = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT s.*, u.display_name, u.user_email FROM {$wpdb->prefix}esk_students s
			JOIN {$wpdb->prefix}users u ON s.user_id = u.ID WHERE s.id = %d",
			$edit_id
		)
	);
}

if ( isset( $_POST['esk_student_save'] ) ) {
	check_admin_referer( 'esk_student_form' );

	$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
	$data    = array(
		'class_id'          => absint( $_POST['class_id'] ?? 0 ),
		'section_id'        => absint( $_POST['section_id'] ?? 0 ),
		'batch_id'          => absint( $_POST['batch_id'] ?? 0 ),
		'admission_number'  => sanitize_text_field( $_POST['admission_number'] ?? '' ),
		'admission_date'    => sanitize_text_field( $_POST['admission_date'] ?? '' ),
		'roll_number'       => sanitize_text_field( $_POST['roll_number'] ?? '' ),
		'blood_group'       => sanitize_text_field( $_POST['blood_group'] ?? '' ),
		'religion'          => sanitize_text_field( $_POST['religion'] ?? '' ),
		'nationality'       => sanitize_text_field( $_POST['nationality'] ?? 'Bangladeshi' ),
		'phone_1'           => sanitize_text_field( $_POST['phone_1'] ?? '' ),
		'phone_2'           => sanitize_text_field( $_POST['phone_2'] ?? '' ),
		'email'             => sanitize_email( $_POST['email'] ?? '' ),
		'parent_name'       => sanitize_text_field( $_POST['parent_name'] ?? '' ),
		'parent_phone'      => sanitize_text_field( $_POST['parent_phone'] ?? '' ),
		'parent_email'      => sanitize_email( $_POST['parent_email'] ?? '' ),
		'permanent_address' => sanitize_textarea_field( $_POST['permanent_address'] ?? '' ),
		'present_address'   => sanitize_textarea_field( $_POST['present_address'] ?? '' ),
		'city'              => sanitize_text_field( $_POST['city'] ?? '' ),
		'state'             => sanitize_text_field( $_POST['state'] ?? '' ),
		'zip_code'          => sanitize_text_field( $_POST['zip_code'] ?? '' ),
		'country'           => sanitize_text_field( $_POST['country'] ?? 'Bangladesh' ),
		'monthly_fee'       => floatval( $_POST['monthly_fee'] ?? 0 ),
		'transport_fee'     => floatval( $_POST['transport_fee'] ?? 0 ),
		'discount'          => floatval( $_POST['discount'] ?? 0 ),
		'status'            => sanitize_text_field( $_POST['status'] ?? 'active' ),
		'notes'             => sanitize_textarea_field( $_POST['notes'] ?? '' ),
	);

	if ( ! $user_id && ! $is_edit ) {
		$email = $data['email'] ?: ( sanitize_email( $_POST['email'] ?? '' ) . '@placeholder.local' );
		$user_id = wp_insert_user( array(
			'user_login'   => $email,
			'user_email'   => $email,
			'display_name' => sanitize_text_field( $_POST['parent_name'] ?? 'Student' ),
			'user_pass'    => wp_generate_password(),
			'role'         => 'subscriber',
		) );
	}

	if ( is_wp_error( $user_id ) ) {
		add_settings_error( 'esk', 'user_error', $user_id->get_error_message(), 'error' );
	} else {
		$data['user_id'] = $user_id;
		if ( $is_edit ) {
			$wpdb->update( $wpdb->prefix . 'esk_students', $data, array( 'id' => $edit_id ) );
			esk_flash( 'success', __( 'Student updated successfully.', 'eskoofy' ) );
		} else {
			if ( empty( $data['admission_number'] ) ) {
				$data['admission_number'] = esk_generate_number( 'ADM' );
			}
			if ( empty( $data['admission_date'] ) ) {
				$data['admission_date'] = gmdate( 'Y-m-d' );
			}
			$wpdb->insert( $wpdb->prefix . 'esk_students', $data );
			esk_flash( 'success', __( 'Student added successfully.', 'eskoofy' ) );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=esk-students' ) );
		exit;
	}
}

$flash = esk_get_flash( 'success' );
?>
<div class="wrap esk-admin-wrap">
	<h1><?php echo $is_edit ? esc_html__( 'Edit Student', 'eskoofy' ) : esc_html__( 'Add New Student', 'eskoofy' ); ?></h1>

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<form method="post" class="esk-form">
		<?php wp_nonce_field( 'esk_student_form' ); ?>
		<?php if ( $is_edit ) : ?>
			<input type="hidden" name="user_id" value="<?php echo esc_attr( $student->user_id ?? 0 ); ?>">
		<?php endif; ?>

		<div class="esk-card esk-form-card">
			<h2><?php esc_html_e( 'Student Information', 'eskoofy' ); ?></h2>
			<table class="form-table">
				<tr>
					<th><label for="class_id"><?php esc_html_e( 'Class', 'eskoofy' ); ?> *</label></th>
					<td>
						<select name="class_id" id="class_id" required>
							<option value=""><?php esc_html_e( 'Select Class', 'eskoofy' ); ?></option>
							<?php foreach ( $all_classes as $c ) : ?>
								<option value="<?php echo esc_attr( $c->id ); ?>" <?php selected( $student->class_id ?? 0, $c->id ); ?>><?php echo esc_html( $c->name ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="section_id"><?php esc_html_e( 'Section', 'eskoofy' ); ?></label></th>
					<td>
						<select name="section_id" id="section_id">
							<option value=""><?php esc_html_e( 'Select Section', 'eskoofy' ); ?></option>
							<?php foreach ( $all_sections as $sec ) : ?>
								<option value="<?php echo esc_attr( $sec->id ); ?>" <?php selected( $student->section_id ?? 0, $sec->id ); ?>><?php echo esc_html( $sec->name ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="admission_number"><?php esc_html_e( 'Admission Number', 'eskoofy' ); ?></label></th>
					<td><input type="text" name="admission_number" id="admission_number" value="<?php echo esc_attr( $student->admission_number ?? '' ); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th><label for="admission_date"><?php esc_html_e( 'Admission Date', 'eskoofy' ); ?></label></th>
					<td><input type="date" name="admission_date" id="admission_date" value="<?php echo esc_attr( $student->admission_date ?? '' ); ?>"></td>
				</tr>
				<tr>
					<th><label for="roll_number"><?php esc_html_e( 'Roll Number', 'eskoofy' ); ?></label></th>
					<td><input type="text" name="roll_number" id="roll_number" value="<?php echo esc_attr( $student->roll_number ?? '' ); ?>"></td>
				</tr>
				<tr>
					<th><label for="blood_group"><?php esc_html_e( 'Blood Group', 'eskoofy' ); ?></label></th>
					<td><input type="text" name="blood_group" id="blood_group" value="<?php echo esc_attr( $student->blood_group ?? '' ); ?>" maxlength="5"></td>
				</tr>
				<tr>
					<th><label for="religion"><?php esc_html_e( 'Religion', 'eskoofy' ); ?></label></th>
					<td><input type="text" name="religion" id="religion" value="<?php echo esc_attr( $student->religion ?? '' ); ?>"></td>
				</tr>
				<tr>
					<th><label for="phone_1"><?php esc_html_e( 'Phone', 'eskoofy' ); ?></label></th>
					<td><input type="tel" name="phone_1" id="phone_1" value="<?php echo esc_attr( $student->phone_1 ?? '' ); ?>"></td>
				</tr>
				<tr>
					<th><label for="email"><?php esc_html_e( 'Email', 'eskoofy' ); ?></label></th>
					<td><input type="email" name="email" id="email" value="<?php echo esc_attr( $student->email ?? '' ); ?>"></td>
				</tr>
				<tr>
					<th><label for="permanent_address"><?php esc_html_e( 'Permanent Address', 'eskoofy' ); ?></label></th>
					<td><textarea name="permanent_address" id="permanent_address" rows="3" class="large-text"><?php echo esc_textarea( $student->permanent_address ?? '' ); ?></textarea></td>
				</tr>
				<tr>
					<th><label for="present_address"><?php esc_html_e( 'Present Address', 'eskoofy' ); ?></label></th>
					<td><textarea name="present_address" id="present_address" rows="3" class="large-text"><?php echo esc_textarea( $student->present_address ?? '' ); ?></textarea></td>
				</tr>
				<tr>
					<th><label for="city"><?php esc_html_e( 'City', 'eskoofy' ); ?></label></th>
					<td><input type="text" name="city" id="city" value="<?php echo esc_attr( $student->city ?? '' ); ?>"></td>
				</tr>
				<tr>
					<th><label for="status"><?php esc_html_e( 'Status', 'eskoofy' ); ?></label></th>
					<td>
						<select name="status" id="status">
							<option value="active" <?php selected( $student->status ?? 'active', 'active' ); ?>><?php esc_html_e( 'Active', 'eskoofy' ); ?></option>
							<option value="inactive" <?php selected( $student->status ?? '', 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'eskoofy' ); ?></option>
							<option value="graduated" <?php selected( $student->status ?? '', 'graduated' ); ?>><?php esc_html_e( 'Graduated', 'eskoofy' ); ?></option>
							<option value="transferred" <?php selected( $student->status ?? '', 'transferred' ); ?>><?php esc_html_e( 'Transferred', 'eskoofy' ); ?></option>
						</select>
					</td>
				</tr>
			</table>
		</div>

		<div class="esk-card esk-form-card">
			<h2><?php esc_html_e( 'Parent/Guardian', 'eskoofy' ); ?></h2>
			<table class="form-table">
				<tr>
					<th><label for="parent_name"><?php esc_html_e( 'Parent Name', 'eskoofy' ); ?></label></th>
					<td><input type="text" name="parent_name" id="parent_name" value="<?php echo esc_attr( $student->parent_name ?? '' ); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th><label for="parent_phone"><?php esc_html_e( 'Parent Phone', 'eskoofy' ); ?></label></th>
					<td><input type="tel" name="parent_phone" id="parent_phone" value="<?php echo esc_attr( $student->parent_phone ?? '' ); ?>"></td>
				</tr>
				<tr>
					<th><label for="parent_email"><?php esc_html_e( 'Parent Email', 'eskoofy' ); ?></label></th>
					<td><input type="email" name="parent_email" id="parent_email" value="<?php echo esc_attr( $student->parent_email ?? '' ); ?>"></td>
				</tr>
			</table>
		</div>

		<div class="esk-card esk-form-card">
			<h2><?php esc_html_e( 'Fees', 'eskoofy' ); ?></h2>
			<table class="form-table">
				<tr>
					<th><label for="monthly_fee"><?php esc_html_e( 'Monthly Fee', 'eskoofy' ); ?></label></th>
					<td><input type="number" name="monthly_fee" id="monthly_fee" value="<?php echo esc_attr( $student->monthly_fee ?? 0 ); ?>" step="0.01"></td>
				</tr>
				<tr>
					<th><label for="transport_fee"><?php esc_html_e( 'Transport Fee', 'eskoofy' ); ?></label></th>
					<td><input type="number" name="transport_fee" id="transport_fee" value="<?php echo esc_attr( $student->transport_fee ?? 0 ); ?>" step="0.01"></td>
				</tr>
				<tr>
					<th><label for="discount"><?php esc_html_e( 'Discount', 'eskoofy' ); ?></label></th>
					<td><input type="number" name="discount" id="discount" value="<?php echo esc_attr( $student->discount ?? 0 ); ?>" step="0.01"></td>
				</tr>
			</table>
		</div>

		<p class="submit">
			<button type="submit" name="esk_student_save" class="button button-primary">
				<?php echo $is_edit ? esc_html__( 'Update Student', 'eskoofy' ) : esc_html__( 'Add Student', 'eskoofy' ); ?>
			</button>
		</p>
	</form>
</div>
