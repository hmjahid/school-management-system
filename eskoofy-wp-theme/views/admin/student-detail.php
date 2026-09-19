<?php
/**
 * Student detail view with tabs.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

$student_id = absint( $_GET['id'] ?? 0 );
if ( ! $student_id ) {
	echo '<div class="wrap"><p>' . esc_html__( 'No student specified.', 'eskoofy' ) . '</p></div>';
	return;
}

$student = $wpdb->get_row(
	$wpdb->prepare(
		"SELECT s.*, u.display_name, u.user_email
		FROM {$wpdb->prefix}esk_students s
		JOIN {$wpdb->prefix}users u ON s.user_id = u.ID
		WHERE s.id = %d",
		$student_id
	)
);

if ( ! $student ) {
	echo '<div class="wrap"><p>' . esc_html__( 'Student not found.', 'eskoofy' ) . '</p></div>';
	return;
}

$class_name  = $wpdb->get_var( $wpdb->prepare( "SELECT name FROM {$wpdb->prefix}esk_classes WHERE id = %d", $student->class_id ) );
$section_name = $student->section_id ? $wpdb->get_var( $wpdb->prepare( "SELECT name FROM {$wpdb->prefix}esk_sections WHERE id = %d", $student->section_id ) ) : '';

$tab      = sanitize_text_field( $_GET['tab'] ?? 'profile' );
$tabs     = array( 'profile', 'attendance', 'results', 'fees' );
?>
<div class="wrap esk-admin-wrap">
	<h1><?php echo esc_html( $student->display_name ); ?></h1>

	<nav class="nav-tab-wrapper esk-tabs">
		<?php foreach ( $tabs as $t ) : ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=esk-students&tab=' . $t . '&id=' . $student_id ) ); ?>"
				class="nav-tab <?php echo $tab === $t ? 'nav-tab-active' : ''; ?>">
				<?php echo esc_html( ucfirst( $t ) ); ?>
			</a>
		<?php endforeach; ?>
	</nav>

	<?php if ( 'profile' === $tab ) : ?>
		<div class="esk-card esk-tab-content">
			<table class="esk-table">
				<tr><th><?php esc_html_e( 'Admission No', 'eskoofy' ); ?></th><td><?php echo esc_html( $student->admission_number ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Class', 'eskoofy' ); ?></th><td><?php echo esc_html( $class_name ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Section', 'eskoofy' ); ?></th><td><?php echo esc_html( $section_name ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Roll Number', 'eskoofy' ); ?></th><td><?php echo esc_html( $student->roll_number ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Phone', 'eskoofy' ); ?></th><td><?php echo esc_html( $student->phone_1 ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Email', 'eskoofy' ); ?></th><td><?php echo esc_html( $student->user_email ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Blood Group', 'eskoofy' ); ?></th><td><?php echo esc_html( $student->blood_group ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Religion', 'eskoofy' ); ?></th><td><?php echo esc_html( $student->religion ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Status', 'eskoofy' ); ?></th><td><span class="esk-badge esk-badge-<?php echo esc_attr( $student->status ); ?>"><?php echo esc_html( ucfirst( $student->status ) ); ?></span></td></tr>
				<tr><th><?php esc_html_e( 'Address', 'eskoofy' ); ?></th><td><?php echo esc_html( $student->present_address ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Parent Name', 'eskoofy' ); ?></th><td><?php echo esc_html( $student->parent_name ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Parent Phone', 'eskoofy' ); ?></th><td><?php echo esc_html( $student->parent_phone ); ?></td></tr>
			</table>
		</div>

	<?php elseif ( 'attendance' === $tab ) :
		$attendance = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}esk_attendances WHERE student_id = %d ORDER BY date DESC LIMIT 50",
				$student_id
			)
		);
	?>
		<div class="esk-card esk-tab-content">
			<table class="wp-list-table widefat striped esk-table">
				<thead><tr><th><?php esc_html_e( 'Date', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Status', 'eskoofy' ); ?></th></tr></thead>
				<tbody>
					<?php if ( empty( $attendance ) ) : ?>
						<tr><td colspan="2"><?php esc_html_e( 'No attendance records.', 'eskoofy' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $attendance as $a ) : ?>
							<tr>
								<td><?php echo esc_html( esk_date_format( $a->date ) ); ?></td>
								<td><span class="esk-badge esk-badge-<?php echo esc_attr( $a->status ); ?>"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $a->status ) ) ); ?></span></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>

	<?php elseif ( 'results' === $tab ) :
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT er.*, e.name AS exam_name, e.total_marks, e.passing_marks
				FROM {$wpdb->prefix}esk_exam_results er
				JOIN {$wpdb->prefix}esk_exams e ON er.exam_id = e.id
				WHERE er.student_id = %d AND er.deleted_at IS NULL
				ORDER BY e.start_date DESC",
				$student_id
			)
		);
	?>
		<div class="esk-card esk-tab-content">
			<table class="wp-list-table widefat striped esk-table">
				<thead><tr><th><?php esc_html_e( 'Exam', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Obtained', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Total', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Status', 'eskoofy' ); ?></th></tr></thead>
				<tbody>
					<?php if ( empty( $results ) ) : ?>
						<tr><td colspan="4"><?php esc_html_e( 'No results yet.', 'eskoofy' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $results as $r ) : ?>
							<tr>
								<td><?php echo esc_html( $r->exam_name ); ?></td>
								<td><?php echo esc_html( $r->obtained_marks ); ?></td>
								<td><?php echo esc_html( $r->total_marks ); ?></td>
								<td><span class="esk-badge esk-badge-<?php echo esc_attr( $r->status ); ?>"><?php echo esc_html( ucfirst( $r->status ) ); ?></span></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>

	<?php elseif ( 'fees' === $tab ) :
		$fees = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT fp.*, f.name AS fee_name
				FROM {$wpdb->prefix}esk_fee_payments fp
				JOIN {$wpdb->prefix}esk_fees f ON fp.fee_id = f.id
				WHERE fp.student_id = %d AND fp.deleted_at IS NULL
				ORDER BY fp.payment_date DESC",
				$student_id
			)
		);
	?>
		<div class="esk-card esk-tab-content">
			<table class="wp-list-table widefat striped esk-table">
				<thead><tr><th><?php esc_html_e( 'Invoice', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Fee', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Amount', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Paid', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Status', 'eskoofy' ); ?></th></tr></thead>
				<tbody>
					<?php if ( empty( $fees ) ) : ?>
						<tr><td colspan="5"><?php esc_html_e( 'No payment records.', 'eskoofy' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $fees as $fp ) : ?>
							<tr>
								<td><?php echo esc_html( $fp->invoice_number ); ?></td>
								<td><?php echo esc_html( $fp->fee_name ); ?></td>
								<td><?php echo esc_html( esk_format_currency( $fp->amount ) ); ?></td>
								<td><?php echo esc_html( esk_format_currency( $fp->paid_amount ) ); ?></td>
								<td><span class="esk-badge esk-badge-<?php echo esc_attr( $fp->status ); ?>"><?php echo esc_html( ucfirst( $fp->status ) ); ?></span></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</div>
