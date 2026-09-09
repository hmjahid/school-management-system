<?php
/**
 * Class routines / timetables.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

if ( isset( $_POST['esk_routine_save'] ) ) {
	check_admin_referer( 'esk_routine_form' );
	$class_id      = absint( $_POST['class_id'] ?? 0 );
	$subject_id    = absint( $_POST['subject_id'] ?? 0 );
	$teacher_user  = absint( $_POST['teacher_user_id'] ?? 0 );
	$teacher_id    = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}esk_teachers WHERE user_id = %d", $teacher_user ) );
	$day_of_week   = absint( $_POST['day_of_week'] ?? 1 );
	$start_time    = sanitize_text_field( $_POST['start_time'] ?? '' );
	$end_time      = sanitize_text_field( $_POST['end_time'] ?? '' );
	$room_number   = sanitize_text_field( $_POST['room_number'] ?? '' );

	if ( $class_id && $subject_id && $teacher_id && $start_time && $end_time ) {
		$wpdb->insert( $wpdb->prefix . 'esk_routines', array(
			'school_class_id' => $class_id,
			'subject_id'      => $subject_id,
			'teacher_id'      => $teacher_id,
			'day_of_week'     => $day_of_week,
			'start_time'      => $start_time,
			'end_time'        => $end_time,
			'room_number'     => $room_number,
		) );
		esk_flash( 'success', __( 'Routine entry saved.', 'eskoofy' ) );
	}
	wp_safe_redirect( admin_url( 'admin.php?page=esk-routines' ) );
	exit;
}

if ( isset( $_GET['action'] ) && 'delete' === $_GET['action'] && ! empty( $_GET['id'] ) ) {
	$id = absint( $_GET['id'] );
	check_admin_referer( 'esk_routine_delete_' . $id );
	$wpdb->delete( $wpdb->prefix . 'esk_routines', array( 'id' => $id ) );
	esk_flash( 'success', __( 'Routine deleted.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-routines' ) );
	exit;
}

$rows     = $wpdb->get_results(
	"SELECT r.*, c.name AS class_name, s.name AS subject_name, u.display_name AS teacher_name
	FROM {$wpdb->prefix}esk_routines r
	LEFT JOIN {$wpdb->prefix}esk_classes c ON r.school_class_id = c.id
	LEFT JOIN {$wpdb->prefix}esk_subjects s ON r.subject_id = s.id
	LEFT JOIN {$wpdb->prefix}esk_teachers t ON r.teacher_id = t.id
	LEFT JOIN {$wpdb->prefix}users u ON t.user_id = u.ID
	ORDER BY r.day_of_week, r.start_time"
);
$classes  = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}esk_classes ORDER BY name" );
$subjects = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}esk_subjects ORDER BY name" );
$teachers = $wpdb->get_results(
	"SELECT u.ID AS user_id, u.display_name FROM {$wpdb->prefix}esk_teachers t
	JOIN {$wpdb->prefix}users u ON t.user_id = u.ID ORDER BY u.display_name"
);
$flash    = esk_get_flash( 'success' );

$days = array( 1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun' );
?>
<div class="wrap esk-admin-wrap">
	<h1><?php esc_html_e( 'Class Routines', 'eskoofy' ); ?></h1>

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<div class="esk-card esk-form-card" style="margin-bottom:1.5rem;">
		<h2><?php esc_html_e( 'Add Routine Entry', 'eskoofy' ); ?></h2>
		<form method="post" class="esk-form">
			<?php wp_nonce_field( 'esk_routine_form' ); ?>
			<div class="esk-form-row">
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Class', 'eskoofy' ); ?></label>
					<select name="class_id" required>
						<option value=""><?php esc_html_e( 'Select', 'eskoofy' ); ?></option>
						<?php foreach ( $classes as $c ) : ?>
							<option value="<?php echo esc_attr( $c->id ); ?>"><?php echo esc_html( $c->name ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Subject', 'eskoofy' ); ?></label>
					<select name="subject_id" required>
						<option value=""><?php esc_html_e( 'Select', 'eskoofy' ); ?></option>
						<?php foreach ( $subjects as $s ) : ?>
							<option value="<?php echo esc_attr( $s->id ); ?>"><?php echo esc_html( $s->name ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Teacher', 'eskoofy' ); ?></label>
					<select name="teacher_user_id" required>
						<option value=""><?php esc_html_e( 'Select', 'eskoofy' ); ?></option>
						<?php foreach ( $teachers as $t ) : ?>
							<option value="<?php echo esc_attr( $t->user_id ); ?>"><?php echo esc_html( $t->display_name ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Day', 'eskoofy' ); ?></label>
					<select name="day_of_week" required>
						<?php foreach ( $days as $k => $d ) : ?>
							<option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $d ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="esk-form-group"><label><?php esc_html_e( 'Start', 'eskoofy' ); ?></label><input type="time" name="start_time" required></div>
				<div class="esk-form-group"><label><?php esc_html_e( 'End', 'eskoofy' ); ?></label><input type="time" name="end_time" required></div>
				<div class="esk-form-group"><label><?php esc_html_e( 'Room', 'eskoofy' ); ?></label><input type="text" name="room_number"></div>
			</div>
			<button type="submit" name="esk_routine_save" class="button button-primary"><?php esc_html_e( 'Save', 'eskoofy' ); ?></button>
		</form>
	</div>

	<table class="wp-list-table widefat striped esk-table">
		<thead><tr>
			<th><?php esc_html_e( 'Day', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Class', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Subject', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Teacher', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Time', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Room', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Actions', 'eskoofy' ); ?></th>
		</tr></thead>
		<tbody>
			<?php if ( empty( $rows ) ) : ?>
				<tr><td colspan="7"><?php esc_html_e( 'No routines yet.', 'eskoofy' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $rows as $r ) : ?>
					<tr>
						<td><?php echo esc_html( $days[ $r->day_of_week ] ?? $r->day_of_week ); ?></td>
						<td><?php echo esc_html( $r->class_name ); ?></td>
						<td><?php echo esc_html( $r->subject_name ); ?></td>
						<td><?php echo esc_html( $r->teacher_name ); ?></td>
						<td><?php echo esc_html( $r->start_time . ' – ' . $r->end_time ); ?></td>
						<td><?php echo esc_html( $r->room_number ); ?></td>
						<td>
							<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=esk-routines&action=delete&id=' . $r->id ), 'esk_routine_delete_' . $r->id ) ); ?>" class="button button-small" onclick="return confirm('Delete?');"><?php esc_html_e( 'Delete', 'eskoofy' ); ?></a>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>