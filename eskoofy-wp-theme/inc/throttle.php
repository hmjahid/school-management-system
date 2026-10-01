<?php
/**
 * Rate limiting — parity with the app's DashboardWriteThrottle middleware and
 * AuthSessionController login limiter.
 *
 * WordPress has no queue/middleware stack, so the limiter is a small
 * transient-backed fixed-window counter with two entry points:
 *
 *  - esk_login_throttle()      — public /login/ credential stuffing guard
 *  - esk_dashboard_throttle()  — dashboard write guard (POST/PUT/PATCH/DELETE)
 *
 * Both return null when the request may proceed, or a human-readable reason
 * string when it is blocked. State lives in transients (autoloaded options
 * table), so no extra table is required.
 *
 * @package Eskoofy
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

if ( ! function_exists( 'esk_client_ip' ) ) {
	/**
	 * Best-effort client IP for rate-limit keys. Only REMOTE_ADDR is trusted —
	 * forwarded headers are attacker-controlled unless a proxy is configured.
	 */
	function esk_client_ip(): string {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		if ( '' === $ip ) {
			$ip = 'unknown';
		}

		return substr( $ip, 0, 45 );
	}
}

if ( ! function_exists( 'esk_throttle_hit' ) ) {
	/**
	 * Fixed-window counter.
	 *
	 * @param string $key      Bucket identity.
	 * @param int    $limit    Attempts allowed per window.
	 * @param int    $decay    Window length in seconds.
	 * @return array{hits:int,remaining:int,retry_after:int,blocked:bool}
	 */
	function esk_throttle_hit( string $key, int $limit, int $decay ): array {
		$option = 'esk_throttle_' . md5( $key );
		$state  = get_option( $option );

		$now = time();
		if ( ! is_array( $state ) || ! isset( $state['hits'], $state['started'] ) || ( $now - (int) $state['started'] ) >= $decay ) {
			$state = array(
				'hits'    => 0,
				'started' => $now,
			);
		}

		++$state['hits'];
		$windowEnd = (int) $state['started'] + $decay;

		// update_option() creates the row when absent, so a blocked window still
		// gets written and the count keeps expiring on its own.
		update_option( $option, $state, false );

		$retry = max( 0, $windowEnd - $now );

		return array(
			'hits'        => (int) $state['hits'],
			'remaining'   => max( 0, $limit - (int) $state['hits'] ),
			'retry_after' => $retry,
			'blocked'     => (int) $state['hits'] > $limit,
		);
	}
}

if ( ! function_exists( 'esk_throttle_clear' ) ) {
	/**
	 * Reset a bucket — called after a successful sign-in.
	 */
	function esk_throttle_clear( string $key ): void {
		delete_option( 'esk_throttle_' . md5( $key ) );
	}
}

if ( ! function_exists( 'esk_throttle_remaining' ) ) {
	/**
	 * Attempts left in the current window, without recording an attempt.
	 */
	function esk_throttle_remaining( string $key, int $limit, int $decay ): int {
		$state = get_option( 'esk_throttle_' . md5( $key ) );
		if ( ! is_array( $state ) || ! isset( $state['hits'], $state['started'] ) ) {
			return $limit;
		}
		if ( ( time() - (int) $state['started'] ) >= $decay ) {
			return $limit;
		}

		return max( 0, $limit - (int) $state['hits'] );
	}
}

if ( ! function_exists( 'esk_login_throttle' ) ) {
	/**
	 * Login attempt limiter — 5 attempts per 60s per (ip, login).
	 *
	 * @return string|null Error message when blocked, null when allowed.
	 */
	function esk_login_throttle( string $login ): ?string {
		$key    = 'login:' . esk_client_ip() . '|' . strtolower( $login );
		$result = esk_throttle_hit( $key, ESK_LOGIN_THROTTLE_LIMIT, ESK_LOGIN_THROTTLE_DECAY );

		if ( ! $result['blocked'] ) {
			return null;
		}

		$minutes = max( 1, (int) ceil( $result['retry_after'] / 60 ) );

		return sprintf(
			/* translators: %d: number of minutes to wait. */
			__( 'Too many login attempts. Please wait %d minute(s) and try again.', 'eskoofy' ),
			$minutes
		);
	}
}

if ( ! function_exists( 'esk_login_throttle_key' ) ) {
	/**
	 * Bucket key for a login attempt — shared by hit() and clear().
	 */
	function esk_login_throttle_key( string $login ): string {
		return 'login:' . esk_client_ip() . '|' . strtolower( $login );
	}
}

if ( ! function_exists( 'esk_login_throttle_reset' ) ) {
	/**
	 * Clear the login bucket after a successful authentication.
	 */
	function esk_login_throttle_reset( string $login ): void {
		esk_throttle_clear( esk_login_throttle_key( $login ) );
	}
}

if ( ! function_exists( 'esk_dashboard_throttle' ) ) {
	/**
	 * Dashboard write limiter — 120 writes per 60s per (user, ip). Read requests
	 * are never throttled, mirroring the app's method filter.
	 *
	 * @return string|null Error message when blocked, null when allowed.
	 */
	function esk_dashboard_throttle( int $userId ): ?string {
		$key    = 'dashboard_write:' . $userId . ':' . esk_client_ip();
		$result = esk_throttle_hit( $key, ESK_DASHBOARD_THROTTLE_LIMIT, ESK_DASHBOARD_THROTTLE_DECAY );

		if ( ! $result['blocked'] ) {
			return null;
		}

		$seconds = max( 1, $result['retry_after'] );

		return sprintf(
			/* translators: %d: number of seconds to wait. */
			__( 'Too many changes at once. Please wait %d second(s) and try again.', 'eskoofy' ),
			$seconds
		);
	}
}

if ( ! defined( 'ESK_LOGIN_THROTTLE_LIMIT' ) ) {
	define( 'ESK_LOGIN_THROTTLE_LIMIT', 5 );
}
if ( ! defined( 'ESK_LOGIN_THROTTLE_DECAY' ) ) {
	define( 'ESK_LOGIN_THROTTLE_DECAY', MINUTE_IN_SECONDS );
}
if ( ! defined( 'ESK_DASHBOARD_THROTTLE_LIMIT' ) ) {
	define( 'ESK_DASHBOARD_THROTTLE_LIMIT', 120 );
}
if ( ! defined( 'ESK_DASHBOARD_THROTTLE_DECAY' ) ) {
	define( 'ESK_DASHBOARD_THROTTLE_DECAY', MINUTE_IN_SECONDS );
}
