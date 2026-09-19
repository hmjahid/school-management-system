<?php
/**
 * Events management (esk_events table).
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

if ( isset( $_POST['esk_event_save'] ) ) {
	check_admin_referer( 'esk_event_form' );
	$wpdb->insert( $wpdb->prefix . 'esk_events', array(
		'created_by'  => get_current_user_id(),
		'title'       => sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) ),
		'description' => sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) ),
		'location'    => sanitize_text_field( wp_unslash( $_POST['location'] ?? '' ) ),
		'start_date'  => sanitize_text_field( wp_unslash( $_POST['start_date'] ?? '' ) ),
		'status'      => in_array( $_POST['status'] ?? 'draft', array( 'published', 'draft' ), true ) ? sanitize_text_field( $_POST['status'] ) : 'draft',
	) );
	esk_flash( 'success', __( 'Event added.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-events' ) );
	exit;
}

if ( isset( $_POST['esk_event_update'] ) ) {
	check_admin_referer( 'esk_event_form' );
	$event_id = absint( $_POST['event_id'] ?? 0 );
	if ( $event_id ) {
		$wpdb->update( $wpdb->prefix . 'esk_events', array(
			'title'       => sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) ),
			'description' => sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) ),
			'location'    => sanitize_text_field( wp_unslash( $_POST['location'] ?? '' ) ),
			'start_date'  => sanitize_text_field( wp_unslash( $_POST['start_date'] ?? '' ) ),
			'status'      => in_array( $_POST['status'] ?? 'draft', array( 'published', 'draft' ), true ) ? sanitize_text_field( $_POST['status'] ) : 'draft',
		), array( 'id' => $event_id ) );
	}
	esk_flash( 'success', __( 'Event updated.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-events' ) );
	exit;
}

if ( isset( $_POST['esk_event_delete'] ) ) {
	check_admin_referer( 'esk_event_delete_' . absint( $_POST['event_id'] ?? 0 ) );
	$wpdb->delete( $wpdb->prefix . 'esk_events', array( 'id' => absint( $_POST['event_id'] ?? 0 ) ) );
	esk_flash( 'success', __( 'Event removed.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-events' ) );
	exit;
}

$events = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}esk_events ORDER BY start_date DESC" );
$flash  = esk_get_flash( 'success' );

$edit_event = null;
if ( isset( $_GET['edit_event'] ) ) {
	$edit_event = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}esk_events WHERE id = %d", absint( $_GET['edit_event'] ) ) );
}
?>
<div class="wrap esk-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Events', 'eskoofy' ); ?></h1>
	<hr class="wp-header-end">

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<div class="esk-card esk-form-card" style="margin-bottom:1.5rem;">
		<h2><?php echo $edit_event ? esc_html__( 'Edit Event', 'eskoofy' ) : esc_html__( 'Add Event', 'eskoofy' ); ?></h2>
		<form method="post" class="esk-form">
			<?php wp_nonce_field( 'esk_event_form' ); ?>
			<?php if ( $edit_event ) : ?>
				<input type="hidden" name="event_id" value="<?php echo esc_attr( $edit_event->id ); ?>">
			<?php endif; ?>
			<div class="esk-form-row">
				<div class="esk-form-group"><label><?php esc_html_e( 'Title', 'eskoofy' ); ?> *</label><input type="text" name="title" required value="<?php echo esc_attr( $edit_event->title ?? '' ); ?>"></div>
				<div class="esk-form-group"><label><?php esc_html_e( 'Start date', 'eskoofy' ); ?> *</label><input type="datetime-local" name="start_date" required value="<?php echo esc_attr( $edit_event->start_date ?? '' ); ?>"></div>
			</div>
			<div class="esk-form-row">
				<div class="esk-form-group"><label><?php esc_html_e( 'Location', 'eskoofy' ); ?></label><input type="text" name="location" class="regular-text" value="<?php echo esc_attr( $edit_event->location ?? '' ); ?>"></div>
				<div class="esk-form-group"><label><?php esc_html_e( 'Status', 'eskoofy' ); ?></label>
					<select name="status">
						<option value="published" <?php selected( $edit_event->status ?? '', 'published' ); ?>><?php esc_html_e( 'Published', 'eskoofy' ); ?></option>
						<option value="draft" <?php selected( $edit_event->status ?? '', 'draft' ); ?>><?php esc_html_e( 'Draft', 'eskoofy' ); ?></option>
					</select>
				</div>
			</div>
			<div class="esk-form-group"><label><?php esc_html_e( 'Description', 'eskoofy' ); ?></label><textarea name="description" rows="3" class="large-text"><?php echo esc_textarea( $edit_event->description ?? '' ); ?></textarea></div>
			<button type="submit" name="<?php echo $edit_event ? 'esk_event_update' : 'esk_event_save'; ?>" class="button button-primary"><?php echo $edit_event ? esc_html__( 'Update Event', 'eskoofy' ) : esc_html__( 'Add Event', 'eskoofy' ); ?></button>
			<?php if ( $edit_event ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=esk-events' ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'eskoofy' ); ?></a>
			<?php endif; ?>
		</form>
	</div>

	<table class="wp-list-table widefat striped esk-table">
		<thead><tr>
			<th><?php esc_html_e( 'Title', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Start', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Location', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Status', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Actions', 'eskoofy' ); ?></th>
		</tr></thead>
		<tbody>
			<?php if ( empty( $events ) ) : ?>
				<tr><td colspan="5"><?php esc_html_e( 'No events.', 'eskoofy' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $events as $e ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $e->title ); ?></strong></td>
						<td><?php echo esc_html( $e->start_date ? esk_date_format( $e->start_date, 'M j, Y g:i A' ) : '—' ); ?></td>
						<td><?php echo esc_html( $e->location ); ?></td>
						<td><span class="esk-badge esk-badge-<?php echo esc_attr( $e->status ); ?>"><?php echo esc_html( ucfirst( $e->status ) ); ?></span></td>
						<td>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=esk-events&edit_event=' . $e->id ) ); ?>" class="button button-small"><?php esc_html_e( 'Edit', 'eskoofy' ); ?></a>
							<form method="post" style="display:inline;" onsubmit="return confirm('<?php esc_attr_e( 'Delete this event?', 'eskoofy' ); ?>');">
								<?php wp_nonce_field( 'esk_event_delete_' . $e->id ); ?>
								<input type="hidden" name="event_id" value="<?php echo esc_attr( $e->id ); ?>">
								<button type="submit" name="esk_event_delete" class="button button-small"><?php esc_html_e( 'Delete', 'eskoofy' ); ?></button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>