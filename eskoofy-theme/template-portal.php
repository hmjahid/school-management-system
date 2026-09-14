<?php
/**
 * Template Name: Portal Info
 *
 * Public info about the student/parent portal.
 *
 * @package Eskoofy
 */

declare(strict_types=1);

get_header();

get_template_part(
	'template-parts/inner-hero',
	null,
	array(
		'title'    => (string) esk_site_ui( 'pages.portal_heading', __( 'Parent / Student portal', 'eskoofy' ) ),
		'subtitle' => (string) esk_site_ui( 'pages.portal_intro', '' ),
	)
);
?>
<div class="esk-page-sections">
	<div class="esk-container">
		<div class="esk-panel-grid">
			<div class="esk-panel-card esk-card">
				<span class="esk-panel-icon" aria-hidden="true">&#128274;</span>
				<h2 class="esk-card-title"><?php echo esc_html( esk_site_ui( 'pages.login', __( 'Login', 'eskoofy' ) ) ); ?></h2>
				<p class="esk-card-text"><?php echo esc_html( esk_site_ui( 'pages.portal_intro', '' ) ); ?></p>
				<a class="esk-btn" href="<?php echo esc_url( home_url( '/login/' ) ); ?>"><?php echo esc_html( esk_site_ui( 'pages.login', '' ) ); ?></a>
			</div>
		</div>
	</div>
</div>
<?php
get_template_part( 'template-parts/page-sections', null, array( 'page' => 'portal', 'fallback_option' => '' ) );
get_footer();