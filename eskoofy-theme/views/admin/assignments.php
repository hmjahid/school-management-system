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

if ( isset( $_GET['action'] ) && 'delete' === $_GET['action'] && ! empty( $_GET['id'] ) ) {
	$id = absint( $_GET['id'] );
	check_admin_referer( 'esk_assign_delete_' . $id );
	$wpdb->update( $wpdb->prefix . 'esk_assignments', array( 'deleted_at' => current_time( 'mysql' ) ), array( 'id' => $id ) );
	esk_flash( 'success', __( 'Assignment deleted.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-assignments' ) );
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
?>
<div class="wrap esk-admin-wrap">
	<h1><?php esc_html_e( 'Assignments', 'eskoofy' ); ?></h1>

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<div class="esk-card esk-form-card" style="margin-bottom:1.5rem;">
		<h2><?php esc_html_e( 'New Assignment', 'eskoofy' ); ?></h2>
		<form method="post" class="esk-form">
			<?php wp_nonce_field( 'esk_assignment_form' ); ?>
			<div class="esk-form-row">
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Title', 'eskoofy' ); ?> *</label>
					<input type="text" name="title" required>
				</div>
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Batch', 'eskoofy' ); ?></label>
					<select name="batch_id" required>
						<option value=""><?php esc_html_e( 'Select', 'eskoofy' ); ?></option>
						<?php foreach ( $batches as $b ) : ?>
							<option value="<?php echo esc_attr( $b->id ); ?>"><?php echo esc_html( $b->name ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Subject', 'eskoofy' ); ?></label>
					<select name="subject_id" required>
						<option value=""><?php esc_html_e( 'Select', 'eskoofy' ); ?></option>
						<?php foreach ( $subjects as $s ) : ?>
							<option value="<?php echo esc_attr( $s->id ); ?>"><?php echo esc_html( $s->name ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
			<div class="esk-form-row">
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Due Date', 'eskoofy' ); ?></label>
					<input type="datetime-local" name="due_date">
				</div>
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Total Marks', 'eskoofy' ); ?></label>
					<input type="number" name="total_marks" value="100" min="0">
				</div>
			</div>
			<div class="esk-form-group">
				<label><?php esc_html_e( 'Description', 'eskoofy' ); ?></label>
				<textarea name="description" rows="4" class="large-text"></textarea>
			</div>
			<button type="submit" name="esk_assignment_save" class="button button-primary"><?php esc_html_e( 'Save', 'eskoofy' ); ?></button>
		</form>
	</div>

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
							<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=esk-assignments&action=delete&id=' . $r->id ), 'esk_assign_delete_' . $r->id ) ); ?>" class="button button-small" onclick="return confirm('Delete?');"><?php esc_html_e( 'Delete', 'eskoofy' ); ?></a>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>