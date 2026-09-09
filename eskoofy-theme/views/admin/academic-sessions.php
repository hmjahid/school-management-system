<?php
/**
 * Academic Sessions management.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

if ( isset( $_POST['esk_session_save'] ) ) {
	check_admin_referer( 'esk_session_form' );
	$name       = sanitize_text_field( $_POST['name'] ?? '' );
	$code       = sanitize_text_field( $_POST['code'] ?? '' );
	$start_date = sanitize_text_field( $_POST['start_date'] ?? '' );
	$end_date   = sanitize_text_field( $_POST['end_date'] ?? '' );
	$is_current = isset( $_POST['is_current'] ) ? 1 : 0;

	if ( $is_current ) {
		$wpdb->update( $wpdb->prefix . 'esk_academic_sessions', array( 'is_current' => 0 ), array( 'is_current' => 1 ) );
	}

	$wpdb->insert( $wpdb->prefix . 'esk_academic_sessions', array(
		'name'       => $name,
		'code'       => $code,
		'start_date' => $start_date,
		'end_date'   => $end_date,
		'is_current' => $is_current,
		'is_active'  => 1,
		'status'     => 'upcoming',
	) );
	esk_flash( 'success', __( 'Academic session added.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-academic-sessions' ) );
	exit;
}

if ( isset( $_POST['esk_session_delete'] ) ) {
	check_admin_referer( 'esk_session_delete_' . absint( $_POST['session_id'] ?? 0 ) );
	$wpdb->update(
		$wpdb->prefix . 'esk_academic_sessions',
		array( 'deleted_at' => current_time( 'mysql' ) ),
		array( 'id' => absint( $_POST['session_id'] ?? 0 ) )
	);
	esk_flash( 'success', __( 'Session deleted.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-academic-sessions' ) );
	exit;
}

$sessions = $wpdb->get_results(
	"SELECT * FROM {$wpdb->prefix}esk_academic_sessions WHERE deleted_at IS NULL ORDER BY start_date DESC"
);
$flash = esk_get_flash( 'success' );
?>
<div class="wrap esk-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Academic Sessions', 'eskoofy' ); ?></h1>
	<hr class="wp-header-end">

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<div class="esk-card esk-form-card" style="margin-bottom:1.5rem;">
		<h2><?php esc_html_e( 'Add Academic Session', 'eskoofy' ); ?></h2>
		<form method="post" class="esk-form">
			<?php wp_nonce_field( 'esk_session_form' ); ?>
			<div class="esk-form-row">
				<div class="esk-form-group"><label><?php esc_html_e( 'Name', 'eskoofy' ); ?> *</label><input type="text" name="name" class="regular-text" required></div>
				<div class="esk-form-group"><label><?php esc_html_e( 'Code', 'eskoofy' ); ?> *</label><input type="text" name="code" required></div>
			</div>
			<div class="esk-form-row">
				<div class="esk-form-group"><label><?php esc_html_e( 'Start Date', 'eskoofy' ); ?> *</label><input type="date" name="start_date" required></div>
				<div class="esk-form-group"><label><?php esc_html_e( 'End Date', 'eskoofy' ); ?> *</label><input type="date" name="end_date" required></div>
				<div class="esk-form-group"><label><input type="checkbox" name="is_current" value="1"> <?php esc_html_e( 'Set as current session', 'eskoofy' ); ?></label></div>
			</div>
			<button type="submit" name="esk_session_save" class="button button-primary"><?php esc_html_e( 'Add Session', 'eskoofy' ); ?></button>
		</form>
	</div>

	<table class="wp-list-table widefat striped esk-table">
		<thead><tr>
			<th><?php esc_html_e( 'Name', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Code', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Start', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'End', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Current', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Actions', 'eskoofy' ); ?></th>
		</tr></thead>
		<tbody>
			<?php if ( empty( $sessions ) ) : ?>
				<tr><td colspan="6"><?php esc_html_e( 'No sessions found.', 'eskoofy' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $sessions as $s ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $s->name ); ?></strong></td>
						<td><?php echo esc_html( $s->code ); ?></td>
						<td><?php echo esc_html( esk_date_format( $s->start_date ) ); ?></td>
						<td><?php echo esc_html( esk_date_format( $s->end_date ) ); ?></td>
						<td><?php echo $s->is_current ? '✓' : '—'; ?></td>
						<td>
							<form method="post" style="display:inline;" onsubmit="return confirm('<?php esc_attr_e( 'Delete this session?', 'eskoofy' ); ?>');">
								<?php wp_nonce_field( 'esk_session_delete_' . $s->id ); ?>
								<input type="hidden" name="session_id" value="<?php echo esc_attr( $s->id ); ?>">
								<button type="submit" name="esk_session_delete" class="button button-small"><?php esc_html_e( 'Delete', 'eskoofy' ); ?></button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
