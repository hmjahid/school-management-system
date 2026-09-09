<?php
/**
 * Custom Post Types registration.
 *
 * @package Eskoofy
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

function esk_register_post_types(): void {
	// ─── News ──────────────────────────────────────────────────
	register_post_type(
		'esk_news',
		array(
			'labels'       => array(
				'name'               => esc_html__( 'News', 'eskoofy' ),
				'singular_name'      => esc_html__( 'News Item', 'eskoofy' ),
				'add_new_item'       => esc_html__( 'Add New News', 'eskoofy' ),
				'edit_item'          => esc_html__( 'Edit News', 'eskoofy' ),
				'view_item'          => esc_html__( 'View News', 'eskoofy' ),
				'all_items'          => esc_html__( 'All News', 'eskoofy' ),
				'search_items'       => esc_html__( 'Search News', 'eskoofy' ),
				'not_found'          => esc_html__( 'No news found', 'eskoofy' ),
				'not_found_in_trash' => esc_html__( 'No news found in Trash', 'eskoofy' ),
			),
			'public'       => true,
			'has_archive'  => true,
			'show_in_rest' => true,
			'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
			'menu_icon'    => 'dashicons-megaphone',
			'rewrite'      => array( 'slug' => 'news' ),
			'menu_position' => 25,
		)
	);

	// ─── Events ────────────────────────────────────────────────
	register_post_type(
		'esk_events',
		array(
			'labels'       => array(
				'name'               => esc_html__( 'Events', 'eskoofy' ),
				'singular_name'      => esc_html__( 'Event', 'eskoofy' ),
				'add_new_item'       => esc_html__( 'Add New Event', 'eskoofy' ),
				'edit_item'          => esc_html__( 'Edit Event', 'eskoofy' ),
				'view_item'          => esc_html__( 'View Event', 'eskoofy' ),
				'all_items'          => esc_html__( 'All Events', 'eskoofy' ),
				'search_items'       => esc_html__( 'Search Events', 'eskoofy' ),
				'not_found'          => esc_html__( 'No events found', 'eskoofy' ),
			),
			'public'       => true,
			'has_archive'  => true,
			'show_in_rest' => true,
			'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
			'menu_icon'    => 'dashicons-calendar-alt',
			'rewrite'      => array( 'slug' => 'events' ),
		)
	);

	// ─── Notices ───────────────────────────────────────────────
	register_post_type(
		'esk_notices',
		array(
			'labels'       => array(
				'name'               => esc_html__( 'Notices', 'eskoofy' ),
				'singular_name'      => esc_html__( 'Notice', 'eskoofy' ),
				'add_new_item'       => esc_html__( 'Add New Notice', 'eskoofy' ),
				'edit_item'          => esc_html__( 'Edit Notice', 'eskoofy' ),
				'view_item'          => esc_html__( 'View Notice', 'eskoofy' ),
				'all_items'          => esc_html__( 'All Notices', 'eskoofy' ),
				'search_items'       => esc_html__( 'Search Notices', 'eskoofy' ),
				'not_found'          => esc_html__( 'No notices found', 'eskoofy' ),
			),
			'public'       => true,
			'has_archive'  => true,
			'show_in_rest' => true,
			'supports'     => array( 'title', 'editor', 'thumbnail' ),
			'menu_icon'    => 'dashicons-sticky',
			'rewrite'      => array( 'slug' => 'notices' ),
			'menu_position' => 26,
		)
	);

	// ─── Galleries ─────────────────────────────────────────────
	register_post_type(
		'esk_galleries',
		array(
			'labels'       => array(
				'name'               => esc_html__( 'Galleries', 'eskoofy' ),
				'singular_name'      => esc_html__( 'Gallery', 'eskoofy' ),
				'add_new_item'       => esc_html__( 'Add New Gallery', 'eskoofy' ),
				'edit_item'          => esc_html__( 'Edit Gallery', 'eskoofy' ),
				'view_item'          => esc_html__( 'View Gallery', 'eskoofy' ),
				'all_items'          => esc_html__( 'All Galleries', 'eskoofy' ),
				'search_items'       => esc_html__( 'Search Galleries', 'eskoofy' ),
				'not_found'          => esc_html__( 'No galleries found', 'eskoofy' ),
			),
			'public'       => true,
			'has_archive'  => true,
			'show_in_rest' => true,
			'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
			'menu_icon'    => 'dashicons-format-gallery',
			'rewrite'      => array( 'slug' => 'gallery' ),
			'menu_position' => 27,
		)
	);

	// ─── Testimonials ──────────────────────────────────────────
	register_post_type(
		'esk_testimonials',
		array(
			'labels'       => array(
				'name'               => esc_html__( 'Testimonials', 'eskoofy' ),
				'singular_name'      => esc_html__( 'Testimonial', 'eskoofy' ),
				'add_new_item'       => esc_html__( 'Add New Testimonial', 'eskoofy' ),
				'edit_item'          => esc_html__( 'Edit Testimonial', 'eskoofy' ),
				'view_item'          => esc_html__( 'View Testimonial', 'eskoofy' ),
				'all_items'          => esc_html__( 'All Testimonials', 'eskoofy' ),
				'search_items'       => esc_html__( 'Search Testimonials', 'eskoofy' ),
				'not_found'          => esc_html__( 'No testimonials found', 'eskoofy' ),
			),
			'public'       => false,
			'show_ui'      => true,
			'show_in_rest' => true,
			'supports'     => array( 'title', 'editor', 'thumbnail' ),
			'menu_icon'    => 'dashicons-format-quote',
			'rewrite'      => array( 'slug' => 'testimonials' ),
			'menu_position' => 28,
		)
	);

	// ─── Committee Members ─────────────────────────────────────
	register_post_type(
		'esk_committee_members',
		array(
			'labels'       => array(
				'name'               => esc_html__( 'Committee Members', 'eskoofy' ),
				'singular_name'      => esc_html__( 'Committee Member', 'eskoofy' ),
				'add_new_item'       => esc_html__( 'Add New Member', 'eskoofy' ),
				'edit_item'          => esc_html__( 'Edit Member', 'eskoofy' ),
				'view_item'          => esc_html__( 'View Member', 'eskoofy' ),
				'all_items'          => esc_html__( 'All Members', 'eskoofy' ),
				'search_items'       => esc_html__( 'Search Members', 'eskoofy' ),
				'not_found'          => esc_html__( 'No members found', 'eskoofy' ),
			),
			'public'       => false,
			'show_ui'      => true,
			'show_in_rest' => true,
			'supports'     => array( 'title', 'editor', 'thumbnail' ),
			'menu_icon'    => 'dashicons-groups',
			'menu_position' => 29,
		)
	);

	// ─── Careers ───────────────────────────────────────────────
	register_post_type(
		'esk_careers',
		array(
			'labels'       => array(
				'name'               => esc_html__( 'Careers', 'eskoofy' ),
				'singular_name'      => esc_html__( 'Career', 'eskoofy' ),
				'add_new_item'       => esc_html__( 'Add New Career', 'eskoofy' ),
				'edit_item'          => esc_html__( 'Edit Career', 'eskoofy' ),
				'view_item'          => esc_html__( 'View Career', 'eskoofy' ),
				'all_items'          => esc_html__( 'All Careers', 'eskoofy' ),
				'search_items'       => esc_html__( 'Search Careers', 'eskoofy' ),
				'not_found'          => esc_html__( 'No careers found', 'eskoofy' ),
			),
			'public'       => true,
			'has_archive'  => true,
			'show_in_rest' => true,
			'supports'     => array( 'title', 'editor', 'thumbnail' ),
			'menu_icon'    => 'dashicons-briefcase',
			'rewrite'      => array( 'slug' => 'careers' ),
			'menu_position' => 30,
		)
	);
}
add_action( 'init', 'esk_register_post_types' );
