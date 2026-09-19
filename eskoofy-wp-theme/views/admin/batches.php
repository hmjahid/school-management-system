<?php
/**
 * Batches management.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

if ( isset( $_POST['esk_batch_save'] ) ) {
	check_admin_referer( 'esk_batch_form' );
	$wpdb->insert( $wpdb->prefix . 'esk_batches', array(
		'name'                 => sanitize_text_field( $_POST['name'] ?? '' ),
		'code'                 => sanitize_text_field( $_POST['code'] ?? '' ),
		'description'          => sanitize_textarea_field( $_POST['description'] ?? '' ),
		'start_date'           => sanitize_text_field( $_POST['start_date'] ?? '' ),
		'end_date'             => sanitize_text_field( $_POST['end_date'] ?? '' ),
		'academic_session_id'  => absint( $_POST['academic_session_id'] ?? 0 ) ?: null,
		'status'               => sanitize_text_field( $_POST['status'] ?? 'active' ),
		'is_active'            => 1,
	) );
	esk_flash( 'success', __( 'Batch added.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-batches' ) );
	exit;
}

if ( isset( $_POST['esk_batch_delete'] ) ) {
	check_admin_referer( 'esk_batch_delete_' . absint( $_POST['batch_id'] ?? 0 ) );
	$batch_id = absint( $_POST['batch_id'] ?? 0 );
	if ( $batch_id ) {
		$wpdb->update( $wpdb->prefix . 'esk_batches', array( 'deleted_at' => current_time( 'mysql' ) ), array( 'id' => $batch_id ) );
	}
	esk_flash( 'success', __( 'Batch deleted.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-batches' ) );
	exit;
}

$sessions = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}esk_academic_sessions WHERE deleted_at IS NULL ORDER BY name" );
$batches  = $wpdb->get_results(
	"SELECT b.*, a.name AS session_name
	FROM {$wpdb->prefix}esk_batches b
	LEFT JOIN {$wpdb->prefix}esk_academic_sessions a ON b.academic_session_id = a.id
	WHERE b.deleted_at IS NULL
	ORDER BY b.id DESC"
);
$flash = esk_get_flash( 'success' );
?>
<div class="wrap esk-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Batches', 'eskoofy' ); ?></h1>
	<hr class="wp-header-end">

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<div class="esk-card esk-form-card" style="margin-bottom:1.5rem;">
		<h2><?php esc_html_e( 'Add New Batch', 'eskoofy' ); ?></h2>
		<form method="post" class="esk-form">
			<?php wp_nonce_field( 'esk_batch_form' ); ?>
			<div class="esk-form-row">
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Name', 'eskoofy' ); ?> *</label>
					<input type="text" name="name" class="regular-text" required>
				</div>
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Code', 'eskoofy' ); ?></label>
					<input type="text" name="code">
				</div>
			</div>
			<div class="esk-form-row">
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Start Date', 'eskoofy' ); ?></label>
					<input type="date" name="start_date">
				</div>
				<div class="esk-form-group">
					<label><?php esc_html_e( 'End Date', 'eskoofy' ); ?></label>
					<input type="date" name="end_date">
				</div>
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Academic Session', 'eskoofy' ); ?></label>
					<select name="academic_session_id">
						<option value=""><?php esc_html_e( 'None', 'eskoofy' ); ?></option>
						<?php foreach ( $sessions as $sess ) : ?>
							<option value="<?php echo esc_attr( $sess->id ); ?>"><?php echo esc_html( $sess->name ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Status', 'eskoofy' ); ?></label>
					<select name="status">
						<option value="active"><?php esc_html_e( 'Active', 'eskoofy' ); ?></option>
						<option value="inactive"><?php esc_html_e( 'Inactive', 'eskoofy' ); ?></option>
					</select>
				</div>
			</div>
			<div class="esk-form-group">
				<label><?php esc_html_e( 'Description', 'eskoofy' ); ?></label>
				<textarea name="description" class="large-text" rows="2"></textarea>
			</div>
			<button type="submit" name="esk_batch_save" class="button button-primary"><?php esc_html_e( 'Add Batch', 'eskoofy' ); ?></button>
		</form>
	</div>

	<table class="wp-list-table widefat striped esk-table">
		<thead><tr>
			<th><?php esc_html_e( 'Name', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Code', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Session', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Start', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'End', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Status', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Actions', 'eskoofy' ); ?></th>
		</tr></thead>
		<tbody>
			<?php if ( empty( $batches ) ) : ?>
				<tr><td colspan="7"><?php esc_html_e( 'No batches found.', 'eskoofy' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $batches as $b ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $b->name ); ?></strong></td>
						<td><?php echo esc_html( $b->code ); ?></td>
						<td><?php echo esc_html( $b->session_name ?? '—' ); ?></td>
						<td><?php echo esc_html( esk_date_format( $b->start_date ) ); ?></td>
						<td><?php echo esc_html( esk_date_format( $b->end_date ) ); ?></td>
						<td><span class="esk-badge esk-badge-<?php echo esc_attr( $b->status ?? 'active' ); ?>"><?php echo esc_html( ucfirst( $b->status ?? 'active' ) ); ?></span></td>
						<td>
							<form method="post" style="display:inline;" onsubmit="return confirm('<?php esc_attr_e( 'Delete this batch?', 'eskoofy' ); ?>');">
								<?php wp_nonce_field( 'esk_batch_delete_' . $b->id ); ?>
								<input type="hidden" name="batch_id" value="<?php echo esc_attr( $b->id ); ?>">
								<button type="submit" name="esk_batch_delete" class="button button-small"><?php esc_html_e( 'Delete', 'eskoofy' ); ?></button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
