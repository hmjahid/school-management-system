<?php
/**
 * Template Name: Terms & Conditions
 *
 * @package Eskoofy
 */

declare(strict_types=1);

get_header();
get_template_part(
	'template-parts/inner-hero',
	null,
	array(
		'title'    => esk_page_title( 'terms', __( 'Terms & Conditions', 'eskoofy' ) ),
		'subtitle' => '',
	)
);
get_template_part(
	'template-parts/page-sections',
	null,
	array(
		'page'            => 'terms',
		'fallback_option' => 'esk_terms_page_content',
	)
);
get_footer();
