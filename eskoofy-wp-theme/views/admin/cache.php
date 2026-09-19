<?php
/**
 * Admin Clear Cache — flush WordPress object/transient caches and opcode cache.
 *
 * @package Eskoofy
 */

defined( 'ABSPATH' ) || exit;

global $wpdb;

$cleared = '';
$error   = '';

if ( isset( $_POST['esk_cache_clear'] ) && current_user_can( 'manage_options' ) ) {
	check_admin_referer( 'esk_cache_nonce' );

	if ( function_exists( 'opcache_reset' ) ) {
		opcache_reset();
	}
	if ( function_exists( 'wp_cache_flush' ) ) {
		wp_cache_flush();
	}
	if ( function_exists( 'wp_clean_plugins_cache' ) ) {
		wp_clean_plugins_cache();
	}

	$deleted = 0;
	if ( $wpdb ) {
		$deleted = (int) $wpdb->query(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_esk_%' OR option_name LIKE '_transient_timeout_esk_%'"
		);
	}

	$cleared = sprintf(
		/* translators: %d: number of transient rows removed. */
		__( 'Frontend cache cleared. Removed %d cached row(s).', 'eskoofy' ),
		$deleted
	);
}
?>

<div class="max-w-2xl">
	<div class="rounded-xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800">
		<h2 class="text-lg font-bold text-slate-900 dark:text-slate-100"><?php esc_html_e( 'Clear frontend cache', 'eskoofy' ); ?></h2>
		<p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
			<?php esc_html_e( 'Flushes the PHP opcode cache, WordPress object cache, plugin cache, and all Eskoofy transients. Safe to run at any time.', 'eskoofy' ); ?>
		</p>

		<?php if ( '' !== $cleared ) : ?>
			<div class="mt-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800 dark:border-green-900 dark:bg-green-900/30 dark:text-green-300"><?php echo esc_html( $cleared ); ?></div>
		<?php endif; ?>
		<?php if ( '' !== $error ) : ?>
			<div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700 dark:border-red-900 dark:bg-red-900/30 dark:text-red-300"><?php echo esc_html( $error ); ?></div>
		<?php endif; ?>

		<form method="post" class="mt-5">
			<?php wp_nonce_field( 'esk_cache_nonce' ); ?>
			<button type="submit" name="esk_cache_clear" value="1" class="inline-flex items-center rounded-lg bg-brand-600 px-5 py-2 text-sm font-semibold text-white hover:bg-brand-500">
				<?php esc_html_e( 'Clear cache', 'eskoofy' ); ?>
			</button>
		</form>
	</div>
</div>