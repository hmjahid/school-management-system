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

	<table class="wp-list-table widefat fixed striped esk-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Name', 'eskoofy' ); ?></th>
				<th><?php esc_html_e( 'Email', 'eskoofy' ); ?></th>
				<th><?php esc_html_e( 'Qualification', 'eskoofy' ); ?></th>
				<th><?php esc_html_e( 'Subjects', 'eskoofy' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $teachers ) ) : ?>
				<tr><td colspan="4"><?php esc_html_e( 'No teachers found.', 'eskoofy' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $teachers as $t ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $t->display_name ); ?></strong></td>
						<td><?php echo esc_html( $t->user_email ); ?></td>
						<td><?php echo esc_html( $t->qualification ); ?></td>
						<td><?php echo esc_html( $t->subjects ? implode( ', ', json_decode( $t->subjects, true ) ?? array() ) : '—' ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
