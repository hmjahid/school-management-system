<?php
/**
 * Helper functions for Eskoofy.
 *
 * @package Eskoofy
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

if ( ! function_exists( 'esk_e' ) ) {
	/**
	 * Escape output for display.
	 */
	function esk_e( string $text ): void {
		echo esc_html( $text );
	}
}

if ( ! function_exists( 'esk_esc' ) ) {
	/**
	 * Return escaped string.
	 */
	function esk_esc( string $text ): string {
		return esc_html( $text );
	}
}

if ( ! function_exists( 'esk_format_currency' ) ) {
	/**
	 * Format amount with currency symbol.
	 */
	function esk_format_currency( $amount, string $currency = '' ): string {
		if ( '' === $currency ) {
			$currency = get_option( 'esk_currency', '৳' );
		}
		return $currency . ' ' . number_format( (float) $amount, 2 );
	}
}

if ( ! function_exists( 'esk_generate_number' ) ) {
	/**
	 * Generate a unique prefixed number (admission, invoice, etc.).
	 *
	 * Looks at the maximum numeric portion of existing numbers with the
	 * given prefix and increments. Defaults the table via prefix->table map.
	 */
	function esk_generate_number( string $prefix = 'ADM', ?string $table = null ): string {
		global $wpdb;

		$map = array(
			'ADM' => 'students',
			'APP' => 'admissions',
			'INV' => 'payments',
			'FPT' => 'fee_payments',
			'TRN' => 'hostel_rooms',
			'JOB' => 'job_applications',
		);

		if ( null === $table ) {
			$table = $map[ $prefix ] ?? 'students';
		}

		$full_table = $wpdb->prefix . 'esk_' . $table;

		$max = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT MAX(CAST(SUBSTRING(number, %d) AS UNSIGNED)) FROM (SELECT CONCAT(%s, '-', LPAD(id, 6, '0')) AS number FROM {$full_table}) sub",
				5,
				$prefix
			)
		);

		return $prefix . '-' . str_pad( (string) ( $max + 1 ), 6, '0', STR_PAD_LEFT );
	}
}

if ( ! function_exists( 'esk_upload_file' ) ) {
	/**
	 * Handle file upload.
	 */
	function esk_upload_file( array $file, string $subdir = 'eskoofy-uploads' ): array|false {
		if ( empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
			return false;
		}
		$upload_dir = wp_upload_dir();
		$dir        = trailingslashit( $upload_dir['path'] ) . $subdir;
		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
		}
		$filename   = sanitize_file_name( $file['name'] );
		$move_to    = $dir . '/' . $filename;
		if ( ! move_uploaded_file( $file['tmp_name'], $move_to ) ) {
			return false;
		}
		return array(
			'url'      => str_replace( wp_upload_dir()['basedir'], wp_upload_dir()['baseurl'], $move_to ),
			'path'     => $move_to,
			'filename' => $filename,
			'type'     => $file['type'],
			'size'     => $file['size'],
		);
	}
}

if ( ! function_exists( 'esk_flash' ) ) {
	/**
	 * Set a flash message.
	 */
	function esk_flash( string $key, string $value ): void {
		if ( ! session_id() ) {
			session_start();
		}
		$_SESSION[ '_esk_flash_' . $key ] = $value;
	}
}

if ( ! function_exists( 'esk_get_flash' ) ) {
	/**
	 * Get and clear a flash message.
	 */
	function esk_get_flash( string $key ): string {
		if ( ! session_id() ) {
			session_start();
		}
		$key_full = '_esk_flash_' . $key;
		$value    = isset( $_SESSION[ $key_full ] ) ? sanitize_text_field( wp_unslash( $_SESSION[ $key_full ] ) ) : '';
		unset( $_SESSION[ $key_full ] );
		return $value;
	}
}

if ( ! function_exists( 'esk_date_format' ) ) {
	/**
	 * Format a date string.
	 */
	function esk_date_format( string $date, string $format = 'd M, Y' ): string {
		if ( empty( $date ) ) {
			return '';
		}
		$timestamp = strtotime( $date );
		return $timestamp ? wp_date( $format, $timestamp ) : $date;
	}
}

if ( ! function_exists( 'esk_site_ui' ) ) {
	/**
	 * Get a translation string (or array) from the site UI language file.
	 *
	 * Looks for languages/{locale}/site_ui.php, then falls back to the
	 * base language code (e.g. `bn` -> `bn_BD`) and finally `en_US`,
	 * mirroring the app's deep-merge i18n behaviour.
	 *
	 * @param  string $key     Dot-notation key, e.g. "home.hero_headline".
	 * @param  mixed  $default Value to return when the key is missing.
	 * @return mixed
	 */
	function esk_site_ui( string $key, $default = null ) {
		static $strings = null;
		if ( null === $strings ) {
			$locale = (string) get_option( 'esk_locale', '' );
			if ( '' === $locale ) {
				$locale = get_locale();
			}
			$base    = strtolower( (string) strtok( $locale, '_' ) );
			$paths   = array_map(
				static fn( string $lang ) => ESK_PATH . '/languages/' . $lang . '/site_ui.php',
				array_unique( array( $locale, $base, 'en_US' ) )
			);
			foreach ( $paths as $candidate ) {
				if ( file_exists( $candidate ) ) {
					$strings = include $candidate;
					break;
				}
			}
			if ( ! is_array( $strings ) ) {
				$strings = array();
			}
		}
		$parts = explode( '.', $key );
		$value = $strings;
		foreach ( $parts as $part ) {
			if ( is_array( $value ) && isset( $value[ $part ] ) ) {
				$value = $value[ $part ];
			} else {
				return $default;
			}
		}
		// Global-labels override (Settings → Global Labels): a saved option
		// beats the language file, mirroring the app's label overrides.
		if ( ! is_array( $value ) && is_scalar( $value ) ) {
			$override = get_option( 'esk_label_' . str_replace( '.', '_', $key ), '' );
			if ( is_string( $override ) && '' !== $override ) {
				return $override;
			}
		}
		return $value;
	}
}

if ( ! function_exists( 'esk_get_option' ) ) {
	/**
	 * Get a school option with default.
	 */
	function esk_get_option( string $key, string $default = '' ): string {
		return get_option( 'esk_' . $key, $default );
	}
}

if ( ! function_exists( 'esk_theme_primary' ) ) {
	/**
	 * Resolve the primary accent color.
	 *
	 * Single source of truth so the customizer theme mod, the admin Settings
	 * option and the stylesheet default can never disagree: theme mod first,
	 * then option, then the app default (#2563eb = blue-600).
	 */
	function esk_theme_primary(): string {
		$mod = get_theme_mod( 'esk_color_primary', '' );
		if ( is_string( $mod ) && '' !== trim( $mod ) ) {
			return sanitize_hex_color( trim( $mod ) ) ?: '#2563eb';
		}
		$option = (string) esk_get_option( 'theme_color', '' );
		if ( '' !== $option ) {
			return sanitize_hex_color( $option ) ?: '#2563eb';
		}
		return '#2563eb';
	}
}

if ( ! function_exists( 'esk_theme_secondary' ) ) {
	/**
	 * Resolve the secondary/warm accent color.
	 *
	 * Mirrors the app's orange secondary (#f97316 = orange-500): theme mod
	 * first, then option, then the app default.
	 */
	function esk_theme_secondary(): string {
		$mod = get_theme_mod( 'esk_color_accent', '' );
		if ( is_string( $mod ) && '' !== trim( $mod ) ) {
			return sanitize_hex_color( trim( $mod ) ) ?: '#f97316';
		}
		$option = (string) esk_get_option( 'theme_secondary_color', '' );
		if ( '' !== $option ) {
			return sanitize_hex_color( $option ) ?: '#f97316';
		}
		return '#f97316';
	}
}

if ( ! function_exists( 'esk_school' ) ) {
	/**
	 * Resolve a school setting: WP option first, then customizer theme mod,
	 * then fallback. Mirrors the app's merged settings lookup.
	 */
	function esk_school( string $key, string $default = '' ): string {
		$option = get_option( 'esk_' . $key, '' );
		if ( '' !== $option ) {
			return (string) $option;
		}
		$mod = get_theme_mod( 'esk_' . $key, '' );
		return '' !== $mod ? (string) $mod : $default;
	}
}

if ( ! function_exists( 'esk_home_section' ) ) {
	/**
	 * Fetch a row from esk_website_contents for the homepage (page='home').
	 *
	 * @return object|null Row object or null when missing / table absent.
	 */
	function esk_home_section( string $section ) {
		global $wpdb;

		$table = $wpdb->prefix . 'esk_website_contents';
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
			return null;
		}

		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$table} WHERE page = 'home' AND section = %s LIMIT 1",
			$section
		) );
	}
}

if ( ! function_exists( 'esk_admissions_open' ) ) {
	/**
	 * Whether the admissions round is currently open.
	 */
	function esk_admissions_open(): bool {
		global $wpdb;

		$table = $wpdb->prefix . 'esk_admission_settings';
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
			return false;
		}

		return (bool) $wpdb->get_var( "SELECT is_open FROM {$table} ORDER BY id DESC LIMIT 1" );
	}
}

if ( ! function_exists( 'esk_initials' ) ) {
	/**
	 * Uppercase initials for the first words of a name.
	 */
	function esk_initials( string $name ): string {
		$parts = preg_split( '/\s+/', trim( $name ) );
		$parts = array_slice( (array) $parts, 0, 2 );
		$initials = '';
		foreach ( $parts as $part ) {
			$initials .= strtoupper( mb_substr( (string) $part, 0, 1 ) );
		}
		return $initials !== '' ? $initials : '?';
	}
}

if ( ! function_exists( 'esk_social_icons' ) ) {
	/**
	 * Social icon SVGs keyed by platform (mirrors the app's partial).
	 */
	function esk_social_icons(): array {
		return array(
			'facebook' => '<svg class="esk-icon" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>',
			'instagram' => '<svg class="esk-icon" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>',
			'twitter'   => '<svg class="esk-icon" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>',
			'youtube'   => '<svg class="esk-icon" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>',
			'linkedin'  => '<svg class="esk-icon" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>',
		);
	}
}

if ( ! function_exists( 'esk_social_profiles' ) ) {
	/**
	 * Active social profiles (URL set + platform toggle on), mirroring the
	 * app's social-links partial. Includes LinkedIn and X/Twitter.
	 *
	 * @return array<int,array{key:string,label:string,url:string,svg:string}>
	 */
	function esk_social_profiles(): array {
		$icons = esk_social_icons();
		$order = array( 'facebook', 'instagram', 'twitter', 'youtube', 'linkedin' );
		// Demo defaults mirror the app's WebsiteSettingSeeder so the icons render
		// out of the box; admins can override each URL or toggle it off.
		$defaults = array(
			'facebook' => 'https://facebook.com/exampleschool',
			'instagram' => 'https://instagram.com/exampleschool',
			'twitter'  => 'https://twitter.com/exampleschool',
			'youtube'  => 'https://youtube.com/exampleschool',
			'linkedin' => 'https://linkedin.com/school/exampleschool',
		);
		$out   = array();
		foreach ( $order as $key ) {
			$url = (string) esk_school( 'social_' . $key . '_url', $defaults[ $key ] );
			if ( '' === $url ) {
				$url = (string) esk_school( 'social_' . $key, $defaults[ $key ] );
			}
			if ( '' === $url ) {
				continue;
			}
			$show = esk_get_option( 'social_show_' . $key, '1' );
			if ( '0' === $show || 'no' === $show || 'off' === $show ) {
				continue;
			}
			$labels = array(
				'facebook' => 'Facebook',
				'instagram' => 'Instagram',
				'twitter'  => 'X',
				'youtube'  => 'YouTube',
				'linkedin' => 'LinkedIn',
			);
			$out[] = array(
				'key'   => $key,
				'label' => $labels[ $key ],
				'url'   => $url,
				'svg'   => $icons[ $key ],
			);
		}
		return $out;
	}
}

if ( ! function_exists( 'esk_csrf_field' ) ) {
	/**
	 * Output a nonce field for CSRF protection.
	 */
	function esk_csrf_field( string $action = 'esk_nonce' ): void {
		wp_nonce_field( $action, 'esk_nonce' );
	}
}

if ( ! function_exists( 'esk_verify_nonce' ) ) {
	/**
	 * Verify a nonce or die.
	 */
	function esk_verify_nonce( string $action = 'esk_nonce' ): bool {
		if ( ! isset( $_POST['esk_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['esk_nonce'] ) ), $action ) ) {
			wp_die( 'Security check failed' );
		}
		return true;
	}
}

/**
 * Fetch website-contents rows for a given page slug, guarded against missing table.
 */
if ( ! function_exists( 'esk_page_rows' ) ) {
	function esk_page_rows( string $page ): array {
		global $wpdb;
		$table = $wpdb->prefix . 'esk_website_contents';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = (string) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
		if ( '' === $table_exists ) {
			return array();
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE page = %s ORDER BY sort_order, section", $page ) );
	}
}

/**
 * Render sections from esk_website_contents for a page.
 */
if ( ! function_exists( 'esk_render_page_content' ) ) {
	function esk_render_page_content( string $page, string $fallback_option = '' ): void {
		$rows = esk_page_rows( $page );
		if ( ! empty( $rows ) ) {
			foreach ( $rows as $row ) {
				if ( empty( $row->title ) && empty( $row->content ) ) {
					continue;
				}
				echo '<section class="esk-page-section reveal">';
				if ( ! empty( $row->title ) ) {
					echo '<h2 class="esk-page-section-title">' . esc_html( $row->title ) . '</h2>';
				}
				if ( ! empty( $row->image ) ) {
					echo '<img class="esk-page-section-image" src="' . esc_url( $row->image ) . '" alt="' . esc_attr( $row->title ?? '' ) . '">';
				}
				if ( ! empty( $row->content ) ) {
					echo '<div class="esk-page-section-content">' . wp_kses_post( $row->content ) . '</div>';
				}
				echo '</section>';
			}
		} elseif ( $fallback_option !== '' ) {
			$fallback = get_option( $fallback_option, '' );
			if ( '' !== $fallback ) {
				echo '<section class="esk-page-section"><div class="esk-page-section-content">' . wp_kses_post( $fallback ) . '</div></section>';
			}
		}
	}
}

/**
 * Fetch first title from esk_website_contents for a page slug.
 */
if ( ! function_exists( 'esk_page_title' ) ) {
	function esk_page_title( string $page, string $fallback = '' ): string {
		global $wpdb;
		$table = $wpdb->prefix . 'esk_website_contents';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = (string) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
		if ( '' === $table_exists ) {
			return $fallback;
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT title FROM {$table} WHERE page = %s AND title != '' ORDER BY sort_order LIMIT 1", $page ) );
		return $row && ! empty( $row->title ) ? $row->title : $fallback;
	}
}

/* Contact form handling lives in functions.php init hook (esk_post_contact). */

/* ─── RBAC helper ────────────────────────────────────────────────── */

/**
 * Whether the current user may perform an esk capability.
 *
 * Capability map is stored in the `esk_role_caps` option as
 * role_slug => array of capability keys. Administrators always pass.
 */
if ( ! function_exists( 'esk_can' ) ) {
	function esk_can( string $cap ): bool {
		$user = wp_get_current_user();
		if ( ! $user || ! $user->exists() ) {
			return false;
		}
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}
		$map = (array) get_option( 'esk_role_caps', array() );
		foreach ( (array) $user->roles as $role ) {
			$caps = (array) ( $map[ $role ] ?? array() );
			if ( in_array( $cap, $caps, true ) ) {
				return true;
			}
		}
		return false;
	}
}

/**
 * Whether the current user may access the management dashboard at all.
 */
if ( ! function_exists( 'esk_can_access_dashboard' ) ) {
	function esk_can_access_dashboard(): bool {
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}
		$user = wp_get_current_user();
		if ( ! $user || ! $user->exists() ) {
			return false;
		}
		$map = (array) get_option( 'esk_role_caps', array() );
		foreach ( (array) $user->roles as $role ) {
			if ( ! empty( $map[ $role ] ) ) {
				return true;
			}
		}
		return false;
	}
}

/**
 * Encrypt a gateway secret (API key / secret / webhook secret) at rest using
 * AES-256-GCM with a key derived from the WordPress salts. Returns an
 * `esk1:`-prefixed value so decryption can recognise both legacy plaintext
 * and encrypted rows.
 */
function esk_encrypt_secret( string $plain ): string {
	if ( '' === $plain || str_starts_with( $plain, 'esk1:' ) ) {
		return $plain;
	}
	$key  = (string) wp_salt( 'auth' );
	$iv   = random_bytes( 12 );
	$tag  = '';
	$ct   = openssl_encrypt( $plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16 );
	if ( false === $ct ) {
		return $plain;
	}
	return 'esk1:' . base64_encode( $iv . $tag . $ct );
}

/**
 * Decrypt a gateway secret stored with esk_encrypt_secret(). Legacy plaintext
 * values (no `esk1:` prefix) are returned unchanged so existing installs keep
 * working until their rows are migrated.
 */
function esk_decrypt_secret( ?string $value ): string {
	if ( null === $value || '' === $value ) {
		return '';
	}
	if ( ! str_starts_with( $value, 'esk1:' ) ) {
		return $value;
	}
	$raw  = base64_decode( substr( $value, 5 ), true );
	if ( false === $raw || strlen( $raw ) < 28 ) {
		return $value;
	}
	$iv  = substr( $raw, 0, 12 );
	$tag = substr( $raw, 12, 16 );
	$ct  = substr( $raw, 28 );
	$key = (string) wp_salt( 'auth' );
	$out = openssl_decrypt( $ct, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag );
	return false === $out ? '' : $out;
}

/**
 * Migrate any plaintext gateway secrets to encrypted-at-rest values. Safe to
 * re-run (encrypted rows are left untouched). Call on theme activation and
 * from the Tools page.
 */
function esk_encrypt_gateway_secrets(): int {
	global $wpdb;
	$table = $wpdb->prefix . 'esk_payment_gateways';
	if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) !== $table ) {
		return 0;
	}
	$rows = $wpdb->get_results( "SELECT id, api_key, api_secret, api_password FROM {$table}", ARRAY_A );
	if ( ! is_array( $rows ) ) {
		return 0;
	}
	$migrated = 0;
	foreach ( $rows as $row ) {
		$update = array();
		foreach ( array( 'api_key', 'api_secret', 'api_password' ) as $col ) {
			$cur = (string) ( $row[ $col ] ?? '' );
			if ( '' !== $cur && ! str_starts_with( $cur, 'esk1:' ) ) {
				$update[ $col ] = esk_encrypt_secret( $cur );
			}
		}
		if ( $update ) {
			$wpdb->update( $table, $update, array( 'id' => (int) $row['id'] ) );
			$migrated++;
		}
	}
	return $migrated;
}
