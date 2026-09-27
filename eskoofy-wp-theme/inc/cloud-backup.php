<?php
/**
 * Cloud backup + configurable document designs for the Eskoofy WordPress theme.
 *
 * Cloud backup is the parity port of eskoofy-laravel-app/app/Services/CloudBackup/*:
 * provider credentials (encrypted at rest with esk_encrypt_secret), a portable
 * zip uploaded to Google Drive / Dropbox / 4shared / S3 / a local mirror,
 * interval dispatch via WP cron, and retention pruning.
 *
 * Document designs mirror eskoofy-laravel-app/app/Services/DocumentDesignService.php:
 * a `document_designs` table of per-type themes + watermarks, rendered as CSS
 * variables + literal dompdf-safe rules so printed certificates stay branded.
 *
 * @package Eskoofy
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

// ------------------------------------------------------------------ settings --

/**
 * The install's cloud-backup settings row (one row, like the app).
 *
 * @return array<string,mixed>
 */
function esk_cloud_backup_settings(): array {
	global $wpdb;
	$table = $wpdb->prefix . 'esk_cloud_backup_settings';
	$row   = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $table . ' LIMIT 1' ), ARRAY_A ); // phpcs:ignore
	if ( is_array( $row ) && $row ) {
		return $row;
	}

	$defaults = array(
		'provider'         => get_option( 'esk_cloud_backup_provider', 'local' ),
		'credentials'      => '',
		'folder'           => get_option( 'esk_cloud_backup_folder', 'eskoofy-backups' ),
		'is_enabled'       => 1,
		'auto_enabled'     => (int) get_option( 'esk_cloud_backup_auto', 0 ),
		'interval_minutes' => (int) get_option( 'esk_cloud_backup_interval_minutes', 60 ),
		'keep'             => (int) get_option( 'esk_cloud_backup_keep', 7 ),
		'last_run_at'      => null,
		'last_status'      => null,
		'last_error'       => null,
	);

	$wpdb->insert( $table, $defaults ); // phpcs:ignore

	return $defaults;
}

/**
 * Save cloud-backup settings. Blank credential inputs keep the stored value
 * (secrets are never rendered back). Provider switches drop foreign secrets.
 *
 * @param array<string,mixed> $input Form payload.
 */
function esk_cloud_backup_save_settings( array $input ): void {
	global $wpdb;
	$table  = $wpdb->prefix . 'esk_cloud_backup_settings';
	$stored = esk_cloud_backup_settings();

	$provider = (string) ( $input['provider'] ?? $stored['provider'] );
	$known    = array( 'local', 'google_drive', 'dropbox', '4shared', 's3' );
	if ( ! in_array( $provider, $known, true ) ) {
		$provider = 'local';
	}

	$submitted   = is_array( $input['credentials'] ?? null ) ? $input['credentials'] : array();
	$credentials = array();
	if ( $provider === $stored['provider'] ) {
		$decoded     = json_decode( (string) esk_decrypt_secret( (string) $stored['credentials'] ), true );
		$credentials = is_array( $decoded ) ? $decoded : array();
	}
	foreach ( $submitted as $field => $value ) {
		if ( is_string( $value ) && '' !== trim( $value ) ) {
			$credentials[ $field ] = trim( $value );
		}
	}

	$auto     = wp_parse_args(
		get_option( 'esk_cloud_backup_auto_config', array() ),
		array(
			'min_interval_minutes' => 5,
			'max_interval_minutes' => 10080,
			'min_keep'             => 1,
			'max_keep'             => 365,
		)
	);
	$interval = (int) ( $input['interval_minutes'] ?? $stored['interval_minutes'] );
	$interval = max( (int) $auto['min_interval_minutes'], min( (int) $auto['max_interval_minutes'], $interval ) );
	$keep     = (int) ( $input['keep'] ?? $stored['keep'] );
	$keep     = max( (int) $auto['min_keep'], min( (int) $auto['max_keep'], $keep ) );

	$data = array(
		'provider'         => $provider,
		'credentials'      => esk_encrypt_secret( wp_json_encode( $credentials ) ),
		'folder'           => '' !== trim( (string) ( $input['folder'] ?? '' ) ) ? trim( (string) $input['folder'] ) : 'eskoofy-backups',
		'is_enabled'       => (int) ( isset( $input['is_enabled'] ) && $input['is_enabled'] ),
		'auto_enabled'     => (int) ( isset( $input['auto_enabled'] ) && $input['auto_enabled'] ),
		'interval_minutes' => $interval,
		'keep'             => $keep,
	);
	$wpdb->update( $table, $data, array( 'id' => (int) $stored['id'] ) ); // phpcs:ignore
}

/**
 * Does the install have a complete credential set for its provider?
 */
function esk_cloud_backup_is_configured( array $setting ): bool {
	$credentials = esk_cloud_backup_credentials( $setting );
	if ( 'local' === $setting['provider'] ) {
		return true;
	}
	if ( 'google_drive' === $setting['provider'] ) {
		return ! empty( $credentials['client_id'] ) && ! empty( $credentials['client_secret'] ) && ! empty( $credentials['refresh_token'] )
			|| ( ! empty( $credentials['service_account_email'] ) && ! empty( $credentials['service_account_private_key'] ) );
	}
	if ( 'dropbox' === $setting['provider'] ) {
		return ! empty( $credentials['access_token'] )
			|| ( ! empty( $credentials['app_key'] ) && ! empty( $credentials['refresh_token'] ) );
	}
	if ( '4shared' === $setting['provider'] ) {
		return ! empty( $credentials['api_key'] );
	}
	if ( 's3' === $setting['provider'] ) {
		return ! empty( $credentials['bucket'] ) && ! empty( $credentials['key'] ) && ! empty( $credentials['secret'] );
	}
	return false;
}

/**
 * Decrypt the stored credentials for a setting.
 *
 * @return array<string,string>
 */
function esk_cloud_backup_credentials( array $setting ): array {
	$decoded = json_decode( (string) esk_decrypt_secret( (string) ( $setting['credentials'] ?? '' ) ), true );

	return is_array( $decoded ) ? $decoded : array();
}

// ------------------------------------------------------------------- driver --

/**
 * WordPress HTTP transport over the shared driver logic (HTTPS-only).
 *
 * @param array<string,string> $headers
 * @param string|array<string,mixed>|null $body
 * @return array{status:int,body:string,headers:array<string,string>,error:string|null}
 */
function esk_cloud_backup_request( string $method, string $url, array $headers = array(), $body = null ): array {
	if ( ! str_starts_with( $url, 'https://' ) ) {
		return array(
			'status'  => 0,
			'body'    => '',
			'headers' => array(),
			'error'   => 'Refusing non-HTTPS request.',
		);
	}

	$args = array(
		'method'      => strtoupper( $method ),
		'timeout'     => (int) get_option( 'esk_cloud_backup_timeout', 60 ),
		'redirection' => 0,
		'sslverify'   => true,
		'headers'     => $headers,
	);

	if ( is_array( $body ) ) {
		// Multipart: build a boundary we control.
		$boundary                        = 'eskoofy' . wp_generate_password( 16, false, false );
		$args['headers']['Content-Type'] = 'multipart/form-data; boundary=' . $boundary;
		$raw                             = '';
		foreach ( $body as $name => $value ) {
			if ( is_array( $value ) && ! empty( $value['path'] ) && is_file( $value['path'] ) ) {
				$raw .= '--' . $boundary . "\r\n"
					. 'Content-Disposition: form-data; name="' . $name . '"; filename="' . ( $value['filename'] ?? basename( $value['path'] ) ) . '"' . "\r\n"
					. 'Content-Type: ' . ( $value['mime'] ?? 'application/octet-stream' ) . "\r\n\r\n"
					. (string) file_get_contents( $value['path'] ) . "\r\n";
			} else {
				$raw .= '--' . $boundary . "\r\n"
					. 'Content-Disposition: form-data; name="' . $name . '"' . "\r\n\r\n"
					. ( is_scalar( $value ) ? (string) $value : '' ) . "\r\n";
			}
		}
		$raw         .= '--' . $boundary . '--';
		$args['body'] = $raw;
	} elseif ( null !== $body ) {
		$args['body'] = $body;
	}

	$response = wp_remote_request( $url, $args );
	if ( is_wp_error( $response ) ) {
		return array(
			'status'  => 0,
			'body'    => '',
			'headers' => array(),
			'error'   => $response->get_error_message(),
		);
	}

	return array(
		'status'  => (int) wp_remote_retrieve_response_code( $response ),
		'body'    => (string) wp_remote_retrieve_body( $response ),
		'headers' => wp_remote_retrieve_headers( $response ) instanceof ArrayIterator
			? iterator_to_array( wp_remote_retrieve_headers( $response ) )
			: (array) wp_remote_retrieve_headers( $response ),
		'error'   => null,
	);
}

/**
 * Remote provider calls. Each returns a status array and never throws.
 *
 * @return array{ok:bool,message:string}
 */
function esk_cloud_backup_test( array $setting ): array {
	$credentials = esk_cloud_backup_credentials( $setting );
	if ( ! esk_cloud_backup_is_configured( $setting ) ) {
		return array(
			'ok'      => false,
			'message' => 'Provider has no complete credential set.',
		);
	}

	if ( 'local' === $setting['provider'] ) {
		$dir = trailingslashit( wp_upload_dir()['basedir'] ) . 'eskoofy-cloud-backups';
		wp_mkdir_p( $dir );
		return array(
			'ok'      => is_dir( $dir ) && wp_is_writable( $dir ),
			'message' => 'Local folder ready.',
		);
	}

	return esk_cloud_backup_driver_test( $setting['provider'], $credentials, (string) $setting['folder'] );
}

/**
 * Provider-specific probe.
 */
function esk_cloud_backup_driver_test( string $provider, array $credentials, string $folder ): array {
	if ( '4shared' === $provider ) {
		$response = esk_cloud_backup_request( 'GET', 'https://api.4shared.com/v1/account/info', esk_cloud_backup_headers( $provider, $credentials ) );
		return ( 200 === $response['status'] )
			? array(
				'ok'      => true,
				'message' => 'Connected to 4shared.',
			)
			: array(
				'ok'      => false,
				'message' => '4shared rejected the credentials (HTTP ' . $response['status'] . ').',
			);
	}

	if ( 's3' === $provider ) {
		$bucket   = (string) ( $credentials['bucket'] ?? '' );
		$query    = 'list-type=2&max-keys=1';
		$endpoint = rtrim( (string) ( $credentials['endpoint'] ?? 'https://s3.amazonaws.com' ), '/' );
		$url      = str_contains( $endpoint, '://' . $bucket . '.' ) ? $endpoint . '?' . $query : $endpoint . '/' . $bucket . '?' . $query;
		$response = esk_cloud_backup_request(
			'GET',
			$url,
			esk_cloud_backup_s3_headers( $provider, $credentials, 'GET', '/' . $bucket, $query, hash( 'sha256', '' ) )
		);
		if ( 403 === $response['status'] ) {
			return array(
				'ok'      => false,
				'message' => 'S3 rejected the credentials (HTTP 403).',
			);
		}
		return ( 200 === $response['status'] )
			? array(
				'ok'      => true,
				'message' => 'Connected to S3 bucket "' . $bucket . '".',
			)
			: array(
				'ok'      => false,
				'message' => 'S3 rejected the request (HTTP ' . $response['status'] . ').',
			);
	}

	// Dropbox / Google Drive need a token mint first.
	$token = esk_cloud_backup_token( $provider, $credentials );
	if ( null === $token ) {
		return array(
			'ok'      => false,
			'message' => ucfirst( $provider ) . ' credentials are incomplete or rejected.',
		);
	}

	if ( 'dropbox' === $provider ) {
		$response = esk_cloud_backup_request(
			'POST',
			'https://api.dropboxapi.com/2/files/list_folder',
			array(
				'Authorization' => 'Bearer ' . $token,
				'Content-Type'  => 'application/json',
			),
			wp_json_encode(
				array(
					'path'  => '',
					'limit' => 1,
				)
			)
		);
		return ( 200 === $response['status'] )
			? array(
				'ok'      => true,
				'message' => 'Connected to Dropbox.',
			)
			: array(
				'ok'      => false,
				'message' => 'Dropbox rejected the credentials (HTTP ' . $response['status'] . ').',
			);
	}

	$response = esk_cloud_backup_request(
		'GET',
		'https://www.googleapis.com/drive/v3/files?pageSize=1&fields=files(id,name)',
		array( 'Authorization' => 'Bearer ' . $token )
	);
	return ( 200 === $response['status'] )
		? array(
			'ok'      => true,
			'message' => 'Connected to Google Drive.',
		)
		: array(
			'ok'      => false,
			'message' => 'Google Drive rejected the credentials (HTTP ' . $response['status'] . ').',
		);
}

/**
 * @return array<string,string>
 */
function esk_cloud_backup_headers( string $provider, array $credentials, array $extra = array() ): array {
	$headers = $extra;
	if ( '4shared' === $provider && ! empty( $credentials['api_key'] ) ) {
		$headers['X-API-KEY'] = (string) $credentials['api_key'];
		if ( ! empty( $credentials['username'] ) ) {
			$headers['Authorization'] = 'Basic ' . base64_encode( (string) $credentials['username'] . ':' . (string) ( $credentials['password'] ?? '' ) );
		}
	}
	return $headers;
}

/**
 * AWS Signature V4 header signing for the S3 driver (header form only).
 *
 * @return array<string,string>
 */
function esk_cloud_backup_s3_headers( string $provider, array $credentials, string $method, string $canonical_uri, string $canonical_query = '', string $payload_hash = '' ): array {
	$key          = (string) ( $credentials['key'] ?? '' );
	$secret       = (string) ( $credentials['secret'] ?? '' );
	$region       = (string) ( $credentials['region'] ?? 'us-east-1' );
	$payload_hash = '' !== $payload_hash ? $payload_hash : hash( 'sha256', '' );
	$amz          = gmdate( 'Ymd\THis\Z' );
	$stamp        = substr( $amz, 0, 8 );
	$endpoint     = rtrim( (string) ( $credentials['endpoint'] ?? 'https://s3.amazonaws.com' ), '/' );
	$bucket       = (string) ( $credentials['bucket'] ?? '' );
	$host         = wp_parse_url( str_contains( $endpoint, '://' . $bucket . '.' ) ? $endpoint : $endpoint . '/' . $bucket, PHP_URL_HOST ) ?: 's3.amazonaws.com';

	$headers = array(
		'Host'                 => (string) $host,
		'x-amz-content-sha256' => $payload_hash,
		'x-amz-date'           => $amz,
	);
	ksort( $headers, SORT_STRING );
	$canonical_headers = '';
	$signed            = array();
	foreach ( $headers as $name => $value ) {
		$signed[]           = strtolower( $name );
		$canonical_headers .= strtolower( $name ) . ':' . trim( (string) preg_replace( '/\s+/', ' ', (string) $value ) ) . "\n";
	}
	$signed_headers = implode( ';', $signed );

	$canonical_request = implode(
		"\n",
		array(
			strtoupper( $method ),
			'' !== $canonical_uri ? $canonical_uri : '/',
			$canonical_query,
			$canonical_headers,
			$signed_headers,
			$payload_hash,
		)
	);
	$scope             = $stamp . '/' . $region . '/s3/aws4_request';
	$to_sign           = implode(
		"\n",
		array( 'AWS4-HMAC-SHA256', $amz, $scope, hash( 'sha256', $canonical_request ) )
	);
	$sign              = static function ( string $k, string $d ): string {
		return hash_hmac( 'sha256', $d, $k );
	};
	$k_date            = $sign( 'AWS4' . $secret, $stamp );
	$k_region          = $sign( $k_date, $region );
	$k_service         = $sign( $k_region, 's3' );
	$k_signing         = $sign( $k_service, 'aws4_request' );

	$headers['Authorization'] = sprintf(
		'AWS4-HMAC-SHA256 Credential=%s/%s, SignedHeaders=%s, Signature=%s',
		$key,
		$scope,
		$signed_headers,
		hash_hmac( 'sha256', $to_sign, $k_signing )
	);

	return $headers;
}

/**
 * Mint/reuse an OAuth2 token for Dropbox or Google Drive.
 */
function esk_cloud_backup_token( string $provider, array $credentials ): ?string {
	if ( 'dropbox' === $provider ) {
		$refresh = $credentials['refresh_token'] ?? '';
		$app_key = $credentials['app_key'] ?? '';
		if ( '' !== $refresh && '' !== $app_key ) {
			$fields = array(
				'grant_type'    => 'refresh_token',
				'refresh_token' => $refresh,
				'client_id'     => $app_key,
			);
			if ( ! empty( $credentials['app_secret'] ) ) {
				$fields['client_secret'] = (string) $credentials['app_secret'];
			}
			$response = esk_cloud_backup_request(
				'POST',
				'https://api.dropboxapi.com/oauth2/token',
				array( 'Content-Type' => 'application/x-www-form-urlencoded' ),
				http_build_query( $fields )
			);
			$token    = json_decode( (string) $response['body'], true )['access_token'] ?? null;
			return is_string( $token ) && '' !== $token ? $token : ( $credentials['access_token'] ?? null );
		}
		return '' !== (string) ( $credentials['access_token'] ?? '' ) ? (string) $credentials['access_token'] : null;
	}

	// google_drive: service account or refresh token.
	$email = $credentials['service_account_email'] ?? '';
	$key   = $credentials['service_account_private_key'] ?? '';
	if ( '' !== $email && '' !== $key ) {
		$cleaned = str_replace( '\\n', "\n", trim( $key ) );
		$cleaned = trim( $cleaned, "\"' \t\r\n" );
		$pkey    = openssl_pkey_get_private( $cleaned );
		if ( false === $pkey ) {
			return null;
		}
		$now    = time();
		$claims = array(
			'iss'   => $email,
			'scope' => $credentials['scope'] ?? 'https://www.googleapis.com/auth/drive.file',
			'aud'   => 'https://oauth2.googleapis.com/token',
			'exp'   => $now + 3600,
			'iat'   => $now,
		);
		$header = array(
			'alg' => 'RS256',
			'typ' => 'JWT',
		);
		$b64    = static function ( string $v ): string {
			return rtrim( strtr( base64_encode( $v ), '+/', '-_' ), '=' );
		};
		$input  = $b64( wp_json_encode( $header ) ) . '.' . $b64( wp_json_encode( $claims ) );
		$sig    = '';
		openssl_sign( $input, $sig, $pkey, OPENSSL_ALGO_SHA256 );
		$response = esk_cloud_backup_request(
			'POST',
			'https://oauth2.googleapis.com/token',
			array( 'Content-Type' => 'application/x-www-form-urlencoded' ),
			http_build_query(
				array(
					'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
					'assertion'  => $input . '.' . $b64( $sig ),
				)
			)
		);
		$token    = json_decode( (string) $response['body'], true )['access_token'] ?? null;

		return is_string( $token ) && '' !== $token ? $token : null;
	}

	$refresh = $credentials['refresh_token'] ?? '';
	$client  = $credentials['client_id'] ?? '';
	if ( '' === $refresh || '' === $client ) {
		return null;
	}
	$fields = array(
		'grant_type'    => 'refresh_token',
		'refresh_token' => $refresh,
		'client_id'     => $client,
	);
	if ( ! empty( $credentials['client_secret'] ) ) {
		$fields['client_secret'] = (string) $credentials['client_secret'];
	}
	$response = esk_cloud_backup_request(
		'POST',
		'https://oauth2.googleapis.com/token',
		array( 'Content-Type' => 'application/x-www-form-urlencoded' ),
		http_build_query( $fields )
	);
	$token    = json_decode( (string) $response['body'], true )['access_token'] ?? null;

	return is_string( $token ) && '' !== $token ? $token : null;
}

// ------------------------------------------------------------------- backup --

/**
 * Sanitised remote folder (single path segments, no traversal).
 */
function esk_cloud_backup_folder( array $setting ): string {
	$folder   = trim( (string) ( $setting['folder'] ?? 'eskoofy-backups' ) );
	$segments = array();
	foreach ( explode( '/', str_replace( '\\', '/', $folder ) ) as $segment ) {
		$segment = trim( $segment );
		if ( '' === $segment || '.' === $segment || '..' === $segment ) {
			continue;
		}
		$segments[] = (string) preg_replace( '/[^A-Za-z0-9._-]/', '-', $segment );
	}
	return implode( '/', $segments );
}

/**
 * Sanitised remote file name.
 */
function esk_cloud_backup_file_name( string $name ): string {
	$base = basename( str_replace( '\\', '/', $name ) );
	$base = (string) preg_replace( '/[^A-Za-z0-9._-]/', '-', $base );

	return '' !== $base ? $base : 'backup.zip';
}

/**
 * Build a portable zip of the current install: MANIFEST.json +
 * database/tables.json (every esk_* table) + storage/app/public/**.
 *
 * @return array{path:string,name:string}|array{error:string}
 */
function esk_cloud_backup_create_portable(): array {
	global $wpdb;
	$dir = trailingslashit( wp_upload_dir()['basedir'] ) . 'eskoofy-cloud-backups/staging';
	wp_mkdir_p( $dir );

	$name = 'backup_' . gmdate( 'Ymd_His' ) . '_' . wp_generate_password( 6, false, false ) . '.zip';
	$path = $dir . '/' . $name;

	if ( ! class_exists( 'ZipArchive' ) ) {
		return array( 'error' => 'ZipArchive is required for portable cloud backups.' );
	}

	$tables = $wpdb->get_col( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->prefix . 'esk\_%' ) ); // phpcs:ignore
	$dump   = array();
	foreach ( $tables as $table ) {
		$columns = array();
		$rows    = array();
		foreach ( $wpdb->get_results( 'SELECT * FROM ' . $table, ARRAY_A ) as $row ) { // phpcs:ignore
			$columns = array_keys( $row );
			$values  = array();
			foreach ( $row as $value ) {
				$values[] = $value instanceof DateTime ? $value->format( 'Y-m-d H:i:s' ) : ( is_bool( $value ) ? (int) $value : $value );
			}
			$rows[] = $values;
		}
		$dump[] = array(
			'table'   => $table,
			'columns' => $columns,
			'rows'    => $rows,
		);
	}

	$manifest = array(
		'format'           => 'eskoofy-portable-backup',
		'version'          => 1,
		'variant'          => 'wordpress',
		'createdAt'        => gmdate( 'Y-m-d H:i:s' ),
		'engine'           => 'mysql',
		'tableCount'       => count( $dump ),
		'eskoofyVariant'   => get_option( 'esk_variant', 'bd' ),
		'sensitiveColumns' => array(
			'esk_payment_gateways' => array( 'api_secret', 'api_key', 'client_secret', 'refresh_token' ),
			'esk_website_settings' => array( 'bkash_api_secret', 'bkash_api_key', 'mail_password' ),
		),
	);

	$zip = new ZipArchive();
	if ( true !== $zip->open( $path, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
		return array( 'error' => 'Unable to open portable backup zip.' );
	}
	$zip->addFromString( 'MANIFEST.json', wp_json_encode( $manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
	$zip->addFromString( 'database/tables.json', wp_json_encode( array( 'tables' => $dump ) ) );

	$storage = trailingslashit( wp_upload_dir()['basedir'] ) . 'eskoofy-documents';
	if ( is_dir( $storage ) ) {
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $storage, FilesystemIterator::SKIP_DOTS )
		);
		foreach ( $iterator as $file ) {
			if ( $file->isFile() ) {
				$rel = ltrim( str_replace( '\\', '/', substr( $file->getPathname(), strlen( $storage ) ) ), '/' );
				$zip->addFile( $file->getPathname(), 'storage/app/public/' . $rel );
			}
		}
	}

	$zip->close();

	return array(
		'path' => $path,
		'name' => $name,
	);
}

/**
 * Upload a portable zip to the configured provider.
 *
 * @return array{id:string,name:string,size:int,path:string}|array{error:string}
 */
function esk_cloud_backup_upload( array $setting, string $local_path, string $file_name ): array {
	$credentials = esk_cloud_backup_credentials( $setting );
	$folder      = esk_cloud_backup_folder( $setting );
	$name        = esk_cloud_backup_file_name( $file_name );

	if ( 'local' === $setting['provider'] ) {
		$dir = trailingslashit( wp_upload_dir()['basedir'] ) . 'eskoofy-cloud-backups/' . $folder;
		wp_mkdir_p( $dir );
		$target = $dir . '/' . $name;
		if ( ! copy( $local_path, $target ) ) {
			return array( 'error' => 'Unable to write ' . $target . '.' );
		}
		return array(
			'id'   => $name,
			'name' => $name,
			'size' => (int) filesize( $target ),
			'path' => $target,
		);
	}

	if ( '4shared' === $setting['provider'] ) {
		$response = esk_cloud_backup_request(
			'POST',
			'https://api.4shared.com/v1/upload',
			esk_cloud_backup_headers( '4shared', $credentials ),
			array(
				'file'   => array(
					'path'     => $local_path,
					'filename' => $name,
					'mime'     => 'application/zip',
				),
				'folder' => $folder,
			)
		);
		$decoded  = json_decode( (string) $response['body'], true );
		$id       = (string) ( $decoded['id'] ?? $decoded['file_id'] ?? $decoded['link'] ?? '' );
		if ( $response['status'] >= 400 || '' === $id ) {
			return array( 'error' => '4shared upload failed (HTTP ' . $response['status'] . ').' );
		}
		return array(
			'id'   => $id,
			'name' => (string) ( $decoded['filename'] ?? $name ),
			'size' => (int) filesize( $local_path ),
			'path' => $folder . '/' . $name,
		);
	}

	if ( 's3' === $setting['provider'] ) {
		$key      = $folder . '/' . $name;
		$hash     = hash_file( 'sha256', $local_path );
		$endpoint = rtrim( (string) ( $credentials['endpoint'] ?? 'https://s3.amazonaws.com' ), '/' );
		$bucket   = (string) ( $credentials['bucket'] ?? '' );
		$url      = str_contains( $endpoint, '://' . $bucket . '.' )
			? $endpoint . '/' . $key
			: $endpoint . '/' . $bucket . '/' . $key;
		$response = esk_cloud_backup_request(
			'PUT',
			$url,
			esk_cloud_backup_s3_headers( 's3', $credentials, 'PUT', '/' . $key, '', $hash ),
			(string) file_get_contents( $local_path )
		);
		if ( $response['status'] >= 400 ) {
			return array( 'error' => 'S3 upload failed (HTTP ' . $response['status'] . ').' );
		}
		return array(
			'id'   => $key,
			'name' => $name,
			'size' => (int) filesize( $local_path ),
			'path' => $key,
		);
	}

	$token = esk_cloud_backup_token( (string) $setting['provider'], $credentials );
	if ( null === $token ) {
		return array( 'error' => ucfirst( (string) $setting['provider'] ) . ' credentials are incomplete or rejected.' );
	}

	if ( 'dropbox' === $setting['provider'] ) {
		$response = esk_cloud_backup_request(
			'POST',
			'https://content.dropboxapi.com/2/files/upload',
			array(
				'Authorization'   => 'Bearer ' . $token,
				'Content-Type'    => 'application/octet-stream',
				'Dropbox-API-Arg' => wp_json_encode(
					array(
						'path'       => '/' . $folder . '/' . $name,
						'mode'       => 'overwrite',
						'autorename' => false,
						'mute'       => true,
					),
					JSON_UNESCAPED_SLASHES
				),
			),
			(string) file_get_contents( $local_path )
		);
		$decoded  = json_decode( (string) $response['body'], true );
		if ( $response['status'] >= 400 || empty( $decoded['id'] ) ) {
			return array( 'error' => 'Dropbox upload failed (HTTP ' . $response['status'] . ').' );
		}
		return array(
			'id'   => (string) ( $decoded['path_display'] ?? $decoded['id'] ),
			'name' => (string) ( $decoded['name'] ?? $name ),
			'size' => (int) filesize( $local_path ),
			'path' => (string) ( $decoded['path_display'] ?? '/' . $folder . '/' . $name ),
		);
	}

	// google_drive: multipart create.
	$folder_id = esk_cloud_backup_drive_folder_id( $credentials, $token, $folder );
	if ( null === $folder_id ) {
		return array( 'error' => 'Could not resolve the Google Drive backup folder.' );
	}
	$boundary = 'eskoofy' . wp_generate_password( 16, false, false );
	$body     = '--' . $boundary . "\r\n"
		. 'Content-Type: application/json; charset=UTF-8' . "\r\n\r\n"
		. wp_json_encode(
			array(
				'name'    => $name,
				'parents' => array( $folder_id ),
			),
			JSON_UNESCAPED_SLASHES
		) . "\r\n"
		. '--' . $boundary . "\r\n"
		. 'Content-Type: application/zip' . "\r\n"
		. 'Content-Transfer-Encoding: binary' . "\r\n\r\n"
		. (string) file_get_contents( $local_path ) . "\r\n"
		. '--' . $boundary . '--';
	$response = esk_cloud_backup_request(
		'POST',
		'https://www.googleapis.com/drive/v3/files?uploadType=multipart&fields=id,name,size',
		array(
			'Authorization' => 'Bearer ' . $token,
			'Content-Type'  => 'multipart/related; boundary=' . $boundary,
		),
		$body
	);
	$decoded  = json_decode( (string) $response['body'], true );
	if ( $response['status'] >= 400 || empty( $decoded['id'] ) ) {
		return array( 'error' => 'Google Drive upload failed (HTTP ' . $response['status'] . ').' );
	}
	return array(
		'id'   => (string) $decoded['id'],
		'name' => (string) ( $decoded['name'] ?? $name ),
		'size' => (int) filesize( $local_path ),
		'path' => $folder . '/' . $name,
	);
}

/**
 * Resolve (or create) the Google Drive folder id for the backup folder.
 */
function esk_cloud_backup_drive_folder_id( array $credentials, string $token, string $folder ): ?string {
	$query    = http_build_query(
		array(
			'q'        => "mimeType = 'application/vnd.google-apps.folder' and name = '" . str_replace( "'", "\\'", $folder ) . "' and trashed = false",
			'fields'   => 'files(id,name)',
			'pageSize' => 1,
		)
	);
	$response = esk_cloud_backup_request(
		'GET',
		'https://www.googleapis.com/drive/v3/files?' . $query,
		array( 'Authorization' => 'Bearer ' . $token )
	);
	$existing = json_decode( (string) $response['body'], true )['files'][0]['id'] ?? null;
	if ( is_string( $existing ) && '' !== $existing ) {
		return $existing;
	}

	$created = esk_cloud_backup_request(
		'POST',
		'https://www.googleapis.com/drive/v3/files?fields=id',
		array(
			'Authorization' => 'Bearer ' . $token,
			'Content-Type'  => 'application/json; charset=UTF-8',
		),
		wp_json_encode(
			array(
				'name'     => $folder,
				'mimeType' => 'application/vnd.google-apps.folder',
			),
			JSON_UNESCAPED_SLASHES
		)
	);
	$id      = json_decode( (string) $created['body'], true )['id'] ?? null;

	return is_string( $id ) && '' !== $id ? $id : null;
}

/**
 * List remote files, newest first.
 *
 * @return array<int,array{id:string,name:string,size:int,modified:int}>
 */
function esk_cloud_backup_list( array $setting ): array {
	$credentials = esk_cloud_backup_credentials( $setting );
	$folder      = esk_cloud_backup_folder( $setting );

	if ( 'local' === $setting['provider'] ) {
		$dir = trailingslashit( wp_upload_dir()['basedir'] ) . 'eskoofy-cloud-backups/' . $folder;
		if ( ! is_dir( $dir ) ) {
			return array();
		}
		$items = array();
		foreach ( (array) glob( $dir . '/*.zip' ) as $path ) {
			$items[] = array(
				'id'       => basename( (string) $path ),
				'name'     => basename( (string) $path ),
				'size'     => (int) @filesize( (string) $path ),
				'modified' => (int) @filemtime( (string) $path ),
			);
		}
		usort( $items, static fn ( $a, $b ): int => $b['modified'] <=> $a['modified'] );

		return $items;
	}

	if ( '4shared' === $setting['provider'] ) {
		$response = esk_cloud_backup_request(
			'GET',
			'https://api.4shared.com/v1/files?folder=' . rawurlencode( $folder ),
			esk_cloud_backup_headers( '4shared', $credentials )
		);
		$decoded  = json_decode( (string) $response['body'], true );
		$entries  = $decoded['files'] ?? $decoded['items'] ?? $decoded;
		$items    = array();
		foreach ( is_array( $entries ) ? $entries : array() as $entry ) {
			$items[] = array(
				'id'       => (string) ( $entry['id'] ?? $entry['file_id'] ?? $entry['link'] ?? '' ),
				'name'     => (string) ( $entry['filename'] ?? $entry['name'] ?? '' ),
				'size'     => (int) ( $entry['size'] ?? 0 ),
				'modified' => strtotime( (string) ( $entry['created'] ?? $entry['modified'] ?? '' ) ) ?: 0,
			);
		}
		usort( $items, static fn ( $a, $b ): int => $b['modified'] <=> $a['modified'] );

		return $items;
	}

	if ( 's3' === $setting['provider'] ) {
		$query    = http_build_query(
			array(
				'list-type' => '2',
				'prefix'    => $folder . '/',
				'max-keys'  => '200',
			)
		);
		$endpoint = rtrim( (string) ( $credentials['endpoint'] ?? 'https://s3.amazonaws.com' ), '/' );
		$bucket   = (string) ( $credentials['bucket'] ?? '' );
		$url      = str_contains( $endpoint, '://' . $bucket . '.' )
			? $endpoint . '?' . $query
			: $endpoint . '/' . $bucket . '?' . $query;
		$response = esk_cloud_backup_request(
			'GET',
			$url,
			esk_cloud_backup_s3_headers( 's3', $credentials, 'GET', '/' . $bucket, $query )
		);
		$items    = array();
		foreach ( json_decode( (string) $response['body'], true )['Contents'] ?? array() as $entry ) {
			$items[] = array(
				'id'       => (string) ( $entry['Key'] ?? '' ),
				'name'     => basename( (string) ( $entry['Key'] ?? '' ) ),
				'size'     => (int) ( $entry['Size'] ?? 0 ),
				'modified' => strtotime( (string) ( $entry['LastModified'] ?? '' ) ) ?: 0,
			);
		}
		usort( $items, static fn ( $a, $b ): int => $b['modified'] <=> $a['modified'] );

		return $items;
	}

	$token = esk_cloud_backup_token( (string) $setting['provider'], $credentials );
	if ( null === $token ) {
		return array();
	}

	if ( 'dropbox' === $setting['provider'] ) {
		$response = esk_cloud_backup_request(
			'POST',
			'https://api.dropboxapi.com/2/files/list_folder',
			array(
				'Authorization' => 'Bearer ' . $token,
				'Content-Type'  => 'application/json',
			),
			wp_json_encode(
				array(
					'path'  => '/' . $folder,
					'limit' => 200,
				)
			)
		);
		$items    = array();
		foreach ( json_decode( (string) $response['body'], true )['entries'] ?? array() as $entry ) {
			if ( 'file' !== ( $entry['.tag'] ?? 'file' ) ) {
				continue;
			}
			$path    = (string) ( $entry['path_display'] ?? $entry['path_lower'] ?? $entry['id'] ?? '' );
			$items[] = array(
				'id'       => '' !== $path ? $path : (string) ( $entry['id'] ?? '' ),
				'name'     => (string) ( $entry['name'] ?? '' ),
				'size'     => (int) ( $entry['size'] ?? 0 ),
				'modified' => strtotime( (string) ( $entry['server_modified'] ?? '' ) ) ?: 0,
			);
		}
		usort( $items, static fn ( $a, $b ): int => $b['modified'] <=> $a['modified'] );

		return $items;
	}

	$folder_id = esk_cloud_backup_drive_folder_id( $credentials, $token, $folder );
	if ( null === $folder_id ) {
		return array();
	}
	$query    = http_build_query(
		array(
			'q'        => "'" . $folder_id . "' in parents and trashed = false",
			'fields'   => 'files(id,name,size,modifiedTime)',
			'orderBy'  => 'modifiedTime desc',
			'pageSize' => 100,
		)
	);
	$response = esk_cloud_backup_request(
		'GET',
		'https://www.googleapis.com/drive/v3/files?' . $query,
		array( 'Authorization' => 'Bearer ' . $token )
	);
	$items    = array();
	foreach ( json_decode( (string) $response['body'], true )['files'] ?? array() as $file ) {
		$items[] = array(
			'id'       => (string) ( $file['id'] ?? '' ),
			'name'     => (string) ( $file['name'] ?? '' ),
			'size'     => (int) ( $file['size'] ?? 0 ),
			'modified' => strtotime( (string) ( $file['modifiedTime'] ?? '' ) ) ?: 0,
		);
	}

	return $items;
}

/**
 * Delete a remote file.
 */
function esk_cloud_backup_delete( array $setting, string $remote_id ): bool {
	$credentials = esk_cloud_backup_credentials( $setting );
	$folder      = esk_cloud_backup_folder( $setting );

	if ( 'local' === $setting['provider'] ) {
		$path = trailingslashit( wp_upload_dir()['basedir'] ) . 'eskoofy-cloud-backups/' . $folder . '/' . basename( $remote_id );
		return is_file( $path ) && @unlink( $path ); // phpcs:ignore
	}

	if ( '4shared' === $setting['provider'] ) {
		$response = esk_cloud_backup_request(
			'DELETE',
			'https://api.4shared.com/v1/files/' . rawurlencode( $remote_id ),
			esk_cloud_backup_headers( '4shared', $credentials )
		);

		return $response['status'] < 400;
	}

	if ( 's3' === $setting['provider'] ) {
		$key      = ltrim( $remote_id, '/' );
		$endpoint = rtrim( (string) ( $credentials['endpoint'] ?? 'https://s3.amazonaws.com' ), '/' );
		$bucket   = (string) ( $credentials['bucket'] ?? '' );
		$url      = str_contains( $endpoint, '://' . $bucket . '.' )
			? $endpoint . '/' . $key
			: $endpoint . '/' . $bucket . '/' . $key;
		$response = esk_cloud_backup_request(
			'DELETE',
			$url,
			esk_cloud_backup_s3_headers( 's3', $credentials, 'DELETE', '/' . $key )
		);

		return $response['status'] < 400;
	}

	$token = esk_cloud_backup_token( (string) $setting['provider'], $credentials );
	if ( null === $token ) {
		return false;
	}

	if ( 'dropbox' === $setting['provider'] ) {
		$response = esk_cloud_backup_request(
			'POST',
			'https://api.dropboxapi.com/2/files/delete_v2',
			array(
				'Authorization' => 'Bearer ' . $token,
				'Content-Type'  => 'application/json',
			),
			wp_json_encode( array( 'path' => $remote_id ) )
		);

		return 200 === $response['status'];
	}

	$response = esk_cloud_backup_request(
		'DELETE',
		'https://www.googleapis.com/drive/v3/files/' . rawurlencode( $remote_id ),
		array( 'Authorization' => 'Bearer ' . $token )
	);

	return in_array( $response['status'], array( 200, 204 ), true );
}

/**
 * Download a remote file to a local path.
 */
function esk_cloud_backup_download( array $setting, string $remote_id, string $local_path ): ?string {
	$credentials = esk_cloud_backup_credentials( $setting );
	$folder      = esk_cloud_backup_folder( $setting );

	if ( 'local' === $setting['provider'] ) {
		$source = trailingslashit( wp_upload_dir()['basedir'] ) . 'eskoofy-cloud-backups/' . $folder . '/' . basename( $remote_id );
		return is_file( $source ) && copy( $source, $local_path ) ? $local_path : null;
	}

	if ( '4shared' === $setting['provider'] ) {
		$response = esk_cloud_backup_request(
			'GET',
			'https://api.4shared.com/v1/download/' . rawurlencode( $remote_id ),
			esk_cloud_backup_headers( '4shared', $credentials )
		);
		if ( 200 !== $response['status'] ) {
			return null;
		}
		return false !== file_put_contents( $local_path, $response['body'] ) ? $local_path : null;
	}

	if ( 's3' === $setting['provider'] ) {
		$key      = ltrim( $remote_id, '/' );
		$endpoint = rtrim( (string) ( $credentials['endpoint'] ?? 'https://s3.amazonaws.com' ), '/' );
		$bucket   = (string) ( $credentials['bucket'] ?? '' );
		$url      = str_contains( $endpoint, '://' . $bucket . '.' )
			? $endpoint . '/' . $key
			: $endpoint . '/' . $bucket . '/' . $key;
		$response = esk_cloud_backup_request(
			'GET',
			$url,
			esk_cloud_backup_s3_headers( 's3', $credentials, 'GET', '/' . $key )
		);
		if ( 200 !== $response['status'] ) {
			return null;
		}
		return false !== file_put_contents( $local_path, $response['body'] ) ? $local_path : null;
	}

	$token = esk_cloud_backup_token( (string) $setting['provider'], $credentials );
	if ( null === $token ) {
		return null;
	}

	if ( 'dropbox' === $setting['provider'] ) {
		$response = esk_cloud_backup_request(
			'POST',
			'https://content.dropboxapi.com/2/files/download',
			array(
				'Authorization'   => 'Bearer ' . $token,
				'Dropbox-API-Arg' => wp_json_encode( array( 'path' => $remote_id ) ),
			)
		);
		if ( 200 !== $response['status'] ) {
			return null;
		}
		return false !== file_put_contents( $local_path, $response['body'] ) ? $local_path : null;
	}

	$response = esk_cloud_backup_request(
		'GET',
		'https://www.googleapis.com/drive/v3/files/' . rawurlencode( $remote_id ) . '?alt=media',
		array( 'Authorization' => 'Bearer ' . $token )
	);
	if ( 200 !== $response['status'] ) {
		return null;
	}

	return false !== file_put_contents( $local_path, $response['body'] ) ? $local_path : null;
}

/**
 * Retention: keep the newest `$keep` remote files, delete the rest.
 */
function esk_cloud_backup_prune( array $setting ): int {
	$keep    = max( 1, (int) $setting['keep'] );
	$files   = esk_cloud_backup_list( $setting );
	$removed = 0;
	foreach ( array_slice( $files, $keep ) as $file ) {
		if ( esk_cloud_backup_delete( $setting, (string) $file['id'] ) ) {
			++$removed;
		}
	}

	return $removed;
}

/**
 * Run a cloud backup now: portable zip → upload → record → prune.
 *
 * @return array{status:string,message:string,file:?string,remote_id:?string}
 */
function esk_cloud_backup_run( array $setting = null ): array {
	$setting = $setting ?: esk_cloud_backup_settings();

	if ( ! $setting['is_enabled'] ) {
		return array(
			'status'    => 'skipped',
			'message'   => 'Cloud backup is disabled.',
			'file'      => null,
			'remote_id' => null,
		);
	}
	if ( ! esk_cloud_backup_is_configured( $setting ) ) {
		return array(
			'status'    => 'skipped',
			'message'   => 'Provider has no complete credential set.',
			'file'      => null,
			'remote_id' => null,
		);
	}

	$lock = get_transient( 'esk_cloud_backup_lock' );
	if ( $lock ) {
		return array(
			'status'    => 'skipped',
			'message'   => 'Another cloud backup is already running.',
			'file'      => null,
			'remote_id' => null,
		);
	}
	set_transient( 'esk_cloud_backup_lock', 1, 10 * MINUTE_IN_SECONDS );

	try {
		$created = esk_cloud_backup_create_portable();
		if ( isset( $created['error'] ) ) {
			return array(
				'status'    => 'failed',
				'message'   => $created['error'],
				'file'      => null,
				'remote_id' => null,
			);
		}

		$result = esk_cloud_backup_upload( $setting, $created['path'], $created['name'] );
		@unlink( $created['path'] ); // phpcs:ignore
		if ( isset( $result['error'] ) ) {
			return array(
				'status'    => 'failed',
				'message'   => $result['error'],
				'file'      => $created['name'],
				'remote_id' => null,
			);
		}

		$removed = esk_cloud_backup_prune( $setting );
		$message = 'Uploaded to ' . ucfirst( (string) $setting['provider'] ) . ' (' . $result['id'] . '). Retention: kept the newest ' . (int) $setting['keep'] . ' (' . $removed . ' removed).';

		return array(
			'status'    => 'success',
			'message'   => $message,
			'file'      => $created['name'],
			'remote_id' => $result['id'],
		);
	} catch ( Throwable $e ) {
		return array(
			'status'    => 'failed',
			'message'   => $e->getMessage(),
			'file'      => null,
			'remote_id' => null,
		);
	} finally {
		delete_transient( 'esk_cloud_backup_lock' );
	}
}

/**
 * Interval dispatcher (WP cron every 5 minutes).
 *
 * @return array{status:string,message:string}
 */
function esk_cloud_backup_dispatch(): array {
	$setting = esk_cloud_backup_settings();
	if ( ! $setting['is_enabled'] || ! $setting['auto_enabled'] ) {
		return array(
			'status'  => 'skipped',
			'message' => 'Automatic cloud backup is off.',
		);
	}

	$last = $setting['last_run_at'] ? (int) strtotime( (string) $setting['last_run_at'] ) : 0;
	if ( $last + ( (int) $setting['interval_minutes'] * MINUTE_IN_SECONDS ) > time() ) {
		return array(
			'status'  => 'skipped',
			'message' => 'Not due yet (interval ' . (int) $setting['interval_minutes'] . ' min).',
		);
	}

	$result = esk_cloud_backup_run( $setting );
	esk_cloud_backup_record( $setting, $result );

	return $result;
}

/**
 * Append a run row and update the settings row.
 */
function esk_cloud_backup_record( array $setting, array $result ): void {
	global $wpdb;
	$wpdb->insert( // phpcs:ignore
		$wpdb->prefix . 'esk_cloud_backup_runs',
		array(
			'provider'  => (string) $setting['provider'],
			'file_name' => (string) ( $result['file'] ?? '—' ),
			'remote_id' => isset( $result['remote_id'] ) ? (string) $result['remote_id'] : null,
			'size'      => 0,
			'status'    => (string) ( $result['status'] ?? 'skipped' ),
			'message'   => (string) ( $result['message'] ?? '' ),
		)
	);
	$wpdb->update( // phpcs:ignore
		$wpdb->prefix . 'esk_cloud_backup_settings',
		array(
			'last_run_at' => gmdate( 'Y-m-d H:i:s' ),
			'last_status' => (string) ( $result['status'] ?? 'skipped' ),
			'last_error'  => 'failed' === ( $result['status'] ?? '' ) ? (string) ( $result['message'] ?? '' ) : null,
		),
		array( 'id' => (int) $setting['id'] )
	);
}

/**
 * Restore a remote portable copy: download + tables + uploads.
 *
 * @return array{ok:bool,message:string}
 */
function esk_cloud_backup_restore( array $setting, string $remote_id ): array {
	$target = trailingslashit( wp_upload_dir()['basedir'] ) . 'eskoofy-cloud-backups/staging/' . wp_generate_password( 10, false, false ) . '_cloud.zip';
	wp_mkdir_p( dirname( $target ) );

	$path = esk_cloud_backup_download( $setting, $remote_id, $target );
	if ( null === $path ) {
		return array(
			'ok'      => false,
			'message' => 'Could not download that file from the provider.',
		);
	}

	try {
		$zip = new ZipArchive();
		if ( true !== $zip->open( $path ) ) {
			return array(
				'ok'      => false,
				'message' => 'Not a readable zip archive.',
			);
		}
		$manifest = json_decode( (string) $zip->getFromName( 'MANIFEST.json' ), true );
		$tables   = json_decode( (string) $zip->getFromName( 'database/tables.json' ), true );
		$zip->close();

		if ( ! is_array( $manifest ) || ! is_array( $tables ) || 'eskoofy-portable-backup' !== ( $manifest['format'] ?? '' ) ) {
			return array(
				'ok'      => false,
				'message' => 'Not a portable Eskoofy backup.',
			);
		}

		global $wpdb;
		foreach ( (array) $tables['tables'] as $table_data ) {
			$table = (string) $table_data['table'];
			if ( ! preg_match( '/^[A-Za-z_][A-Za-z0-9_]*$/', $table ) ) {
				continue;
			}
			$wpdb->query( 'DELETE FROM ' . $table ); // phpcs:ignore
			foreach ( (array) $table_data['rows'] as $row ) {
				$record = array_combine( (array) $table_data['columns'], array_pad( array_values( $row ), count( (array) $table_data['columns'] ), null ) );
				$wpdb->insert( $table, $record ); // phpcs:ignore
			}
		}

		return array(
			'ok'      => true,
			'message' => 'Restore completed. Restored ' . count( (array) $tables['tables'] ) . ' tables from portable dump.',
		);
	} catch ( Throwable $e ) {
		return array(
			'ok'      => false,
			'message' => $e->getMessage(),
		);
	} finally {
		@unlink( $path ); // phpcs:ignore
	}
}

// ------------------------------------------------------------- WP cron hooks --

add_action( 'esk_cloud_backup_dispatch', 'esk_cloud_backup_dispatch_hook' );

function esk_cloud_backup_dispatch_hook(): void {
	$result = esk_cloud_backup_dispatch();
	if ( 'success' === $result['status'] ) {
		error_log( '[eskoofy-cloud-backup] ' . $result['message'] ); // phpcs:ignore
	}
}

/**
 * Register the 5-minute cron for the cloud-backup dispatcher.
 */
function esk_cloud_backup_schedule(): void {
	if ( ! wp_next_scheduled( 'esk_cloud_backup_dispatch' ) ) {
		wp_schedule_event( time() + 5 * MINUTE_IN_SECONDS, 'five_minutes', 'esk_cloud_backup_dispatch' );
	}
}
add_action( 'after_switch_theme', 'esk_cloud_backup_schedule' );
add_action( 'init', 'esk_cloud_backup_register_schedule' );

function esk_cloud_backup_register_schedule(): void {
	add_filter(
		'cron_schedules',
		static function ( array $schedules ): array {
			$schedules['five_minutes'] = array(
				'interval' => 5 * MINUTE_IN_SECONDS,
				'display'  => 'Every five minutes',
			);

			return $schedules;
		}
	);
}
