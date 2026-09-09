<?php
/**
 * Eskoofy WordPress theme functions.
 *
 * Plugin-theme hybrid: includes admin, REST API, shortcodes,
 * payment gateways, and all custom database tables.
 *
 * @package Eskoofy
 */

declare(strict_types=1);

/* ─── Include inc/ loader (loads helpers, database, CPTs, admin,
     AJAX, REST, shortcodes, widgets, customizer, gateways) ────── */

$esk_inc = __DIR__ . '/inc/plugin-loader.php';
if ( file_exists( $esk_inc ) ) {
	require_once $esk_inc;
}

/* ─── Theme setup ────────────────────────────────────────────────────────── */

if ( ! function_exists( 'eskoofy_theme_setup' ) ) {
	function eskoofy_theme_setup(): void {
		load_theme_textdomain( 'eskoofy', get_template_directory() . '/languages' );

		add_theme_support( 'automatic-feed-links' );
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support(
			'html5',
			array(
				'search-form',
				'comment-form',
				'comment-list',
				'gallery',
				'caption',
				'style',
				'script',
			)
		);
		add_theme_support( 'customize-selective-refresh-widgets' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'editor-styles' );

		add_image_size( 'eskoofy-featured', 1200, 630, true );
		add_image_size( 'eskoofy-thumbnail', 400, 300, true );

		register_nav_menus(
			array(
				'primary' => esc_html__( 'Primary menu', 'eskoofy' ),
				'footer'  => esc_html__( 'Footer menu', 'eskoofy' ),
			)
		);
	}
}
add_action( 'after_setup_theme', 'eskoofy_theme_setup' );

/* ─── Widget areas ───────────────────────────────────────────────────────── */

if ( ! function_exists( 'eskoofy_widgets_init' ) ) {
	function eskoofy_widgets_init(): void {
		// Legacy widget areas — kept for backward compatibility.
		// Primary widget areas are registered in inc/widgets.php via
		// esk_register_widget_areas() on the 'widgets_init' hook.
		register_sidebar(
			array(
				'name'          => esc_html__( 'Sidebar', 'eskoofy' ),
				'id'            => 'sidebar-1-legacy',
				'description'   => esc_html__( 'Legacy sidebar widget area.', 'eskoofy' ),
				'before_widget' => '<div id="%1$s" class="eskoofy-widget %2$s">',
				'after_widget'  => '</div>',
				'before_title'  => '<h3 class="eskoofy-widget-title">',
				'after_title'   => '</h3>',
			)
		);
	}
}
add_action( 'widgets_init', 'eskoofy_widgets_init' );

/* ─── Front-end scripts & styles ─────────────────────────────────────────── */

if ( ! function_exists( 'eskoofy_scripts' ) ) {
	function eskoofy_scripts(): void {
		wp_enqueue_style(
			'eskoofy-style',
			get_stylesheet_uri(),
			array(),
			wp_get_theme()->get( 'Version' )
		);

		wp_enqueue_style(
			'eskoofy-admin-style',
			get_template_directory_uri() . '/inc/admin-style.css',
			array(),
			'1.0.0'
		);
	}
}
add_action( 'wp_enqueue_scripts', 'eskoofy_scripts' );

/* ─── Admin scripts & styles ─────────────────────────────────────────────── */

function esk_admin_enqueue( string $hook ): void {
	if ( strpos( $hook, 'esk-' ) === false && 'toplevel_page_esk-dashboard' !== $hook ) {
		return;
	}

	wp_enqueue_style(
		'eskoofy-admin-style',
		get_template_directory_uri() . '/inc/admin-style.css',
		array( 'wp-admin' ),
		'1.0.0',
		true
	);

	wp_enqueue_script(
		'eskoofy-admin',
		get_template_directory_uri() . '/inc/admin.js',
		array( 'jquery' ),
		'1.0.0',
		true
	);

	wp_localize_script(
		'eskoofy-admin',
		'eskAdmin',
		array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'esk_ajax_nonce' ),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'esk_admin_enqueue' );

/* ─── Activation hook: create database tables ────────────────────────────── */

function esk_theme_activation(): void {
	if ( function_exists( 'esk_create_tables' ) ) {
		esk_create_tables();
	}
}
add_action( 'after_switch_theme', 'esk_theme_activation' );

/* ─── Handle public form submissions ─────────────────────────────────────── */

if ( isset( $_POST['esk_admission_submit'] ) && ! is_admin() ) {
	check_admin_referer( 'esk_admission_form' );
	global $wpdb;

	$wpdb->insert( $wpdb->prefix . 'esk_admissions', array(
		'application_number'  => esk_generate_number( 'APP' ),
		'academic_session_id' => 1,
		'batch_id'            => 1,
		'first_name'          => sanitize_text_field( $_POST['first_name'] ?? '' ),
		'last_name'           => sanitize_text_field( $_POST['last_name'] ?? '' ),
		'gender'              => sanitize_text_field( $_POST['gender'] ?? 'male' ),
		'date_of_birth'       => sanitize_text_field( $_POST['date_of_birth'] ?? '' ),
		'email'               => sanitize_email( $_POST['email'] ?? '' ),
		'phone'               => sanitize_text_field( $_POST['phone'] ?? '' ),
		'address'             => sanitize_textarea_field( $_POST['address'] ?? '' ),
		'city'                => sanitize_text_field( $_POST['city'] ?? '' ),
		'postal_code'         => sanitize_text_field( $_POST['postal_code'] ?? '' ),
		'father_name'         => sanitize_text_field( $_POST['father_name'] ?? '' ),
		'father_phone'        => sanitize_text_field( $_POST['father_phone'] ?? '' ),
		'mother_name'         => sanitize_text_field( $_POST['mother_name'] ?? '' ),
		'mother_phone'        => sanitize_text_field( $_POST['mother_phone'] ?? '' ),
		'status'              => 'submitted',
		'submitted_at'        => current_time( 'mysql' ),
	) );

	esk_flash( 'success', __( 'Application submitted successfully!', 'eskoofy' ) );
	wp_safe_redirect( remove_query_arg() );
	exit;
}

if ( isset( $_POST['esk_contact_submit'] ) && ! is_admin() ) {
	check_admin_referer( 'esk_contact_form' );
	global $wpdb;

	$wpdb->insert( $wpdb->prefix . 'esk_contact_submissions', array(
		'name'    => sanitize_text_field( $_POST['contact_name'] ?? '' ),
		'email'   => sanitize_email( $_POST['contact_email'] ?? '' ),
		'phone'   => sanitize_text_field( $_POST['contact_phone'] ?? '' ),
		'subject' => sanitize_text_field( $_POST['contact_subject'] ?? '' ),
		'message' => sanitize_textarea_field( $_POST['contact_message'] ?? '' ),
	) );

	esk_flash( 'success', __( 'Message sent successfully!', 'eskoofy' ) );
	wp_safe_redirect( remove_query_arg() );
	exit;
}

/* ─── Admin menu icon color ──────────────────────────────────────────────── */

function esk_admin_menu_icon(): void {
	?>
	<style>
		#adminmenu .toplevel_page_esk-dashboard .wp-menu-image.dashicons-before:before { content: '\f540'; color: #2563eb; }
		#adminmenu .toplevel_page_esk-dashboard:hover .wp-menu-image.dashicons-before:before,
		#adminmenu .toplevel_page_esk-dashboard.current .wp-menu-image.dashicons-before:before { color: #1d4ed8; }
	</style>
	<?php
}
add_action( 'admin_head', 'esk_admin_menu_icon' );
