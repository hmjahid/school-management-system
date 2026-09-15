<?php
/**
 * Admin Backups — create, download and delete school data backups.
 *
 * Mirrors the app's backup page: file list with download / restore / delete.
 * Backups are JSON exports of every esk_* table, stored in the uploads folder.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;

global $wpdb;

$backup_dir = '';
$uploads    = wp_get_upload_dir();
if ( ! is_wp_error( $uploads ) ) {
	$backup_dir = trailingslashit( $uploads['basedir'] ) . 'eskoofy-backups';
}

$created = '';
$error   = '';

if ( isset( $_POST['esk_backup_create'] ) && current_user_can( 'manage_options' ) ) {
	check_admin_referer( 'esk_backup_nonce' );
	if ( ! $backup_dir ) {
		$error = __( 'Could not resolve the uploads directory.', 'eskoofy' );
	} else {
		wp_mkdir_p( $backup_dir );
		if ( ! wp_is_writable( $backup_dir ) ) {
			$error = __( 'The backup folder is not writable.', 'eskoofy' );
		} else {
			$tables = $wpdb->get_col( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->prefix . 'esk\_%' ) );
			$data   = array(
				'exported_at' => gmdate( 'c' ),
				'site'        => get_bloginfo( 'name' ),
				'home'        => home_url(),
				'tables'      => array(),
			);
			$count = 0;
			foreach ( (array) $tables as $table ) {
				$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM `%1$s`', $table ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$data['tables'][ $table ] = $rows;
				$count += count( (array) $rows );
			}
			$file_name = gmdate( 'Y-m-d-Hi' ) . '.json';
			$written   = file_put_contents( trailingslashit( $backup_dir ) . $file_name, wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) ); // phpcs:ignore
			if ( false === $written ) {
				$error = __( 'Failed to write the backup file.', 'eskoofy' );
			} else {
				$created = sprintf(
					/* translators: 1: row count 2: table count 3: file name */
					__( 'Backup created — %1$d rows across %2$d tables (%3$s).', 'eskoofy' ),
					(int) $count,
					count( (array) $tables ),
					$file_name
				);
			}
		}
	}
}

if ( isset( $_GET['esk_backup_delete'] ) && current_user_can( 'manage_options' ) ) {
	$name = sanitize_file_name( wp_unslash( $_GET['esk_backup_delete'] ) );
	if ( $name && $backup_dir ) {
		$path = trailingslashit( $backup_dir ) . $name;
		if ( strpos( $path, $backup_dir ) === 0 && file_exists( $path ) && @unlink( $path ) ) { // phpcs:ignore
			$created = __( 'Backup deleted.', 'eskoofy' );
		} else {
			$error = __( 'Could not delete that backup.', 'eskoofy' );
		}
	}
}

if ( isset( $_GET['esk_backup_download'] ) && current_user_can( 'manage_options' ) ) {
	$name = sanitize_file_name( wp_unslash( $_GET['esk_backup_download'] ) );
	if ( $name && $backup_dir ) {
		$path = trailingslashit( $backup_dir ) . $name;
		if ( strpos( $path, $backup_dir ) === 0 && file_exists( $path ) ) {
			header( 'Content-Type: application/json' );
			header( 'Content-Disposition: attachment; filename="' . esc_attr( $name ) . '"' );
			header( 'Content-Length: ' . filesize( $path ) );
			readfile( $path ); // phpcs:ignore
			exit;
		}
	}
	$error = __( 'That backup file does not exist.', 'eskoofy' );
}

if ( isset( $_GET['esk_backup_restore'] ) && current_user_can( 'manage_options' ) ) {
	$name = sanitize_file_name( wp_unslash( $_GET['esk_backup_restore'] ) );
	if ( $name && $backup_dir ) {
		$path  = trailingslashit( $backup_dir ) . $name;
		$raw   = ( strpos( $path, $backup_dir ) === 0 && file_exists( $path ) ) ? file_get_contents( $path ) : false; // phpcs:ignore
		$data  = $raw ? json_decode( (string) $raw, true ) : null;
		$t     = is_array( $data ) && ! empty( $data['tables'] ) ? $data['tables'] : null;
		if ( is_array( $t ) ) {
			$restored = 0;
			foreach ( $t as $table => $rows ) {
				// Only touch esk_* tables from this install.
				if ( 0 !== strpos( (string) $table, $wpdb->prefix . 'esk_' ) ) {
					continue;
				}
				$wpdb->query( $wpdb->prepare( 'DELETE FROM `%1$s`', $table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				foreach ( (array) $rows as $row ) {
					if ( ! is_array( $row ) ) {
						continue;
					}
					$inserted = $wpdb->insert( $table, wp_unslash( $row ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
					if ( false !== $inserted ) {
						$restored++;
					}
				}
			}
			$created = sprintf(
				/* translators: %d: total restored rows */
				__( 'Backup restored — %d rows imported.', 'eskoofy' ),
				(int) $restored
			);
		} else {
			$error = __( 'That file is not a valid Eskoofy backup.', 'eskoofy' );
		}
	} else {
		$error = __( 'That backup file does not exist.', 'eskoofy' );
	}
}

$files = array();
if ( $backup_dir && is_dir( $backup_dir ) ) {
	$paths  = glob( trailingslashit( $backup_dir ) . '*.json' );
	foreach ( (array) $paths as $path ) {
		$files[] = array(
			'name'     => basename( $path ),
			'size'     => file_exists( $path ) ? (int) filesize( $path ) : 0,
			'modified' => file_exists( $path ) ? filemtime( $path ) : 0,
		);
	}
	usort( $files, static function ( $a, $b ) {
		return strcmp( $b['name'], $a['name'] );
	} );
}
?>
<div class="wrap esk-admin-wrap">

	<div class="mb-6 flex items-center justify-between">
		<div>
			<h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white"><?php esc_html_e( 'Backups', 'eskoofy' ); ?></h1>
			<p class="mt-1 text-sm text-slate-500 dark:text-slate-400"><?php esc_html_e( 'Create and restore file + database backups.', 'eskoofy' ); ?></p>
		</div>
		<div>
			<form method="post" action="">
				<?php wp_nonce_field( 'esk_backup_nonce' ); ?>
				<button type="submit" name="esk_backup_create" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700"><?php esc_html_e( 'Create backup now', 'eskoofy' ); ?></button>
			</form>
		</div>
	</div>

	<?php if ( '' !== $created ) : ?>
		<div data-esk-flash-toast data-message="<?php echo esc_attr( $created ); ?>" data-type="success"></div>
	<?php elseif ( '' !== $error ) : ?>
		<div data-esk-flash-toast data-message="<?php echo esc_attr( $error ); ?>" data-type="error"></div>
	<?php endif; ?>

	<div class="admin-card overflow-hidden">
		<div class="overflow-x-auto">
			<table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
				<thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600 dark:bg-slate-800 dark:text-slate-400">
					<tr>
						<th class="px-4 py-3"><?php esc_html_e( 'File', 'eskoofy' ); ?></th>
						<th class="px-4 py-3"><?php esc_html_e( 'Size', 'eskoofy' ); ?></th>
						<th class="px-4 py-3"><?php esc_html_e( 'Modified', 'eskoofy' ); ?></th>
						<th class="px-4 py-3 text-right"><?php esc_html_e( 'Actions', 'eskoofy' ); ?></th>
					</tr>
				</thead>
				<tbody class="divide-y divide-slate-100 dark:divide-slate-700">
					<?php if ( $files ) : ?>
						<?php foreach ( $files as $f ) : ?>
							<tr>
								<td class="px-4 py-3 font-mono text-xs text-slate-900 dark:text-slate-100"><?php echo esc_html( $f['name'] ); ?></td>
								<td class="px-4 py-3 text-slate-700 dark:text-slate-300"><?php echo esc_html( number_format( (float) $f['size'] / 1024, 1 ) ); ?> KB</td>
								<td class="px-4 py-3 text-slate-500 dark:text-slate-400"><?php echo esc_html( gmdate( 'Y-m-d H:i', (int) $f['modified'] ) ); ?></td>
								<td class="px-4 py-3 text-right">
									<a class="text-xs font-semibold text-slate-600 hover:text-brand-600 dark:text-slate-300 dark:hover:text-brand-400" href="<?php echo esc_url( add_query_arg( 'esk_backup_download', rawurlencode( $f['name'] ) ) ); ?>"><?php esc_html_e( 'Download', 'eskoofy' ); ?></a>
									<a class="ml-3 text-xs font-semibold text-amber-700 hover:underline dark:text-amber-400" data-confirm="<?php esc_attr_e( 'Restore this backup? Existing data will be overwritten.', 'eskoofy' ); ?>" href="<?php echo esc_url( add_query_arg( 'esk_backup_restore', rawurlencode( $f['name'] ) ) ); ?>"><?php esc_html_e( 'Restore', 'eskoofy' ); ?></a>
									<a class="ml-3 text-xs font-semibold text-red-700 hover:underline dark:text-red-400" data-confirm="<?php esc_attr_e( 'Delete this backup file?', 'eskoofy' ); ?>" href="<?php echo esc_url( add_query_arg( 'esk_backup_delete', rawurlencode( $f['name'] ) ) ); ?>"><?php esc_html_e( 'Delete', 'eskoofy' ); ?></a>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php else : ?>
						<tr>
							<td colspan="4" class="px-4 py-16">
								<div class="text-center">
									<svg class="mx-auto h-10 w-10 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
									<p class="mt-4 text-sm font-medium text-slate-900 dark:text-white"><?php esc_html_e( 'No backups yet', 'eskoofy' ); ?></p>
									<p class="mt-1 text-sm text-slate-500 dark:text-slate-400"><?php esc_html_e( 'Create your first backup to get started.', 'eskoofy' ); ?></p>
								</div>
							</td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>