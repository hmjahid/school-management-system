<?php
/**
 * Eskoofy Child theme functions.
 *
 * CRITICAL — mother theme bootstrap:
 * When a child theme is active WordPress loads ONLY this file and SKIPS the
 * parent's functions.php. Eskoofy is a plugin-hybrid: its functions.php is the
 * bootloader for everything in inc/ (admin shell, REST API, custom database
 * tables, CPTs, shortcodes, widgets, payment/SMS gateways, demo content).
 * If we did not re-require it, the shop would go dark. So line 1 pulls the
 * full parent bootstrap back in; everything below it is then YOUR safe space
 * for custom styles/scripts/filters — 100% untouched by parent updates.
 *
 * @package Eskoofy
 */

declare(strict_types=1);

require get_template_directory() . '/functions.php'; // DO NOT remove.

/* ─── Custom front-end styles & scripts (survive parent updates) ────────── */

if ( ! function_exists( 'esk_child_enqueue' ) ) {
	function esk_child_enqueue(): void {
		// custom.css loads AFTER the parent's base styles on every page.
		// Dependency 'eskoofy-style' is the parent's get_stylesheet_uri()
		// handle, which WordPress already resolves to THIS child's style.css.
		wp_enqueue_style(
			'esk-child-custom',
			get_stylesheet_directory_uri() . '/custom.css',
			array( 'eskoofy-style' ),
			wp_get_theme()->get( 'Version' )
		);

		// Drop your own JS here (runs in the footer, deferred).
		wp_enqueue_script(
			'esk-child-custom',
			get_stylesheet_directory_uri() . '/custom.js',
			array( 'esk-main' ),
			wp_get_theme()->get( 'Version' ),
			array( 'in_footer' => true, 'strategy' => 'defer' )
		);
	}
}
add_action( 'wp_enqueue_scripts', 'esk_child_enqueue', 30 );

/* ─── Custom admin styles (Eskoofy dashboard + WP admin) ─────────────────── */

if ( ! function_exists( 'esk_child_admin_enqueue' ) ) {
	function esk_child_admin_enqueue( string $hook ): void {
		$is_esk = strpos( $hook, 'esk-' ) !== false || 'toplevel_page_esk-dashboard' === $hook;
		$dep    = $is_esk ? 'eskoofy-admin-shell-style' : 'wp-admin';

		wp_enqueue_style(
			'esk-child-admin-custom',
			get_stylesheet_directory_uri() . '/custom-admin.css',
			array( $dep ),
			wp_get_theme()->get( 'Version' )
		);
	}
}
add_action( 'admin_enqueue_scripts', 'esk_child_admin_enqueue' );