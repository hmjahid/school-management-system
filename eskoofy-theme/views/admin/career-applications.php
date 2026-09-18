<?php
/**
 * Job Applications — review applications and update status.
 *
 * @package Eskoofy
 */

defined( 'ABSPATH' ) || exit;
global $wpdb;

$statuses = array( 'pending', 'reviewed', 'shortlisted', 'rejected', 'hired' );

if ( isset( $_POST['esk_application_status'] ) ) {
	check_admin_referer( 'esk_application_status_' . absint( $_POST['application_id'] ?? 0 ) );
	$status = sanitize_text_field( $_POST['application_status'] ?? 'pending' );
	if ( in_array( $status, $statuses, true ) ) {
		$wpdb->update(
			$wpdb->prefix . 'esk_job_applications',
			array( 'status' => $status ),
			array( 'id' => absint( $_POST['application_id'] ?? 0 ) )
		);
		esk_flash( 'success', __( 'Application status updated.', 'eskoofy' ) );
	}
	wp_safe_redirect( admin_url( 'admin.php?page=esk-careers-applications' ) );
	exit;
}

$applications = $wpdb->get_results(
	"SELECT ja.*, c.title AS career_title
	FROM {$wpdb->prefix}esk_job_applications ja
	JOIN {$wpdb->prefix}esk_careers c ON ja.career_id = c.id
	ORDER BY ja.id DESC"
);
$flash = esk_get_flash( 'success' );
?>
<div class="wrap esk-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Job Applications', 'eskoofy' ); ?></h1>
	<hr class="wp-header-end">

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<table class="wp-list-table widefat striped esk-table">
		<thead><tr>
			<th><?php esc_html_e( 'Name', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Email', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Job', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Status', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Date', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Actions', 'eskoofy' ); ?></th>
		</tr></thead>
		<tbody>
			<?php if ( empty( $applications ) ) : ?>
				<tr><td colspan="6"><?php esc_html_e( 'No applications.', 'eskoofy' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $applications as $app ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $app->name ); ?></strong></td>
						<td><?php echo esc_html( $app->email ); ?></td>
						<td><?php echo esc_html( $app->career_title ); ?></td>
						<td><span class="esk-badge esk-badge-<?php echo esc_attr( $app->status ); ?>"><?php echo esc_html( ucfirst( $app->status ) ); ?></span></td>
						<td><?php echo esc_html( esk_date_format( $app->created_at ) ); ?></td>
						<td>
							<form method="post" style="display:inline-flex;gap:0.35rem;align-items:center;">
								<?php wp_nonce_field( 'esk_application_status_' . $app->id ); ?>
								<input type="hidden" name="application_id" value="<?php echo esc_attr( $app->id ); ?>">
								<select name="application_status">
									<?php foreach ( $statuses as $s ) : ?>
										<option value="<?php echo esc_attr( $s ); ?>" <?php selected( $app->status, $s ); ?>><?php echo esc_html( ucfirst( $s ) ); ?></option>
									<?php endforeach; ?>
								</select>
								<button type="submit" name="esk_application_status" class="button button-small"><?php esc_html_e( 'Update', 'eskoofy' ); ?></button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>