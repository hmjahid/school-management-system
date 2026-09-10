<?php
/**
 * Master loader — includes all inc/ files.
 *
 * @package Eskoofy
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

define('ESK_VERSION', '1.0.0');
define('ESK_PATH', get_template_directory());
define('ESK_URL', get_template_directory_uri());
define('ESK_PLUGIN_DIR', __DIR__);

$esk_includes = array(
	'helpers.php',
	'database.php',
	'sms-gateway.php',
	'custom-post-types.php',
	'admin-pages.php',
	'admin-ajax.php',
	'rest-api.php',
	'shortcodes.php',
	'widgets.php',
	'customizer.php',
	'payment-gateways.php',
);

foreach ( $esk_includes as $file ) {
	$file_path = ESK_PLUGIN_DIR . '/' . $file;
	if ( file_exists( $file_path ) ) {
		require_once $file_path;
	}
}
