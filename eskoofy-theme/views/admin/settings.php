<?php
/**
 * Settings page — tabbed.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;

if ( isset( $_POST['esk_settings_save'] ) ) {
	check_admin_referer( 'esk_settings_form' );

	$current_tab = sanitize_text_field( wp_unslash( $_POST['current_tab'] ?? 'school' ) );
	$redirect_to = add_query_arg( array( 'page' => 'esk-settings', 'tab' => $current_tab ), admin_url( 'admin.php' ) );

	if ( 'school' === $current_tab ) {
		foreach ( array( 'school_name', 'school_tagline', 'school_phone', 'school_email', 'school_address', 'currency' ) as $key ) {
			$value = isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
			update_option( 'esk_' . $key, $value );
		}
	} elseif ( 'theme' === $current_tab ) {
		update_option( 'esk_theme_color', sanitize_hex_color( wp_unslash( $_POST['theme_color'] ?? '#2563eb' ) ) );
		update_option( 'esk_theme_font', sanitize_text_field( wp_unslash( $_POST['theme_font'] ?? 'Inter' ) ) );
	} elseif ( 'localization' === $current_tab ) {
		update_option( 'esk_locale', sanitize_text_field( wp_unslash( $_POST['locale'] ?? 'en' ) ) );
	} elseif ( 'payment' === $current_tab ) {
		update_option( 'esk_default_gateway', sanitize_text_field( wp_unslash( $_POST['default_gateway'] ?? 'offline' ) ) );
		update_option( 'esk_test_mode', isset( $_POST['test_mode'] ) ? 1 : 0 );
	} elseif ( 'mail' === $current_tab ) {
		update_option( 'esk_smtp_host', sanitize_text_field( wp_unslash( $_POST['smtp_host'] ?? '' ) ) );
		update_option( 'esk_smtp_port', absint( $_POST['smtp_port'] ?? 587 ) );
		update_option( 'esk_smtp_user', sanitize_text_field( wp_unslash( $_POST['smtp_user'] ?? '' ) ) );
		update_option( 'esk_smtp_pass', sanitize_text_field( wp_unslash( $_POST['smtp_pass'] ?? '' ) ) );
		update_option( 'esk_smtp_secure', sanitize_text_field( wp_unslash( $_POST['smtp_secure'] ?? 'tls' ) ) );
	} elseif ( 'library' === $current_tab ) {
		update_option( 'esk_default_loan_days', absint( $_POST['default_loan_days'] ?? 14 ) );
		update_option( 'esk_fine_per_day', (float) ( $_POST['fine_per_day'] ?? 0 ) );
	} elseif ( 'cms' === $current_tab ) {
		update_option( 'esk_default_terms', sanitize_textarea_field( wp_unslash( $_POST['default_terms'] ?? '' ) ) );
	} elseif ( 'about' === $current_tab ) {
		update_option( 'esk_about_page_content', wp_kses_post( wp_unslash( $_POST['about_page_content'] ?? '' ) ) );
	} elseif ( 'offline' === $current_tab ) {
		foreach ( array( 'offline_bank_name', 'offline_account_name', 'offline_account_no', 'offline_branch', 'offline_routing', 'offline_instructions' ) as $key ) {
			$value = isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
			update_option( 'esk_' . $key, $value );
		}
	}

	esk_flash( 'success', __( 'Settings saved.', 'eskoofy' ) );
	wp_safe_redirect( $redirect_to );
	exit;
}

if ( isset( $_POST['esk_mail_test'] ) ) {
	check_admin_referer( 'esk_settings_form' );
	$to      = sanitize_email( wp_unslash( $_POST['test_email'] ?? '' ) );
	$subject = __( 'Eskoofy Test Email', 'eskoofy' );
	$body    = __( 'This is a test email from your Eskoofy site.', 'eskoofy' );
	$sent    = wp_mail( $to, $subject, $body );
	esk_flash( $sent ? 'success' : 'error', $sent ? __( 'Test email sent.', 'eskoofy' ) : __( 'Failed to send test email.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-settings&tab=mail' ) );
	exit;
}

$tab   = sanitize_text_field( $_GET['tab'] ?? 'school' );
$flash = esk_get_flash( 'success' );
$error = esk_get_flash( 'error' );
?>
<div class="wrap esk-admin-wrap">
	<h1><?php esc_html_e( 'Settings', 'eskoofy' ); ?></h1>

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>
	<?php if ( $error ) : ?>
		<div class="notice notice-error is-dismissible"><p><?php echo esc_html( $error ); ?></p></div>
	<?php endif; ?>

	<nav class="nav-tab-wrapper esk-tabs">
		<?php
		$tabs = array(
			'school'        => __( 'School Info', 'eskoofy' ),
			'theme'         => __( 'Theme', 'eskoofy' ),
			'localization'  => __( 'Localization', 'eskoofy' ),
			'payment'       => __( 'Payment', 'eskoofy' ),
			'offline'       => __( 'Offline / Bank', 'eskoofy' ),
			'mail'          => __( 'Mail', 'eskoofy' ),
			'library'       => __( 'Library', 'eskoofy' ),
			'cms'           => __( 'CMS', 'eskoofy' ),
			'about'         => __( 'About', 'eskoofy' ),
		);
		foreach ( $tabs as $slug => $label ) :
			?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=esk-settings&tab=' . $slug ) ); ?>" class="nav-tab <?php echo $slug === $tab ? 'nav-tab-active' : ''; ?>"><?php echo esc_html( $label ); ?></a>
		<?php endforeach; ?>
	</nav>

	<form method="post" class="esk-form">
		<?php wp_nonce_field( 'esk_settings_form' ); ?>
		<input type="hidden" name="current_tab" value="<?php echo esc_attr( $tab ); ?>">

		<?php if ( 'school' === $tab ) : ?>
			<div class="esk-card esk-form-card">
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

		<?php elseif ( 'theme' === $tab ) : ?>
			<div class="esk-card esk-form-card">
				<table class="form-table">
					<tr><th><label for="theme_color"><?php esc_html_e( 'Primary Color', 'eskoofy' ); ?></label></th>
						<td><input type="color" name="theme_color" id="theme_color" value="<?php echo esc_attr( get_option( 'esk_theme_color', '#2563eb' ) ); ?>"></td></tr>
					<tr><th><label for="theme_font"><?php esc_html_e( 'Font Family', 'eskoofy' ); ?></label></th>
						<td><input type="text" name="theme_font" id="theme_font" value="<?php echo esc_attr( get_option( 'esk_theme_font', 'Inter' ) ); ?>"></td></tr>
				</table>
			</div>

		<?php elseif ( 'localization' === $tab ) : ?>
			<div class="esk-card esk-form-card">
				<table class="form-table">
					<tr><th><label for="locale"><?php esc_html_e( 'Default Locale', 'eskoofy' ); ?></label></th>
						<td>
							<select name="locale" id="locale">
								<option value="en" <?php selected( get_option( 'esk_locale', 'en' ), 'en' ); ?>>English</option>
								<option value="bn_BD" <?php selected( get_option( 'esk_locale', 'en' ), 'bn_BD' ); ?>>বাংলা (Bengali)</option>
							</select>
						</td></tr>
				</table>
			</div>

		<?php elseif ( 'payment' === $tab ) : ?>
			<div class="esk-card esk-form-card">
				<?php
				global $wpdb;
				$gateways = $wpdb->get_results( "SELECT code, name FROM {$wpdb->prefix}esk_payment_gateways WHERE deleted_at IS NULL ORDER BY name" );
				?>
				<table class="form-table">
					<tr><th><label for="default_gateway"><?php esc_html_e( 'Default Gateway', 'eskoofy' ); ?></label></th>
						<td>
							<select name="default_gateway" id="default_gateway">
								<?php foreach ( $gateways as $g ) : ?>
									<option value="<?php echo esc_attr( $g->code ); ?>" <?php selected( get_option( 'esk_default_gateway', 'offline' ), $g->code ); ?>><?php echo esc_html( $g->name ); ?></option>
								<?php endforeach; ?>
							</select>
						</td></tr>
					<tr><th><?php esc_html_e( 'Test Mode', 'eskoofy' ); ?></th>
						<td><label><input type="checkbox" name="test_mode" value="1" <?php checked( get_option( 'esk_test_mode', 1 ), 1 ); ?>> <?php esc_html_e( 'Use sandbox/test mode', 'eskoofy' ); ?></label></td></tr>
				</table>
				<p class="description"><?php esc_html_e( 'Per-gateway API credentials are configured in the Payment Gateways screen.', 'eskoofy' ); ?></p>
			</div>

		<?php elseif ( 'offline' === $tab ) : ?>
			<div class="esk-card esk-form-card">
				<table class="form-table">
					<tr><th><label for="offline_bank_name"><?php esc_html_e( 'Bank Name', 'eskoofy' ); ?></label></th>
						<td><input type="text" name="offline_bank_name" id="offline_bank_name" value="<?php echo esc_attr( get_option( 'esk_offline_bank_name', '' ) ); ?>" class="regular-text"></td></tr>
					<tr><th><label for="offline_account_name"><?php esc_html_e( 'Account Name', 'eskoofy' ); ?></label></th>
						<td><input type="text" name="offline_account_name" id="offline_account_name" value="<?php echo esc_attr( get_option( 'esk_offline_account_name', '' ) ); ?>" class="regular-text"></td></tr>
					<tr><th><label for="offline_account_no"><?php esc_html_e( 'Account Number', 'eskoofy' ); ?></label></th>
						<td><input type="text" name="offline_account_no" id="offline_account_no" value="<?php echo esc_attr( get_option( 'esk_offline_account_no', '' ) ); ?>"></td></tr>
					<tr><th><label for="offline_branch"><?php esc_html_e( 'Branch', 'eskoofy' ); ?></label></th>
						<td><input type="text" name="offline_branch" id="offline_branch" value="<?php echo esc_attr( get_option( 'esk_offline_branch', '' ) ); ?>"></td></tr>
					<tr><th><label for="offline_routing"><?php esc_html_e( 'Routing Number', 'eskoofy' ); ?></label></th>
						<td><input type="text" name="offline_routing" id="offline_routing" value="<?php echo esc_attr( get_option( 'esk_offline_routing', '' ) ); ?>"></td></tr>
					<tr><th><label for="offline_instructions"><?php esc_html_e( 'Instructions', 'eskoofy' ); ?></label></th>
						<td><textarea name="offline_instructions" id="offline_instructions" rows="3" class="large-text"><?php echo esc_textarea( get_option( 'esk_offline_instructions', '' ) ); ?></textarea></td></tr>
				</table>
			</div>

		<?php elseif ( 'mail' === $tab ) : ?>
			<div class="esk-card esk-form-card">
				<table class="form-table">
					<tr><th><label for="smtp_host"><?php esc_html_e( 'SMTP Host', 'eskoofy' ); ?></label></th>
						<td><input type="text" name="smtp_host" id="smtp_host" value="<?php echo esc_attr( get_option( 'esk_smtp_host', '' ) ); ?>" class="regular-text"></td></tr>
					<tr><th><label for="smtp_port"><?php esc_html_e( 'SMTP Port', 'eskoofy' ); ?></label></th>
						<td><input type="number" name="smtp_port" id="smtp_port" value="<?php echo esc_attr( get_option( 'esk_smtp_port', 587 ) ); ?>"></td></tr>
					<tr><th><label for="smtp_user"><?php esc_html_e( 'Username', 'eskoofy' ); ?></label></th>
						<td><input type="text" name="smtp_user" id="smtp_user" value="<?php echo esc_attr( get_option( 'esk_smtp_user', '' ) ); ?>" class="regular-text"></td></tr>
					<tr><th><label for="smtp_pass"><?php esc_html_e( 'Password', 'eskoofy' ); ?></label></th>
						<td><input type="password" name="smtp_pass" id="smtp_pass" value="<?php echo esc_attr( get_option( 'esk_smtp_pass', '' ) ); ?>"></td></tr>
					<tr><th><label for="smtp_secure"><?php esc_html_e( 'Encryption', 'eskoofy' ); ?></label></th>
						<td>
							<select name="smtp_secure" id="smtp_secure">
								<option value="tls" <?php selected( get_option( 'esk_smtp_secure', 'tls' ), 'tls' ); ?>>TLS</option>
								<option value="ssl" <?php selected( get_option( 'esk_smtp_secure', 'tls' ), 'ssl' ); ?>>SSL</option>
								<option value="" <?php selected( get_option( 'esk_smtp_secure', 'tls' ), '' ); ?>>None</option>
							</select>
						</td></tr>
				</table>
				<hr>
				<h3><?php esc_html_e( 'Send Test Email', 'eskoofy' ); ?></h3>
				<input type="email" name="test_email" placeholder="<?php esc_attr_e( 'recipient@example.com', 'eskoofy' ); ?>">
				<button type="submit" name="esk_mail_test" class="button"><?php esc_html_e( 'Send Test', 'eskoofy' ); ?></button>
			</div>

		<?php elseif ( 'library' === $tab ) : ?>
			<div class="esk-card esk-form-card">
				<table class="form-table">
					<tr><th><label for="default_loan_days"><?php esc_html_e( 'Default Loan Period (days)', 'eskoofy' ); ?></label></th>
						<td><input type="number" name="default_loan_days" id="default_loan_days" value="<?php echo esc_attr( get_option( 'esk_default_loan_days', 14 ) ); ?>" min="1"></td></tr>
					<tr><th><label for="fine_per_day"><?php esc_html_e( 'Late Fine Per Day', 'eskoofy' ); ?></label></th>
						<td><input type="number" step="0.01" name="fine_per_day" id="fine_per_day" value="<?php echo esc_attr( get_option( 'esk_fine_per_day', 5 ) ); ?>"></td></tr>
				</table>
			</div>

		<?php elseif ( 'cms' === $tab ) : ?>
			<div class="esk-card esk-form-card">
				<table class="form-table">
					<tr><th><label for="default_terms"><?php esc_html_e( 'Default Terms & Conditions', 'eskoofy' ); ?></label></th>
						<td><textarea name="default_terms" id="default_terms" rows="5" class="large-text"><?php echo esc_textarea( get_option( 'esk_default_terms', '' ) ); ?></textarea></td></tr>
				</table>
			</div>

		<?php elseif ( 'about' === $tab ) : ?>
			<div class="esk-card esk-form-card">
				<?php wp_editor( get_option( 'esk_about_page_content', '' ), 'esk_about_page_content', array( 'textarea_name' => 'about_page_content', 'textarea_rows' => 10 ) ); ?>
			</div>

		<?php endif; ?>

		<?php if ( 'mail' !== $tab ) : ?>
			<p class="submit">
				<button type="submit" name="esk_settings_save" class="button button-primary"><?php esc_html_e( 'Save Settings', 'eskoofy' ); ?></button>
			</p>
		<?php endif; ?>
	</form>
</div>