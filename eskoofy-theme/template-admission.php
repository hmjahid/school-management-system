<?php
/**
 * Template Name: Admission Page
 *
 * @package Eskoofy
 */

declare(strict_types=1);

get_header();

$open    = esk_admissions_open();
$heading = (string) esk_site_ui( 'pages.admissions_heading', __( 'Admissions', 'eskoofy' ) );

get_template_part(
	'template-parts/inner-hero',
	null,
	array(
		'title'    => $heading,
		'subtitle' => '',
	)
);
?>
<div class="esk-page-sections">
	<div class="esk-container">
		<?php if ( $open ) : ?>
			<div class="esk-message-box esk-message-success">
				<strong><?php echo esc_html( (string) esk_site_ui( 'admissions_bar.title', __( 'Admissions Open', 'eskoofy' ) ) ); ?></strong>
			</div>
			<div class="esk-panel-grid">
				<div class="esk-panel-card esk-card">
					<span class="esk-panel-icon" aria-hidden="true">&#128196;</span>
					<h2 class="esk-card-title"><?php echo esc_html( esk_site_ui( 'pages.apply_online', '' ) ); ?></h2>
					<p class="esk-card-text"><?php esc_html_e( 'Start a new admission application.', 'eskoofy' ); ?></p>
					<a class="esk-btn" href="<?php echo esc_url( home_url( '/admissions/' ) ); ?>"><?php echo esc_html( esk_site_ui( 'pages.apply_online', '' ) ); ?></a>
				</div>
				<div class="esk-panel-card esk-card">
					<span class="esk-panel-icon" aria-hidden="true">&#128270;</span>
					<h2 class="esk-card-title"><?php echo esc_html( esk_site_ui( 'pages.check_status', '' ) ); ?></h2>
					<p class="esk-card-text"><?php esc_html_e( 'Track your submitted application.', 'eskoofy' ); ?></p>
					<a class="esk-btn esk-btn-secondary" href="<?php echo esc_url( home_url( '/portal/' ) ); ?>"><?php echo esc_html( esk_site_ui( 'pages.check_status', '' ) ); ?></a>
				</div>
			</div>
		<?php else : ?>
			<div class="esk-message-box esk-message-error">
				<strong><?php echo esc_html( esk_site_ui( 'pages.admissions_closed_title', '' ) ); ?></strong>
				<p><?php echo esc_html( esk_site_ui( 'pages.admissions_closed_message', '' ) ); ?></p>
			</div>
			<p>
				<a class="esk-btn esk-btn-secondary" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><?php echo esc_html( esk_site_ui( 'pages.contact_address_card', __( 'Contact us', 'eskoofy' ) ) ); ?></a>
			</p>
		<?php endif; ?>
	</div>
</div>
<?php
get_template_part( 'template-parts/page-sections', null, array( 'page' => 'admission', 'fallback_option' => '' ) );
get_footer();