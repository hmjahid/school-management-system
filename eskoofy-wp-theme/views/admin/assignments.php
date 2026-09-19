<?php
/**
 * Assignments CRUD.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

if ( isset( $_POST['esk_assignment_save'] ) ) {
	check_admin_referer( 'esk_assignment_form' );
	$title        = sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) );
	$description  = wp_kses_post( wp_unslash( $_POST['description'] ?? '' ) );
	$batch_id     = absint( $_POST['batch_id'] ?? 0 );
	$subject_id   = absint( $_POST['subject_id'] ?? 0 );
	$due_date     = sanitize_text_field( $_POST['due_date'] ?? '' );
	$total_marks  = absint( $_POST['total_marks'] ?? 100 );

	if ( $title && $batch_id && $subject_id ) {
		$wpdb->insert( $wpdb->prefix . 'esk_assignments', array(
			'title'        => $title,
			'description'  => $description,
			'batch_id'     => $batch_id,
			'subject_id'   => $subject_id,
			'due_date'     => $due_date,
			'total_marks'  => $total_marks,
			'created_by'   => get_current_user_id(),
		) );
		esk_flash( 'success', __( 'Assignment saved.', 'eskoofy' ) );
	}
	wp_safe_redirect( admin_url( 'admin.php?page=esk-assignments' ) );
	exit;
}

if ( isset( $_POST['esk_assignment_update'] ) ) {
	check_admin_referer( 'esk_assignment_form' );
	$assignment_id = absint( $_POST['assignment_id'] ?? 0 );
	if ( $assignment_id ) {
		$wpdb->update( $wpdb->prefix . 'esk_assignments', array(
			'title'       => sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) ),
			'description' => wp_kses_post( wp_unslash( $_POST['description'] ?? '' ) ),
			'batch_id'    => absint( $_POST['batch_id'] ?? 0 ),
			'subject_id'  => absint( $_POST['subject_id'] ?? 0 ),
			'due_date'    => sanitize_text_field( $_POST['due_date'] ?? '' ),
			'total_marks' => absint( $_POST['total_marks'] ?? 100 ),
		), array( 'id' => $assignment_id ) );
	}
	esk_flash( 'success', __( 'Assignment updated.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-assignments' ) );
	exit;
}

if ( isset( $_GET['action'] ) && 'delete' === $_GET['action'] && ! empty( $_GET['id'] ) ) {
	$id = absint( $_GET['id'] );
	check_admin_referer( 'esk_assign_delete_' . $id );
	$wpdb->update( $wpdb->prefix . 'esk_assignments', array( 'deleted_at' => current_time( 'mysql' ) ), array( 'id' => $id ) );
	esk_flash( 'success', __( 'Assignment deleted.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-assignments' ) );
	exit;
}

if ( isset( $_POST['esk_assignment_grade'] ) ) {
	check_admin_referer( 'esk_assignment_grade_form' );
	$submission_id = absint( $_POST['submission_id'] ?? 0 );
	$marks         = isset( $_POST['marks'] ) ? absint( $_POST['marks'] ) : null;
	$feedback      = sanitize_textarea_field( $_POST['feedback'] ?? '' );
	if ( $submission_id ) {
		$wpdb->update( $wpdb->prefix . 'esk_assignment_submissions', array(
			'marks'     => $marks,
			'feedback'  => $feedback,
			'graded_by' => get_current_user_id(),
			'graded_at' => current_time( 'mysql' ),
			'status'    => 'graded',
		), array( 'id' => $submission_id ) );
	}
	esk_flash( 'success', __( 'Submission graded.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-assignments' ) . ( ! empty( $_POST['assignment_id'] ) ? '&assignment=' . absint( $_POST['assignment_id'] ) : '' ) );
	exit;
}

$rows = $wpdb->get_results(
	"SELECT a.*, s.name AS subject_name, b.name AS batch_name
	FROM {$wpdb->prefix}esk_assignments a
	LEFT JOIN {$wpdb->prefix}esk_subjects s ON a.subject_id = s.id
	LEFT JOIN {$wpdb->prefix}esk_batches b ON a.batch_id = b.id
	WHERE a.deleted_at IS NULL ORDER BY a.due_date DESC"
);

$batches  = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}esk_batches ORDER BY name" );
$subjects = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}esk_subjects ORDER BY name" );
$flash    = esk_get_flash( 'success' );

$view_assignment  = null;
$submissions      = array();
$edit_assignment  = null;
if ( isset( $_GET['assignment'] ) ) {
	$view_assignment = $wpdb->get_row( $wpdb->prepare( "SELECT a.*, s.name AS subject_name, b.name AS batch_name FROM {$wpdb->prefix}esk_assignments a LEFT JOIN {$wpdb->prefix}esk_subjects s ON a.subject_id = s.id LEFT JOIN {$wpdb->prefix}esk_batches b ON a.batch_id = b.id WHERE a.id = %d", absint( $_GET['assignment'] ) ) );
	if ( $view_assignment ) {
		$submissions = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT sub.*, u.display_name AS student_name
				FROM {$wpdb->prefix}esk_assignment_submissions sub
				LEFT JOIN {$wpdb->prefix}esk_students st ON sub.student_id = st.id
				LEFT JOIN {$wpdb->prefix}users u ON st.user_id = u.ID
				WHERE sub.assignment_id = %d ORDER BY sub.submitted_at ASC",
				$view_assignment->id
			)
		);
	}
}
if ( isset( $_GET['edit_assignment'] ) ) {
	$edit_assignment = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}esk_assignments WHERE id = %d", absint( $_GET['edit_assignment'] ) ) );
}
?>
<div class="wrap esk-admin-wrap">
	<h1><?php esc_html_e( 'Assignments', 'eskoofy' ); ?></h1>

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<div class="esk-card esk-form-card" style="margin-bottom:1.5rem;">
		<h2><?php echo $edit_assignment ? esc_html__( 'Edit Assignment', 'eskoofy' ) : esc_html__( 'New Assignment', 'eskoofy' ); ?></h2>
		<form method="post" class="esk-form">
			<?php wp_nonce_field( 'esk_assignment_form' ); ?>
			<?php if ( $edit_assignment ) : ?>
				<input type="hidden" name="assignment_id" value="<?php echo esc_attr( $edit_assignment->id ); ?>">
			<?php endif; ?>
			<div class="esk-form-row">
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Title', 'eskoofy' ); ?> *</label>
					<input type="text" name="title" required value="<?php echo esc_attr( $edit_assignment->title ?? '' ); ?>">
				</div>
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Batch', 'eskoofy' ); ?></label>
					<select name="batch_id" required>
						<option value=""><?php esc_html_e( 'Select', 'eskoofy' ); ?></option>
						<?php foreach ( $batches as $b ) : ?>
							<option value="<?php echo esc_attr( $b->id ); ?>" <?php selected( $edit_assignment->batch_id ?? 0, $b->id ); ?>><?php echo esc_html( $b->name ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Subject', 'eskoofy' ); ?></label>
					<select name="subject_id" required>
						<option value=""><?php esc_html_e( 'Select', 'eskoofy' ); ?></option>
						<?php foreach ( $subjects as $s ) : ?>
							<option value="<?php echo esc_attr( $s->id ); ?>" <?php selected( $edit_assignment->subject_id ?? 0, $s->id ); ?>><?php echo esc_html( $s->name ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
			<div class="esk-form-row">
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Due Date', 'eskoofy' ); ?></label>
					<input type="datetime-local" name="due_date" value="<?php echo esc_attr( $edit_assignment->due_date ?? '' ); ?>">
				</div>
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Total Marks', 'eskoofy' ); ?></label>
					<input type="number" name="total_marks" value="<?php echo esc_attr( $edit_assignment->total_marks ?? 100 ); ?>" min="0">
				</div>
			</div>
			<div class="esk-form-group">
				<label><?php esc_html_e( 'Description', 'eskoofy' ); ?></label>
				<textarea name="description" rows="4" class="large-text"><?php echo esc_textarea( $edit_assignment->description ?? '' ); ?></textarea>
			</div>
			<button type="submit" name="<?php echo $edit_assignment ? 'esk_assignment_update' : 'esk_assignment_save'; ?>" class="button button-primary"><?php echo $edit_assignment ? esc_html__( 'Update', 'eskoofy' ) : esc_html__( 'Save', 'eskoofy' ); ?></button>
			<?php if ( $edit_assignment ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=esk-assignments' ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'eskoofy' ); ?></a>
			<?php endif; ?>
		</form>
	</div>

	<?php if ( $view_assignment ) : ?>
		<div class="esk-card esk-form-card" style="margin-bottom:1.5rem;">
			<h2><?php esc_html_e( 'Submissions', 'eskoofy' ); ?>: <?php echo esc_html( $view_assignment->title ); ?></h2>
			<p style="margin:0 0 1rem;color:#64748b;"><?php echo esc_html( $view_assignment->subject_name . ' · ' . $view_assignment->batch_name . ' · ' . esc_html__( 'Total marks:', 'eskoofy' ) . ' ' . (int) $view_assignment->total_marks ); ?></p>
			<table class="wp-list-table widefat striped esk-table">
				<thead><tr>
					<th><?php esc_html_e( 'Student', 'eskoofy' ); ?></th>
					<th><?php esc_html_e( 'Submitted', 'eskoofy' ); ?></th>
					<th><?php esc_html_e( 'Marks', 'eskoofy' ); ?></th>
					<th><?php esc_html_e( 'Status', 'eskoofy' ); ?></th>
					<th><?php esc_html_e( 'Grade', 'eskoofy' ); ?></th>
				</tr></thead>
				<tbody>
					<?php if ( empty( $submissions ) ) : ?>
						<tr><td colspan="5"><?php esc_html_e( 'No submissions yet.', 'eskoofy' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $submissions as $sub ) : ?>
							<tr>
								<td><strong><?php echo esc_html( $sub->student_name ); ?></strong></td>
								<td><?php echo esc_html( $sub->submitted_at ? esk_date_format( $sub->submitted_at, 'M j, Y g:i A' ) : '—' ); ?></td>
								<td><?php echo null !== $sub->marks ? esc_html( $sub->marks . '/' . (int) $view_assignment->total_marks ) : '—'; ?></td>
								<td><span class="esk-badge esk-badge-<?php echo esc_attr( $sub->status ); ?>"><?php echo esc_html( ucfirst( $sub->status ) ); ?></span></td>
								<td>
									<form method="post" style="display:inline;">
										<?php wp_nonce_field( 'esk_assignment_grade_form' ); ?>
										<input type="hidden" name="assignment_id" value="<?php echo esc_attr( $view_assignment->id ); ?>">
										<input type="hidden" name="submission_id" value="<?php echo esc_attr( $sub->id ); ?>">
										<input type="number" name="marks" min="0" max="<?php echo esc_attr( (int) $view_assignment->total_marks ); ?>" value="<?php echo esc_attr( $sub->marks ?? '' ); ?>" style="width:80px;" placeholder="<?php esc_attr_e( 'Marks', 'eskoofy' ); ?>">
										<input type="text" name="feedback" value="<?php echo esc_attr( $sub->feedback ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Feedback', 'eskoofy' ); ?>">
										<button type="submit" name="esk_assignment_grade" class="button button-small"><?php esc_html_e( 'Grade', 'eskoofy' ); ?></button>
									</form>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
			<p style="margin-top:1rem;"><a href="<?php echo esc_url( admin_url( 'admin.php?page=esk-assignments' ) ); ?>" class="button"><?php esc_html_e( 'Back to assignments', 'eskoofy' ); ?></a></p>
		</div>
	<?php endif; ?>

	<table class="wp-list-table widefat striped esk-table">
		<thead><tr>
			<th><?php esc_html_e( 'Title', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Batch', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Subject', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Due', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Actions', 'eskoofy' ); ?></th>
		</tr></thead>
		<tbody>
			<?php if ( empty( $rows ) ) : ?>
				<tr><td colspan="5"><?php esc_html_e( 'No assignments.', 'eskoofy' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $rows as $r ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $r->title ); ?></strong></td>
						<td><?php echo esc_html( $r->batch_name ); ?></td>
						<td><?php echo esc_html( $r->subject_name ); ?></td>
						<td><?php echo esc_html( esk_date_format( $r->due_date ) ); ?></td>
						<td>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=esk-assignments&assignment=' . $r->id ) ); ?>" class="button button-small"><?php esc_html_e( 'Submissions', 'eskoofy' ); ?></a>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=esk-assignments&edit_assignment=' . $r->id ) ); ?>" class="button button-small"><?php esc_html_e( 'Edit', 'eskoofy' ); ?></a>
							<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=esk-assignments&action=delete&id=' . $r->id ), 'esk_assign_delete_' . $r->id ) ); ?>" class="button button-small" onclick="return confirm('Delete?');"><?php esc_html_e( 'Delete', 'eskoofy' ); ?></a>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>