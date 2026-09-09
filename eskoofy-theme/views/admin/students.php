<?php
/**
 * Students list view.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

$page     = max( 1, (int) ( $_GET['paged'] ?? 1 ) );
$per_page = 20;
$offset   = ( $page - 1 ) * $per_page;
$search   = sanitize_text_field( $_GET['s'] ?? '' );
$class_id = absint( $_GET['class_id'] ?? 0 );

$where  = 'WHERE s.deleted_at IS NULL';
$params = array();

if ( $search ) {
	$where  .= " AND (u.display_name LIKE %s OR s.admission_number LIKE %s OR s.phone_1 LIKE %s)";
	$like    = '%' . $wpdb->esc_like( $search ) . '%';
	$params[] = $like;
	$params[] = $like;
	$params[] = $like;
}
if ( $class_id ) {
	$where  .= " AND s.class_id = %d";
	$params[] = $class_id;
}

$total_sql = "SELECT COUNT(*) FROM {$wpdb->prefix}esk_students s JOIN {$wpdb->prefix}users u ON s.user_id = u.ID {$where}";
$total     = (int) ( ! empty( $params ) ? $wpdb->get_var( $wpdb->prepare( $total_sql, ...$params ) ) : $wpdb->get_var( $total_sql ) );

$query = "SELECT s.*, u.display_name, c.name AS class_name
	FROM {$wpdb->prefix}esk_students s
	JOIN {$wpdb->prefix}users u ON s.user_id = u.ID
	LEFT JOIN {$wpdb->prefix}esk_classes c ON s.class_id = c.id
	{$where}
	ORDER BY s.id DESC LIMIT %d OFFSET %d";
$params[] = $per_page;
$params[] = $offset;
$students = $wpdb->get_results( $wpdb->prepare( $query, ...$params ) );

$all_classes = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}esk_classes ORDER BY name" );
$pages       = ceil( $total / $per_page );
?>
<div class="wrap esk-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Students', 'eskoofy' ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=esk-student-add' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'eskoofy' ); ?></a>
	<hr class="wp-header-end">

	<div class="esk-toolbar">
		<form method="get" class="esk-filter-form">
			<input type="hidden" name="page" value="esk-students">
			<input type="search" id="esk-student-search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search students...', 'eskoofy' ); ?>">
			<select name="class_id">
				<option value=""><?php esc_html_e( 'All Classes', 'eskoofy' ); ?></option>
				<?php foreach ( $all_classes as $c ) : ?>
					<option value="<?php echo esc_attr( $c->id ); ?>" <?php selected( $class_id, $c->id ); ?>><?php echo esc_html( $c->name ); ?></option>
				<?php endforeach; ?>
			</select>
			<button type="submit" class="button"><?php esc_html_e( 'Filter', 'eskoofy' ); ?></button>
		</form>
		<div id="esk-student-results"></div>
	</div>

	<p class="esk-table-info"><?php printf( esc_html__( 'Total: %d students', 'eskoofy' ), $total ); ?></p>

	<table class="wp-list-table widefat fixed striped esk-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Name', 'eskoofy' ); ?></th>
				<th><?php esc_html_e( 'Admission No', 'eskoofy' ); ?></th>
				<th><?php esc_html_e( 'Class', 'eskoofy' ); ?></th>
				<th><?php esc_html_e( 'Roll', 'eskoofy' ); ?></th>
				<th><?php esc_html_e( 'Phone', 'eskoofy' ); ?></th>
				<th><?php esc_html_e( 'Status', 'eskoofy' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $students ) ) : ?>
				<tr><td colspan="6"><?php esc_html_e( 'No students found.', 'eskoofy' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $students as $s ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $s->display_name ); ?></strong></td>
						<td><?php echo esc_html( $s->admission_number ); ?></td>
						<td><?php echo esc_html( $s->class_name ); ?></td>
						<td><?php echo esc_html( $s->roll_number ); ?></td>
						<td><?php echo esc_html( $s->phone_1 ); ?></td>
						<td><span class="esk-badge esk-badge-<?php echo esc_attr( $s->status ); ?>"><?php echo esc_html( ucfirst( $s->status ) ); ?></span></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>

	<?php if ( $pages > 1 ) : ?>
		<div class="esk-pagination">
			<?php
			echo wp_kses_post( paginate_links( array(
				'base'    => add_query_arg( 'paged', '%#%' ),
				'format'  => '',
				'current' => $page,
				'total'   => $pages,
			) ) );
			?>
		</div>
	<?php endif; ?>
</div>
