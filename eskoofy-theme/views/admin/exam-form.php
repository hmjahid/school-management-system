<?php
/**
 * Exam add/edit form.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

if ( isset( $_POST['esk_exam_save'] ) ) {
	check_admin_referer( 'esk_exam_form' );
	$wpdb->insert( $wpdb->prefix . 'esk_exams', array(
		'name'          => sanitize_text_field( $_POST['name'] ?? '' ),
		'code'          => sanitize_text_field( $_POST['code'] ?? '' ),
		'class_id'      => absint( $_POST['class_id'] ?? 0 ),
		'subject_id'    => absint( $_POST['subject_id'] ?? 0 ),
		'start_date'    => sanitize_text_field( $_POST['start_date'] ?? '' ),
		'end_date'      => sanitize_text_field( $_POST['end_date'] ?? '' ),
		'start_time'    => sanitize_text_field( $_POST['start_time'] ?? '' ),
		'end_time'      => sanitize_text_field( $_POST['end_time'] ?? '' ),
		'total_marks'   => absint( $_POST['total_marks'] ?? 0 ),
		'passing_marks' => absint( $_POST['passing_marks'] ?? 0 ),
		'status'        => sanitize_text_field( $_POST['status'] ?? 'upcoming' ),
		'created_by'    => get_current_user_id(),
	) );
	esk_flash( 'success', __( 'Exam created.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-exams' ) );
	exit;
}

$all_classes  = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}esk_classes ORDER BY name" );
$all_subjects = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}esk_subjects WHERE deleted_at IS NULL ORDER BY name" );
$flash        = esk_get_flash( 'success' );
?>
<div class="wrap esk-admin-wrap">
	<h1><?php esc_html_e( 'Add New Exam', 'eskoofy' ); ?></h1>
	<?php if ( $flash ) : ?><div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div><?php endif; ?>

	<form method="post" class="esk-form">
		<?php wp_nonce_field( 'esk_exam_form' ); ?>
		<div class="esk-card esk-form-card">
			<table class="form-table">
				<tr><th><label><?php esc_html_e( 'Exam Name', 'eskoofy' ); ?> *</label></th>
					<td><input type="text" name="name" class="regular-text" required></td></tr>
				<tr><th><label><?php esc_html_e( 'Code', 'eskoofy' ); ?> *</label></th>
					<td><input type="text" name="code" required></td></tr>
				<tr><th><label><?php esc_html_e( 'Class', 'eskoofy' ); ?> *</label></th>
					<td><select name="class_id" required>
						<option value=""><?php esc_html_e( 'Select', 'eskoofy' ); ?></option>
						<?php foreach ( $all_classes as $c ) : ?>
							<option value="<?php echo esc_attr( $c->id ); ?>"><?php echo esc_html( $c->name ); ?></option>
						<?php endforeach; ?>
					</select></td></tr>
				<tr><th><label><?php esc_html_e( 'Subject', 'eskoofy' ); ?> *</label></th>
					<td><select name="subject_id" required>
						<option value=""><?php esc_html_e( 'Select', 'eskoofy' ); ?></option>
						<?php foreach ( $all_subjects as $s ) : ?>
							<option value="<?php echo esc_attr( $s->id ); ?>"><?php echo esc_html( $s->name ); ?></option>
						<?php endforeach; ?>
					</select></td></tr>
				<tr><th><label><?php esc_html_e( 'Start Date', 'eskoofy' ); ?> *</label></th>
					<td><input type="date" name="start_date" required></td></tr>
				<tr><th><label><?php esc_html_e( 'End Date', 'eskoofy' ); ?> *</label></th>
					<td><input type="date" name="end_date" required></td></tr>
				<tr><th><label><?php esc_html_e( 'Start Time', 'eskoofy' ); ?> *</label></th>
					<td><input type="time" name="start_time" required></td></tr>
				<tr><th><label><?php esc_html_e( 'End Time', 'eskoofy' ); ?> *</label></th>
					<td><input type="time" name="end_time" required></td></tr>
				<tr><th><label><?php esc_html_e( 'Total Marks', 'eskoofy' ); ?> *</label></th>
					<td><input type="number" name="total_marks" required min="1"></td></tr>
				<tr><th><label><?php esc_html_e( 'Passing Marks', 'eskoofy' ); ?> *</label></th>
					<td><input type="number" name="passing_marks" required min="1"></td></tr>
				<tr><th><label><?php esc_html_e( 'Status', 'eskoofy' ); ?></label></th>
					<td><select name="status">
						<option value="upcoming"><?php esc_html_e( 'Upcoming', 'eskoofy' ); ?></option>
						<option value="ongoing"><?php esc_html_e( 'Ongoing', 'eskoofy' ); ?></option>
						<option value="completed"><?php esc_html_e( 'Completed', 'eskoofy' ); ?></option>
					</select></td></tr>
			</table>
		</div>
		<p class="submit"><button type="submit" name="esk_exam_save" class="button button-primary"><?php esc_html_e( 'Create Exam', 'eskoofy' ); ?></button></p>
	</form>
</div>
