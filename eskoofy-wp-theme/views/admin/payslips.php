<?php
/**
 * Payslips.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

if ( isset( $_POST['esk_payslip_generate'] ) ) {
	check_admin_referer( 'esk_payslip_form' );
	$user_id      = absint( $_POST['user_id'] ?? 0 );
	$period_start = sanitize_text_field( $_POST['period_start'] ?? gmdate( 'Y-m-01' ) );
	$period_end   = sanitize_text_field( $_POST['period_end'] ?? gmdate( 'Y-m-t' ) );

	if ( $user_id ) {
		$ss = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}esk_salary_structures
				WHERE user_id = %d AND (effective_from <= %s) AND (effective_to IS NULL OR effective_to >= %s)
				ORDER BY effective_from DESC LIMIT 1",
				$user_id, $period_start, $period_start
			)
		);

		$basic = $ss ? (float) $ss->basic_salary : 0;
		$allow = $ss ? (float) $ss->house_allowance + (float) $ss->medical_allowance + (float) $ss->transport_allowance + (float) $ss->other_allowance : 0;
		$ded   = $ss ? (float) $ss->tax_deduction + (float) $ss->provident_fund + (float) $ss->other_deduction : 0;
		$net   = $basic + $allow - $ded;

		$wpdb->insert( $wpdb->prefix . 'esk_payslips', array(
			'user_id'            => $user_id,
			'salary_structure_id' => $ss ? $ss->id : null,
			'period_start'       => $period_start,
			'period_end'         => $period_end,
			'basic_salary'       => $basic,
			'total_allowances'   => $allow,
			'total_deductions'   => $ded,
			'net_salary'         => $net,
			'status'             => 'pending',
		) );
		esk_flash( 'success', __( 'Payslip generated.', 'eskoofy' ) );
	}
	wp_safe_redirect( admin_url( 'admin.php?page=esk-payslips' ) );
	exit;
}

if ( isset( $_GET['action'] ) && 'pay' === $_GET['action'] && ! empty( $_GET['id'] ) ) {
	$id = absint( $_GET['id'] );
	check_admin_referer( 'esk_payslip_pay_' . $id );
	$wpdb->update(
		$wpdb->prefix . 'esk_payslips',
		array( 'status' => 'paid', 'paid_at' => current_time( 'mysql' ) ),
		array( 'id' => $id )
	);
	esk_flash( 'success', __( 'Payslip marked as paid.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-payslips' ) );
	exit;
}

$payslips = $wpdb->get_results(
	"SELECT p.*, u.display_name FROM {$wpdb->prefix}esk_payslips p
	JOIN {$wpdb->prefix}users u ON p.user_id = u.ID
	ORDER BY p.period_start DESC LIMIT 100"
);

$teachers = $wpdb->get_results(
	"SELECT t.*, u.display_name, u.ID AS user_id FROM {$wpdb->prefix}esk_teachers t
	JOIN {$wpdb->prefix}users u ON t.user_id = u.ID ORDER BY u.display_name"
);

$flash = esk_get_flash( 'success' );
?>
<div class="wrap esk-admin-wrap">
	<h1><?php esc_html_e( 'Payslips', 'eskoofy' ); ?></h1>

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<div class="esk-card esk-form-card" style="margin-bottom:1.5rem;">
		<h2><?php esc_html_e( 'Generate Payslip', 'eskoofy' ); ?></h2>
		<form method="post" class="esk-form">
			<?php wp_nonce_field( 'esk_payslip_form' ); ?>
			<div class="esk-form-row">
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Staff', 'eskoofy' ); ?></label>
					<select name="user_id" required>
						<option value=""><?php esc_html_e( 'Select', 'eskoofy' ); ?></option>
						<?php foreach ( $teachers as $t ) : ?>
							<option value="<?php echo esc_attr( $t->user_id ); ?>"><?php echo esc_html( $t->display_name ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Period Start', 'eskoofy' ); ?></label>
					<input type="date" name="period_start" value="<?php echo esc_attr( gmdate( 'Y-m-01' ) ); ?>" required>
				</div>
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Period End', 'eskoofy' ); ?></label>
					<input type="date" name="period_end" value="<?php echo esc_attr( gmdate( 'Y-m-t' ) ); ?>" required>
				</div>
			</div>
			<button type="submit" name="esk_payslip_generate" class="button button-primary"><?php esc_html_e( 'Generate', 'eskoofy' ); ?></button>
		</form>
	</div>

	<table class="wp-list-table widefat striped esk-table">
		<thead><tr>
			<th><?php esc_html_e( 'Staff', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Period', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Basic', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Allowances', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Deductions', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Net', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Status', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Actions', 'eskoofy' ); ?></th>
		</tr></thead>
		<tbody>
			<?php if ( empty( $payslips ) ) : ?>
				<tr><td colspan="8"><?php esc_html_e( 'No payslips yet.', 'eskoofy' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $payslips as $p ) : ?>
					<tr>
						<td><?php echo esc_html( $p->display_name ); ?></td>
						<td><?php echo esc_html( esk_date_format( $p->period_start ) . ' – ' . esk_date_format( $p->period_end ) ); ?></td>
						<td><?php echo esc_html( esk_format_currency( $p->basic_salary ) ); ?></td>
						<td><?php echo esc_html( esk_format_currency( $p->total_allowances ) ); ?></td>
						<td><?php echo esc_html( esk_format_currency( $p->total_deductions ) ); ?></td>
						<td><strong><?php echo esc_html( esk_format_currency( $p->net_salary ) ); ?></strong></td>
						<td><span class="esk-badge esk-badge-<?php echo esc_attr( $p->status ); ?>"><?php echo esc_html( ucfirst( $p->status ) ); ?></span></td>
						<td>
							<?php if ( 'pending' === $p->status ) : ?>
								<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=esk-payslips&action=pay&id=' . $p->id ), 'esk_payslip_pay_' . $p->id ) ); ?>" class="button button-small"><?php esc_html_e( 'Mark Paid', 'eskoofy' ); ?></a>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>