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
	 * Get a translation string from the site UI language file.
	 */
	function esk_site_ui( string $key, string $default = '' ): string {
		static $strings = null;
		if ( null === $strings ) {
			$locale    = get_locale();
			$file_path = ESK_PATH . '/languages/' . $locale . '/site_ui.php';
			if ( ! file_exists( $file_path ) ) {
				$file_path = ESK_PATH . '/languages/en/site_ui.php';
			}
			if ( file_exists( $file_path ) ) {
				$strings = include $file_path;
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
				return $default !== '' ? $default : $key;
			}
		}
		return is_string( $value ) ? $value : $key;
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
