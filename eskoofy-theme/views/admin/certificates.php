<?php
/**
 * Certificates management.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

if ( isset( $_POST['esk_certificate_save'] ) ) {
	check_admin_referer( 'esk_certificate_form' );
	$wpdb->insert( $wpdb->prefix . 'esk_certificates', array(
		'name'       => sanitize_text_field( $_POST['name'] ?? '' ),
		'template'   => wp_kses_post( wp_unslash( $_POST['template'] ?? '' ) ),
		'created_by' => get_current_user_id(),
	) );
	esk_flash( 'success', __( 'Certificate template added.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-certificates' ) );
	exit;
}

if ( isset( $_POST['esk_certificate_delete'] ) ) {
	check_admin_referer( 'esk_certificate_delete_' . absint( $_POST['cert_id'] ?? 0 ) );
	$wpdb->delete( $wpdb->prefix . 'esk_certificates', array( 'id' => absint( $_POST['cert_id'] ?? 0 ) ) );
	esk_flash( 'success', __( 'Certificate template deleted.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-certificates' ) );
	exit;
}

$certificates = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}esk_certificates ORDER BY id DESC" );
$flash        = esk_get_flash( 'success' );
?>
<div class="wrap esk-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Certificates', 'eskoofy' ); ?></h1>
	<hr class="wp-header-end">

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<div class="esk-card esk-form-card" style="margin-bottom:1.5rem;">
		<h2><?php esc_html_e( 'Add Certificate Template', 'eskoofy' ); ?></h2>
		<form method="post" class="esk-form">
			<?php wp_nonce_field( 'esk_certificate_form' ); ?>
			<div class="esk-form-group"><label><?php esc_html_e( 'Name', 'eskoofy' ); ?> *</label><input type="text" name="name" class="regular-text" required></div>
			<div class="esk-form-group"><label><?php esc_html_e( 'Template (HTML)', 'eskoofy' ); ?> *</label><textarea name="template" class="large-text code" rows="8" required></textarea></div>
			<button type="submit" name="esk_certificate_save" class="button button-primary"><?php esc_html_e( 'Add Template', 'eskoofy' ); ?></button>
		</form>
	</div>

	<table class="wp-list-table widefat striped esk-table">
		<thead><tr>
			<th><?php esc_html_e( 'Name', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Created', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Actions', 'eskoofy' ); ?></th>
		</tr></thead>
		<tbody>
			<?php if ( empty( $certificates ) ) : ?>
				<tr><td colspan="3"><?php esc_html_e( 'No certificate templates.', 'eskoofy' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $certificates as $c ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $c->name ); ?></strong></td>
						<td><?php echo esc_html( esk_date_format( $c->created_at ) ); ?></td>
						<td>
							<form method="post" style="display:inline;" onsubmit="return confirm('<?php esc_attr_e( 'Delete this template?', 'eskoofy' ); ?>');">
								<?php wp_nonce_field( 'esk_certificate_delete_' . $c->id ); ?>
								<input type="hidden" name="cert_id" value="<?php echo esc_attr( $c->id ); ?>">
								<button type="submit" name="esk_certificate_delete" class="button button-small"><?php esc_html_e( 'Delete', 'eskoofy' ); ?></button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
