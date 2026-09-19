<?php
/**
 * Template Name: Students Life
 *
 * @package Eskoofy
 */

declare(strict_types=1);

get_header();
get_template_part(
	'template-parts/inner-hero',
	null,
	array(
		'title'    => esk_page_title( 'students', __( 'Students', 'eskoofy' ) ),
		'subtitle' => esc_html__( 'Student life, resources and activities.', 'eskoofy' ),
	)
);
get_template_part(
	'template-parts/page-sections',
	null,
	array(
		'page'            => 'students',
		'fallback_option' => 'esk_students_page_content',
	)
);
get_footer();
