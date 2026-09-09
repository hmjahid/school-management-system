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

function esk_shortcode_results_lookup( $atts ): string {
	$atts   = shortcode_atts( array(), $atts );
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
				<?php
				global $wpdb;
				$exams = $wpdb->get_col(
					"SELECT CONCAT(name, ' (', code, ')') FROM {$wpdb->prefix}esk_exams WHERE deleted_at IS NULL ORDER BY start_date DESC"
				);
				?>
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
	ob_start();
	?>
	<div class="esk-admission-form">
		<h3><?php esc_html_e( 'Apply for Admission', 'eskoofy' ); ?></h3>
		<?php if ( isset( $_POST['esk_admission_submit'] ) ) : ?>
			<div class="esk-notice esk-notice-success">
				<?php esc_html_e( 'Your admission application has been submitted successfully. We will contact you soon.', 'eskoofy' ); ?>
			</div>
		<?php else : ?>
			<form method="post" enctype="multipart/form-data" class="esk-form">
				<?php esk_csrf_field( 'esk_admission_form' ); ?>
				<h4><?php esc_html_e( 'Student Information', 'eskoofy' ); ?></h4>
				<div class="esk-form-row">
					<div class="esk-form-group">
						<label><?php esc_html_e( 'First Name', 'eskoofy' ); ?> *</label>
						<input type="text" name="first_name" class="esk-input" required>
					</div>
					<div class="esk-form-group">
						<label><?php esc_html_e( 'Last Name', 'eskoofy' ); ?> *</label>
						<input type="text" name="last_name" class="esk-input" required>
					</div>
				</div>
				<div class="esk-form-row">
					<div class="esk-form-group">
						<label><?php esc_html_e( 'Gender', 'eskoofy' ); ?> *</label>
						<select name="gender" class="esk-select" required>
							<option value="male"><?php esc_html_e( 'Male', 'eskoofy' ); ?></option>
							<option value="female"><?php esc_html_e( 'Female', 'eskoofy' ); ?></option>
							<option value="other"><?php esc_html_e( 'Other', 'eskoofy' ); ?></option>
						</select>
					</div>
					<div class="esk-form-group">
						<label><?php esc_html_e( 'Date of Birth', 'eskoofy' ); ?> *</label>
						<input type="date" name="date_of_birth" class="esk-input" required>
					</div>
				</div>
				<div class="esk-form-row">
					<div class="esk-form-group">
						<label><?php esc_html_e( 'Email', 'eskoofy' ); ?> *</label>
						<input type="email" name="email" class="esk-input" required>
					</div>
					<div class="esk-form-group">
						<label><?php esc_html_e( 'Phone', 'eskoofy' ); ?> *</label>
						<input type="tel" name="phone" class="esk-input" required>
					</div>
				</div>
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Address', 'eskoofy' ); ?> *</label>
					<textarea name="address" class="esk-textarea" required></textarea>
				</div>
				<div class="esk-form-row">
					<div class="esk-form-group">
						<label><?php esc_html_e( 'City', 'eskoofy' ); ?> *</label>
						<input type="text" name="city" class="esk-input" required>
					</div>
					<div class="esk-form-group">
						<label><?php esc_html_e( 'Postal Code', 'eskoofy' ); ?> *</label>
						<input type="text" name="postal_code" class="esk-input" required>
					</div>
				</div>

				<h4><?php esc_html_e( "Parent/Guardian Information", 'eskoofy' ); ?></h4>
				<div class="esk-form-row">
					<div class="esk-form-group">
						<label><?php esc_html_e( "Father's Name", 'eskoofy' ); ?> *</label>
						<input type="text" name="father_name" class="esk-input" required>
					</div>
					<div class="esk-form-group">
						<label><?php esc_html_e( "Father's Phone", 'eskoofy' ); ?> *</label>
						<input type="tel" name="father_phone" class="esk-input" required>
					</div>
				</div>
				<div class="esk-form-row">
					<div class="esk-form-group">
						<label><?php esc_html_e( "Mother's Name", 'eskoofy' ); ?> *</label>
						<input type="text" name="mother_name" class="esk-input" required>
					</div>
					<div class="esk-form-group">
						<label><?php esc_html_e( "Mother's Phone", 'eskoofy' ); ?> *</label>
						<input type="tel" name="mother_phone" class="esk-input" required>
					</div>
				</div>

				<h4><?php esc_html_e( 'Documents', 'eskoofy' ); ?></h4>
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Upload Photo', 'eskoofy' ); ?></label>
					<input type="file" name="photo" class="esk-input" accept="image/*">
				</div>

				<button type="submit" name="esk_admission_submit" class="esk-button esk-button-primary">
					<?php esc_html_e( 'Submit Application', 'eskoofy' ); ?>
				</button>
			</form>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}

function esk_shortcode_fees_payment( $atts ): string {
	$atts   = shortcode_atts( array(), $atts );
	ob_start();
	?>
	<div class="esk-fees-payment">
		<h3><?php esc_html_e( 'Fee Payment', 'eskoofy' ); ?></h3>
		<form method="post" class="esk-form">
			<?php esk_csrf_field( 'esk_fees_payment' ); ?>
			<div class="esk-form-group">
				<label><?php esc_html_e( 'Admission Number', 'eskoofy' ); ?></label>
				<input type="text" name="admission_number" class="esk-input" required>
			</div>
			<div class="esk-form-group">
				<label><?php esc_html_e( 'Fee Type', 'eskoofy' ); ?></label>
				<select name="fee_id" class="esk-select" required>
					<?php
					global $wpdb;
					$fees = $wpdb->get_results( "SELECT id, name, amount FROM {$wpdb->prefix}esk_fees WHERE status = 'active' AND deleted_at IS NULL" );
					foreach ( $fees as $fee ) :
						?>
						<option value="<?php echo esc_attr( $fee->id ); ?>">
							<?php echo esc_html( $fee->name . ' - ' . esk_format_currency( $fee->amount ) ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
			<button type="submit" class="esk-button esk-button-primary"><?php esc_html_e( 'Pay Now', 'eskoofy' ); ?></button>
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
