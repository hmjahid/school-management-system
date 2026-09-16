<?php
/**
 * Shortcodes for Eskoofy.
 *
 * @package Eskoofy
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

add_shortcode( 'eskoofy_results_lookup', 'esk_shortcode_results_lookup' );
add_shortcode( 'eskoofy_admission_form', 'esk_shortcode_admission_form' );
add_shortcode( 'eskoofy_fees_payment', 'esk_shortcode_fees_payment' );
add_shortcode( 'eskoofy_student_profile', 'esk_shortcode_student_profile' );
add_shortcode( 'eskoofy_class_schedule', 'esk_shortcode_class_schedule' );
add_shortcode( 'eskoofy_news_list', 'esk_shortcode_news_list' );
add_shortcode( 'eskoofy_events_list', 'esk_shortcode_events_list' );
add_shortcode( 'eskoofy_gallery', 'esk_shortcode_gallery' );
add_shortcode( 'eskoofy_contact_form', 'esk_shortcode_contact_form' );
add_shortcode( 'eskoofy_payment_gateway', 'esk_shortcode_payment_gateway' );
add_shortcode( 'eskoofy_bright_students', 'esk_shortcode_bright_students' );
add_shortcode( 'eskoofy_login_form', 'esk_shortcode_login_form' );

function esk_shortcode_results_lookup( $atts ): string {
	$atts   = shortcode_atts( array(), $atts );
	global $wpdb;

	if ( isset( $_POST['esk_results_lookup'] ) ) {
		$nonce = isset( $_POST['esk_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['esk_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'esk_results_lookup' ) ) {
			return '<div class="esk-notice esk-notice-error"><p>' . esc_html__( 'Security check failed. Please reload and try again.', 'eskoofy' ) . '</p></div>';
		}
		$admission_number = sanitize_text_field( wp_unslash( $_POST['admission_number'] ?? '' ) );
		$exam_id          = absint( $_POST['exam_id'] ?? 0 );

		$student = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT s.*, u.display_name FROM {$wpdb->prefix}esk_students s
				JOIN {$wpdb->prefix}users u ON s.user_id = u.ID
				WHERE s.admission_number = %s AND s.deleted_at IS NULL",
				$admission_number
			)
		);

		ob_start();

		if ( ! $student ) {
			echo '<div class="esk-results-lookup"><div class="esk-notice esk-notice-error"><p>' . esc_html__( 'Student not found.', 'eskoofy' ) . '</p></div></div>';
			return ob_get_clean();
		}

		$where  = "WHERE er.student_id = %d AND er.deleted_at IS NULL";
		$params = array( $student->id );
		if ( $exam_id ) {
			$where  .= ' AND er.exam_id = %d';
			$params[] = $exam_id;
		}

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT er.*, e.name AS exam_name, e.total_marks, e.passing_marks
				FROM {$wpdb->prefix}esk_exam_results er
				JOIN {$wpdb->prefix}esk_exams e ON er.exam_id = e.id
				{$where}
				ORDER BY e.start_date DESC",
				...$params
			)
		);

		?>
		<div class="esk-results-lookup">
			<h3><?php esc_html_e( 'Results for', 'eskoofy' ); ?> <?php echo esc_html( $student->display_name ); ?></h3>
			<p><strong><?php esc_html_e( 'Admission No:', 'eskoofy' ); ?></strong> <?php echo esc_html( $student->admission_number ); ?></p>
			<?php if ( empty( $results ) ) : ?>
				<p><?php esc_html_e( 'No results found.', 'eskoofy' ); ?></p>
			<?php else : ?>
				<table class="esk-table esk-table-striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Exam', 'eskoofy' ); ?></th>
							<th><?php esc_html_e( 'Marks', 'eskoofy' ); ?></th>
							<th><?php esc_html_e( 'Passing', 'eskoofy' ); ?></th>
							<th><?php esc_html_e( 'Status', 'eskoofy' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $results as $r ) : ?>
							<tr>
								<td><?php echo esc_html( $r->exam_name ); ?></td>
								<td><?php echo esc_html( $r->obtained_marks ); ?></td>
								<td><?php echo esc_html( $r->passing_marks ); ?></td>
								<td>
									<span class="esk-badge esk-badge-<?php echo 'pass' === $r->status ? 'pass' : 'fail'; ?>">
										<?php echo 'pass' === $r->status ? esc_html__( 'Pass', 'eskoofy' ) : esc_html__( 'Fail', 'eskoofy' ); ?>
									</span>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
			<p><a href="<?php echo esc_url( remove_query_arg() ); ?>" class="esk-button"><?php esc_html_e( 'Look Up Another', 'eskoofy' ); ?></a></p>
		</div>
		<?php
		return ob_get_clean();
	}

	ob_start();
	?>
	<div class="esk-results-lookup">
		<h3><?php esc_html_e( 'Check Your Results', 'eskoofy' ); ?></h3>
		<form method="post" class="esk-form">
			<?php esk_csrf_field( 'esk_results_lookup' ); ?>
			<div class="esk-form-group">
				<label for="esk-admission-number"><?php esc_html_e( 'Admission Number', 'eskoofy' ); ?></label>
				<input type="text" id="esk-admission-number" name="admission_number" class="esk-input" required>
			</div>
			<div class="esk-form-group">
				<label for="esk-result-exam"><?php esc_html_e( 'Exam', 'eskoofy' ); ?></label>
				<select id="esk-result-exam" name="exam_id" class="esk-select">
					<option value=""><?php esc_html_e( 'All Exams', 'eskoofy' ); ?></option>
					<?php
					$exam_rows = $wpdb->get_results(
						"SELECT id, name, code FROM {$wpdb->prefix}esk_exams WHERE deleted_at IS NULL ORDER BY start_date DESC"
					);
					foreach ( $exam_rows as $exam ) :
						?>
						<option value="<?php echo esc_attr( $exam->id ); ?>">
							<?php echo esc_html( $exam->name . ' (' . $exam->code . ')' ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
			<button type="submit" class="esk-button esk-button-primary"><?php esc_html_e( 'Look Up', 'eskoofy' ); ?></button>
		</form>
	</div>
	<?php
	return ob_get_clean();
}

function esk_shortcode_admission_form( $atts ): string {
	$atts   = shortcode_atts( array(), $atts );
	global $wpdb;
	$sessions = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}esk_academic_sessions WHERE deleted_at IS NULL ORDER BY name DESC" );
	$batches  = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}esk_batches WHERE deleted_at IS NULL ORDER BY name" );

	ob_start();
	?>
	<div class="esk-admission-form">
		<h3><?php esc_html_e( 'Apply for Admission', 'eskoofy' ); ?></h3>
		<?php
		$flash_success = esk_get_flash( 'success' );
		if ( '' !== $flash_success ) :
			?>
			<div class="esk-notice esk-notice-success">
				<?php echo esc_html( $flash_success ); ?>
			</div>
		<?php else : ?>
			<div class="esk-wizard" data-esk-wizard>
				<ol class="esk-wizard-steps" data-esk-wizard-steps>
					<li data-esk-wizard-step-dot="0" class="is-active"><span>1</span><?php esc_html_e( 'Student', 'eskoofy' ); ?></li>
					<li data-esk-wizard-step-dot="1"><span>2</span><?php esc_html_e( 'Contact', 'eskoofy' ); ?></li>
					<li data-esk-wizard-step-dot="2"><span>3</span><?php esc_html_e( 'Academic', 'eskoofy' ); ?></li>
					<li data-esk-wizard-step-dot="3"><span>4</span><?php esc_html_e( 'Guardian', 'eskoofy' ); ?></li>
					<li data-esk-wizard-step-dot="4"><span>5</span><?php esc_html_e( 'Documents', 'eskoofy' ); ?></li>
					<li data-esk-wizard-step-dot="5"><span>6</span><?php esc_html_e( 'Review', 'eskoofy' ); ?></li>
				</ol>

				<form method="post" enctype="multipart/form-data" class="esk-form" data-esk-wizard-form>
					<?php esk_csrf_field( 'esk_admission_form' ); ?>

					<fieldset data-esk-wizard-panel="0">
						<h4><?php esc_html_e( 'Student Information', 'eskoofy' ); ?></h4>
						<div class="esk-form-row">
							<div class="esk-form-group"><label><?php esc_html_e( 'First Name', 'eskoofy' ); ?> *</label><input type="text" name="first_name" class="esk-input" required></div>
							<div class="esk-form-group"><label><?php esc_html_e( 'Last Name', 'eskoofy' ); ?> *</label><input type="text" name="last_name" class="esk-input" required></div>
						</div>
						<div class="esk-form-row">
							<div class="esk-form-group"><label><?php esc_html_e( 'Gender', 'eskoofy' ); ?> *</label>
								<select name="gender" class="esk-select" required>
									<option value="male"><?php esc_html_e( 'Male', 'eskoofy' ); ?></option>
									<option value="female"><?php esc_html_e( 'Female', 'eskoofy' ); ?></option>
									<option value="other"><?php esc_html_e( 'Other', 'eskoofy' ); ?></option>
								</select>
							</div>
							<div class="esk-form-group"><label><?php esc_html_e( 'Date of Birth', 'eskoofy' ); ?> *</label><input type="date" name="date_of_birth" class="esk-input" required></div>
						</div>
						<div class="esk-form-row">
							<div class="esk-form-group"><label><?php esc_html_e( 'Blood Group', 'eskoofy' ); ?></label><input type="text" name="blood_group" class="esk-input" placeholder="A+"></div>
							<div class="esk-form-group"><label><?php esc_html_e( 'Religion', 'eskoofy' ); ?></label><input type="text" name="religion" class="esk-input"></div>
						</div>
						<button type="button" class="esk-button esk-button-primary" data-esk-wizard-next><?php esc_html_e( 'Continue', 'eskoofy' ); ?></button>
					</fieldset>

					<fieldset data-esk-wizard-panel="1" hidden>
						<h4><?php esc_html_e( 'Contact Information', 'eskoofy' ); ?></h4>
						<div class="esk-form-row">
							<div class="esk-form-group"><label><?php esc_html_e( 'Email', 'eskoofy' ); ?> *</label><input type="email" name="email" class="esk-input" required></div>
							<div class="esk-form-group"><label><?php esc_html_e( 'Phone', 'eskoofy' ); ?> *</label><input type="tel" name="phone" class="esk-input" required></div>
						</div>
						<div class="esk-form-group"><label><?php esc_html_e( 'Address', 'eskoofy' ); ?> *</label><textarea name="address" class="esk-textarea" required></textarea></div>
						<div class="esk-form-row">
							<div class="esk-form-group"><label><?php esc_html_e( 'City', 'eskoofy' ); ?> *</label><input type="text" name="city" class="esk-input" required></div>
							<div class="esk-form-group"><label><?php esc_html_e( 'Postal Code', 'eskoofy' ); ?> *</label><input type="text" name="postal_code" class="esk-input" required></div>
						</div>
						<div class="esk-wizard-actions">
							<button type="button" class="esk-button" data-esk-wizard-back><?php esc_html_e( 'Back', 'eskoofy' ); ?></button>
							<button type="button" class="esk-button esk-button-primary" data-esk-wizard-next><?php esc_html_e( 'Continue', 'eskoofy' ); ?></button>
						</div>
					</fieldset>

					<fieldset data-esk-wizard-panel="2" hidden>
						<h4><?php esc_html_e( 'Academic Information', 'eskoofy' ); ?></h4>
						<div class="esk-form-group">
							<label><?php esc_html_e( 'Academic Session', 'eskoofy' ); ?> *</label>
							<select name="academic_session_id" class="esk-select" required>
								<option value=""><?php esc_html_e( 'Select', 'eskoofy' ); ?></option>
								<?php foreach ( $sessions as $s ) : ?>
									<option value="<?php echo esc_attr( $s->id ); ?>"><?php echo esc_html( $s->name ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="esk-form-group">
							<label><?php esc_html_e( 'Class / Batch', 'eskoofy' ); ?> *</label>
							<select name="batch_id" class="esk-select" required>
								<option value=""><?php esc_html_e( 'Select', 'eskoofy' ); ?></option>
								<?php foreach ( $batches as $b ) : ?>
									<option value="<?php echo esc_attr( $b->id ); ?>"><?php echo esc_html( $b->name ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="esk-form-row">
							<div class="esk-form-group"><label><?php esc_html_e( 'Previous School', 'eskoofy' ); ?></label><input type="text" name="previous_school" class="esk-input"></div>
							<div class="esk-form-group"><label><?php esc_html_e( 'Previous Class', 'eskoofy' ); ?></label><input type="text" name="previous_class" class="esk-input"></div>
						</div>
						<div class="esk-wizard-actions">
							<button type="button" class="esk-button" data-esk-wizard-back><?php esc_html_e( 'Back', 'eskoofy' ); ?></button>
							<button type="button" class="esk-button esk-button-primary" data-esk-wizard-next><?php esc_html_e( 'Continue', 'eskoofy' ); ?></button>
						</div>
					</fieldset>

					<fieldset data-esk-wizard-panel="3" hidden>
						<h4><?php esc_html_e( 'Guardian Information', 'eskoofy' ); ?></h4>
						<div class="esk-form-row">
							<div class="esk-form-group"><label><?php esc_html_e( "Father's Name", 'eskoofy' ); ?> *</label><input type="text" name="father_name" class="esk-input" required></div>
							<div class="esk-form-group"><label><?php esc_html_e( "Father's Phone", 'eskoofy' ); ?> *</label><input type="tel" name="father_phone" class="esk-input" required></div>
						</div>
						<div class="esk-form-group"><label><?php esc_html_e( "Father's Occupation", 'eskoofy' ); ?></label><input type="text" name="father_occupation" class="esk-input"></div>
						<div class="esk-form-row">
							<div class="esk-form-group"><label><?php esc_html_e( "Mother's Name", 'eskoofy' ); ?> *</label><input type="text" name="mother_name" class="esk-input" required></div>
							<div class="esk-form-group"><label><?php esc_html_e( "Mother's Phone", 'eskoofy' ); ?> *</label><input type="tel" name="mother_phone" class="esk-input" required></div>
						</div>
						<div class="esk-form-group"><label><?php esc_html_e( "Mother's Occupation", 'eskoofy' ); ?></label><input type="text" name="mother_occupation" class="esk-input"></div>
						<div class="esk-form-row">
							<div class="esk-form-group"><label><?php esc_html_e( 'Guardian Name (if different)', 'eskoofy' ); ?></label><input type="text" name="guardian_name" class="esk-input"></div>
							<div class="esk-form-group"><label><?php esc_html_e( 'Relation', 'eskoofy' ); ?></label><input type="text" name="guardian_relation" class="esk-input"></div>
						</div>
						<div class="esk-form-group"><label><?php esc_html_e( 'Guardian Phone', 'eskoofy' ); ?></label><input type="tel" name="guardian_phone" class="esk-input"></div>
						<div class="esk-wizard-actions">
							<button type="button" class="esk-button" data-esk-wizard-back><?php esc_html_e( 'Back', 'eskoofy' ); ?></button>
							<button type="button" class="esk-button esk-button-primary" data-esk-wizard-next><?php esc_html_e( 'Continue', 'eskoofy' ); ?></button>
						</div>
					</fieldset>

					<fieldset data-esk-wizard-panel="4" hidden>
						<h4><?php esc_html_e( 'Documents', 'eskoofy' ); ?></h4>
						<div class="esk-form-group">
							<label><?php esc_html_e( 'Student Photo', 'eskoofy' ); ?></label>
							<input type="file" name="photo" class="esk-input" accept="image/*">
						</div>
						<div class="esk-form-group">
							<label><?php esc_html_e( 'Transfer Certificate (T.C.)', 'eskoofy' ); ?></label>
							<input type="file" name="transfer_certificate" accept=".pdf,.jpg,.png">
						</div>
						<div class="esk-form-group">
							<label><?php esc_html_e( 'Birth Certificate', 'eskoofy' ); ?></label>
							<input type="file" name="birth_certificate" accept=".pdf,.jpg,.png">
						</div>
						<div class="esk-wizard-actions">
							<button type="button" class="esk-button" data-esk-wizard-back><?php esc_html_e( 'Back', 'eskoofy' ); ?></button>
							<button type="button" class="esk-button esk-button-primary" data-esk-wizard-next><?php esc_html_e( 'Review', 'eskoofy' ); ?></button>
						</div>
					</fieldset>

					<fieldset data-esk-wizard-panel="5" hidden>
						<h4><?php esc_html_e( 'Review & Submit', 'eskoofy' ); ?></h4>
						<div class="esk-notice esk-notice-info"><p><?php esc_html_e( 'Please review all information before submitting. You can go back to edit any section.', 'eskoofy' ); ?></p></div>
						<div class="esk-wizard-review" data-esk-wizard-review></div>
						<div class="esk-wizard-actions">
							<button type="button" class="esk-button" data-esk-wizard-back><?php esc_html_e( 'Back', 'eskoofy' ); ?></button>
							<button type="submit" name="esk_admission_submit" class="esk-button esk-button-primary"><?php esc_html_e( 'Submit Application', 'eskoofy' ); ?></button>
						</div>
					</fieldset>
				</form>
			</div>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}

function esk_shortcode_fees_payment( $atts ): string {
	$atts   = shortcode_atts( array(), $atts );
	global $wpdb;

	// Show result notice when returning from a gateway callback.
	$payment_result = sanitize_text_field( $_GET['payment'] ?? '' );
	if ( 'success' === $payment_result ) {
		return '<div class="esk-notice esk-notice-success"><p>' . esc_html__( 'Payment completed successfully. Thank you!', 'eskoofy' ) . '</p></div>';
	}
	if ( 'failed' === $payment_result ) {
		return '<div class="esk-notice esk-notice-error"><p>' . esc_html__( 'Payment was not completed. Please try again.', 'eskoofy' ) . '</p></div>';
	}

	// ── Online payment initiation ────────────────────────────────────────
	if ( isset( $_POST['esk_fees_pay'] ) ) {
		$nonce = isset( $_POST['esk_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['esk_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'esk_fees_pay' ) ) {
			return '<div class="esk-notice esk-notice-error"><p>' . esc_html__( 'Security check failed. Please reload and try again.', 'eskoofy' ) . '</p></div>';
		}

		$admission_number = sanitize_text_field( wp_unslash( $_POST['admission_number'] ?? '' ) );
		$fee_id           = absint( $_POST['fee_id'] ?? 0 );
		$gateway_code     = sanitize_text_field( wp_unslash( $_POST['payment_gateway'] ?? '' ) );
		$amount           = (float) ( $_POST['amount'] ?? 0 );

		$student = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT s.*, u.display_name, u.user_email, u.user_login FROM {$wpdb->prefix}esk_students s
				JOIN {$wpdb->prefix}users u ON s.user_id = u.ID
				WHERE s.admission_number = %s AND s.deleted_at IS NULL",
				$admission_number
			)
		);
		$fee     = $fee_id ? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}esk_fees WHERE id = %d AND status = 'active' AND deleted_at IS NULL", $fee_id ) ) : null;

		if ( ! $student || ! $fee ) {
			return '<div class="esk-notice esk-notice-error"><p>' . esc_html__( 'Student or fee not found.', 'eskoofy' ) . '</p></div>';
		}
		if ( $amount <= 0 ) {
			$amount = (float) $fee->amount;
		}
		$gateway = esk_get_payment_gateway( $gateway_code );
		if ( ! $gateway || ! $gateway->is_online ) {
			return '<div class="esk-notice esk-notice-error"><p>' . esc_html__( 'Selected payment method is unavailable. Please try again.', 'eskoofy' ) . '</p></div>';
		}

		// Create the pending fee payment + generic payment records (mirrors app).
		$invoice = esk_generate_number( 'INV', 'fee_payments' );
		$wpdb->insert( $wpdb->prefix . 'esk_fee_payments', array(
			'invoice_number'  => $invoice,
			'student_id'      => $student->id,
			'fee_id'          => $fee->id,
			'amount'          => $amount,
			'paid_amount'     => 0,
			'balance'         => $amount,
			'payment_date'    => gmdate( 'Y-m-d' ),
			'payment_method'  => $gateway_code,
			'status'          => 'pending',
			'metadata'        => wp_json_encode( array( 'gateway' => $gateway_code ) ),
			'created_by'      => get_current_user_id() ?: $student->user_id,
		) );
		$fee_payment_id = (int) $wpdb->insert_id;

		$wpdb->insert( $wpdb->prefix . 'esk_payments', array(
			'paymentable_type' => 'App\\Models\\FeePayment',
			'paymentable_id'   => $fee_payment_id,
			'invoice_number'   => $invoice,
			'amount'           => $amount,
			'due_amount'       => $amount,
			'total_amount'     => $amount,
			'payment_method'   => $gateway_code,
			'payment_status'   => 'pending',
			'payment_details'  => wp_json_encode( array(
				'description' => 'Fee payment: ' . $fee->name,
			) ),
			'metadata'         => wp_json_encode( array(
				'fee_payment_id' => $fee_payment_id,
				'student_id'     => $student->id,
				'fee_id'         => $fee->id,
			) ),
			'created_by'       => get_current_user_id() ?: $student->user_id,
		) );

		$init = esk_process_payment(
			$gateway_code,
			$amount,
			array(
				'order_id'  => $invoice,
				'customer'  => array(
					'name'  => $student->display_name,
					'email' => $student->user_email,
					'phone' => isset( $student->phone ) ? $student->phone : '',
				),
				'description' => 'Fee payment: ' . $fee->name,
			)
		);

		if ( ! empty( $init['redirect_url'] ) ) {
			wp_safe_redirect( $init['redirect_url'] );
			exit;
		}

		esk_flash( 'success', $init['message'] ?? __( 'Payment recorded. Please complete the transfer and submit proof to the office.', 'eskoofy' ) );
		wp_safe_redirect( add_query_arg( 'admission_number', rawurlencode( $admission_number ), remove_query_arg( array( 'order_id', 'status' ) ) ) );
		exit;
	}

	if ( isset( $_POST['esk_fees_lookup'] ) ) {
		$nonce = isset( $_POST['esk_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['esk_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'esk_fees_lookup' ) ) {
			return '<div class="esk-notice esk-notice-error"><p>' . esc_html__( 'Security check failed. Please reload and try again.', 'eskoofy' ) . '</p></div>';
		}
		$admission_number = sanitize_text_field( wp_unslash( $_POST['admission_number'] ?? '' ) );
		$fee_id           = absint( $_POST['fee_id'] ?? 0 );

		$student = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT s.*, u.display_name FROM {$wpdb->prefix}esk_students s
				JOIN {$wpdb->prefix}users u ON s.user_id = u.ID
				WHERE s.admission_number = %s AND s.deleted_at IS NULL",
				$admission_number
			)
		);

		ob_start();

		if ( ! $student ) {
			echo '<div class="esk-fees-payment"><div class="esk-notice esk-notice-error"><p>' . esc_html__( 'Student not found.', 'eskoofy' ) . '</p></div></div>';
			return ob_get_clean();
		}

		$where  = 'WHERE fp.student_id = %d AND fp.deleted_at IS NULL';
		$params = array( $student->id );
		if ( $fee_id ) {
			$where  .= ' AND fp.fee_id = %d';
			$params[] = $fee_id;
		}

		$payments = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT fp.*, f.name AS fee_name, f.amount AS fee_amount
				FROM {$wpdb->prefix}esk_fee_payments fp
				JOIN {$wpdb->prefix}esk_fees f ON fp.fee_id = f.id
				{$where}
				ORDER BY fp.payment_date DESC",
				...$params
			)
		);

		?>
		<div class="esk-fees-payment">
			<h3><?php esc_html_e( 'Fee Status for', 'eskoofy' ); ?> <?php echo esc_html( $student->display_name ); ?></h3>
			<p><strong><?php esc_html_e( 'Admission No:', 'eskoofy' ); ?></strong> <?php echo esc_html( $student->admission_number ); ?></p>
			<?php if ( empty( $payments ) ) : ?>
				<p><?php esc_html_e( 'No fee records found.', 'eskoofy' ); ?></p>
			<?php else : ?>
				<table class="esk-table esk-table-striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Fee', 'eskoofy' ); ?></th>
							<th><?php esc_html_e( 'Amount', 'eskoofy' ); ?></th>
							<th><?php esc_html_e( 'Paid', 'eskoofy' ); ?></th>
							<th><?php esc_html_e( 'Date', 'eskoofy' ); ?></th>
							<th><?php esc_html_e( 'Status', 'eskoofy' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $payments as $p ) : ?>
							<tr>
								<td><?php echo esc_html( $p->fee_name ); ?></td>
								<td><?php echo esc_html( esk_format_currency( $p->amount ) ); ?></td>
								<td><?php echo esc_html( esk_format_currency( $p->paid_amount ) ); ?></td>
								<td><?php echo esc_html( esk_date_format( $p->payment_date ) ); ?></td>
								<td>
									<span class="esk-badge esk-badge-<?php echo esc_attr( $p->status ); ?>">
										<?php echo esc_html( ucfirst( $p->status ) ); ?>
									</span>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<?php
			// Offer online payment for active fees not fully paid.
			$payable = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT f.id, f.name, f.amount,
						COALESCE((SELECT SUM(fp.paid_amount) FROM {$wpdb->prefix}esk_fee_payments fp WHERE fp.fee_id = f.id AND fp.student_id = %d AND fp.status = 'completed'), 0) AS paid
					FROM {$wpdb->prefix}esk_fees f
					WHERE f.status = 'active' AND f.deleted_at IS NULL
					HAVING paid < f.amount
					ORDER BY f.name",
					$student->id
				)
			);
			$active_gateways = $wpdb->get_results(
				"SELECT * FROM {$wpdb->prefix}esk_payment_gateways WHERE is_active = 1 AND is_online = 1 AND deleted_at IS NULL ORDER BY sort_order, name"
			);
			?>
			<?php if ( ! empty( $payable ) && ! empty( $active_gateways ) ) : ?>
				<div class="esk-card" style="margin-top:1.5rem;">
					<h4><?php esc_html_e( 'Pay Online', 'eskoofy' ); ?></h4>
					<form method="post" class="esk-form">
						<?php esk_csrf_field( 'esk_fees_pay' ); ?>
						<input type="hidden" name="admission_number" value="<?php echo esc_attr( $student->admission_number ); ?>">
						<div class="esk-form-group">
							<label><?php esc_html_e( 'Fee', 'eskoofy' ); ?></label>
							<select name="fee_id" required>
								<?php foreach ( $payable as $pf ) : ?>
									<option value="<?php echo esc_attr( $pf->id ); ?>"><?php echo esc_html( $pf->name . ' - ' . esk_format_currency( $pf->amount ) ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="esk-form-group">
							<label><?php esc_html_e( 'Amount', 'eskoofy' ); ?></label>
							<input type="number" name="amount" step="0.01" min="1" class="esk-input" required>
						</div>
						<div class="esk-form-group">
							<label><?php esc_html_e( 'Payment Method', 'eskoofy' ); ?></label>
							<select name="payment_gateway" required>
								<?php foreach ( $active_gateways as $gw ) : ?>
									<option value="<?php echo esc_attr( $gw->code ); ?>"><?php echo esc_html( $gw->name ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<button type="submit" name="esk_fees_pay" class="esk-button esk-button-primary"><?php esc_html_e( 'Pay Now', 'eskoofy' ); ?></button>
					</form>
				</div>
			<?php endif; ?>
			<p><a href="<?php echo esc_url( remove_query_arg() ); ?>" class="esk-button"><?php esc_html_e( 'Check Another', 'eskoofy' ); ?></a></p>
		</div>
		<?php
		return ob_get_clean();
	}

	ob_start();
	?>
	<div class="esk-fees-payment">
		<h3><?php esc_html_e( 'Fee Payment Status', 'eskoofy' ); ?></h3>
		<form method="post" class="esk-form">
			<?php esk_csrf_field( 'esk_fees_lookup' ); ?>
			<div class="esk-form-group">
				<label><?php esc_html_e( 'Admission Number', 'eskoofy' ); ?></label>
				<input type="text" name="admission_number" class="esk-input" required>
			</div>
			<div class="esk-form-group">
				<label><?php esc_html_e( 'Fee Type', 'eskoofy' ); ?></label>
				<select name="fee_id" class="esk-select" required>
					<?php
					$fees = $wpdb->get_results( "SELECT id, name, amount FROM {$wpdb->prefix}esk_fees WHERE status = 'active' AND deleted_at IS NULL" );
					foreach ( $fees as $fee ) :
						?>
						<option value="<?php echo esc_attr( $fee->id ); ?>">
							<?php echo esc_html( $fee->name . ' - ' . esk_format_currency( $fee->amount ) ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
			<button type="submit" name="esk_fees_lookup" class="esk-button esk-button-primary"><?php esc_html_e( 'Check Status', 'eskoofy' ); ?></button>
		</form>
	</div>
	<?php
	return ob_get_clean();
}

function esk_shortcode_student_profile( $atts ): string {
	$atts = shortcode_atts( array( 'id' => 0 ), $atts, 'eskoofy_student_profile' );
	$id   = absint( $atts['id'] );

	if ( ! $id ) {
		return '<p>' . esc_html__( 'Student ID not specified.', 'eskoofy' ) . '</p>';
	}

	global $wpdb;
	$student = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT s.*, u.display_name, u.user_email
			FROM {$wpdb->prefix}esk_students s
			JOIN {$wpdb->prefix}users u ON s.user_id = u.ID
			WHERE s.id = %d AND s.deleted_at IS NULL",
			$id
		)
	);

	if ( ! $student ) {
		return '<p>' . esc_html__( 'Student not found.', 'eskoofy' ) . '</p>';
	}

	$class_name = $wpdb->get_var( $wpdb->prepare( "SELECT name FROM {$wpdb->prefix}esk_classes WHERE id = %d", $student->class_id ) );

	ob_start();
	?>
	<div class="esk-student-profile-card">
		<div class="esk-student-profile-header">
			<h3><?php echo esc_html( $student->display_name ); ?></h3>
			<span class="esk-badge esk-badge-<?php echo esc_attr( $student->status ); ?>">
				<?php echo esc_html( ucfirst( $student->status ) ); ?>
			</span>
		</div>
		<table class="esk-table">
			<tr><td><strong><?php esc_html_e( 'Admission No', 'eskoofy' ); ?></strong></td>
				<td><?php echo esc_html( $student->admission_number ); ?></td></tr>
			<tr><td><strong><?php esc_html_e( 'Class', 'eskoofy' ); ?></strong></td>
				<td><?php echo esc_html( $class_name ); ?></td></tr>
			<tr><td><strong><?php esc_html_e( 'Roll Number', 'eskoofy' ); ?></strong></td>
				<td><?php echo esc_html( $student->roll_number ); ?></td></tr>
			<tr><td><strong><?php esc_html_e( 'Phone', 'eskoofy' ); ?></strong></td>
				<td><?php echo esc_html( $student->phone_1 ); ?></td></tr>
			<tr><td><strong><?php esc_html_e( 'Email', 'eskoofy' ); ?></strong></td>
				<td><?php echo esc_html( $student->user_email ); ?></td></tr>
			<tr><td><strong><?php esc_html_e( 'Blood Group', 'eskoofy' ); ?></strong></td>
				<td><?php echo esc_html( $student->blood_group ); ?></td></tr>
			<tr><td><strong><?php esc_html_e( 'Religion', 'eskoofy' ); ?></strong></td>
				<td><?php echo esc_html( $student->religion ); ?></td></tr>
		</table>
	</div>
	<?php
	return ob_get_clean();
}

function esk_shortcode_class_schedule( $atts ): string {
	$atts    = shortcode_atts( array( 'id' => 0 ), $atts, 'eskoofy_class_schedule' );
	$class_id = absint( $atts['id'] );

	if ( ! $class_id ) {
		return '<p>' . esc_html__( 'Class ID not specified.', 'eskoofy' ) . '</p>';
	}

	global $wpdb;
	$routines = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT r.*, s.name AS subject_name, t.user_id, u.display_name AS teacher_name
			FROM {$wpdb->prefix}esk_routines r
			JOIN {$wpdb->prefix}esk_subjects s ON r.subject_id = s.id
			JOIN {$wpdb->prefix}esk_teachers t ON r.teacher_id = t.id
			JOIN {$wpdb->prefix}users u ON t.user_id = u.ID
			WHERE r.school_class_id = %d
			ORDER BY r.day_of_week, r.start_time",
			$class_id
		)
	);

	$days = array(
		1 => esc_html__( 'Monday', 'eskoofy' ),
		2 => esc_html__( 'Tuesday', 'eskoofy' ),
		3 => esc_html__( 'Wednesday', 'eskoofy' ),
		4 => esc_html__( 'Thursday', 'eskoofy' ),
		5 => esc_html__( 'Friday', 'eskoofy' ),
		6 => esc_html__( 'Saturday', 'eskoofy' ),
		7 => esc_html__( 'Sunday', 'eskoofy' ),
	);

	ob_start();
	?>
	<div class="esk-class-schedule">
		<h3><?php esc_html_e( 'Class Schedule', 'eskoofy' ); ?></h3>
		<table class="esk-table esk-table-striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Day', 'eskoofy' ); ?></th>
					<th><?php esc_html_e( 'Subject', 'eskoofy' ); ?></th>
					<th><?php esc_html_e( 'Teacher', 'eskoofy' ); ?></th>
					<th><?php esc_html_e( 'Time', 'eskoofy' ); ?></th>
					<th><?php esc_html_e( 'Room', 'eskoofy' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $routines ) ) : ?>
					<tr><td colspan="5"><?php esc_html_e( 'No schedule found.', 'eskoofy' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $routines as $r ) : ?>
						<tr>
							<td><?php echo esc_html( $days[ $r->day_of_week ] ?? '' ); ?></td>
							<td><?php echo esc_html( $r->subject_name ); ?></td>
							<td><?php echo esc_html( $r->teacher_name ); ?></td>
							<td><?php echo esc_html( $r->start_time . ' - ' . $r->end_time ); ?></td>
							<td><?php echo esc_html( $r->room_number ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
	<?php
	return ob_get_clean();
}

function esk_shortcode_news_list( $atts ): string {
	$atts  = shortcode_atts( array( 'count' => 5 ), $atts, 'eskoofy_news_list' );
	$count = absint( $atts['count'] );

	global $wpdb;
	$news = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}esk_news WHERE is_published = 1 AND deleted_at IS NULL ORDER BY published_at DESC LIMIT %d",
			$count
		)
	);

	ob_start();
	?>
	<div class="esk-news-list">
		<h3><?php esc_html_e( 'Latest News', 'eskoofy' ); ?></h3>
		<?php if ( empty( $news ) ) : ?>
			<p><?php esc_html_e( 'No news available.', 'eskoofy' ); ?></p>
		<?php else : ?>
			<?php foreach ( $news as $item ) : ?>
				<article class="esk-news-item">
					<h4><?php echo esc_html( $item->title ); ?></h4>
					<time datetime="<?php echo esc_attr( $item->published_at ); ?>">
						<?php echo esc_html( esk_date_format( $item->published_at ) ); ?>
					</time>
					<p><?php echo wp_kses_post( wp_trim_words( $item->content, 30 ) ); ?></p>
				</article>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}

function esk_shortcode_events_list( $atts ): string {
	$atts  = shortcode_atts( array( 'count' => 5 ), $atts, 'eskoofy_events_list' );
	$count = absint( $atts['count'] );

	global $wpdb;
	$events = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}esk_events WHERE status = 'published' AND start_date >= NOW() ORDER BY start_date ASC LIMIT %d",
			$count
		)
	);

	ob_start();
	?>
	<div class="esk-events-list">
		<h3><?php esc_html_e( 'Upcoming Events', 'eskoofy' ); ?></h3>
		<?php if ( empty( $events ) ) : ?>
			<p><?php esc_html_e( 'No upcoming events.', 'eskoofy' ); ?></p>
		<?php else : ?>
			<?php foreach ( $events as $event ) : ?>
				<div class="esk-event-item">
					<h4><?php echo esc_html( $event->title ); ?></h4>
					<time datetime="<?php echo esc_attr( $event->start_date ); ?>">
						<?php echo esc_html( esk_date_format( $event->start_date, 'd M, Y g:i A' ) ); ?>
					</time>
					<?php if ( $event->location ) : ?>
						<span class="esk-event-location"><?php echo esc_html( $event->location ); ?></span>
					<?php endif; ?>
					<p><?php echo wp_kses_post( wp_trim_words( $event->description, 20 ) ); ?></p>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}

function esk_shortcode_gallery( $atts ): string {
	$atts     = shortcode_atts( array( 'category' => '' ), $atts, 'eskoofy_gallery' );
	$category = sanitize_text_field( $atts['category'] );

	global $wpdb;
	$where  = 'WHERE is_published = 1';
	$params = array();

	if ( $category ) {
		$where  .= ' AND category = %s';
		$params[] = $category;
	}

	$query  = "SELECT * FROM {$wpdb->prefix}esk_galleries {$where} ORDER BY id DESC";
	$images = ! empty( $params ) ? $wpdb->get_results( $wpdb->prepare( $query, ...$params ) ) : $wpdb->get_results( $query );

	ob_start();
	?>
	<div class="esk-gallery-grid">
		<?php if ( empty( $images ) ) : ?>
			<p><?php esc_html_e( 'No gallery images found.', 'eskoofy' ); ?></p>
		<?php else : ?>
			<?php foreach ( $images as $img ) : ?>
				<div class="esk-gallery-item">
					<img src="<?php echo esc_url( $img->image_path ); ?>" alt="<?php echo esc_attr( $img->title ); ?>" loading="lazy">
					<div class="esk-gallery-caption">
						<strong><?php echo esc_html( $img->title ); ?></strong>
					</div>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}

function esk_shortcode_contact_form( $atts ): string {
	$atts = shortcode_atts( array(), $atts );
	ob_start();
	?>
	<div class="esk-contact-form">
		<h3><?php esc_html_e( 'Contact Us', 'eskoofy' ); ?></h3>
		<?php if ( isset( $_POST['esk_contact_submit'] ) ) : ?>
			<div class="esk-notice esk-notice-success">
				<?php esc_html_e( 'Thank you for your message. We will get back to you soon.', 'eskoofy' ); ?>
			</div>
		<?php else : ?>
			<form method="post" class="esk-form">
				<?php esk_csrf_field( 'esk_contact_form' ); ?>
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Name', 'eskoofy' ); ?> *</label>
					<input type="text" name="contact_name" class="esk-input" required>
				</div>
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Email', 'eskoofy' ); ?> *</label>
					<input type="email" name="contact_email" class="esk-input" required>
				</div>
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Phone', 'eskoofy' ); ?></label>
					<input type="tel" name="contact_phone" class="esk-input">
				</div>
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Subject', 'eskoofy' ); ?></label>
					<input type="text" name="contact_subject" class="esk-input">
				</div>
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Message', 'eskoofy' ); ?> *</label>
					<textarea name="contact_message" class="esk-textarea" rows="5" required></textarea>
				</div>
				<button type="submit" name="esk_contact_submit" class="esk-button esk-button-primary">
					<?php esc_html_e( 'Send Message', 'eskoofy' ); ?>
				</button>
			</form>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}

function esk_shortcode_payment_gateway( $atts ): string {
	$atts = shortcode_atts( array(), $atts );

	global $wpdb;
	$gateways = $wpdb->get_results(
		"SELECT * FROM {$wpdb->prefix}esk_payment_gateways WHERE is_active = 1 AND deleted_at IS NULL ORDER BY sort_order"
	);

	ob_start();
	?>
	<div class="esk-payment-gateway">
		<h3><?php esc_html_e( 'Select Payment Method', 'eskoofy' ); ?></h3>
		<?php if ( empty( $gateways ) ) : ?>
			<p><?php esc_html_e( 'No payment methods available.', 'eskoofy' ); ?></p>
		<?php else : ?>
			<div class="esk-payment-methods">
				<?php foreach ( $gateways as $gw ) : ?>
					<label class="esk-payment-method">
						<input type="radio" name="payment_gateway" value="<?php echo esc_attr( $gw->code ); ?>">
						<?php if ( $gw->logo ) : ?>
							<img src="<?php echo esc_url( $gw->logo ); ?>" alt="<?php echo esc_attr( $gw->name ); ?>">
						<?php endif; ?>
						<span><?php echo esc_html( $gw->name ); ?></span>
					</label>
				<?php endforeach; ?>
 			</div>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}

function esk_shortcode_bright_students( $atts ): string {
	$atts   = shortcode_atts( array( 'count' => 8 ), $atts, 'eskoofy_bright_students' );
	$count  = absint( $atts['count'] );
	$count  = $count < 1 ? 8 : $count;

	global $wpdb;
	$students = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT s.*, u.display_name, u.user_email, c.name AS class_name
			FROM {$wpdb->prefix}esk_students s
			JOIN {$wpdb->prefix}users u ON s.user_id = u.ID
			LEFT JOIN {$wpdb->prefix}esk_classes c ON s.class_id = c.id
			WHERE s.is_notable = 1 AND s.deleted_at IS NULL
			ORDER BY s.id DESC LIMIT %d",
			$count
		)
	);

	ob_start();
	?>
	<div class="esk-bright-students">
		<h3><?php esc_html_e( 'Bright Students', 'eskoofy' ); ?></h3>
		<?php if ( empty( $students ) ) : ?>
			<p><?php esc_html_e( 'No notable students to display yet.', 'eskoofy' ); ?></p>
		<?php else : ?>
			<div class="esk-student-grid">
				<?php foreach ( $students as $student ) : ?>
					<div class="esk-student-card">
						<div class="esk-student-avatar">
							<?php
							$name = $student->display_name;
							$initials = implode( '', array_map( fn( $w ) => strtoupper( substr( $w, 0, 1 ) ), explode( ' ', $name ) ) );
							echo esc_html( $initials );
							?>
						</div>
						<h4><?php echo esc_html( $name ); ?></h4>
						<?php if ( $student->class_name ) : ?>
							<p class="esk-student-class"><?php echo esc_html( $student->class_name ); ?></p>
						<?php endif; ?>
						<?php if ( $student->achievement ) : ?>
							<p class="esk-student-achievement"><?php echo esc_html( $student->achievement ); ?></p>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}

function esk_shortcode_login_form( $atts ): string {
	$atts = shortcode_atts( array(), $atts );

	if ( is_user_logged_in() ) {
		$current_user = wp_get_current_user();
		ob_start();
		?>
		<div class="esk-login-form">
			<p><?php echo esc_html( sprintf( __( 'Welcome, %s!', 'eskoofy' ), $current_user->display_name ) ); ?></p>
			<p><a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" class="esk-button"><?php esc_html_e( 'Log Out', 'eskoofy' ); ?></a></p>
		</div>
		<?php
		return ob_get_clean();
	}

	ob_start();
	?>
	<div class="esk-login-form">
		<h3><?php esc_html_e( 'Sign In', 'eskoofy' ); ?></h3>
		<form method="post" action="<?php echo esc_url( home_url( '/login/' ) ); ?>" class="esk-form">
			<?php esk_csrf_field( 'esk_login_form' ); ?>
			<div class="esk-form-group">
				<label for="esk-login-user"><?php esc_html_e( 'Username or Email', 'eskoofy' ); ?></label>
				<input type="text" id="esk-login-user" name="esk_login" class="esk-input" required>
			</div>
			<div class="esk-form-group">
				<label for="esk-login-pass"><?php esc_html_e( 'Password', 'eskoofy' ); ?></label>
				<input type="password" id="esk-login-pass" name="esk_password" class="esk-input" required>
			</div>
			<div class="esk-form-group">
				<label>
					<input type="checkbox" name="rememberme" value="forever">
					<?php esc_html_e( 'Remember Me', 'eskoofy' ); ?>
				</label>
			</div>
			<button type="submit" name="esk_login_submit" class="esk-button esk-button-primary"><?php esc_html_e( 'Sign In', 'eskoofy' ); ?></button>
		</form>
		<p><a href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php esc_html_e( 'Forgot password?', 'eskoofy' ); ?></a></p>
	</div>
	<?php
	return ob_get_clean();
}
