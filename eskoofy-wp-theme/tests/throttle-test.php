<?php
/**
 * Standalone guard for inc/throttle.php (login limiter + dashboard write limiter).
 *
 * The theme has no PHPUnit harness, so this file stubs the handful of WP
 * functions the limiter touches and exercises the real implementation.
 *
 * Run: php tests/throttle-test.php
 *
 * @package Eskoofy
 */

declare(strict_types=1);

define('ABSPATH', __DIR__ . '/');
define('MINUTE_IN_SECONDS', 60);
define('ESK_LOGIN_THROTTLE_LIMIT', 5);
define('ESK_LOGIN_THROTTLE_DECAY', MINUTE_IN_SECONDS);
define('ESK_DASHBOARD_THROTTLE_LIMIT', 120);
define('ESK_DASHBOARD_THROTTLE_DECAY', MINUTE_IN_SECONDS);

$GLOBALS['db'] = [];
$_SERVER['REMOTE_ADDR'] = '10.0.0.9';

function get_option($k, $d = false) { return $GLOBALS['db'][$k] ?? $d; }
function update_option($k, $v, $a = null) { $GLOBALS['db'][$k] = $v; return true; }
function delete_option($k) { unset($GLOBALS['db'][$k]); return true; }
function sanitize_text_field($s) { return trim((string) $s); }
function sanitize_user($s) { return strtolower(trim((string) $s)); }
function wp_unslash($s) { return $s; }
function __($s, $d = null) { return $s; }

require dirname(__DIR__) . '/inc/throttle.php';

$fail = 0;
function ok($cond, $msg) { global $fail; if (!$cond) { $fail++; echo "FAIL: $msg\n"; } else { echo "ok: $msg\n"; } }

// login: 5 attempts allowed, 6th blocked
for ($i = 1; $i <= 5; $i++) {
    ok(esk_login_throttle('admin') === null, "login attempt $i allowed");
}
$m = esk_login_throttle('admin');
ok($m !== null, 'login attempt 6 blocked');
ok(str_contains((string) $m, 'Too many login attempts'), "lockout message is human readable: $m");

// different login on same IP is unaffected
ok(esk_login_throttle('other') === null, 'different account has its own bucket');

// successful login clears the bucket
esk_login_throttle_reset('admin');
ok(esk_login_throttle('admin') === null, 'reset clears lockout');

// remaining() is non-consuming
ok(esk_throttle_remaining(esk_login_throttle_key('admin'), 5, 60) === 4, 'remaining reflects the one post-reset attempt');
esk_throttle_hit(esk_login_throttle_key('admin'), 5, 60);
ok(esk_throttle_remaining(esk_login_throttle_key('admin'), 5, 60) === 3, 'remaining decrements without consuming');

// window expiry resets the counter
esk_throttle_hit(esk_login_throttle_key('admin'), 5, 60);
esk_throttle_hit(esk_login_throttle_key('admin'), 5, 60);
ok(esk_login_throttle('admin') === null, 'still allowed at 3');
$GLOBALS['db']['esk_throttle_' . md5(esk_login_throttle_key('admin'))]['started'] = time() - 61;
ok(esk_login_throttle('admin') === null, 'window expiry resets to full allowance');
ok(esk_throttle_remaining(esk_login_throttle_key('admin'), 5, 60) === 4, 'stale window does not report 0 remaining');

// dashboard write throttle: 120 allowed, 121st blocked
for ($i = 1; $i <= 120; $i++) { $r = esk_dashboard_throttle(7); }
ok($r === null, 'dashboard write 120 allowed');
$d = esk_dashboard_throttle(7);
ok($d !== null, 'dashboard write 121 blocked');
ok(str_contains((string) $d, 'Too many changes'), "dashboard message: $d");
// per-user isolation
ok(esk_dashboard_throttle(8) === null, 'dashboard bucket is per-user');

echo $fail === 0 ? "\nALL PASS\n" : "\n$fail FAILURE(S)\n";
exit($fail === 0 ? 0 : 1);
