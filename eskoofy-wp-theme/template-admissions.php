<?php
/**
 * Template Name: Admissions Apply
 *
 * Online admission application form. Uses [eskoofy_admission_form] shortcode.
 *
 * @package Eskoofy
 */

declare(strict_types=1);

get_header();

$open = esk_admissions_open();

get_template_part(
	'template-parts/inner-hero',
	null,
	array(
		'title'    => (string) esk_site_ui( 'pages.admissions_heading', __( 'Admissions', 'eskoofy' ) ),
		'subtitle' => $open ? (string) esk_site_ui( 'pages.apply_online', __( 'Start your application', 'eskoofy' ) ) : (string) esk_site_ui( 'pages.admissions_closed_title', '' ),
	)
);
?>
<div class="esk-page-sections">
	<div class="esk-container" style="max-width:48rem;">
		<?php if ( ! $open ) : ?>
			<div class="esk-message-box esk-message-error">
				<strong><?php echo esc_html( esk_site_ui( 'pages.admissions_closed_title', '' ) ); ?></strong>
				<p><?php echo esc_html( esk_site_ui( 'pages.admissions_closed_message', '' ) ); ?></p>
			</div>
			<p><a class="esk-btn esk-btn-secondary" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><?php esc_html_e( 'Contact us', 'eskoofy' ); ?></a></p>
		<?php else : ?>
			<?php echo do_shortcode( '[eskoofy_admission_form]' ); ?>
		<?php endif; ?>
	</div>
</div>
<?php
get_footer();