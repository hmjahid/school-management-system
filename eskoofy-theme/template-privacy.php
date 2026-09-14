<?php
/**
 * Template Name: Privacy Policy
 *
 * @package Eskoofy
 */

declare(strict_types=1);

get_header();
get_template_part(
	'template-parts/inner-hero',
	null,
	array(
		'title'    => esk_page_title( 'privacy', __( 'Privacy Policy', 'eskoofy' ) ),
		'subtitle' => '',
	)
);
get_template_part(
	'template-parts/page-sections',
	null,
	array(
		'page'            => 'privacy',
		'fallback_option' => 'esk_privacy_page_content',
	)
);
get_footer();
