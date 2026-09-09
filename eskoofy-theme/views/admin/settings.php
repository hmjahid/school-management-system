<?php
/**
 * Settings page.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;

if ( isset( $_POST['esk_settings_save'] ) ) {
	check_admin_referer( 'esk_settings_form' );

	$settings = array(
		'school_name',
		'school_tagline',
		'school_phone',
		'school_email',
		'school_address',
		'currency',
	);

	foreach ( $settings as $key ) {
		$value = isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
		update_option( 'esk_' . $key, $value );
	}

	esk_flash( 'success', __( 'Settings saved.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-settings' ) );
	exit;
}

$flash = esk_get_flash( 'success' );
?>
<div class="wrap esk-admin-wrap">
	<h1><?php esc_html_e( 'Settings', 'eskoofy' ); ?></h1>

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<form method="post" class="esk-form">
		<?php wp_nonce_field( 'esk_settings_form' ); ?>

		<div class="esk-card esk-form-card">
			<h2><?php esc_html_e( 'School Information', 'eskoofy' ); ?></h2>
			<table class="form-table">
				<tr><th><label for="school_name"><?php esc_html_e( 'School Name', 'eskoofy' ); ?></label></th>
					<td><input type="text" name="school_name" id="school_name" value="<?php echo esc_attr( esk_get_option( 'school_name' ) ); ?>" class="regular-text"></td></tr>
				<tr><th><label for="school_tagline"><?php esc_html_e( 'Tagline', 'eskoofy' ); ?></label></th>
					<td><input type="text" name="school_tagline" id="school_tagline" value="<?php echo esc_attr( esk_get_option( 'school_tagline' ) ); ?>" class="regular-text"></td></tr>
				<tr><th><label for="school_phone"><?php esc_html_e( 'Phone', 'eskoofy' ); ?></label></th>
					<td><input type="tel" name="school_phone" id="school_phone" value="<?php echo esc_attr( esk_get_option( 'school_phone' ) ); ?>"></td></tr>
				<tr><th><label for="school_email"><?php esc_html_e( 'Email', 'eskoofy' ); ?></label></th>
					<td><input type="email" name="school_email" id="school_email" value="<?php echo esc_attr( esk_get_option( 'school_email' ) ); ?>"></td></tr>
				<tr><th><label for="school_address"><?php esc_html_e( 'Address', 'eskoofy' ); ?></label></th>
					<td><textarea name="school_address" id="school_address" rows="3" class="large-text"><?php echo esc_textarea( esk_get_option( 'school_address' ) ); ?></textarea></td></tr>
				<tr><th><label for="currency"><?php esc_html_e( 'Currency Symbol', 'eskoofy' ); ?></label></th>
					<td><input type="text" name="currency" id="currency" value="<?php echo esc_attr( esk_get_option( 'currency', '৳' ) ); ?>" maxlength="5"></td></tr>
			</table>
		</div>

		<div class="esk-card esk-form-card">
			<h2><?php esc_html_e( 'Payment Gateways', 'eskoofy' ); ?></h2>
			<p><?php esc_html_e( 'Payment gateways are configured via the Eskoofy API and database. Manage them from the Eskoofy payment settings.', 'eskoofy' ); ?></p>
		</div>

		<p class="submit">
			<button type="submit" name="esk_settings_save" class="button button-primary"><?php esc_html_e( 'Save Settings', 'eskoofy' ); ?></button>
		</p>
	</form>
</div>
