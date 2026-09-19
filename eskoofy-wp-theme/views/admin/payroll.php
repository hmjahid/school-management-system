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
	$user_id          = absint( $_POST['user_id'] ?? 0 );
	$effective_from   = sanitize_text_field( $_POST['effective_from'] ?? gmdate( 'Y-m-d' ) );
	$effective_to     = sanitize_text_field( $_POST['effective_to'] ?? '' );
	$basic_salary     = (float) ( $_POST['basic_salary'] ?? 0 );
	$house_allowance  = (float) ( $_POST['house_allowance'] ?? 0 );
	$medical_allowance = (float) ( $_POST['medical_allowance'] ?? 0 );
	$transport_allowance = (float) ( $_POST['transport_allowance'] ?? 0 );
	$other_allowance  = (float) ( $_POST['other_allowance'] ?? 0 );
	$tax_deduction    = (float) ( $_POST['tax_deduction'] ?? 0 );
	$provident_fund   = (float) ( $_POST['provident_fund'] ?? 0 );
	$other_deduction  = (float) ( $_POST['other_deduction'] ?? 0 );
	$notes            = sanitize_textarea_field( $_POST['notes'] ?? '' );

	if ( $user_id ) {
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}esk_salary_structures WHERE user_id = %d AND effective_from = %s",
				$user_id,
				$effective_from
			)
		);

		$data = array(
			'user_id'             => $user_id,
			'basic_salary'        => $basic_salary,
			'house_allowance'     => $house_allowance,
			'medical_allowance'   => $medical_allowance,
			'transport_allowance' => $transport_allowance,
			'other_allowance'     => $other_allowance,
			'tax_deduction'       => $tax_deduction,
			'provident_fund'      => $provident_fund,
			'other_deduction'     => $other_deduction,
			'effective_from'      => $effective_from,
			'effective_to'        => $effective_to ?: null,
			'notes'               => $notes,
		);

		if ( $existing ) {
			$wpdb->update( $wpdb->prefix . 'esk_salary_structures', $data, array( 'id' => $existing ) );
		} else {
			$wpdb->insert( $wpdb->prefix . 'esk_salary_structures', $data );
		}
	}

	esk_flash( 'success', __( 'Salary structure saved.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-payroll&tab=structures' ) );
	exit;
}

if ( isset( $_POST['esk_leave_action'] ) ) {
	check_admin_referer( 'esk_leave_action_' . absint( $_POST['leave_id'] ?? 0 ) );
	$leave_action = sanitize_text_field( $_POST['esk_leave_action'] ?? '' );
	$leave_id     = absint( $_POST['leave_id'] ?? 0 );
	if ( $leave_id && in_array( $leave_action, array( 'approve', 'reject' ), true ) ) {
		$wpdb->update(
			$wpdb->prefix . 'esk_leave_requests',
			array(
				'status'      => 'approved' === $leave_action ? 'approved' : 'rejected',
				'approved_by' => get_current_user_id(),
				'approved_at' => current_time( 'mysql' ),
			),
			array( 'id' => $leave_id )
		);
	}
	esk_flash( 'success', __( 'Leave request updated.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-payroll&tab=leave' ) );
	exit;
}

$teachers = $wpdb->get_results(
	"SELECT t.*, u.display_name FROM {$wpdb->prefix}esk_teachers t JOIN {$wpdb->prefix}users u ON t.user_id = u.ID ORDER BY u.display_name"
);

$structures = array();
if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}esk_salary_structures'" ) === $wpdb->prefix . 'esk_salary_structures' ) {
	$structures = $wpdb->get_results(
		"SELECT ss.*, u.display_name FROM {$wpdb->prefix}esk_salary_structures ss
		JOIN {$wpdb->prefix}users u ON ss.user_id = u.ID
		ORDER BY ss.effective_from DESC"
	);
}

$leave_requests = array();
if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}esk_leave_requests'" ) === $wpdb->prefix . 'esk_leave_requests' ) {
	$leave_requests = $wpdb->get_results(
		"SELECT lr.*, u.display_name, lt.name AS leave_type_name
		FROM {$wpdb->prefix}esk_leave_requests lr
		JOIN {$wpdb->prefix}users u ON lr.user_id = u.ID
		LEFT JOIN {$wpdb->prefix}esk_leave_types lt ON lr.leave_type_id = lt.id
		ORDER BY lr.created_at DESC"
	);
}

$flash = esk_get_flash( 'success' );
?>
<div class="wrap esk-admin-wrap">
	<h1><?php esc_html_e( 'Payroll', 'eskoofy' ); ?></h1>

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<nav class="nav-tab-wrapper esk-tabs">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=esk-payroll&tab=structures' ) ); ?>" class="nav-tab <?php echo 'structures' === $tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Salary Structures', 'eskoofy' ); ?></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=esk-payroll&tab=leave' ) ); ?>" class="nav-tab <?php echo 'leave' === $tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Leave Requests', 'eskoofy' ); ?></a>
	</nav>

	<?php if ( 'leave' === $tab ) : ?>
		<div class="esk-card">
			<h2><?php esc_html_e( 'Leave Requests', 'eskoofy' ); ?></h2>
			<table class="wp-list-table widefat striped esk-table">
				<thead><tr>
					<th><?php esc_html_e( 'Staff', 'eskoofy' ); ?></th>
					<th><?php esc_html_e( 'Type', 'eskoofy' ); ?></th>
					<th><?php esc_html_e( 'From', 'eskoofy' ); ?></th>
					<th><?php esc_html_e( 'To', 'eskoofy' ); ?></th>
					<th><?php esc_html_e( 'Days', 'eskoofy' ); ?></th>
					<th><?php esc_html_e( 'Status', 'eskoofy' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'eskoofy' ); ?></th>
				</tr></thead>
				<tbody>
					<?php if ( empty( $leave_requests ) ) : ?>
						<tr><td colspan="7"><?php esc_html_e( 'No leave requests.', 'eskoofy' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $leave_requests as $lr ) : ?>
							<tr>
								<td><?php echo esc_html( $lr->display_name ); ?></td>
								<td><?php echo esc_html( $lr->leave_type_name ); ?></td>
								<td><?php echo esc_html( esk_date_format( $lr->start_date ) ); ?></td>
								<td><?php echo esc_html( esk_date_format( $lr->end_date ) ); ?></td>
								<td><?php echo esc_html( $lr->days ); ?></td>
								<td><span class="esk-badge esk-badge-<?php echo esc_attr( $lr->status ); ?>"><?php echo esc_html( ucfirst( $lr->status ) ); ?></span></td>
								<td>
									<?php if ( 'pending' === $lr->status ) : ?>
										<form method="post" style="display:inline;">
											<?php wp_nonce_field( 'esk_leave_action_' . $lr->id ); ?>
											<input type="hidden" name="leave_id" value="<?php echo esc_attr( $lr->id ); ?>">
											<button type="submit" name="esk_leave_action" value="approve" class="button button-small"><?php esc_html_e( 'Approve', 'eskoofy' ); ?></button>
										</form>
										<form method="post" style="display:inline;">
											<?php wp_nonce_field( 'esk_leave_action_' . $lr->id ); ?>
											<input type="hidden" name="leave_id" value="<?php echo esc_attr( $lr->id ); ?>">
											<button type="submit" name="esk_leave_action" value="reject" class="button button-small"><?php esc_html_e( 'Reject', 'eskoofy' ); ?></button>
										</form>
									<?php else : ?>—<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	<?php else : ?>
		<div class="esk-card esk-form-card" style="margin-bottom:1.5rem;">
			<h2><?php esc_html_e( 'Add / Update Salary Structure', 'eskoofy' ); ?></h2>
			<form method="post" class="esk-form">
				<?php wp_nonce_field( 'esk_salary_form' ); ?>
				<div class="esk-form-row">
					<div class="esk-form-group">
						<label><?php esc_html_e( 'Staff', 'eskoofy' ); ?> *</label>
						<select name="user_id" required>
							<option value=""><?php esc_html_e( 'Select staff', 'eskoofy' ); ?></option>
							<?php foreach ( $teachers as $t ) : ?>
								<option value="<?php echo esc_attr( $t->user_id ); ?>"><?php echo esc_html( $t->display_name ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="esk-form-group">
						<label><?php esc_html_e( 'Effective From', 'eskoofy' ); ?> *</label>
						<input type="date" name="effective_from" value="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>" required>
					</div>
					<div class="esk-form-group">
						<label><?php esc_html_e( 'Effective To', 'eskoofy' ); ?></label>
						<input type="date" name="effective_to">
					</div>
				</div>
				<div class="esk-form-row">
					<div class="esk-form-group"><label><?php esc_html_e( 'Basic Salary', 'eskoofy' ); ?></label><input type="number" step="0.01" name="basic_salary" value="0"></div>
					<div class="esk-form-group"><label><?php esc_html_e( 'House Allowance', 'eskoofy' ); ?></label><input type="number" step="0.01" name="house_allowance" value="0"></div>
					<div class="esk-form-group"><label><?php esc_html_e( 'Medical Allowance', 'eskoofy' ); ?></label><input type="number" step="0.01" name="medical_allowance" value="0"></div>
					<div class="esk-form-group"><label><?php esc_html_e( 'Transport Allowance', 'eskoofy' ); ?></label><input type="number" step="0.01" name="transport_allowance" value="0"></div>
					<div class="esk-form-group"><label><?php esc_html_e( 'Other Allowance', 'eskoofy' ); ?></label><input type="number" step="0.01" name="other_allowance" value="0"></div>
				</div>
				<div class="esk-form-row">
					<div class="esk-form-group"><label><?php esc_html_e( 'Tax Deduction', 'eskoofy' ); ?></label><input type="number" step="0.01" name="tax_deduction" value="0"></div>
					<div class="esk-form-group"><label><?php esc_html_e( 'Provident Fund', 'eskoofy' ); ?></label><input type="number" step="0.01" name="provident_fund" value="0"></div>
					<div class="esk-form-group"><label><?php esc_html_e( 'Other Deduction', 'eskoofy' ); ?></label><input type="number" step="0.01" name="other_deduction" value="0"></div>
				</div>
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Notes', 'eskoofy' ); ?></label>
					<textarea name="notes" rows="2" class="large-text"></textarea>
				</div>
				<button type="submit" name="esk_salary_structure_save" class="button button-primary"><?php esc_html_e( 'Save Salary Structure', 'eskoofy' ); ?></button>
			</form>
		</div>

		<div class="esk-card">
			<h2><?php esc_html_e( 'Existing Salary Structures', 'eskoofy' ); ?></h2>
			<table class="wp-list-table widefat striped esk-table">
				<thead><tr>
					<th><?php esc_html_e( 'Staff', 'eskoofy' ); ?></th>
					<th><?php esc_html_e( 'Basic', 'eskoofy' ); ?></th>
					<th><?php esc_html_e( 'Allowances', 'eskoofy' ); ?></th>
					<th><?php esc_html_e( 'Deductions', 'eskoofy' ); ?></th>
					<th><?php esc_html_e( 'Effective From', 'eskoofy' ); ?></th>
					<th><?php esc_html_e( 'Effective To', 'eskoofy' ); ?></th>
				</tr></thead>
				<tbody>
					<?php if ( empty( $structures ) ) : ?>
						<tr><td colspan="6"><?php esc_html_e( 'No salary structures yet.', 'eskoofy' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $structures as $s ) : ?>
							<?php
							$allow = (float) $s->house_allowance + (float) $s->medical_allowance + (float) $s->transport_allowance + (float) $s->other_allowance;
							$ded   = (float) $s->tax_deduction + (float) $s->provident_fund + (float) $s->other_deduction;
							?>
							<tr>
								<td><?php echo esc_html( $s->display_name ); ?></td>
								<td><?php echo esc_html( esk_format_currency( $s->basic_salary ) ); ?></td>
								<td><?php echo esc_html( esk_format_currency( $allow ) ); ?></td>
								<td><?php echo esc_html( esk_format_currency( $ded ) ); ?></td>
								<td><?php echo esc_html( esk_date_format( $s->effective_from ) ); ?></td>
								<td><?php echo $s->effective_to ? esc_html( esk_date_format( $s->effective_to ) ) : '—'; ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</div>