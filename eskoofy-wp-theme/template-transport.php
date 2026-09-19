<?php
/**
 * Template Name: Transport
 *
 * @package Eskoofy
 */

declare(strict_types=1);

get_header();
get_template_part(
	'template-parts/inner-hero',
	null,
	array(
		'title'    => esk_page_title( 'transport', __( 'Transport', 'eskoofy' ) ),
		'subtitle' => esc_html__( 'School bus routes, schedules and transport policies.', 'eskoofy' ),
	)
);
get_template_part(
	'template-parts/page-sections',
	null,
	array(
		'page'            => 'transport',
		'fallback_option' => 'esk_transport_page_content',
	)
);
get_footer();
