<?php
/**
 * Salary structures (full-page list view; quick-edit in payroll).
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

if ( isset( $_GET['action'] ) && 'delete' === $_GET['action'] && ! empty( $_GET['id'] ) ) {
	$id = absint( $_GET['id'] );
	check_admin_referer( 'esk_ss_delete_' . $id );
	$wpdb->delete( $wpdb->prefix . 'esk_salary_structures', array( 'id' => $id ) );
	esk_flash( 'success', __( 'Salary structure deleted.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-salary-structures' ) );
	exit;
}

$rows = $wpdb->get_results(
	"SELECT ss.*, u.display_name FROM {$wpdb->prefix}esk_salary_structures ss
	JOIN {$wpdb->prefix}users u ON ss.user_id = u.ID
	ORDER BY ss.effective_from DESC"
);

$flash = esk_get_flash( 'success' );
?>
<div class="wrap esk-admin-wrap">
	<h1><?php esc_html_e( 'Salary Structures', 'eskoofy' ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=esk-payroll' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add / Edit', 'eskoofy' ); ?></a>

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<table class="wp-list-table widefat striped esk-table">
		<thead><tr>
			<th><?php esc_html_e( 'Staff', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Basic', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'House', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Medical', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Transport', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Other All.', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Tax', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'PF', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Other Ded.', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'From', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'To', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Actions', 'eskoofy' ); ?></th>
		</tr></thead>
		<tbody>
			<?php if ( empty( $rows ) ) : ?>
				<tr><td colspan="12"><?php esc_html_e( 'No salary structures.', 'eskoofy' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $rows as $r ) : ?>
					<tr>
						<td><?php echo esc_html( $r->display_name ); ?></td>
						<td><?php echo esc_html( esk_format_currency( $r->basic_salary ) ); ?></td>
						<td><?php echo esc_html( esk_format_currency( $r->house_allowance ) ); ?></td>
						<td><?php echo esc_html( esk_format_currency( $r->medical_allowance ) ); ?></td>
						<td><?php echo esc_html( esk_format_currency( $r->transport_allowance ) ); ?></td>
						<td><?php echo esc_html( esk_format_currency( $r->other_allowance ) ); ?></td>
						<td><?php echo esc_html( esk_format_currency( $r->tax_deduction ) ); ?></td>
						<td><?php echo esc_html( esk_format_currency( $r->provident_fund ) ); ?></td>
						<td><?php echo esc_html( esk_format_currency( $r->other_deduction ) ); ?></td>
						<td><?php echo esc_html( esk_date_format( $r->effective_from ) ); ?></td>
						<td><?php echo $r->effective_to ? esc_html( esk_date_format( $r->effective_to ) ) : '—'; ?></td>
						<td>
							<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=esk-salary-structures&action=delete&id=' . $r->id ), 'esk_ss_delete_' . $r->id ) ); ?>" class="button button-small" onclick="return confirm('Delete?');"><?php esc_html_e( 'Delete', 'eskoofy' ); ?></a>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>