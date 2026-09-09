<?php
/**
 * Customizer settings for Eskoofy.
 *
 * @package Eskoofy
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

function esk_register_customizer( WP_Customize_Manager $wp_customize ): void {

	// ─── Panel: Eskoofy Settings ────────────────────────────────
	$wp_customize->add_panel(
		'esk_panel',
		array(
			'title'    => esc_html__( 'Eskoofy Settings', 'eskoofy' ),
			'priority' => 30,
		)
	);

	// ─── Section: School Info ───────────────────────────────────
	$wp_customize->add_section(
		'esk_school_info',
		array(
			'title' => esc_html__( 'School Information', 'eskoofy' ),
			'panel' => 'esk_panel',
		)
	);

	$wp_customize->add_setting( 'esk_school_name', array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ) );
	$wp_customize->add_control( 'esk_school_name', array(
		'label'   => esc_html__( 'School Name', 'eskoofy' ),
		'section' => 'esk_school_info',
		'type'    => 'text',
	) );

	$wp_customize->add_setting( 'esk_school_tagline', array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ) );
	$wp_customize->add_control( 'esk_school_tagline', array(
		'label'   => esc_html__( 'Tagline', 'eskoofy' ),
		'section' => 'esk_school_info',
		'type'    => 'text',
	) );

	$wp_customize->add_setting( 'esk_school_logo', array( 'default' => '', 'sanitize_callback' => 'esc_url_raw' ) );
	$wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'esk_school_logo', array(
		'label'   => esc_html__( 'School Logo', 'eskoofy' ),
		'section' => 'esk_school_info',
	) ) );

	$wp_customize->add_setting( 'esk_school_phone', array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ) );
	$wp_customize->add_control( 'esk_school_phone', array(
		'label'   => esc_html__( 'Phone', 'eskoofy' ),
		'section' => 'esk_school_info',
		'type'    => 'text',
	) );

	$wp_customize->add_setting( 'esk_school_email', array( 'default' => '', 'sanitize_callback' => 'sanitize_email' ) );
	$wp_customize->add_control( 'esk_school_email', array(
		'label'   => esc_html__( 'Email', 'eskoofy' ),
		'section' => 'esk_school_info',
		'type'    => 'email',
	) );

	$wp_customize->add_setting( 'esk_school_address', array( 'default' => '', 'sanitize_callback' => 'sanitize_textarea_field' ) );
	$wp_customize->add_control( 'esk_school_address', array(
		'label'   => esc_html__( 'Address', 'eskoofy' ),
		'section' => 'esk_school_info',
		'type'    => 'textarea',
	) );

	// ─── Section: Colors ────────────────────────────────────────
	$wp_customize->add_section(
		'esk_colors',
		array(
			'title' => esc_html__( 'Eskoofy Colors', 'eskoofy' ),
			'panel' => 'esk_panel',
		)
	);

	$wp_customize->add_setting( 'esk_color_primary', array( 'default' => '#2563eb', 'sanitize_callback' => 'sanitize_hex_color' ) );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'esk_color_primary', array(
		'label'   => esc_html__( 'Primary Color', 'eskoofy' ),
		'section' => 'esk_colors',
	) ) );

	$wp_customize->add_setting( 'esk_color_accent', array( 'default' => '#10b981', 'sanitize_callback' => 'sanitize_hex_color' ) );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'esk_color_accent', array(
		'label'   => esc_html__( 'Accent Color', 'eskoofy' ),
		'section' => 'esk_colors',
	) ) );

	$wp_customize->add_setting( 'esk_color_text', array( 'default' => '#1a1a1a', 'sanitize_callback' => 'sanitize_hex_color' ) );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'esk_color_text', array(
		'label'   => esc_html__( 'Text Color', 'eskoofy' ),
		'section' => 'esk_colors',
	) ) );

	// ─── Section: Footer ────────────────────────────────────────
	$wp_customize->add_section(
		'esk_footer',
		array(
			'title' => esc_html__( 'Footer Settings', 'eskoofy' ),
			'panel' => 'esk_panel',
		)
	);

	$wp_customize->add_setting( 'esk_footer_copyright', array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ) );
	$wp_customize->add_control( 'esk_footer_copyright', array(
		'label'   => esc_html__( 'Copyright Text', 'eskoofy' ),
		'section' => 'esk_footer',
		'type'    => 'text',
	) );

	$wp_customize->add_setting( 'esk_social_facebook', array( 'default' => '', 'sanitize_callback' => 'esc_url_raw' ) );
	$wp_customize->add_control( 'esk_social_facebook', array(
		'label'   => esc_html__( 'Facebook URL', 'eskoofy' ),
		'section' => 'esk_footer',
		'type'    => 'url',
	) );

	$wp_customize->add_setting( 'esk_social_youtube', array( 'default' => '', 'sanitize_callback' => 'esc_url_raw' ) );
	$wp_customize->add_control( 'esk_social_youtube', array(
		'label'   => esc_html__( 'YouTube URL', 'eskoofy' ),
		'section' => 'esk_footer',
		'type'    => 'url',
	) );

	// ─── Section: Homepage ──────────────────────────────────────
	$wp_customize->add_section(
		'esk_homepage',
		array(
			'title' => esc_html__( 'Homepage Settings', 'eskoofy' ),
			'panel' => 'esk_panel',
		)
	);

	$wp_customize->add_setting( 'esk_hero_title', array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ) );
	$wp_customize->add_control( 'esk_hero_title', array(
		'label'   => esc_html__( 'Hero Title', 'eskoofy' ),
		'section' => 'esk_homepage',
		'type'    => 'text',
	) );

	$wp_customize->add_setting( 'esk_hero_tagline', array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ) );
	$wp_customize->add_control( 'esk_hero_tagline', array(
		'label'   => esc_html__( 'Hero Tagline', 'eskoofy' ),
		'section' => 'esk_homepage',
		'type'    => 'text',
	) );

	$wp_customize->add_setting( 'esk_hero_cta_text', array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ) );
	$wp_customize->add_control( 'esk_hero_cta_text', array(
		'label'   => esc_html__( 'CTA Button Text', 'eskoofy' ),
		'section' => 'esk_homepage',
		'type'    => 'text',
	) );

	$wp_customize->add_setting( 'esk_hero_cta_url', array( 'default' => '', 'sanitize_callback' => 'esc_url_raw' ) );
	$wp_customize->add_control( 'esk_hero_cta_url', array(
		'label'   => esc_html__( 'CTA Button URL', 'eskoofy' ),
		'section' => 'esk_homepage',
		'type'    => 'url',
	) );

	$wp_customize->add_setting( 'esk_currency', array( 'default' => '৳', 'sanitize_callback' => 'sanitize_text_field' ) );
	$wp_customize->add_control( 'esk_currency', array(
		'label'   => esc_html__( 'Currency Symbol', 'eskoofy' ),
		'section' => 'esk_school_info',
		'type'    => 'text',
	) );
}
add_action( 'customize_register', 'esk_register_customizer' );

/**
 * Output customizer CSS.
 */
function esk_customizer_css(): void {
	$primary = get_theme_mod( 'esk_color_primary', '#2563eb' );
	$accent  = get_theme_mod( 'esk_color_accent', '#10b981' );
	$text    = get_theme_mod( 'esk_color_text', '#1a1a1a' );

	printf(
		'<style>
		:root { --esk-primary: %s; --esk-accent: %s; --esk-text: %s; }
		.eskoofy-button { background-color: %s; }
		.eskoofy-button:hover { background-color: %s; }
		.eskoofy-pagination .current { background-color: %s; border-color: %s; }
		</style>',
		esc_attr( $primary ),
		esc_attr( $accent ),
		esc_attr( $text ),
		esc_attr( $primary ),
		esc_attr( $primary ),
		esc_attr( $primary ),
		esc_attr( $primary )
	);
}
add_action( 'wp_head', 'esk_customizer_css' );
