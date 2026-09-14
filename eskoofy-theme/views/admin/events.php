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

if ( isset( $_POST['esk_event_delete'] ) ) {
	check_admin_referer( 'esk_event_delete_' . absint( $_POST['event_id'] ?? 0 ) );
	$wpdb->delete( $wpdb->prefix . 'esk_events', array( 'id' => absint( $_POST['event_id'] ?? 0 ) ) );
	esk_flash( 'success', __( 'Event removed.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-events' ) );
	exit;
}

$events = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}esk_events ORDER BY start_date DESC" );
$flash  = esk_get_flash( 'success' );
?>
<div class="wrap esk-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Events', 'eskoofy' ); ?></h1>
	<hr class="wp-header-end">

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<div class="esk-card esk-form-card" style="margin-bottom:1.5rem;">
		<h2><?php esc_html_e( 'Add Event', 'eskoofy' ); ?></h2>
		<form method="post" class="esk-form">
			<?php wp_nonce_field( 'esk_event_form' ); ?>
			<div class="esk-form-row">
				<div class="esk-form-group"><label><?php esc_html_e( 'Title', 'eskoofy' ); ?> *</label><input type="text" name="title" required></div>
				<div class="esk-form-group"><label><?php esc_html_e( 'Start date', 'eskoofy' ); ?> *</label><input type="datetime-local" name="start_date" required></div>
			</div>
			<div class="esk-form-row">
				<div class="esk-form-group"><label><?php esc_html_e( 'Location', 'eskoofy' ); ?></label><input type="text" name="location" class="regular-text"></div>
				<div class="esk-form-group"><label><?php esc_html_e( 'Status', 'eskoofy' ); ?></label>
					<select name="status">
						<option value="published"><?php esc_html_e( 'Published', 'eskoofy' ); ?></option>
						<option value="draft"><?php esc_html_e( 'Draft', 'eskoofy' ); ?></option>
					</select>
				</div>
			</div>
			<div class="esk-form-group"><label><?php esc_html_e( 'Description', 'eskoofy' ); ?></label><textarea name="description" rows="3" class="large-text"></textarea></div>
			<button type="submit" name="esk_event_save" class="button button-primary"><?php esc_html_e( 'Add Event', 'eskoofy' ); ?></button>
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