<?php
/**
 * Attendance list view.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

$date     = sanitize_text_field( $_GET['date'] ?? gmdate( 'Y-m-d' ) );
$class_id = absint( $_GET['class_id'] ?? 0 );

$where  = 'WHERE a.date = %s';
$params = array( $date );

if ( $class_id ) {
	$where  .= ' AND a.school_class_id = %d';
	$params[] = $class_id;
}

$records = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT a.*, u.display_name, c.name AS class_name
		FROM {$wpdb->prefix}esk_attendances a
		JOIN {$wpdb->prefix}esk_students s ON a.student_id = s.id
		JOIN {$wpdb->prefix}users u ON s.user_id = u.ID
		JOIN {$wpdb->prefix}esk_classes c ON a.school_class_id = c.id
		{$where}
		ORDER BY c.name, u.display_name",
		...$params
	)
);

$all_classes = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}esk_classes ORDER BY name" );
?>
<div class="wrap esk-admin-wrap">
	<h1><?php esc_html_e( 'Attendance Records', 'eskoofy' ); ?></h1>

	<div class="esk-toolbar">
		<form method="get" class="esk-filter-form">
			<input type="hidden" name="page" value="esk-attendance">
			<input type="date" name="date" value="<?php echo esc_attr( $date ); ?>">
			<select name="class_id">
				<option value=""><?php esc_html_e( 'All Classes', 'eskoofy' ); ?></option>
				<?php foreach ( $all_classes as $c ) : ?>
					<option value="<?php echo esc_attr( $c->id ); ?>" <?php selected( $class_id, $c->id ); ?>><?php echo esc_html( $c->name ); ?></option>
				<?php endforeach; ?>
			</select>
			<button type="submit" class="button"><?php esc_html_e( 'Filter', 'eskoofy' ); ?></button>
		</form>
	</div>

	<table class="wp-list-table widefat striped esk-table">
		<thead><tr><th><?php esc_html_e( 'Student', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Class', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Status', 'eskoofy' ); ?></th></tr></thead>
		<tbody>
			<?php if ( empty( $records ) ) : ?>
				<tr><td colspan="3"><?php esc_html_e( 'No attendance records for this date.', 'eskoofy' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $records as $r ) : ?>
					<tr>
						<td><?php echo esc_html( $r->display_name ); ?></td>
						<td><?php echo esc_html( $r->class_name ); ?></td>
						<td><span class="esk-badge esk-badge-<?php echo esc_attr( $r->status ); ?>"><?php echo esc_html( ucfirst( $r->status ) ); ?></span></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
