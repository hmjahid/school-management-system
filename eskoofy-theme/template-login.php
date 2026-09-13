<?php
/**
 * Template Name: Login
 *
 * Login page for students, parents, and staff.
 *
 * @package Eskoofy
 */

declare(strict_types=1);

get_header();
?>
<div class="eskoofy-container eskoofy-login-page">
	<div class="esk-login-wrapper">
		<div class="esk-login-header">
			<h1><?php esc_html_e( 'Sign In', 'eskoofy' ); ?></h1>
			<p><?php esc_html_e( 'Enter your credentials to access your account', 'eskoofy' ); ?></p>
		</div>

		<?php echo do_shortcode( '[eskoofy_login_form]' ); ?>

		<div class="esk-login-links">
			<a href="<?php echo esc_url( home_url( '/portal/' ) ); ?>">
				<?php esc_html_e( '&larr; Back to Portal Info', 'eskoofy' ); ?>
			</a>
		</div>
	</div>
</div>
<?php get_footer();
