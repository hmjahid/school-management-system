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
