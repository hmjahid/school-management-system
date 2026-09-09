<?php
/**
 * Admissions list view.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

$status_filter = sanitize_text_field( $_GET['status'] ?? '' );
$where         = 'WHERE a.deleted_at IS NULL';
$params        = array();

if ( $status_filter ) {
	$where  .= ' AND a.status = %s';
	$params[] = $status_filter;
}

$query     = "SELECT a.* FROM {$wpdb->prefix}esk_admissions a {$where} ORDER BY a.created_at DESC";
$admissions = ! empty( $params ) ? $wpdb->get_results( $wpdb->prepare( $query, ...$params ) ) : $wpdb->get_results( $query );

$statuses = array( 'draft', 'submitted', 'under_review', 'approved', 'rejected', 'waitlisted', 'enrolled', 'cancelled' );
?>
<div class="wrap esk-admin-wrap">
	<h1><?php esc_html_e( 'Admissions', 'eskoofy' ); ?></h1>

	<div class="esk-toolbar">
		<form method="get" class="esk-filter-form">
			<input type="hidden" name="page" value="esk-admissions">
			<select name="status">
				<option value=""><?php esc_html_e( 'All Statuses', 'eskoofy' ); ?></option>
				<?php foreach ( $statuses as $s ) : ?>
					<option value="<?php echo esc_attr( $s ); ?>" <?php selected( $status_filter, $s ); ?>><?php echo esc_html( ucfirst( $s ) ); ?></option>
				<?php endforeach; ?>
			</select>
			<button type="submit" class="button"><?php esc_html_e( 'Filter', 'eskoofy' ); ?></button>
		</form>
	</div>

	<table class="wp-list-table widefat striped esk-table">
		<thead><tr>
			<th><?php esc_html_e( 'Application #', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Name', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Email', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Phone', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Status', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Applied', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Actions', 'eskoofy' ); ?></th>
		</tr></thead>
		<tbody>
			<?php if ( empty( $admissions ) ) : ?>
				<tr><td colspan="7"><?php esc_html_e( 'No admission applications.', 'eskoofy' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $admissions as $a ) : ?>
					<tr>
						<td><?php echo esc_html( $a->application_number ); ?></td>
						<td><strong><?php echo esc_html( $a->first_name . ' ' . $a->last_name ); ?></strong></td>
						<td><?php echo esc_html( $a->email ); ?></td>
						<td><?php echo esc_html( $a->phone ); ?></td>
						<td><span class="esk-badge esk-badge-<?php echo esc_attr( $a->status ); ?>"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $a->status ) ) ); ?></span></td>
						<td><?php echo esc_html( esk_date_format( $a->submitted_at ?? $a->created_at ) ); ?></td>
						<td><a href="<?php echo esc_url( admin_url( 'admin.php?page=esk-admissions&action=view&id=' . $a->id ) ); ?>" class="button button-small"><?php esc_html_e( 'View', 'eskoofy' ); ?></a></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
