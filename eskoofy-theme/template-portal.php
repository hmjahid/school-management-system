<?php
/**
 * Template Name: Portal Info
 *
 * Public info about student/parent portal — login URL etc.
 *
 * @package Eskoofy
 */

declare(strict_types=1);

get_header();
?>
<div class="eskoofy-container">
	<h1><?php esc_html_e( 'Student & Parent Portal', 'eskoofy' ); ?></h1>
	<p><?php esc_html_e( 'The portal gives students and parents access to results, attendance, fee status, and notices.', 'eskoofy' ); ?></p>

	<h2><?php esc_html_e( 'Public Tools', 'eskoofy' ); ?></h2>
	<ul>
		<li><?php esc_html_e( 'Results lookup — by admission number', 'eskoofy' ); ?></li>
		<li><?php esc_html_e( 'Fee status — by admission number', 'eskoofy' ); ?></li>
		<li><?php esc_html_e( 'Admission application', 'eskoofy' ); ?></li>
	</ul>

	<h2><?php esc_html_e( 'Account Login', 'eskoofy' ); ?></h2>
	<p>
		<a class="esk-button esk-button-primary" href="<?php echo esc_url( wp_login_url() ); ?>">
			<?php esc_html_e( 'Sign In', 'eskoofy' ); ?>
		</a>
	</p>

	<h2><?php esc_html_e( 'First Time?', 'eskoofy' ); ?></h2>
	<p><?php esc_html_e( 'Parents and students receive an account from the school office after admission. If you need help, please contact the office.', 'eskoofy' ); ?></p>
</div>
<?php get_footer();