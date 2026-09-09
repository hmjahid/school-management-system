<?php
/**
 * Exam seat plans.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

if ( isset( $_POST['esk_seat_generate'] ) ) {
	check_admin_referer( 'esk_seat_form' );
	$exam_id     = absint( $_POST['exam_id'] ?? 0 );
	$room_number = sanitize_text_field( $_POST['room_number'] ?? '' );
	$cols        = absint( $_POST['cols'] ?? 5 );
	$rows        = absint( $_POST['rows'] ?? 5 );

	if ( $exam_id && $room_number ) {
		$exam = $wpdb->get_row( $wpdb->prepare( "SELECT class_id FROM {$wpdb->prefix}esk_exams WHERE id = %d", $exam_id ) );
		if ( $exam ) {
			$students = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT s.id FROM {$wpdb->prefix}esk_students s
					WHERE s.class_id = %d AND s.deleted_at IS NULL ORDER BY s.roll_number",
					$exam->class_id
				)
			);
			$capacity = $rows * $cols;
			$count    = 0;
			foreach ( array_slice( $students, 0, $capacity ) as $idx => $stu ) {
				$r = intdiv( $idx, $cols ) + 1;
				$c = ( $idx % $cols ) + 1;
				$wpdb->insert( $wpdb->prefix . 'esk_seat_plans', array(
					'exam_id'      => $exam_id,
					'room_number'  => $room_number,
					'row_number'   => $r,
					'column_number' => $c,
					'student_id'   => $stu->id,
				) );
				++$count;
			}
			esk_flash( 'success', sprintf( __( '%d seats generated.', 'eskoofy' ), $count ) );
		}
	}
	wp_safe_redirect( admin_url( 'admin.php?page=esk-seat-plans' ) );
	exit;
}

$exams = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}esk_exams WHERE deleted_at IS NULL ORDER BY id DESC" );
$seats = $wpdb->get_results(
	"SELECT s.*, e.name AS exam_name, st.roll_number, u.display_name
	FROM {$wpdb->prefix}esk_seat_plans s
	JOIN {$wpdb->prefix}esk_exams e ON s.exam_id = e.id
	LEFT JOIN {$wpdb->prefix}esk_students st ON s.student_id = st.id
	LEFT JOIN {$wpdb->prefix}users u ON st.user_id = u.ID
	ORDER BY s.exam_id, s.room_number, s.row_number, s.column_number"
);
$flash = esk_get_flash( 'success' );
?>
<div class="wrap esk-admin-wrap">
	<h1><?php esc_html_e( 'Exam Seat Plans', 'eskoofy' ); ?></h1>

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<div class="esk-card esk-form-card" style="margin-bottom:1.5rem;">
		<h2><?php esc_html_e( 'Generate Seat Plan', 'eskoofy' ); ?></h2>
		<form method="post" class="esk-form">
			<?php wp_nonce_field( 'esk_seat_form' ); ?>
			<div class="esk-form-row">
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Exam', 'eskoofy' ); ?></label>
					<select name="exam_id" required>
						<option value=""><?php esc_html_e( 'Select', 'eskoofy' ); ?></option>
						<?php foreach ( $exams as $e ) : ?>
							<option value="<?php echo esc_attr( $e->id ); ?>"><?php echo esc_html( $e->name ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="esk-form-group"><label><?php esc_html_e( 'Room Number', 'eskoofy' ); ?></label><input type="text" name="room_number" required></div>
				<div class="esk-form-group"><label><?php esc_html_e( 'Rows', 'eskoofy' ); ?></label><input type="number" name="rows" value="5" min="1"></div>
				<div class="esk-form-group"><label><?php esc_html_e( 'Cols', 'eskoofy' ); ?></label><input type="number" name="cols" value="5" min="1"></div>
			</div>
			<button type="submit" name="esk_seat_generate" class="button button-primary"><?php esc_html_e( 'Generate', 'eskoofy' ); ?></button>
		</form>
	</div>

	<table class="wp-list-table widefat striped esk-table">
		<thead><tr>
			<th><?php esc_html_e( 'Exam', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Room', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Row', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Column', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Student', 'eskoofy' ); ?></th>
		</tr></thead>
		<tbody>
			<?php if ( empty( $seats ) ) : ?>
				<tr><td colspan="5"><?php esc_html_e( 'No seat plans yet.', 'eskoofy' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $seats as $s ) : ?>
					<tr>
						<td><?php echo esc_html( $s->exam_name ); ?></td>
						<td><?php echo esc_html( $s->room_number ); ?></td>
						<td><?php echo esc_html( $s->row_number ); ?></td>
						<td><?php echo esc_html( $s->column_number ); ?></td>
						<td><?php echo esc_html( ( $s->display_name ?? '—' ) . ' (' . ( $s->roll_number ?? '' ) . ')' ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>