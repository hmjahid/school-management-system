<?php
/**
 * Notification templates management.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

$template_types = array( 'admission_approved', 'admission_rejected', 'fee_due', 'fee_receipt', 'exam_result', 'notice', 'event', 'assignment', 'attendance', 'leave', 'password_reset', 'general' );

if ( isset( $_POST['esk_notif_template_save'] ) ) {
	check_admin_referer( 'esk_notif_template_form' );
	$key  = sanitize_text_field( $_POST['key'] ?? '' );
	$name = sanitize_text_field( $_POST['name'] ?? '' );
	if ( $key && $name ) {
		$wpdb->insert( $wpdb->prefix . 'esk_notification_templates', array(
			'name'           => $name,
			'key'            => $key,
			'subject'        => sanitize_text_field( $_POST['subject'] ?? '' ),
			'content'        => wp_kses_post( wp_unslash( $_POST['content'] ?? '' ) ),
			'sms_content'    => sanitize_textarea_field( $_POST['sms_content'] ?? '' ),
			'in_app_content' => sanitize_textarea_field( $_POST['in_app_content'] ?? '' ),
			'variables'      => wp_json_encode( explode( ',', (string) ( $_POST['variables'] ?? '' ) ) ),
			'is_active'      => isset( $_POST['is_active'] ) ? 1 : 0,
		) );
		esk_flash( 'success', __( 'Template created.', 'eskoofy' ) );
	}
	wp_safe_redirect( admin_url( 'admin.php?page=esk-notification-templates' ) );
	exit;
}

if ( isset( $_POST['esk_notif_template_update'] ) ) {
	check_admin_referer( 'esk_notif_template_form' );
	$template_id = absint( $_POST['template_id'] ?? 0 );
	if ( $template_id ) {
		$wpdb->update( $wpdb->prefix . 'esk_notification_templates', array(
			'name'           => sanitize_text_field( $_POST['name'] ?? '' ),
			'key'            => sanitize_text_field( $_POST['key'] ?? '' ),
			'subject'        => sanitize_text_field( $_POST['subject'] ?? '' ),
			'content'        => wp_kses_post( wp_unslash( $_POST['content'] ?? '' ) ),
			'sms_content'    => sanitize_textarea_field( $_POST['sms_content'] ?? '' ),
			'in_app_content' => sanitize_textarea_field( $_POST['in_app_content'] ?? '' ),
			'variables'      => wp_json_encode( explode( ',', (string) ( $_POST['variables'] ?? '' ) ) ),
			'is_active'      => isset( $_POST['is_active'] ) ? 1 : 0,
		), array( 'id' => $template_id ) );
		esk_flash( 'success', __( 'Template updated.', 'eskoofy' ) );
	}
	wp_safe_redirect( admin_url( 'admin.php?page=esk-notification-templates' ) );
	exit;
}

if ( isset( $_POST['esk_notif_template_delete'] ) ) {
	check_admin_referer( 'esk_notif_template_delete_' . absint( $_POST['template_id'] ?? 0 ) );
	$wpdb->delete( $wpdb->prefix . 'esk_notification_templates', array( 'id' => absint( $_POST['template_id'] ?? 0 ) ) );
	esk_flash( 'success', __( 'Template deleted.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-notification-templates' ) );
	exit;
}

$templates = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}esk_notification_templates ORDER BY `key`" );
$flash     = esk_get_flash( 'success' );

$edit_template = null;
if ( isset( $_GET['edit_template'] ) ) {
	$edit_template = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}esk_notification_templates WHERE id = %d", absint( $_GET['edit_template'] ) ) );
}
?>
<div class="wrap esk-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Notification Templates', 'eskoofy' ); ?></h1>
	<hr class="wp-header-end">

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<div class="esk-card esk-form-card" style="margin-bottom:1.5rem;">
		<h2><?php echo $edit_template ? esc_html__( 'Edit Template', 'eskoofy' ) : esc_html__( 'Add Template', 'eskoofy' ); ?></h2>
		<form method="post" class="esk-form">
			<?php wp_nonce_field( 'esk_notif_template_form' ); ?>
			<?php if ( $edit_template ) : ?>
				<input type="hidden" name="template_id" value="<?php echo esc_attr( $edit_template->id ); ?>">
			<?php endif; ?>
			<div class="esk-form-row">
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Key', 'eskoofy' ); ?> *</label>
					<select name="key" required <?php echo $edit_template ? 'disabled' : ''; ?>>
						<?php foreach ( $template_types as $k ) : ?>
							<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $edit_template->key ?? '', $k ); ?>><?php echo esc_html( str_replace( '_', ' ', $k ) ); ?></option>
						<?php endforeach; ?>
					</select>
					<?php if ( $edit_template ) : ?><input type="hidden" name="key" value="<?php echo esc_attr( $edit_template->key ); ?>"><?php endif; ?>
				</div>
				<div class="esk-form-group"><label><?php esc_html_e( 'Name', 'eskoofy' ); ?> *</label><input type="text" name="name" required value="<?php echo esc_attr( $edit_template->name ?? '' ); ?>"></div>
				<div class="esk-form-group"><label><?php esc_html_e( 'Subject', 'eskoofy' ); ?></label><input type="text" name="subject" class="regular-text" value="<?php echo esc_attr( $edit_template->subject ?? '' ); ?>"></div>
				<div class="esk-form-group"><label><?php esc_html_e( 'Variables (comma-separated)', 'eskoofy' ); ?></label><input type="text" name="variables" class="regular-text" value="<?php echo esc_attr( $edit_template ? implode( ',', json_decode( (string) $edit_template->variables, true ) ?: array() ) : '' ); ?>"></div>
			</div>
			<div class="esk-form-group"><label><?php esc_html_e( 'Email Content (HTML)', 'eskoofy' ); ?></label><textarea name="content" class="large-text code" rows="5"><?php echo esc_textarea( $edit_template->content ?? '' ); ?></textarea></div>
			<div class="esk-form-row">
				<div class="esk-form-group"><label><?php esc_html_e( 'SMS Content', 'eskoofy' ); ?></label><textarea name="sms_content" class="large-text" rows="2"><?php echo esc_textarea( $edit_template->sms_content ?? '' ); ?></textarea></div>
				<div class="esk-form-group"><label><?php esc_html_e( 'In-App Content', 'eskoofy' ); ?></label><textarea name="in_app_content" class="large-text" rows="2"><?php echo esc_textarea( $edit_template->in_app_content ?? '' ); ?></textarea></div>
			</div>
			<div class="esk-form-group"><label><input type="checkbox" name="is_active" value="1" <?php checked( $edit_template->is_active ?? 1, 1 ); ?>> <?php esc_html_e( 'Active', 'eskoofy' ); ?></label></div>
			<button type="submit" name="<?php echo $edit_template ? 'esk_notif_template_update' : 'esk_notif_template_save'; ?>" class="button button-primary"><?php echo $edit_template ? esc_html__( 'Update Template', 'eskoofy' ) : esc_html__( 'Add Template', 'eskoofy' ); ?></button>
			<?php if ( $edit_template ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=esk-notification-templates' ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'eskoofy' ); ?></a>
			<?php endif; ?>
		</form>
	</div>

	<div class="esk-table-scroll">
		<table class="wp-list-table widefat striped esk-table">
			<thead><tr>
				<th><?php esc_html_e( 'Key', 'eskoofy' ); ?></th>
				<th><?php esc_html_e( 'Name', 'eskoofy' ); ?></th>
				<th><?php esc_html_e( 'Subject', 'eskoofy' ); ?></th>
				<th><?php esc_html_e( 'Status', 'eskoofy' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'eskoofy' ); ?></th>
			</tr></thead>
			<tbody>
				<?php if ( empty( $templates ) ) : ?>
					<tr><td colspan="5"><?php esc_html_e( 'No templates yet.', 'eskoofy' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $templates as $t ) : ?>
						<tr>
							<td><code><?php echo esc_html( $t->key ); ?></code></td>
							<td><strong><?php echo esc_html( $t->name ); ?></strong></td>
							<td><?php echo esc_html( $t->subject ); ?></td>
							<td><?php echo $t->is_active ? '<span class="esk-badge esk-badge-active">' . esc_html__( 'Active', 'eskoofy' ) . '</span>' : '<span class="esk-badge esk-badge-pending">' . esc_html__( 'Inactive', 'eskoofy' ) . '</span>'; ?></td>
							<td>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=esk-notification-templates&edit_template=' . $t->id ) ); ?>" class="button button-small"><?php esc_html_e( 'Edit', 'eskoofy' ); ?></a>
								<form method="post" style="display:inline;" onsubmit="return confirm('<?php esc_attr_e( 'Delete this template?', 'eskoofy' ); ?>');">
									<?php wp_nonce_field( 'esk_notif_template_delete_' . $t->id ); ?>
									<input type="hidden" name="template_id" value="<?php echo esc_attr( $t->id ); ?>">
									<button type="submit" name="esk_notif_template_delete" class="button button-small"><?php esc_html_e( 'Delete', 'eskoofy' ); ?></button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>