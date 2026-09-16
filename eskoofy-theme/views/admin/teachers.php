<?php
/**
 * Teachers list view.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

$search = sanitize_text_field( $_GET['s'] ?? '' );
$where  = '';
$params = array();

if ( $search ) {
	$where  = "WHERE u.display_name LIKE %s OR u.user_email LIKE %s";
	$like    = '%' . $wpdb->esc_like( $search ) . '%';
	$params[] = $like;
	$params[] = $like;
}

$query  = "SELECT t.*, u.display_name, u.user_email
	FROM {$wpdb->prefix}esk_teachers t
	JOIN {$wpdb->prefix}users u ON t.user_id = u.ID
	{$where}
	ORDER BY t.id DESC";
$teachers = ! empty( $params ) ? $wpdb->get_results( $wpdb->prepare( $query, ...$params ) ) : $wpdb->get_results( $query );

if ( isset( $_POST['esk_teacher_delete'] ) ) {
	check_admin_referer( 'esk_teacher_delete_' . absint( $_POST['teacher_id'] ?? 0 ) );
	$teacher_id = absint( $_POST['teacher_id'] ?? 0 );
	if ( $teacher_id ) {
		$teacher = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}esk_teachers WHERE id = %d", $teacher_id ) );
		if ( $teacher && $teacher->user_id ) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
			wp_delete_user( (int) $teacher->user_id );
		}
		$wpdb->delete( $wpdb->prefix . 'esk_teachers', array( 'id' => $teacher_id ) );
	}
	esk_flash( 'success', __( 'Teacher removed.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-teachers' ) );
	exit;
}
$flash = esk_get_flash( 'success' );
?>
<div class="wrap esk-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Teachers', 'eskoofy' ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=esk-teacher-add' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'eskoofy' ); ?></a>
	<hr class="wp-header-end">

	<div class="esk-toolbar">
		<form method="get" class="esk-filter-form">
			<input type="hidden" name="page" value="esk-teachers">
			<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search teachers...', 'eskoofy' ); ?>">
			<button type="submit" class="button"><?php esc_html_e( 'Search', 'eskoofy' ); ?></button>
		</form>
	</div>

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<table class="wp-list-table widefat fixed striped esk-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Name', 'eskoofy' ); ?></th>
				<th><?php esc_html_e( 'Email', 'eskoofy' ); ?></th>
				<th><?php esc_html_e( 'Qualification', 'eskoofy' ); ?></th>
				<th><?php esc_html_e( 'Subjects', 'eskoofy' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'eskoofy' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $teachers ) ) : ?>
				<tr><td colspan="5"><?php esc_html_e( 'No teachers found.', 'eskoofy' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $teachers as $t ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $t->display_name ); ?></strong></td>
						<td><?php echo esc_html( $t->user_email ); ?></td>
						<td><?php echo esc_html( $t->qualification ); ?></td>
						<td><?php echo esc_html( $t->subjects ? implode( ', ', json_decode( $t->subjects, true ) ?? array() ) : '—' ); ?></td>
						<td>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=esk-teacher-add&id=' . $t->id ) ); ?>" class="button button-small"><?php esc_html_e( 'Edit', 'eskoofy' ); ?></a>
							<form method="post" style="display:inline;" onsubmit="return confirm('<?php esc_attr_e( 'Delete this teacher?', 'eskoofy' ); ?>');">
								<?php wp_nonce_field( 'esk_teacher_delete_' . $t->id ); ?>
								<input type="hidden" name="teacher_id" value="<?php echo esc_attr( $t->id ); ?>">
								<button type="submit" name="esk_teacher_delete" class="button button-small"><?php esc_html_e( 'Delete', 'eskoofy' ); ?></button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
