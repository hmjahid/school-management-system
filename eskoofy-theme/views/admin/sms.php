<?php
/**
 * SMS management — compose and history.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

if ( isset( $_POST['esk_sms_send'] ) ) {
	check_admin_referer( 'esk_sms_form' );
	$audience_type = sanitize_text_field( $_POST['audience_type'] ?? 'all' );
	$class_id      = absint( $_POST['school_class_id'] ?? 0 );
	$message       = sanitize_textarea_field( $_POST['message'] ?? '' );
	$sms_name      = sanitize_text_field( $_POST['name'] ?? '' );

	$wpdb->insert( $wpdb->prefix . 'esk_sms_campaigns', array(
		'name'             => $sms_name,
		'audience_type'    => $audience_type,
		'school_class_id'  => $class_id ?: null,
		'message'          => $message,
		'status'           => 'draft',
		'created_by'       => get_current_user_id(),
	) );

	$campaign_id = $wpdb->insert_id;

	if ( $campaign_id ) {
		$phone_query = "SELECT DISTINCT s.phone_1 AS phone, s.id AS user_id
			FROM {$wpdb->prefix}esk_students s
			WHERE s.phone_1 IS NOT NULL AND s.phone_1 != '' AND s.status = 'active' AND s.deleted_at IS NULL";

		if ( 'class' === $audience_type && $class_id ) {
			$phone_query .= $wpdb->prepare( " AND s.class_id = %d", $class_id );
		}

		$recipients = $wpdb->get_results( $phone_query );

		foreach ( $recipients as $r ) {
			$wpdb->insert( $wpdb->prefix . 'esk_sms_campaign_recipients', array(
				'sms_campaign_id' => $campaign_id,
				'phone'           => $r->phone,
				'user_type'       => 'student',
				'user_id'         => $r->user_id,
				'status'          => 'queued',
			) );
		}
	}

	esk_flash( 'success', __( 'SMS campaign created.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-sms' ) );
	exit;
}

$campaigns = $wpdb->get_results(
	"SELECT c.*, u.display_name AS author_name,
		(SELECT COUNT(*) FROM {$wpdb->prefix}esk_sms_campaign_recipients WHERE sms_campaign_id = c.id) AS recipient_count
	FROM {$wpdb->prefix}esk_sms_campaigns c
	LEFT JOIN {$wpdb->prefix}users u ON c.created_by = u.ID
	ORDER BY c.id DESC
	LIMIT 50"
);
$all_classes = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}esk_classes ORDER BY name" );
$flash       = esk_get_flash( 'success' );
?>
<div class="wrap esk-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'SMS', 'eskoofy' ); ?></h1>
	<hr class="wp-header-end">

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<div class="esk-card esk-form-card" style="margin-bottom:1.5rem;">
		<h2><?php esc_html_e( 'Compose SMS', 'eskoofy' ); ?></h2>
		<form method="post" class="esk-form">
			<?php wp_nonce_field( 'esk_sms_form' ); ?>
			<div class="esk-form-row">
				<div class="esk-form-group"><label><?php esc_html_e( 'Campaign Name', 'eskoofy' ); ?></label><input type="text" name="name"></div>
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Recipients', 'eskoofy' ); ?> *</label>
					<select name="audience_type" required id="esk-sms-audience">
						<option value="all"><?php esc_html_e( 'All Students', 'eskoofy' ); ?></option>
						<option value="class"><?php esc_html_e( 'By Class', 'eskoofy' ); ?></option>
					</select>
				</div>
				<div class="esk-form-group" id="esk-sms-class-wrap" style="display:none;">
					<label><?php esc_html_e( 'Class', 'eskoofy' ); ?></label>
					<select name="school_class_id">
						<option value=""><?php esc_html_e( 'Select', 'eskoofy' ); ?></option>
						<?php foreach ( $all_classes as $c ) : ?>
							<option value="<?php echo esc_attr( $c->id ); ?>"><?php echo esc_html( $c->name ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
			<div class="esk-form-group"><label><?php esc_html_e( 'Message', 'eskoofy' ); ?> *</label><textarea name="message" class="large-text" rows="4" required></textarea></div>
			<button type="submit" name="esk_sms_send" class="button button-primary"><?php esc_html_e( 'Create Campaign', 'eskoofy' ); ?></button>
		</form>
	</div>

	<table class="wp-list-table widefat striped esk-table">
		<thead><tr>
			<th><?php esc_html_e( 'Name', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Audience', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Recipients', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Status', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Created By', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Date', 'eskoofy' ); ?></th>
		</tr></thead>
		<tbody>
			<?php if ( empty( $campaigns ) ) : ?>
				<tr><td colspan="6"><?php esc_html_e( 'No SMS campaigns.', 'eskoofy' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $campaigns as $c ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $c->name ); ?></strong></td>
						<td><?php echo esc_html( ucfirst( $c->audience_type ) ); ?></td>
						<td><?php echo esc_html( $c->recipient_count ); ?></td>
						<td><span class="esk-badge esk-badge-<?php echo esc_attr( $c->status ); ?>"><?php echo esc_html( ucfirst( $c->status ) ); ?></span></td>
						<td><?php echo esc_html( $c->author_name ); ?></td>
						<td><?php echo esc_html( esk_date_format( $c->created_at ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>

	<script>
	jQuery('#esk-sms-audience').on('change', function() {
		jQuery('#esk-sms-class-wrap').toggle( 'class' === jQuery(this).val() );
	});
	</script>
</div>
