<?php
/**
 * Payroll management — salary structures, payslips, leave, staff attendance.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

$tab = sanitize_text_field( $_GET['tab'] ?? 'structures' );

if ( isset( $_POST['esk_salary_structure_save'] ) ) {
	check_admin_referer( 'esk_salary_form' );
	$wpdb->insert( $wpdb->prefix . 'esk_teachers', array(
		'user_id'       => absint( $_POST['user_id'] ?? 0 ),
		'qualification' => sanitize_text_field( $_POST['position'] ?? '' ),
	) );
	esk_flash( 'success', __( 'Salary structure saved.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-payroll' ) );
	exit;
}

if ( isset( $_POST['esk_leave_action'] ) ) {
	check_admin_referer( 'esk_leave_action_' . absint( $_POST['leave_id'] ?? 0 ) );
	$leave_action = sanitize_text_field( $_POST['esk_leave_action'] ?? '' );
	$leave_id     = absint( $_POST['leave_id'] ?? 0 );
	if ( $leave_id && in_array( $leave_action, array( 'approve', 'reject' ), true ) ) {
		$wpdb->update( $wpdb->prefix . 'esk_activities', array(
			'type' => 'leave_' . $leave_action,
		), array( 'id' => $leave_id ) );
	}
	esk_flash( 'success', __( 'Leave request updated.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-paytab=leave' ) );
	exit;
}

$teachers = $wpdb->get_results(
	"SELECT t.*, u.display_name FROM {$wpdb->prefix}esk_teachers t JOIN {$wpdb->prefix}users u ON t.user_id = u.ID ORDER BY u.display_name"
);
$flash = esk_get_flash( 'success' );
?>
<div class="wrap esk-admin-wrap">
	<h1><?php esc_html_e( 'Payroll', 'eskoofy' ); ?></h1>

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<nav class="nav-tab-wrapper esk-tabs">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=esk-payroll&tab=structures' ) ); ?>" class="nav-tab <?php echo 'structures' === $tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Staff', 'eskoofy' ); ?></a>
	</nav>

	<?php if ( 'structures' === $tab ) : ?>
		<div class="esk-card esk-form-card" style="margin-bottom:1.5rem;">
			<h2><?php esc_html_e( 'Staff List', 'eskoofy' ); ?></h2>
			<table class="wp-list-table widefat striped esk-table">
				<thead><tr>
					<th><?php esc_html_e( 'Name', 'eskoofy' ); ?></th>
					<th><?php esc_html_e( 'Position / Qualification', 'eskoofy' ); ?></th>
				</tr></thead>
				<tbody>
					<?php if ( empty( $teachers ) ) : ?>
						<tr><td colspan="2"><?php esc_html_e( 'No staff found.', 'eskoofy' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $teachers as $t ) : ?>
							<tr>
								<td><strong><?php echo esc_html( $t->display_name ); ?></strong></td>
								<td><?php echo esc_html( $t->qualification ); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</div>
