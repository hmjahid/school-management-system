<?php
/**
 * SMS management — compose, deliver, history and gateway settings.
 *
 * @package Eskoofy
 */

defined( 'ABSPATH' ) || exit;
global $wpdb;

if ( isset( $_POST['esk_sms_settings'] ) ) {
	check_admin_referer( 'esk_sms_settings_form' );

	$settings = array(
		'driver'             => sanitize_key( $_POST['driver'] ?? 'log' ),
		'twilio_account_sid' => sanitize_text_field( $_POST['twilio_account_sid'] ?? '' ),
		'twilio_auth_token'  => sanitize_text_field( $_POST['twilio_auth_token'] ?? '' ),
		'twilio_from'        => sanitize_text_field( $_POST['twilio_from'] ?? '' ),
		'vonage_api_key'     => sanitize_text_field( $_POST['vonage_api_key'] ?? '' ),
		'vonage_api_secret'  => sanitize_text_field( $_POST['vonage_api_secret'] ?? '' ),
		'vonage_from'        => sanitize_text_field( $_POST['vonage_from'] ?? '' ),
	);

	esk_update_sms_options( $settings );
	esk_flash( 'success', __( 'SMS gateway settings saved.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-sms' ) );
	exit;
}

if ( isset( $_POST['esk_sms_send'] ) ) {
	check_admin_referer( 'esk_sms_form' );
	$audience_type = sanitize_text_field( $_POST['audience_type'] ?? 'all' );
	$class_id      = absint( $_POST['school_class_id'] ?? 0 );
	$message       = sanitize_textarea_field( $_POST['message'] ?? '' );
	$sms_name      = sanitize_text_field( $_POST['name'] ?? '' );
	$manual        = isset( $_POST['recipients'] ) ? array_filter( array_map( 'sanitize_text_field', explode( ',', (string) $_POST['recipients'] ) ) ) : array();

	if ( '' === $message ) {
		esk_flash( 'error', __( 'Message is required.', 'eskoofy' ) );
		wp_safe_redirect( admin_url( 'admin.php?page=esk-sms' ) );
		exit;
	}

	$wpdb->insert(
		$wpdb->prefix . 'esk_sms_campaigns',
		array(
			'name'            => $sms_name,
			'audience_type'   => $audience_type,
			'school_class_id' => $class_id ?: null,
			'message'         => $message,
			'status'          => 'draft',
			'created_by'      => get_current_user_id(),
		)
	);

	$campaign_id = (int) $wpdb->insert_id;

	if ( ! $campaign_id ) {
		esk_flash( 'error', __( 'Could not create SMS campaign.', 'eskoofy' ) );
		wp_safe_redirect( admin_url( 'admin.php?page=esk-sms' ) );
		exit;
	}

	if ( ! empty( $manual ) ) {
		foreach ( $manual as $phone ) {
			$phone = (string) preg_replace( '/[^0-9+]/', '', $phone );
			if ( '' === $phone ) {
				continue;
			}
			$wpdb->insert(
				$wpdb->prefix . 'esk_sms_campaign_recipients',
				array(
					'sms_campaign_id' => $campaign_id,
					'phone'           => $phone,
					'user_type'       => 'custom',
					'user_id'         => null,
					'status'          => 'queued',
				)
			);
		}
	} else {
		$phone_query = "SELECT DISTINCT s.phone_1 AS phone, s.id AS user_id
			FROM {$wpdb->prefix}esk_students s
			WHERE s.phone_1 IS NOT NULL AND s.phone_1 != '' AND s.status = 'active' AND s.deleted_at IS NULL";

		if ( 'class' === $audience_type && $class_id ) {
			$phone_query .= $wpdb->prepare( ' AND s.class_id = %d', $class_id );
		}

		$recipients = $wpdb->get_results( $phone_query );

		foreach ( $recipients as $r ) {
			$wpdb->insert(
				$wpdb->prefix . 'esk_sms_campaign_recipients',
				array(
					'sms_campaign_id' => $campaign_id,
					'phone'           => $r->phone,
					'user_type'       => 'student',
					'user_id'         => $r->user_id,
					'status'          => 'queued',
				)
			);
		}
	}

	esk_process_sms_campaign( $campaign_id, $message );

	esk_flash( 'success', __( 'SMS campaign created and delivery started.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-sms' ) );
	exit;
}

if ( isset( $_POST['esk_sms_send_now'] ) ) {
	check_admin_referer( 'esk_sms_form' );
	$campaign_id = absint( $_POST['campaign_id'] ?? 0 );
	$row         = $campaign_id ? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}esk_sms_campaigns WHERE id = %d", $campaign_id ) ) : null;

	if ( $row ) {
		esk_process_sms_campaign( $campaign_id, (string) $row->message );
		esk_flash( 'success', __( 'SMS campaign delivery started.', 'eskoofy' ) );
	} else {
		esk_flash( 'error', __( 'Campaign not found.', 'eskoofy' ) );
	}

	wp_safe_redirect( admin_url( 'admin.php?page=esk-sms' ) );
	exit;
}

$campaigns   = $wpdb->get_results(
	"SELECT c.*, u.display_name AS author_name,
		(SELECT COUNT(*) FROM {$wpdb->prefix}esk_sms_campaign_recipients WHERE sms_campaign_id = c.id) AS recipient_count,
		(SELECT COUNT(*) FROM {$wpdb->prefix}esk_sms_campaign_recipients WHERE sms_campaign_id = c.id AND status = 'sent') AS sent_count
	FROM {$wpdb->prefix}esk_sms_campaigns c
	LEFT JOIN {$wpdb->prefix}users u ON c.created_by = u.ID
	ORDER BY c.id DESC
	LIMIT 50"
);
$all_classes = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}esk_classes ORDER BY name" );
$sms_options = esk_sms_options();
$driver      = esk_sms_driver();
$sms_logs    = $wpdb->get_results(
	"SELECT * FROM {$wpdb->prefix}esk_sms_logs ORDER BY id DESC LIMIT 10"
);
$flash       = esk_get_flash( 'success' );
?>
<div class="wrap esk-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'SMS', 'eskoofy' ); ?></h1>
	<hr class="wp-header-end">

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<div class="esk-card esk-form-card" style="margin-bottom:1.5rem;">
		<h2><?php esc_html_e( 'Delivery Gateway', 'eskoofy' ); ?></h2>
		<form method="post" class="esk-form">
			<?php wp_nonce_field( 'esk_sms_settings_form' ); ?>
			<div class="esk-form-row">
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Driver', 'eskoofy' ); ?></label>
					<select name="driver">
						<option value="log" <?php selected( $driver, 'log' ); ?>><?php esc_html_e( 'Log (preview)', 'eskoofy' ); ?></option>
						<option value="twilio" <?php selected( $driver, 'twilio' ); ?>>Twilio</option>
						<option value="vonage" <?php selected( $driver, 'vonage' ); ?>>Vonage</option>
					</select>
				</div>
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Twilio Account SID', 'eskoofy' ); ?></label>
					<input type="text" name="twilio_account_sid" value="<?php echo esc_attr( $sms_options['twilio_account_sid'] ); ?>">
				</div>
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Twilio Auth Token', 'eskoofy' ); ?></label>
					<input type="password" name="twilio_auth_token" value="<?php echo esc_attr( $sms_options['twilio_auth_token'] ); ?>">
				</div>
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Twilio From Number', 'eskoofy' ); ?></label>
					<input type="text" name="twilio_from" value="<?php echo esc_attr( $sms_options['twilio_from'] ); ?>">
				</div>
			</div>
			<div class="esk-form-row">
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Vonage API Key', 'eskoofy' ); ?></label>
					<input type="text" name="vonage_api_key" value="<?php echo esc_attr( $sms_options['vonage_api_key'] ); ?>">
				</div>
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Vonage API Secret', 'eskoofy' ); ?></label>
					<input type="password" name="vonage_api_secret" value="<?php echo esc_attr( $sms_options['vonage_api_secret'] ); ?>">
				</div>
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Vonage From / Sender', 'eskoofy' ); ?></label>
					<input type="text" name="vonage_from" value="<?php echo esc_attr( $sms_options['vonage_from'] ); ?>">
				</div>
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Active Driver', 'eskoofy' ); ?></label>
					<strong><?php echo esc_html( strtoupper( $driver ) ); ?></strong>
					<p class="description"><?php esc_html_e( 'International delivery via Twilio or Vonage.', 'eskoofy' ); ?></p>
				</div>
			</div>
			<button type="submit" name="esk_sms_settings" class="button button-primary"><?php esc_html_e( 'Save Gateway', 'eskoofy' ); ?></button>
		</form>
	</div>

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
						<option value="manual"><?php esc_html_e( 'Manual Numbers', 'eskoofy' ); ?></option>
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
				<div class="esk-form-group" id="esk-sms-manual-wrap" style="display:none;">
					<label><?php esc_html_e( 'Numbers (comma separated)', 'eskoofy' ); ?></label>
					<input type="text" name="recipients" placeholder="+14155552671, 01700000000">
				</div>
			</div>
			<div class="esk-form-group"><label><?php esc_html_e( 'Message', 'eskoofy' ); ?> *</label><textarea name="message" class="large-text" rows="4" required></textarea></div>
			<button type="submit" name="esk_sms_send" class="button button-primary"><?php esc_html_e( 'Create & Send Campaign', 'eskoofy' ); ?></button>
		</form>
	</div>

	<table class="wp-list-table widefat striped esk-table">
		<thead><tr>
			<th><?php esc_html_e( 'Name', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Audience', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Recipients', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Sent', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Status', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Created By', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Date', 'eskoofy' ); ?></th>
			<th></th>
		</tr></thead>
		<tbody>
			<?php if ( empty( $campaigns ) ) : ?>
				<tr><td colspan="8"><?php esc_html_e( 'No SMS campaigns.', 'eskoofy' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $campaigns as $c ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $c->name ); ?></strong></td>
						<td><?php echo esc_html( ucfirst( $c->audience_type ) ); ?></td>
						<td><?php echo esc_html( $c->recipient_count ); ?></td>
						<td><?php echo esc_html( $c->sent_count ); ?></td>
						<td><span class="esk-badge esk-badge-<?php echo esc_attr( $c->status ); ?>"><?php echo esc_html( ucfirst( $c->status ) ); ?></span></td>
						<td><?php echo esc_html( $c->author_name ); ?></td>
						<td><?php echo esc_html( esk_date_format( $c->created_at ) ); ?></td>
						<td>
							<?php if ( (int) $c->recipient_count > (int) $c->sent_count ) : ?>
								<form method="post" style="display:inline;">
									<?php wp_nonce_field( 'esk_sms_form' ); ?>
									<input type="hidden" name="campaign_id" value="<?php echo esc_attr( $c->id ); ?>">
									<button type="submit" name="esk_sms_send_now" class="button button-small"><?php esc_html_e( 'Send Now', 'eskoofy' ); ?></button>
								</form>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>

	<?php if ( ! empty( $sms_logs ) ) : ?>
		<h2 style="margin-top:2rem;"><?php esc_html_e( 'Recent Delivery Log', 'eskoofy' ); ?></h2>
		<table class="wp-list-table widefat striped esk-table">
			<thead><tr>
				<th><?php esc_html_e( 'Recipient', 'eskoofy' ); ?></th>
				<th><?php esc_html_e( 'Status', 'eskoofy' ); ?></th>
				<th><?php esc_html_e( 'Provider', 'eskoofy' ); ?></th>
				<th><?php esc_html_e( 'Message ID', 'eskoofy' ); ?></th>
				<th><?php esc_html_e( 'Error', 'eskoofy' ); ?></th>
				<th><?php esc_html_e( 'Date', 'eskoofy' ); ?></th>
			</tr></thead>
			<tbody>
				<?php foreach ( $sms_logs as $log ) : ?>
					<tr>
						<td><?php echo esc_html( $log->recipient ); ?></td>
						<td><?php echo esc_html( $log->status ); ?></td>
						<td><?php echo esc_html( $log->provider ); ?></td>
						<td><?php echo esc_html( $log->message_id ?: '—' ); ?></td>
						<td><?php echo esc_html( $log->error ?: '—' ); ?></td>
						<td><?php echo esc_html( esk_date_format( $log->created_at ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>

	<script>
	jQuery('#esk-sms-audience').on('change', function() {
		var v = jQuery(this).val();
		jQuery('#esk-sms-class-wrap').toggle( 'class' === v );
		jQuery('#esk-sms-manual-wrap').toggle( 'manual' === v );
	});
	</script>
</div>