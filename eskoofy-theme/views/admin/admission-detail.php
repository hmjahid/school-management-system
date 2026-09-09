<?php
/**
 * Admission detail / review view.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

$adm_id = absint( $_GET['id'] ?? 0 );
if ( ! $adm_id ) {
	echo '<div class="wrap"><p>' . esc_html__( 'No admission specified.', 'eskoofy' ) . '</p></div>';
	return;
}

$admission = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}esk_admissions WHERE id = %d AND deleted_at IS NULL", $adm_id ) );
if ( ! $admission ) {
	echo '<div class="wrap"><p>' . esc_html__( 'Admission not found.', 'eskoofy' ) . '</p></div>';
	return;
}

if ( isset( $_POST['esk_admission_action'] ) ) {
	check_admin_referer( 'esk_admission_review' );
	$action    = sanitize_text_field( $_POST['esk_admission_action'] );
	$timestamp = current_time( 'mysql' );

	switch ( $action ) {
		case 'approve':
			$wpdb->update( $wpdb->prefix . 'esk_admissions', array(
				'status'      => 'approved',
				'approved_by' => get_current_user_id(),
				'approved_at' => $timestamp,
			), array( 'id' => $adm_id ) );
			break;
		case 'reject':
			$wpdb->update( $wpdb->prefix . 'esk_admissions', array(
				'status'          => 'rejected',
				'rejected_by'     => get_current_user_id(),
				'rejected_at'     => $timestamp,
				'rejection_reason' => sanitize_textarea_field( $_POST['rejection_reason'] ?? '' ),
			), array( 'id' => $adm_id ) );
			break;
		case 'enroll':
			$wpdb->update( $wpdb->prefix . 'esk_admissions', array(
				'status'      => 'enrolled',
				'enrolled_at' => $timestamp,
				'admission_date' => gmdate( 'Y-m-d' ),
			), array( 'id' => $adm_id ) );
			break;
	}
	esk_flash( 'success', __( 'Admission status updated.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-admissions' ) );
	exit;
}

$flash = esk_get_flash( 'success' );
?>
<div class="wrap esk-admin-wrap">
	<h1><?php esc_html_e( 'Admission Review', 'eskoofy' ); ?></h1>
	<?php if ( $flash ) : ?><div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div><?php endif; ?>

	<div class="esk-dashboard-columns">
		<div class="esk-dashboard-column esk-col-wide">
			<div class="esk-card">
				<h2><?php esc_html_e( 'Applicant Details', 'eskoofy' ); ?></h2>
				<table class="esk-table">
					<tr><th><?php esc_html_e( 'Application #', 'eskoofy' ); ?></th><td><?php echo esc_html( $admission->application_number ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Name', 'eskoofy' ); ?></th><td><?php echo esc_html( $admission->first_name . ' ' . $admission->last_name ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Gender', 'eskoofy' ); ?></th><td><?php echo esc_html( ucfirst( $admission->gender ) ); ?></td></tr>
					<tr><th><?php esc_html_e( 'DOB', 'eskoofy' ); ?></th><td><?php echo esc_html( esk_date_format( $admission->date_of_birth ) ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Email', 'eskoofy' ); ?></th><td><?php echo esc_html( $admission->email ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Phone', 'eskoofy' ); ?></th><td><?php echo esc_html( $admission->phone ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Address', 'eskoofy' ); ?></th><td><?php echo esc_html( $admission->address . ', ' . $admission->city ); ?></td></tr>
					<tr><th><?php esc_html_e( "Father's Name", 'eskoofy' ); ?></th><td><?php echo esc_html( $admission->father_name ); ?></td></tr>
					<tr><th><?php esc_html_e( "Mother's Name", 'eskoofy' ); ?></th><td><?php echo esc_html( $admission->mother_name ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Previous School', 'eskoofy' ); ?></th><td><?php echo esc_html( $admission->previous_school ); ?></td></tr>
				</table>
			</div>
		</div>

		<div class="esk-dashboard-column esk-col-narrow">
			<div class="esk-card">
				<h2><?php esc_html_e( 'Actions', 'eskoofy' ); ?></h2>
				<p><span class="esk-badge esk-badge-<?php echo esc_attr( $admission->status ); ?>"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $admission->status ) ) ); ?></span></p>

				<form method="post" class="esk-form">
					<?php wp_nonce_field( 'esk_admission_review' ); ?>

					<?php if ( in_array( $admission->status, array( 'submitted', 'under_review', 'draft' ), true ) ) : ?>
						<button type="submit" name="esk_admission_action" value="approve" class="button button-primary" style="width:100%;margin-bottom:8px;">
							<?php esc_html_e( 'Approve', 'eskoofy' ); ?>
						</button>
						<button type="submit" name="esk_admission_action" value="reject" class="button" style="width:100%;margin-bottom:8px;" onclick="return confirm('<?php esc_attr_e( 'Reject this application?', 'eskoofy' ); ?>')">
							<?php esc_html_e( 'Reject', 'eskoofy' ); ?>
						</button>
						<div class="esk-form-group">
							<label><?php esc_html_e( 'Rejection Reason', 'eskoofy' ); ?></label>
							<textarea name="rejection_reason" class="esk-textarea" rows="3"></textarea>
						</div>
					<?php endif; ?>

					<?php if ( 'approved' === $admission->status ) : ?>
						<button type="submit" name="esk_admission_action" value="enroll" class="button button-primary" style="width:100%;">
							<?php esc_html_e( 'Enroll Student', 'eskoofy' ); ?>
						</button>
					<?php endif; ?>
				</form>
			</div>
		</div>
	</div>
</div>
