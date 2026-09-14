<?php
/**
 * Template Name: Results Page
 *
 * Public result lookup. Uses [eskoofy_results_lookup] shortcode.
 *
 * @package Eskoofy
 */

declare(strict_types=1);

get_header();

get_template_part(
	'template-parts/inner-hero',
	null,
	array(
		'title'    => (string) esk_site_ui( 'pages.results_heading', __( 'Result lookup', 'eskoofy' ) ),
		'subtitle' => (string) esk_site_ui( 'pages.results_intro', '' ),
	)
);
?>
<div class="esk-page-sections">
	<div class="esk-container" style="max-width:40rem;">
		<?php echo do_shortcode( '[eskoofy_results_lookup]' ); ?>
	</div>
</div>
<?php
get_footer();