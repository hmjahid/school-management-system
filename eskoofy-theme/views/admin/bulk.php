<?php
/**
 * Bulk import / export.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

if ( isset( $_POST['esk_bulk_export'] ) ) {
	check_admin_referer( 'esk_bulk_form' );
	$resource = sanitize_text_field( wp_unslash( $_POST['resource'] ?? '' ) );

	$map = array(
		'students'    => array( 'table' => 'esk_students', 'columns' => array( 'admission_number', 'class_id', 'admission_date', 'roll_number', 'phone_1', 'email', 'status' ) ),
		'teachers'    => array( 'table' => 'esk_teachers', 'columns' => array( 'user_id', 'qualification', 'subjects' ) ),
		'fees'        => array( 'table' => 'esk_fees', 'columns' => array( 'name', 'code', 'class_id', 'amount', 'fee_type', 'frequency' ) ),
		'attendance'  => array( 'table' => 'esk_attendances', 'columns' => array( 'student_id', 'school_class_id', 'date', 'status' ) ),
	);

	if ( isset( $map[ $resource ] ) ) {
		$cfg = $map[ $resource ];
		$rows = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}{$cfg['table']} LIMIT 5000", ARRAY_A );
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $resource . '.csv' );
		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, $cfg['columns'] );
		foreach ( $rows as $r ) {
			$line = array();
			foreach ( $cfg['columns'] as $c ) {
				$line[] = $r[ $c ] ?? '';
			}
			fputcsv( $out, $line );
		}
		fclose( $out );
		exit;
	}
}

if ( isset( $_POST['esk_bulk_import'] ) && ! empty( $_FILES['csv']['tmp_name'] ) ) {
	check_admin_referer( 'esk_bulk_form' );
	$resource = sanitize_text_field( wp_unslash( $_POST['resource'] ?? '' ) );
	$map      = array(
		'students'   => 'esk_students',
		'teachers'   => 'esk_teachers',
	);
	if ( isset( $map[ $resource ] ) ) {
		$handle = fopen( $_FILES['csv']['tmp_name'], 'r' );
		$header = fgetcsv( $handle );
		$count  = 0;
		while ( ( $row = fgetcsv( $handle ) ) !== false ) {
			$data = array();
			foreach ( $header as $i => $col ) {
				$data[ $col ] = sanitize_text_field( $row[ $i ] ?? '' );
			}
			if ( ! empty( $data ) ) {
				$wpdb->insert( $wpdb->prefix . $map[ $resource ], $data );
				++$count;
			}
		}
		fclose( $handle );
		esk_flash( 'success', sprintf( __( '%d rows imported.', 'eskoofy' ), $count ) );
	}
	wp_safe_redirect( admin_url( 'admin.php?page=esk-bulk' ) );
	exit;
}

$flash = esk_get_flash( 'success' );
?>
<div class="wrap esk-admin-wrap">
	<h1><?php esc_html_e( 'Bulk Import / Export', 'eskoofy' ); ?></h1>

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<div class="esk-dashboard-columns">
		<div class="esk-dashboard-column esk-col-wide">
			<div class="esk-card esk-form-card">
				<h2><?php esc_html_e( 'Export CSV', 'eskoofy' ); ?></h2>
				<form method="post" class="esk-form">
					<?php wp_nonce_field( 'esk_bulk_form' ); ?>
					<div class="esk-form-group">
						<label><?php esc_html_e( 'Resource', 'eskoofy' ); ?></label>
						<select name="resource" required>
							<option value="students"><?php esc_html_e( 'Students', 'eskoofy' ); ?></option>
							<option value="teachers"><?php esc_html_e( 'Teachers', 'eskoofy' ); ?></option>
							<option value="fees"><?php esc_html_e( 'Fees', 'eskoofy' ); ?></option>
							<option value="attendance"><?php esc_html_e( 'Attendance', 'eskoofy' ); ?></option>
						</select>
					</div>
					<button type="submit" name="esk_bulk_export" class="button button-primary"><?php esc_html_e( 'Download CSV', 'eskoofy' ); ?></button>
				</form>
			</div>

			<div class="esk-card esk-form-card">
				<h2><?php esc_html_e( 'Import CSV', 'eskoofy' ); ?></h2>
				<form method="post" enctype="multipart/form-data" class="esk-form">
					<?php wp_nonce_field( 'esk_bulk_form' ); ?>
					<div class="esk-form-group">
						<label><?php esc_html_e( 'Resource', 'eskoofy' ); ?></label>
						<select name="resource" required>
							<option value="students"><?php esc_html_e( 'Students', 'eskoofy' ); ?></option>
							<option value="teachers"><?php esc_html_e( 'Teachers', 'eskoofy' ); ?></option>
						</select>
					</div>
					<div class="esk-form-group">
						<label><?php esc_html_e( 'CSV file', 'eskoofy' ); ?></label>
						<input type="file" name="csv" accept=".csv" required>
					</div>
					<button type="submit" name="esk_bulk_import" class="button button-primary"><?php esc_html_e( 'Import', 'eskoofy' ); ?></button>
				</form>
			</div>
		</div>
	</div>
</div>