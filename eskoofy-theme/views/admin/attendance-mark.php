<?php
/**
 * Attendance marking form.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

$all_classes = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}esk_classes ORDER BY name" );
$selected_class = absint( $_GET['class_id'] ?? 0 );
$selected_date  = sanitize_text_field( $_GET['date'] ?? gmdate( 'Y-m-d' ) );

$students = array();
if ( $selected_class ) {
	$students = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT s.id, u.display_name, s.roll_number
			FROM {$wpdb->prefix}esk_students s
			JOIN {$wpdb->prefix}users u ON s.user_id = u.ID
			WHERE s.class_id = %d AND s.status = 'active' AND s.deleted_at IS NULL
			ORDER BY CAST(s.roll_number AS UNSIGNED), u.display_name",
			$selected_class
		)
	);
}
?>
<div class="wrap esk-admin-wrap">
	<h1><?php esc_html_e( 'Mark Attendance', 'eskoofy' ); ?></h1>

	<div class="esk-card esk-form-card">
		<form method="get" class="esk-form esk-inline-form" style="margin-bottom:1.5rem;">
			<input type="hidden" name="page" value="esk-attendance-mark">
			<label><?php esc_html_e( 'Date', 'eskoofy' ); ?>:</label>
			<input type="date" name="date" value="<?php echo esc_attr( $selected_date ); ?>">
			<label><?php esc_html_e( 'Class', 'eskoofy' ); ?>:</label>
			<select name="class_id" required>
				<option value=""><?php esc_html_e( 'Select Class', 'eskoofy' ); ?></option>
				<?php foreach ( $all_classes as $c ) : ?>
					<option value="<?php echo esc_attr( $c->id ); ?>" <?php selected( $selected_class, $c->id ); ?>><?php echo esc_html( $c->name ); ?></option>
				<?php endforeach; ?>
			</select>
			<button type="submit" class="button"><?php esc_html_e( 'Load Students', 'eskoofy' ); ?></button>
		</form>

		<?php if ( $selected_class && ! empty( $students ) ) : ?>
			<form method="post" class="esk-form" id="esk-attendance-form">
				<?php wp_nonce_field( 'esk_attendance_form' ); ?>
				<input type="hidden" name="date" value="<?php echo esc_attr( $selected_date ); ?>">
				<input type="hidden" name="class_id" value="<?php echo esc_attr( $selected_class ); ?>">

				<p>
					<button type="button" class="button" onclick="document.querySelectorAll('.esk-att-present').forEach(c => c.checked = true);"><?php esc_html_e( 'Mark All Present', 'eskoofy' ); ?></button>
					<button type="button" class="button" onclick="document.querySelectorAll('.esk-att-absent').forEach(c => c.checked = true);"><?php esc_html_e( 'Mark All Absent', 'eskoofy' ); ?></button>
				</p>

				<table class="wp-list-table widefat striped esk-table">
					<thead><tr>
						<th><?php esc_html_e( 'Roll', 'eskoofy' ); ?></th>
						<th><?php esc_html_e( 'Name', 'eskoofy' ); ?></th>
						<th><?php esc_html_e( 'Present', 'eskoofy' ); ?></th>
						<th><?php esc_html_e( 'Absent', 'eskoofy' ); ?></th>
						<th><?php esc_html_e( 'Late', 'eskoofy' ); ?></th>
					</tr></thead>
					<tbody>
						<?php foreach ( $students as $s ) : ?>
							<tr>
								<td><?php echo esc_html( $s->roll_number ); ?></td>
								<td><?php echo esc_html( $s->display_name ); ?></td>
								<td><input type="radio" name="status[<?php echo esc_attr( $s->id ); ?>]" value="present" class="esk-att-present" checked></td>
								<td><input type="radio" name="status[<?php echo esc_attr( $s->id ); ?>]" value="absent" class="esk-att-absent"></td>
								<td><input type="radio" name="status[<?php echo esc_attr( $s->id ); ?>]" value="late"></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<p class="submit">
					<button type="submit" name="esk_attendance_save" class="button button-primary"><?php esc_html_e( 'Save Attendance', 'eskoofy' ); ?></button>
				</p>
			</form>
		<?php elseif ( $selected_class ) : ?>
			<p><?php esc_html_e( 'No active students found in this class.', 'eskoofy' ); ?></p>
		<?php endif; ?>
	</div>
</div>
