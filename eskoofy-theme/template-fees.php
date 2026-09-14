<?php
/**
 * Template Name: Fees Payment
 *
 * @package Eskoofy
 */

declare(strict_types=1);

get_header();

get_template_part(
	'template-parts/inner-hero',
	null,
	array(
		'title'    => (string) esk_site_ui( 'pages.fees_heading', __( 'Fees', 'eskoofy' ) ),
		'subtitle' => (string) esk_site_ui( 'pages.fees_intro', '' ),
	)
);
?>
<div class="esk-page-sections">
	<div class="esk-container">
		<div class="esk-panel-grid">
			<div class="esk-panel-card esk-card">
				<span class="esk-panel-icon" aria-hidden="true">&#128179;</span>
				<h2 class="esk-card-title"><?php echo esc_html( esk_site_ui( 'pages.fees_heading', '' ) ); ?></h2>
				<p class="esk-card-text"><?php echo esc_html( esk_site_ui( 'pages.fees_intro', '' ) ); ?></p>
				<a class="esk-btn" href="<?php echo esc_url( wp_login_url() ); ?>"><?php echo esc_html( esk_site_ui( 'pages.login', __( 'Login to pay', 'eskoofy' ) ) ); ?></a>
			</div>
		</div>
	</div>
</div>
<?php
get_template_part( 'template-parts/page-sections', null, array( 'page' => 'fees', 'fallback_option' => '' ) );
get_footer();