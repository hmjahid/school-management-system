<?php
/**
 * Template Name: Academics
 *
 * @package Eskoofy
 */

declare(strict_types=1);

get_header();
get_template_part(
	'template-parts/inner-hero',
	null,
	array(
		'title'    => esk_page_title( 'academics', __( 'Academics', 'eskoofy' ) ),
		'subtitle' => esc_html__( 'Curriculum, academic programmes and learning approach.', 'eskoofy' ),
	)
);
get_template_part(
	'template-parts/page-sections',
	null,
	array(
		'page'            => 'academics',
		'fallback_option' => 'esk_academics_page_content',
	)
);
get_footer();
