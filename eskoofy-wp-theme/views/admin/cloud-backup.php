<?php
/**
 * Cloud backup admin page — provider settings, on-demand upload, remote list.
 *
 * Mirrors the Laravel app's dashboard › cloud backup screen. POST handlers run
 * inline (the theme's canonical pattern) with nonce + capability guards.
 *
 * @package Eskoofy
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

global $wpdb;

$settings   = esk_cloud_backup_settings();
$configured = esk_cloud_backup_is_configured( $settings );
$created    = '';
$error      = '';

if ( isset( $_POST['esk_cloud_backup_save'] ) && current_user_can( 'manage_options' ) ) {
	check_admin_referer( 'esk_cloud_backup_nonce' );
	esk_cloud_backup_save_settings( wp_unslash( $_POST ) ); // phpcs:ignore
	$settings = esk_cloud_backup_settings();
	$created  = __( 'Cloud backup settings saved.', 'eskoofy' );
}

if ( isset( $_POST['esk_cloud_backup_test'] ) && current_user_can( 'manage_options' ) ) {
	check_admin_referer( 'esk_cloud_backup_nonce' );
	$settings = esk_cloud_backup_settings();
	$result   = esk_cloud_backup_test( $settings );
	$created  = $result['ok'] ? $result['message'] : '';
	$error    = $result['ok'] ? '' : $result['message'];
}

if ( isset( $_POST['esk_cloud_backup_run'] ) && current_user_can( 'manage_options' ) ) {
	check_admin_referer( 'esk_cloud_backup_nonce' );
	$result  = esk_cloud_backup_run();
	$created = 'success' === $result['status'] ? $result['message'] : '';
	$error   = 'success' === $result['status'] ? '' : $result['message'];
}

if ( isset( $_POST['esk_cloud_backup_restore'] ) && current_user_can( 'manage_options' ) ) {
	check_admin_referer( 'esk_cloud_backup_nonce' );
	$file    = isset( $_POST['esk_cloud_backup_file'] ) ? sanitize_text_field( wp_unslash( $_POST['esk_cloud_backup_file'] ) ) : '';
	$result  = esk_cloud_backup_restore( esk_cloud_backup_settings(), $file );
	$created = $result['ok'] ? $result['message'] : '';
	$error   = $result['ok'] ? '' : $result['message'];
}

if ( isset( $_POST['esk_cloud_backup_delete'] ) && current_user_can( 'manage_options' ) ) {
	check_admin_referer( 'esk_cloud_backup_nonce' );
	$file    = isset( $_POST['esk_cloud_backup_file'] ) ? sanitize_text_field( wp_unslash( $_POST['esk_cloud_backup_file'] ) ) : '';
	$ok      = esk_cloud_backup_delete( esk_cloud_backup_settings(), $file );
	$created = $ok ? __( 'Remote backup deleted.', 'eskoofy' ) : '';
	$error   = $ok ? '' : __( 'The remote file could not be deleted.', 'eskoofy' );
}

$remote      = esk_cloud_backup_list( $settings );
$runs        = $wpdb->get_results( 'SELECT * FROM ' . $wpdb->prefix . 'esk_cloud_backup_runs ORDER BY id DESC LIMIT 15' ); // phpcs:ignore
$providers   = array(
	'local'        => __( 'This server (local folder)', 'eskoofy' ),
	'google_drive' => __( 'Google Drive', 'eskoofy' ),
	'dropbox'      => __( 'Dropbox', 'eskoofy' ),
	'4shared'      => __( '4shared', 'eskoofy' ),
	's3'           => __( 'Amazon S3 (or compatible)', 'eskoofy' ),
);
$credentials = esk_cloud_backup_credentials( $settings );
?>
<div class="space-y-6">
	<?php if ( $created ) : ?>
		<div data-esk-flash-toast data-message="<?php echo esc_attr( $created ); ?>" data-type="success"></div>
	<?php endif; ?>
	<?php if ( $error ) : ?>
		<div data-esk-flash-toast data-message="<?php echo esc_attr( $error ); ?>" data-type="error"></div>
	<?php endif; ?>

	<?php if ( ! $configured ) : ?>
		<div class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
			<?php esc_html_e( 'This provider has no complete credential set yet, so uploads are skipped. Fill in the required fields below.', 'eskoofy' ); ?>
		</div>
	<?php endif; ?>

	<form method="post" action="">
		<?php wp_nonce_field( 'esk_cloud_backup_nonce' ); ?>
		<div class="rounded-xl border border-slate-200 bg-white p-6">
			<h2 class="mb-4 text-lg font-semibold text-slate-900"><?php esc_html_e( 'Provider & schedule', 'eskoofy' ); ?></h2>

			<label class="mb-1 block text-sm font-medium text-slate-700"><?php esc_html_e( 'Provider', 'eskoofy' ); ?></label>
			<select name="provider" class="mb-4 w-full rounded-lg border-slate-300 text-sm">
				<?php foreach ( $providers as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $settings['provider'], $key ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>

			<label class="mb-1 block text-sm font-medium text-slate-700"><?php esc_html_e( 'Remote folder', 'eskoofy' ); ?></label>
			<input name="folder" type="text" value="<?php echo esc_attr( (string) $settings['folder'] ); ?>" class="mb-4 w-full rounded-lg border-slate-300 text-sm" autocomplete="off">

			<div class="grid gap-4 sm:grid-cols-2">
				<div>
					<label class="mb-1 block text-sm font-medium text-slate-700"><?php esc_html_e( 'Interval (minutes)', 'eskoofy' ); ?></label>
					<input name="interval_minutes" type="number" min="5" max="10080" value="<?php echo esc_attr( (int) $settings['interval_minutes'] ); ?>" class="w-full rounded-lg border-slate-300 text-sm">
				</div>
				<div>
					<label class="mb-1 block text-sm font-medium text-slate-700"><?php esc_html_e( 'Keep newest files', 'eskoofy' ); ?></label>
					<input name="keep" type="number" min="1" max="365" value="<?php echo esc_attr( (int) $settings['keep'] ); ?>" class="w-full rounded-lg border-slate-300 text-sm">
				</div>
			</div>

			<?php
			$field_defs = array(
				'google_drive' => array(
					'client_id'                   => __( 'Client ID', 'eskoofy' ),
					'client_secret'               => __( 'Client secret', 'eskoofy' ),
					'refresh_token'               => __( 'Refresh token', 'eskoofy' ),
					'service_account_email'       => __( 'Service account email', 'eskoofy' ),
					'service_account_private_key' => __( 'Service account private key', 'eskoofy' ),
					'scope'                       => __( 'OAuth scope (optional)', 'eskoofy' ),
				),
				'dropbox'      => array(
					'app_key'       => __( 'App key', 'eskoofy' ),
					'app_secret'    => __( 'App secret', 'eskoofy' ),
					'refresh_token' => __( 'Refresh token', 'eskoofy' ),
					'access_token'  => __( 'Access token (short-lived alternative)', 'eskoofy' ),
				),
				'4shared'      => array(
					'api_key'  => __( 'API key', 'eskoofy' ),
					'username' => __( 'Username', 'eskoofy' ),
					'password' => __( 'Password', 'eskoofy' ),
				),
				's3'           => array(
					'endpoint' => __( 'Endpoint', 'eskoofy' ),
					'bucket'   => __( 'Bucket', 'eskoofy' ),
					'key'      => __( 'Access key', 'eskoofy' ),
					'secret'   => __( 'Secret key', 'eskoofy' ),
					'region'   => __( 'Region', 'eskoofy' ),
				),
			);
			?>

			<?php foreach ( $field_defs as $provider_key => $fields ) : ?>
				<div class="mt-4 border-t border-slate-100 pt-4" data-esk-provider-panel="<?php echo esc_attr( $provider_key ); ?>" <?php echo $settings['provider'] !== $provider_key ? 'style="display:none"' : ''; ?>>
					<h3 class="mb-2 text-sm font-semibold text-slate-700"><?php echo esc_html( $providers[ $provider_key ] ?? $provider_key ); ?></h3>
					<?php foreach ( $fields as $field => $label ) : ?>
						<label class="mb-1 block text-sm font-medium text-slate-700">
							<?php echo esc_html( $label ); ?>
							<?php if ( ! empty( $credentials[ $field ] ) ) : ?>
								<span class="ml-1 text-xs font-normal text-emerald-700"><?php esc_html_e( 'saved', 'eskoofy' ); ?></span>
							<?php endif; ?>
						</label>
						<input name="credentials[<?php echo esc_attr( $field ); ?>]" type="password" value=""
							placeholder="<?php echo ! empty( $credentials[ $field ] ) ? esc_attr__( '•••••••• (unchanged)', 'eskoofy' ) : ''; ?>"
							class="mb-3 w-full rounded-lg border-slate-300 text-sm" autocomplete="new-password">
					<?php endforeach; ?>
				</div>
			<?php endforeach; ?>

			<label class="mt-2 flex items-center gap-2 text-sm text-slate-700">
				<input name="is_enabled" type="checkbox" value="1" <?php checked( (bool) $settings['is_enabled'] ); ?>>
				<?php esc_html_e( 'Cloud backup enabled', 'eskoofy' ); ?>
			</label>
			<label class="flex items-center gap-2 text-sm text-slate-700">
				<input name="auto_enabled" type="checkbox" value="1" <?php checked( (bool) $settings['auto_enabled'] ); ?>>
				<?php esc_html_e( 'Upload automatically on a schedule', 'eskoofy' ); ?>
			</label>

			<div class="mt-4 flex flex-wrap gap-2">
				<button type="submit" name="esk_cloud_backup_save" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700"><?php esc_html_e( 'Save settings', 'eskoofy' ); ?></button>
				<button type="submit" name="esk_cloud_backup_test" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"><?php esc_html_e( 'Test saved settings', 'eskoofy' ); ?></button>
				<button type="submit" name="esk_cloud_backup_run" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700" <?php echo $configured ? '' : 'disabled'; ?>><?php esc_html_e( 'Back up now', 'eskoofy' ); ?></button>
			</div>
		</div>
	</form>

	<div class="mt-6 grid gap-6 lg:grid-cols-2">
		<div class="rounded-xl border border-slate-200 bg-white p-6">
			<h2 class="mb-3 text-lg font-semibold text-slate-900"><?php esc_html_e( 'Remote files', 'eskoofy' ); ?></h2>
			<?php if ( empty( $remote ) ) : ?>
				<p class="text-sm text-slate-500"><?php esc_html_e( 'No files on the provider yet.', 'eskoofy' ); ?></p>
			<?php else : ?>
				<ul class="divide-y divide-slate-100">
					<?php foreach ( $remote as $file ) : ?>
						<li class="flex items-center justify-between gap-4 py-3">
							<div class="min-w-0">
								<p class="truncate text-sm font-medium text-slate-900"><?php echo esc_html( (string) $file['name'] ); ?></p>
								<p class="text-xs text-slate-500">
									<?php echo esc_html( size_format( (int) $file['size'] ) ); ?> —
									<?php echo esc_html( gmdate( 'M d, Y H:i', (int) $file['modified'] ) ); ?>
								</p>
							</div>
							<div class="flex shrink-0 gap-2">
								<form method="post" action="">
									<?php wp_nonce_field( 'esk_cloud_backup_nonce' ); ?>
									<input type="hidden" name="esk_cloud_backup_file" value="<?php echo esc_attr( (string) $file['id'] ); ?>">
									<button type="submit" name="esk_cloud_backup_restore" data-confirm="<?php esc_attr_e( 'Restore this remote backup? Existing data will be overwritten.', 'eskoofy' ); ?>" class="text-sm font-semibold text-amber-700"><?php esc_html_e( 'Restore', 'eskoofy' ); ?></button>
								</form>
								<form method="post" action="">
									<?php wp_nonce_field( 'esk_cloud_backup_nonce' ); ?>
									<input type="hidden" name="esk_cloud_backup_file" value="<?php echo esc_attr( (string) $file['id'] ); ?>">
									<button type="submit" name="esk_cloud_backup_delete" data-confirm="<?php esc_attr_e( 'Delete this remote file?', 'eskoofy' ); ?>" class="text-sm font-semibold text-red-700"><?php esc_html_e( 'Delete', 'eskoofy' ); ?></button>
								</form>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>

		<div class="rounded-xl border border-slate-200 bg-white p-6">
			<h2 class="mb-3 text-lg font-semibold text-slate-900"><?php esc_html_e( 'History', 'eskoofy' ); ?></h2>
			<?php if ( empty( $runs ) ) : ?>
				<p class="text-sm text-slate-500"><?php esc_html_e( 'No backup runs yet.', 'eskoofy' ); ?></p>
			<?php else : ?>
				<ul class="divide-y divide-slate-100">
					<?php foreach ( $runs as $run ) : ?>
						<li class="py-3">
							<div class="flex items-center justify-between gap-2">
								<span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold
									<?php echo 'success' === $run->status ? 'bg-emerald-100 text-emerald-800' : ( 'failed' === $run->status ? 'bg-red-100 text-red-800' : 'bg-slate-100 text-slate-700' ); ?>">
									<?php echo esc_html( $run->status ); ?>
								</span>
								<span class="text-xs text-slate-500"><?php echo esc_html( gmdate( 'M d, Y H:i', (int) strtotime( (string) $run->created_at ) ) ); ?></span>
							</div>
							<p class="mt-1 text-sm text-slate-700"><?php echo esc_html( (string) $run->message ); ?></p>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</div>
</div>

<script>
(function () {
	var select = document.querySelector('select[name="provider"]');
	if (!select) return;
	function sync() {
		document.querySelectorAll('[data-esk-provider-panel]').forEach(function (panel) {
			var active = panel.getAttribute('data-esk-provider-panel') === select.value;
			panel.style.display = active ? '' : 'none';
			panel.querySelectorAll('input').forEach(function (field) {
				field.disabled = !active;
			});
		});
	}
	select.addEventListener('change', sync);
	sync();
})();
</script>