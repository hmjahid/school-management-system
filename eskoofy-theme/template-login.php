<?php
/**
 * Template Name: Login
 *
 * The school system's own login page. Authenticates against the system
 * accounts (username or email) and redirects to the management dashboard.
 * This is NOT the WordPress login — WordPress-only users use wp-login.php.
 *
 * @package Eskoofy
 */

declare(strict_types=1);

$error  = esk_get_flash( 'login_error' );
$notice = esk_get_flash( 'success' );
$redirect_to = isset( $_GET['redirect_to'] ) ? esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ) : '';

get_header();

get_template_part(
	'template-parts/inner-hero',
	null,
	array(
		'title'    => (string) esk_site_ui( 'pages.login_title', __( 'System Login', 'eskoofy' ) ),
		'subtitle' => (string) esk_site_ui( 'pages.login_subtitle', __( 'Sign in to the school management system.', 'eskoofy' ) ),
	)
);
?>
<div class="esk-page-sections">
	<div class="esk-container">
		<div class="esk-panel-card esk-card" style="max-width:30rem; margin:0 auto;">
			<h2 class="esk-card-title" style="text-align:center;"><?php echo esc_html( esk_site_ui( 'pages.portal_heading', __( 'Student / Parent portal', 'eskoofy' ) ) ); ?></h2>
			<div class="esk-page-form" style="margin-top:1rem;">

				<?php if ( is_user_logged_in() ) : ?>
					<div class="esk-message-box esk-message-success" role="status">
						<p><?php esc_html_e( 'You are already signed in.', 'eskoofy' ); ?></p>
					</div>
					<p style="margin-top:1rem; text-align:center;">
						<a class="esk-btn esk-btn-accent" href="<?php echo esc_url( home_url( '/dashboard/' ) ); ?>"><?php esc_html_e( 'Open dashboard', 'eskoofy' ); ?></a>
					</p>
				<?php else : ?>

					<?php if ( '' !== $error ) : ?>
						<div class="esk-message-box esk-message-error" role="alert">
							<p><?php echo esc_html( $error ); ?></p>
						</div>
					<?php endif; ?>
					<?php if ( '' !== $notice ) : ?>
						<div class="esk-message-box esk-message-success" role="status">
							<p><?php echo esc_html( $notice ); ?></p>
						</div>
					<?php endif; ?>

					<form id="esk-login-form" method="post" action="<?php echo esc_url( home_url( '/login/' ) ); ?>" class="esk-form">
						<?php esk_csrf_field( 'esk_login_form' ); ?>
						<?php if ( '' !== $redirect_to ) : ?>
							<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>">
						<?php endif; ?>
						<div class="esk-form-group">
							<label for="esk_login"><?php esc_html_e( 'Username or Email', 'eskoofy' ); ?></label>
							<input type="text" name="esk_login" id="esk_login" class="esk-input" autocomplete="username" required>
						</div>
						<div class="esk-form-group">
							<label for="esk_password"><?php esc_html_e( 'Password', 'eskoofy' ); ?></label>
							<input type="password" name="esk_password" id="esk_password" class="esk-input" autocomplete="current-password" required>
						</div>
						<label class="esk-form-check">
							<input type="checkbox" name="rememberme" value="forever">
							<span><?php esc_html_e( 'Remember me', 'eskoofy' ); ?></span>
						</label>
						<button type="submit" name="esk_login_submit" class="esk-btn esk-btn-accent" style="width:100%;"><?php esc_html_e( 'Login', 'eskoofy' ); ?></button>
					</form>

					<p style="text-align:center; margin-top:1rem; font-size:0.9375rem;">
						<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php esc_html_e( 'Forgot your password?', 'eskoofy' ); ?></a>
					</p>
					<hr class="esk-login-divider">
					<p class="esk-login-note">
						<?php
						printf(
							/* translators: %s: wp-login.php URL for WordPress platform users. */
							esc_html__( 'WordPress platform users (site administrators) log in %s.', 'eskoofy' ),
							'<a href="' . esc_url( wp_login_url() ) . '">' . esc_html__( 'here', 'eskoofy' ) . '</a>'
						);
						?>
					</p>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
<?php
get_footer();