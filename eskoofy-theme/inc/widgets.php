<?php
/**
 * Widget areas registration.
 *
 * @package Eskoofy
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

function esk_register_widget_areas(): void {
	$widget_areas = array(
		'sidebar-1'    => array(
			'name'        => esc_html__( 'Main Sidebar', 'eskoofy' ),
			'description' => esc_html__( 'Main sidebar widget area.', 'eskoofy' ),
		),
		'footer-1'     => array(
			'name'        => esc_html__( 'Footer Column 1', 'eskoofy' ),
			'description' => esc_html__( 'First footer widget area.', 'eskoofy' ),
		),
		'footer-2'     => array(
			'name'        => esc_html__( 'Footer Column 2', 'eskoofy' ),
			'description' => esc_html__( 'Second footer widget area.', 'eskoofy' ),
		),
		'footer-3'     => array(
			'name'        => esc_html__( 'Footer Column 3', 'eskoofy' ),
			'description' => esc_html__( 'Third footer widget area.', 'eskoofy' ),
		),
		'home-hero'    => array(
			'name'        => esc_html__( 'Homepage Hero', 'eskoofy' ),
			'description' => esc_html__( 'Front page hero section.', 'eskoofy' ),
		),
		'home-features' => array(
			'name'        => esc_html__( 'Homepage Features', 'eskoofy' ),
			'description' => esc_html__( 'Front page features section.', 'eskoofy' ),
		),
	);

	foreach ( $widget_areas as $id => $args ) {
		register_sidebar( array_merge( $args, array(
			'id'            => $id,
			'before_widget' => '<div id="%1$s" class="eskoofy-widget %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<h3 class="eskoofy-widget-title">',
			'after_title'   => '</h3>',
		) ) );
	}
}
add_action( 'widgets_init', 'esk_register_widget_areas' );
