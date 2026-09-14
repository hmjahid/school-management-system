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

/* ─── Session init (used by esk_flash / esk_get_flash for front-end          ──────
     flash messages such as contact, admission and newsletter feedback) ────── */

add_action(
	'init',
	static function (): void {
		if ( ! session_id() && ! headers_sent() ) {
			session_start();
		}
	},
	1
);

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

	wp_enqueue_style(
		'eskoofy-admin-shell-style',
		get_template_directory_uri() . '/inc/admin-shell.css',
		array( 'eskoofy-admin-style' ),
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

/* ─── Activation hook: create database tables + demo users ────────────────── */

function esk_theme_activation(): void {
	if ( function_exists( 'esk_create_tables' ) ) {
		esk_create_tables();
	}
	esk_create_demo_users();
}
add_action( 'after_switch_theme', 'esk_theme_activation' );

/**
 * Register custom WordPress roles matching the eskoofy-* user model.
 */
function esk_register_roles(): void {
	add_role( 'teacher',     __( 'Teacher', 'eskoofy' ),     [ 'read' => true ] );
	add_role( 'accountant',  __( 'Accountant', 'eskoofy' ),  [ 'read' => true ] );
	add_role( 'librarian',   __( 'Librarian', 'eskoofy' ),   [ 'read' => true ] );
}
add_action( 'init', 'esk_register_roles' );

/**
 * Create WordPress users matching docs/DEMO-CREDENTIALS.md so all three
 * products (app, php, theme) share the same demo accounts.
 *
 * Idempotent: skips users that already exists; updates password if changed.
 */
function esk_create_demo_users(): void {
	if ( ! function_exists( 'wp_create_user' ) && ! function_exists( 'wp_insert_user' ) ) {
		return;
	}

	$accounts = [
		[ 'username' => 'admin',            'email' => 'admin@school.com',          'password' => 'ChangeMe!2026$Tr0ng', 'role' => 'administrator', 'display' => 'Super Administrator' ],
		[ 'username' => 'principal',        'email' => 'principal@school.com',       'password' => 'principal123',       'role' => 'administrator', 'display' => 'School Principal' ],
		[ 'username' => 'teacher.john',     'email' => 'teacher.john@school.com',   'password' => 'teach1234',         'role' => 'teacher',       'display' => 'John Smith' ],
		[ 'username' => 'teacher.sarah',    'email' => 'teacher.sarah@school.com',  'password' => 'teach5678',         'role' => 'teacher',       'display' => 'Sarah Johnson' ],
		[ 'username' => 'accountant',       'email' => 'accountant@school.com',      'password' => 'accountant123',     'role' => 'accountant',    'display' => 'Demo Accountant' ],
		[ 'username' => 'librarian',        'email' => 'librarian@school.com',       'password' => 'librarian123',      'role' => 'librarian',     'display' => 'Demo Librarian' ],
	];

	foreach ( $accounts as $acct ) {
		$existing = get_user_by( 'login', $acct['username'] );
		if ( ! $existing ) {
			$existing = get_user_by( 'email', $acct['email'] );
		}
		if ( $existing ) {
			wp_update_user( [
				'ID'           => $existing->ID,
				'user_email'   => $acct['email'],
				'user_pass'    => $acct['password'],
				'role'         => $acct['role'],
				'display_name' => $acct['display'],
			] );
		} else {
			$user_id = wp_create_user( $acct['username'], $acct['password'], $acct['email'] );
			if ( ! is_wp_error( $user_id ) ) {
				wp_update_user( [
					'ID'           => $user_id,
					'role'         => $acct['role'],
					'display_name' => $acct['display'],
				] );
			}
		}
	}
}

/* ─── Handle public form submissions ─────────────────────────────────────── */

if ( isset( $_POST['esk_admission_submit'] ) && ! is_admin() ) {
	$nonce = isset( $_POST['esk_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['esk_nonce'] ) ) : '';
	if ( wp_verify_nonce( $nonce, 'esk_admission_form' ) ) {
		global $wpdb;

		$photo_path = '';
		if ( ! empty( $_FILES['photo']['tmp_name'] ) && is_uploaded_file( $_FILES['photo']['tmp_name'] ) ) {
			$upload = esk_upload_file( $_FILES['photo'], 'eskoofy/admissions' );
			if ( $upload ) {
				$photo_path = $upload['url'];
			}
		}

		$wpdb->insert( $wpdb->prefix . 'esk_admissions', array(
			'application_number'  => esk_generate_number( 'APP', 'admissions' ),
			'academic_session_id' => 1,
			'batch_id'            => 1,
			'first_name'          => sanitize_text_field( wp_unslash( $_POST['first_name'] ?? '' ) ),
			'last_name'           => sanitize_text_field( wp_unslash( $_POST['last_name'] ?? '' ) ),
			'gender'              => sanitize_text_field( wp_unslash( $_POST['gender'] ?? 'male' ) ),
			'date_of_birth'       => sanitize_text_field( wp_unslash( $_POST['date_of_birth'] ?? '' ) ),
			'photo'               => $photo_path,
			'email'               => sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ),
			'phone'               => sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) ),
			'address'             => sanitize_textarea_field( wp_unslash( $_POST['address'] ?? '' ) ),
			'city'                => sanitize_text_field( wp_unslash( $_POST['city'] ?? '' ) ),
			'postal_code'         => sanitize_text_field( wp_unslash( $_POST['postal_code'] ?? '' ) ),
			'father_name'         => sanitize_text_field( wp_unslash( $_POST['father_name'] ?? '' ) ),
			'father_phone'        => sanitize_text_field( wp_unslash( $_POST['father_phone'] ?? '' ) ),
			'mother_name'         => sanitize_text_field( wp_unslash( $_POST['mother_name'] ?? '' ) ),
			'mother_phone'        => sanitize_text_field( wp_unslash( $_POST['mother_phone'] ?? '' ) ),
			'status'              => 'submitted',
			'submitted_at'        => current_time( 'mysql' ),
		) );

		esk_flash( 'success', __( 'Application submitted successfully!', 'eskoofy' ) );
		wp_safe_redirect( remove_query_arg() );
		exit;
	}
}

if ( isset( $_POST['esk_contact_submit'] ) && ! is_admin() ) {
	$nonce = isset( $_POST['esk_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['esk_nonce'] ) ) : '';
	if ( wp_verify_nonce( $nonce, 'esk_contact_form' ) ) {
		global $wpdb;

		$wpdb->insert( $wpdb->prefix . 'esk_contact_submissions', array(
			'name'    => sanitize_text_field( wp_unslash( $_POST['contact_name'] ?? '' ) ),
			'email'   => sanitize_email( wp_unslash( $_POST['contact_email'] ?? '' ) ),
			'phone'   => sanitize_text_field( wp_unslash( $_POST['contact_phone'] ?? '' ) ),
			'subject' => sanitize_text_field( wp_unslash( $_POST['contact_subject'] ?? '' ) ),
			'message' => sanitize_textarea_field( wp_unslash( $_POST['contact_message'] ?? '' ) ),
		) );

		esk_flash( 'success', __( 'Message sent successfully!', 'eskoofy' ) );
		wp_safe_redirect( remove_query_arg() );
		exit;
	}
}

/* ─── Public login (system login at /login/, not wp-login.php) ───────────── */

add_action(
	'init',
	static function (): void {
		if ( ! isset( $_POST['esk_login_submit'] ) || is_admin() ) {
			return;
		}
		if ( ! isset( $_POST['esk_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['esk_nonce'] ) ), 'esk_login_form' ) ) {
			return;
		}

		$login = sanitize_user( wp_unslash( $_POST['esk_login'] ?? '' ) );
		$pass  = (string) wp_unslash( $_POST['esk_password'] ?? '' );

		if ( '' === $login || '' === $pass ) {
			esk_flash( 'login_error', __( 'Please enter your username and password.', 'eskoofy' ) );
			wp_safe_redirect( home_url( '/login/' ) );
			exit;
		}

		// Accept username or email (demo credentials are documented by email).
		if ( false !== strpos( $login, '@' ) ) {
			$by_email = get_user_by( 'email', $login );
			if ( $by_email ) {
				$login = $by_email->user_login;
			}
		}

		$user = wp_signon(
			array(
				'user_login'    => $login,
				'user_password' => $pass,
				'remember'      => true,
			),
			is_ssl()
		);

		if ( is_wp_error( $user ) ) {
			esk_flash( 'login_error', __( 'Invalid username or password.', 'eskoofy' ) );
			wp_safe_redirect( home_url( '/login/' ) );
			exit;
		}

		$redirect = home_url( '/dashboard/' );
		$requested = isset( $_POST['redirect_to'] ) ? sanitize_url( wp_unslash( $_POST['redirect_to'] ) ) : '';
		if ( '' !== $requested && false !== strpos( $requested, home_url() ) && false !== strpos( $requested, '/dashboard/' ) ) {
			$redirect = $requested;
		}

		wp_safe_redirect( $redirect );
		exit;
	},
	20
);

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

/* ─── Front-end: Google Fonts + main.js + accent CSS variable ──────────── */

function esk_enqueue_extras(): void {
	wp_enqueue_style(
		'esk-google-fonts',
		'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Bengali:wght@400;500;600;700;800&display=swap',
		array(),
		null
	);

	wp_enqueue_script(
		'esk-main',
		get_template_directory_uri() . '/assets/js/main.js',
		array(),
		wp_get_theme()->get( 'Version' ),
		array( 'in_footer' => true, 'strategy' => 'defer' )
	);

	$accent      = esk_theme_primary();
	$accent_dark = esk_hex_offset( $accent, -30 );
	$warm        = esk_theme_secondary();

	wp_add_inline_style(
		'eskoofy-style',
		":root { --esk-accent: {$accent}; --esk-accent-dark: {$accent_dark}; --esk-warm: {$warm}; }"
	);

	if ( is_singular() && ! is_admin() ) {
		echo '<script>' . "\n";
		echo 'if("serviceWorker" in navigator){window.addEventListener("load",function(){navigator.serviceWorker.register("' . esc_url( home_url( '/sw.js' ) ) . '")});}' . "\n";
		echo '</script>' . "\n";
	}
}
add_action( 'wp_enqueue_scripts', 'esk_enqueue_extras', 20 );

/**
 * Lightly shift a hex colour by $offset (0-255 per channel).
 */
function esk_hex_offset( string $hex, int $offset ): string {
	$hex = ltrim( $hex, '#' );
	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}
	$rgb = array_map( 'hexdec', str_split( substr( $hex, 0, 6 ), 2 ) );
	$rgb = array_map(
		static fn( int $v ): int => max( 0, min( 255, $v + $offset ) ),
		$rgb
	);
	return '#' . implode( '', array_map( static fn( int $v ): string => sprintf( '%02x', $v ), $rgb ) );
}

/* ─── Public: theme-color meta ─────────────────────────────────────────── */

function esk_theme_color_meta(): void {
	if ( is_admin() ) {
		return;
	}
	$color = esc_attr( esk_theme_primary() );
	echo '<meta name="theme-color" content="' . $color . '">' . "\n";
}
add_action( 'wp_head', 'esk_theme_color_meta', 1 );

/* ─── Public: schema.org JSON-LD (School) ──────────────────────────────── */

function esk_schema_json_ld(): void {
	if ( is_admin() ) {
		return;
	}

	$name = esk_school( 'school_name' ) ?: get_bloginfo( 'name' );
	$desc = esk_school( 'school_tagline' ) ?: get_bloginfo( 'description', 'display' );

	$schema = array(
		'@context'    => 'https://schema.org',
		'@type'       => 'School',
		'name'        => $name,
		'description' => $desc,
		'url'         => home_url( '/' ),
	);

	$logo = esk_school( 'school_logo' );
	if ( $logo ) {
		$schema['logo'] = $logo;
	}
	$addr = esk_school( 'school_address' );
	if ( $addr ) {
		$schema['address'] = array( '@type' => 'PostalAddress', 'streetAddress' => $addr );
	}
	$phone = esk_school( 'school_phone' );
	if ( $phone ) {
		$schema['telephone'] = $phone;
	}
	$email = esk_school( 'school_email' );
	if ( $email ) {
		$schema['email'] = $email;
	}
	$established = (int) esk_school( 'established_year' );
	if ( $established > 1900 ) {
		$schema['foundingDate'] = (string) $established;
	}

	echo '<script type="application/ld+json">'
		. wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
		. '</script>' . "\n";
}
add_action( 'wp_head', 'esk_schema_json_ld', 2 );

/* ─── Public: language switcher (GET param) ────────────────────────────── */

function esk_handle_lang_toggle(): void {
	if ( is_admin() || ! isset( $_GET['esk_lang'] ) ) {
		return;
	}

	$lang = sanitize_text_field( wp_unslash( $_GET['esk_lang'] ) );

	if ( in_array( $lang, array( 'en', 'bn_BD', 'en_US', 'en_GB' ), true ) ) {
		update_option( 'esk_locale', $lang );
		wp_safe_redirect( esc_url_raw( remove_query_arg( 'esk_lang' ) ) );
		exit;
	}
}
add_action( 'init', 'esk_handle_lang_toggle' );

/* ─── Public: newsletter subscribe ─────────────────────────────────────── */

function esk_newsletter_subscribe(): void {
	if ( is_admin() || ! isset( $_POST['esk_newsletter'] ) ) {
		return;
	}

	$email = sanitize_email( wp_unslash( $_POST['esk_newsletter'] ) );

	if ( is_email( $email ) ) {
		$subs = get_option( 'esk_newsletter_subscribers', array() );
		if ( ! is_array( $subs ) ) {
			$subs = array();
		}
		if ( ! in_array( $email, $subs, true ) ) {
			$subs[] = $email;
			update_option( 'esk_newsletter_subscribers', $subs );
		}
		esk_flash( 'success', __( 'Thank you for subscribing!', 'eskoofy' ) );
	} else {
		esk_flash( 'error', __( 'Please enter a valid email address.', 'eskoofy' ) );
	}

	wp_safe_redirect( esc_url_raw( remove_query_arg( 'esk_newsletter' ) ) );
	exit;
}
add_action( 'init', 'esk_newsletter_subscribe' );

/* ─── PWA: serve manifest.json + sw.js + offline from template_redirect ── */

function esk_pwa_routes(): void {
	if ( is_admin() ) {
		return;
	}

	global $wp;
	$request = trim( (string) ( $wp->request ?? '' ), '/' );

	if ( 'manifest.json' === $request ) {
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Cache-Control: public, max-age=0' );

		$name  = esk_school( 'school_name' ) ?: get_bloginfo( 'name' );
		$color = esk_theme_primary();
		$manifest = array(
			'name'             => $name,
			'short_name'       => wp_trim_words( $name, 2, '' ),
			'description'      => esk_school( 'school_tagline' ) ?: get_bloginfo( 'description', 'display' ),
			'start_url'        => home_url( '/' ),
			'display'          => 'standalone',
			'background_color' => '#ffffff',
			'theme_color'      => $color,
			'icons'            => array(
				array(
					'src'    => get_template_directory_uri() . '/assets/icons/icon-192.png',
					'sizes'  => '192x192',
					'type'   => 'image/png',
				),
				array(
					'src'    => get_template_directory_uri() . '/assets/icons/icon-512.png',
					'sizes'  => '512x512',
					'type'   => 'image/png',
					'purpose' => 'any maskable',
				),
			),
		);

		echo wp_json_encode( $manifest, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ); // phpcs:ignore
		exit;
	}

	if ( 'sw.js' === $request ) {
		header( 'Content-Type: application/javascript; charset=utf-8' );
		header( 'Cache-Control: public, max-age=0' );
		$sw = get_template_directory() . '/pwa/sw.js';
		if ( file_exists( $sw ) ) {
			readfile( $sw ); // phpcs:ignore
		}
		exit;
	}

	if ( 'offline' === $request ) {
		header( 'Content-Type: text/html; charset=utf-8' );
		http_response_code( 503 );
		$file = get_template_directory() . '/pwa/offline.html';
		if ( file_exists( $file ) ) {
			readfile( $file ); // phpcs:ignore
		}
		exit;
	}
}
add_action( 'template_redirect', 'esk_pwa_routes' );