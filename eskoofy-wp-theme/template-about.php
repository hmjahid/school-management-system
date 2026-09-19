<?php
/**
 * Template Name: About
 *
 * @package Eskoofy
 */

declare(strict_types=1);

get_header();
get_template_part(
	'template-parts/inner-hero',
	null,
	array(
		'title'    => esk_page_title( 'about', __( 'About', 'eskoofy' ) ),
		'subtitle' => esc_html__( 'Learn about our school, mission and history.', 'eskoofy' ),
	)
);
get_template_part(
	'template-parts/page-sections',
	null,
	array(
		'page'            => 'about',
		'fallback_option' => 'esk_about_page_content',
	)
);
get_footer();
