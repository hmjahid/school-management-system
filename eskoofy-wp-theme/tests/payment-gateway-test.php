<?php
/**
 * Standalone guard for inc/payment-gateways.php (test/sandbox gateway + the
 * config-driven BD hosted gateways).
 *
 * The theme has no PHPUnit harness, so this file stubs the handful of WP
 * functions/global the gateways touch and exercises the real implementation
 * end to end — with no network access of any kind.
 *
 * Run: php tests/payment-gateway-test.php
 *
 * @package Eskoofy
 */

declare(strict_types=1);

define('ABSPATH', __DIR__ . '/');
define('ARRAY_A', 'ARRAY_A');

$GLOBALS['db']                = array();
$GLOBALS['esk_test_rows']     = array();
$GLOBALS['esk_test_payments'] = array();

/* ─── WordPress stubs ─────────────────────────────────────────── */

function __( $s, $d = null ) { return $s; }
function esc_html__( $s, $d = null ) { return $s; }
function esc_html( $s ) { return (string) $s; }
function esc_attr( $s ) { return (string) $s; }
function esc_url( $s ) { return (string) $s; }
function sanitize_text_field( $s ) { return trim( (string) $s ); }
function sanitize_key( $s ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', (string) $s ) ); }
function wp_unslash( $s ) { return $s; }
function wp_json_encode( $v, $f = 0 ) { return (string) json_encode( $v, $f ); }
function rest_url( $p = '' ) { return 'https://example.test/wp-json/' . ltrim( (string) $p, '/' ); }
function home_url( $p = '' ) { return 'https://example.test' . $p; }
function admin_url( $p = '' ) { return 'https://example.test/wp-admin/' . $p; }
function get_option( $k, $d = false ) { return $GLOBALS['db'][ $k ] ?? $d; }
function current_time( $t ) { return gmdate( 'Y-m-d H:i:s' ); }
function esk_decrypt_secret( $v ) { return $v; }
function esk_generate_number( $prefix = 'ADM', $table = null ) { return $prefix . '-999999'; }

function esk_dashboard_url( $slug = 'esk-dashboard', $query = '' ) {
	if ( 'esk-dashboard' === $slug ) {
		$url = home_url( '/dashboard/' );
	} else {
		$url = home_url( '/dashboard/' . rawurlencode( (string) preg_replace( '/^esk-/', '', $slug ) ) . '/' );
	}
	return '' !== $query ? $url . '?' . $query : $url;
}

/**
 * Minimal $wpdb stand-in: routes `invoice_number` lookups to the payments
 * fixture and `code` lookups to the gateway fixture.
 */
class Eskoofy_Test_Wpdb {
	public $prefix = 'wp_';

	public function prepare( $query, ...$args ) {
		return array( $query, $args );
	}

	public function get_row( $query, $output = null ) {
		list( $sql, $args ) = is_array( $query ) ? $query : array( $query, array() );
		$arg = (string) ( $args[0] ?? '' );
		if ( false !== strpos( (string) $sql, 'invoice_number' ) ) {
			return $GLOBALS['esk_test_payments'][ $arg ] ?? null;
		}
		return $GLOBALS['esk_test_rows'][ $arg ] ?? null;
	}

	public function get_var( $query ) { return null; }
	public function insert( $table, $data ) { return 1; }
	public function update( $table, $data, $where ) { return 1; }
}

global $wpdb;
$wpdb = new Eskoofy_Test_Wpdb();

require dirname( __DIR__ ) . '/inc/payment-gateways.php';

$fail = 0;
function ok( $cond, $msg ) {
	global $fail;
	if ( ! $cond ) {
		$fail++;
		echo "FAIL: $msg\n";
	} else {
		echo "ok: $msg\n";
	}
}

/* ─── Test / Sandbox gateway ──────────────────────────────────── */

$test = esk_get_payment_gateway( 'test_gateway' );
ok( $test instanceof Eskoofy_Test_Gateway, 'test_gateway resolves to Eskoofy_Test_Gateway' );
ok( 'test_gateway' === $test->code, 'test_gateway code is test_gateway' );
ok( 'Test / Sandbox' === $test->name, 'test_gateway label is "Test / Sandbox"' );
ok( true === $test->is_configured(), 'test_gateway is always configured with zero credentials' );

$checkout = $test->process_payment( 100.0, array( 'order_id' => 'INV-000042' ) );
ok( 'pending' === $checkout['status'], 'test checkout stays pending' );
ok( 'INV-000042' === $checkout['transaction'], 'test checkout keeps the reference' );
ok( false !== strpos( (string) $checkout['redirect_url'], 'https://example.test/dashboard/payment-sandbox/' ), 'test checkout redirects to the local sandbox page' );
ok( false !== strpos( (string) $checkout['redirect_url'], 'order_id=INV-000042' ), 'sandbox URL carries the reference' );
ok( false === strpos( (string) $checkout['redirect_url'], 'http' . 's://sandbox.' ), 'test checkout never leaves for an external host' );

// Simulate success: fresh payment.
$GLOBALS['esk_test_payments']['INV-000042'] = null;
$result = $test->verify_payment( array( 'order_id' => 'INV-000042', 'simulate' => 'success' ) );
ok( true === $result['verified'], 'simulate success verifies' );
ok( 'completed' === $result['status'], 'simulate success marks completed' );
ok( 'TEST-INV-000042' === $result['transaction'], 'simulate success uses TEST-<reference>' );

// Simulate success idempotency: already paid.
$GLOBALS['esk_test_payments']['INV-000042'] = array(
	'payment_status' => 'completed',
	'transaction_id' => 'TEST-INV-000042',
);
$again = $test->verify_payment( array( 'order_id' => 'INV-000042', 'simulate' => 'failure' ) );
ok( true === $again['verified'], 'already-paid test payment re-verifies as paid' );
ok( 'TEST-INV-000042' === $again['transaction'], 'idempotent re-verify keeps the transaction id' );

// Failure / no simulate must not verify.
$GLOBALS['esk_test_payments']['INV-000042'] = array(
	'payment_status' => 'pending',
	'transaction_id' => '',
);
$failed = $test->verify_payment( array( 'order_id' => 'INV-000042' ) );
ok( false === $failed['verified'], 'a pending payment without simulate does not verify' );
ok( false === $test->verify_webhook( array(), 'sig' ), 'test gateway never accepts webhooks' );

/* ─── Extended BD hosted gateways (config-driven) ─────────────── */

$bd = array(
	'shurjopay'  => 'ShurjoPay',
	'portwallet' => 'PortWallet',
	'cellfin'    => 'Cellfin',
	'purse'      => 'Purse',
	'cashby'     => 'Cashby',
	'upay'       => 'UPay',
	'mycash'     => 'MyCash',
	'payer'      => 'Payer',
);
foreach ( $bd as $code => $label ) {
	$gw = esk_get_payment_gateway( $code );
	ok( $gw instanceof Eskoofy_Generic_Hosted_Gateway, "$code resolves to the config-driven hosted gateway" );
	ok( $label === $gw->name, "$code label is $label" );
}

// Unknown code falls back to GenericHosted using the DB row name (no bespoke class).
$GLOBALS['esk_test_rows']['acme_pay'] = array( 'name' => 'Acme Pay' );
$acme = esk_get_payment_gateway( 'acme_pay' );
ok( $acme instanceof Eskoofy_Generic_Hosted_Gateway, 'unknown code falls back to GenericHosted' );
ok( 'Acme Pay' === $acme->name, 'fallback gateway uses the row name' );

/* ─── Config-driven checkout uses sandbox_url in test mode ────── */

$GLOBALS['esk_test_rows']['shurjopay'] = array(
	'code'             => 'shurjopay',
	'name'             => 'ShurjoPay',
	'sandbox_url'      => 'https://sandbox.shurjopay.test/',
	'live_url'         => 'https://live.shurjopay.test/',
	'test_mode'        => 1,
	'currency'         => 'BDT',
	'api_key'          => 'sandbox-key',
	'extra_attributes' => wp_json_encode(
		array(
			'checkout_method'       => 'GET',
			'checkout_url_template' => '{base_url}/?amount={amount}&currency={currency}&reference={reference}&api_key={api_key}',
			'verify_url'            => '{base_url}/verify',
		)
	),
);
$shurjo = esk_get_payment_gateway( 'shurjopay' );
$init   = $shurjo->process_payment( 12.5, array( 'order_id' => 'INV-1' ) );
ok( 0 === strpos( (string) $init['redirect_url'], 'https://sandbox.shurjopay.test/' ), 'configured BD gateway builds the checkout URL from sandbox_url' );
ok( false !== strpos( (string) $init['redirect_url'], 'api_key=sandbox-key' ), 'checkout template interpolation carries the api key' );

echo $fail === 0 ? "\nALL PASS\n" : "\n$fail FAILURE(S)\n";
exit( $fail === 0 ? 0 : 1 );
