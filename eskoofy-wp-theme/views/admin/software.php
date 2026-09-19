<?php
/**
 * Admin Software — WordPress, PHP and database version details.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;

global $wpdb;

$wp_db_server_info = function_exists( 'mysqli_get_server_info' ) && $wpdb->dbh instanceof mysqli ? mysqli_get_server_info( $wpdb->dbh ) : $wpdb->db_version();

$blocks = array(
	array(
		'title'    => __( 'WordPress', 'eskoofy' ),
		'items'    => array(
			__( 'Version', 'eskoofy' )  => get_bloginfo( 'version' ),
			__( 'Site', 'eskoofy' )     => home_url(),
			__( 'Locale', 'eskoofy' )   => get_locale(),
			__( 'Multisite', 'eskoofy' ) => is_multisite() ? __( 'Yes', 'eskoofy' ) : __( 'No', 'eskoofy' ),
		),
		'icon'     => '<path fill-rule="evenodd" d="M14.872 3.43c.512.083.993.286 1.404.564-2.503-.36-4.435.107-5.55 1.23-.876.88-1.223 2.103-1.223 3.326 0 2.393 1.47 4.393 3.496 5.313l-2-2.89a5.55 5.55 0 011.608-1.862l2.19-.88 1.455-1.487c.66-1.417.147-2.948-.38-4.313z" clip-rule="evenodd" />',
	),
	array(
		'title'    => __( 'PHP', 'eskoofy' ),
		'items'    => array(
			__( 'Version', 'eskoofy' ) => PHP_VERSION,
			__( 'Architecture', 'eskoofy' ) => PHP_INT_SIZE * 8 . '-bit',
			__( 'Memory limit', 'eskoofy' ) => ini_get( 'memory_limit' ),
			__( 'Max upload', 'eskoofy' )   => size_format( (int) wp_max_upload_size() ),
			__( 'Timezone', 'eskoofy' )     => date_default_timezone_get(),
		),
		'icon'     => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />',
	),
	array(
		'title'    => __( 'Database', 'eskoofy' ),
		'items'    => array(
			__( 'Engine', 'eskoofy' )  => __( 'MySQL / MariaDB', 'eskoofy' ),
			__( 'Version', 'eskoofy' ) => (string) $wp_db_server_info,
			__( 'Prefix', 'eskoofy' )  => $wpdb->prefix,
			__( 'Collation', 'eskoofy' ) => $wpdb->get_charset_collate(),
		),
		'icon'     => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />',
	),
	array(
		'title'    => __( 'Eskoofy', 'eskoofy' ),
		'items'    => array(
			__( 'Theme version', 'eskoofy' ) => wp_get_theme( 'eskoofy' )->get( 'Version' ),
			__( 'Data tables', 'eskoofy' )   => count( (array) $wpdb->get_col( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->prefix . 'esk\_%' ) ) ),
			__( 'Students', 'eskoofy' )      => number_format_i18n( (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %1$s', $wpdb->prefix . 'esk_students' ) ) ), // phpcs:ignore
			__( 'Teachers', 'eskoofy' )      => number_format_i18n( (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %1$s', $wpdb->prefix . 'esk_teachers' ) ) ), // phpcs:ignore
		),
		'icon'     => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21h18M5 21V8m7 13V3m7 18V11" />',
	),
);
?>
<div class="wrap esk-admin-wrap">

	<div class="mb-6 flex items-center justify-between">
		<div>
			<h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white"><?php esc_html_e( 'About & Software', 'eskoofy' ); ?></h1>
			<p class="mt-1 text-sm text-slate-500 dark:text-slate-400"><?php esc_html_e( 'System information for your install of the school management software.', 'eskoofy' ); ?></p>
		</div>
		</div>

	<div class="grid gap-6 sm:grid-cols-2">
		<?php foreach ( $blocks as $block ) : ?>
			<div class="admin-card">
				<div class="admin-card-header">
					<div class="flex items-center gap-3">
						<span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-100 text-brand-700 dark:bg-brand-900/40 dark:text-brand-400">
							<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><?php echo $block['icon']; // phpcs:ignore ?></svg>
						</span>
						<h2 class="text-base font-semibold text-slate-900 dark:text-white"><?php echo esc_html( $block['title'] ); ?></h2>
					</div>
				</div>
				<div class="admin-card-body">
					<dl class="divide-y divide-slate-100 dark:divide-slate-700">
						<?php foreach ( $block['items'] as $label => $value ) : ?>
							<div class="flex items-center justify-between gap-4 py-2.5">
								<dt class="text-sm text-slate-500 dark:text-slate-400"><?php echo esc_html( $label ); ?></dt>
								<dd class="text-right text-sm font-medium text-slate-900 dark:text-slate-100"><?php echo esc_html( $value ); ?></dd>
							</div>
						<?php endforeach; ?>
					</dl>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</div>