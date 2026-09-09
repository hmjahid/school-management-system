<?php
/**
 * Template Name: Privacy Policy
 *
 * @package Eskoofy
 */

declare(strict_types=1);

get_header();
?>
<div class="eskoofy-container">
	<h1><?php esc_html_e( 'Privacy Policy', 'eskoofy' ); ?></h1>
	<p><?php esc_html_e( 'We respect your privacy. Personal information collected through this site is used solely for school operations and is not shared with third parties except as required by law.', 'eskoofy' ); ?></p>
	<h2><?php esc_html_e( 'Information We Collect', 'eskoofy' ); ?></h2>
	<ul>
		<li><?php esc_html_e( 'Personal information submitted via admission and contact forms.', 'eskoofy' ); ?></li>
		<li><?php esc_html_e( 'Payment and fee-related data.', 'eskoofy' ); ?></li>
		<li><?php esc_html_e( 'Standard server logs (IP, user agent) for security.', 'eskoofy' ); ?></li>
	</ul>
	<h2><?php esc_html_e( 'Contact', 'eskoofy' ); ?></h2>
	<p><?php esc_html_e( 'For privacy questions, contact the school office.', 'eskoofy' ); ?></p>
</div>
<?php get_footer();