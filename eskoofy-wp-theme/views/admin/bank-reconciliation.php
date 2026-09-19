<?php
/**
 * Bank reconciliation.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

if ( isset( $_POST['esk_br_import'] ) && ! empty( $_FILES['csv']['tmp_name'] ) ) {
	check_admin_referer( 'esk_br_form' );
	$handle = fopen( $_FILES['csv']['tmp_name'], 'r' );
	$header = fgetcsv( $handle );
	$count  = 0;
	while ( ( $row = fgetcsv( $handle ) ) !== false ) {
		$data = array();
		foreach ( $header as $i => $col ) {
			$data[ $col ] = sanitize_text_field( $row[ $i ] ?? '' );
		}
		if ( ! empty( $data['bank_account'] ) && ! empty( $data['transaction_date'] ) ) {
			$wpdb->insert( $wpdb->prefix . 'esk_bank_statements', array(
				'bank_account'     => $data['bank_account'],
				'transaction_date' => $data['transaction_date'],
				'description'      => $data['description'] ?? '',
				'amount'           => (float) ( $data['amount'] ?? 0 ),
				'type'             => $data['type'] ?? 'credit',
			) );
			++$count;
		}
	}
	fclose( $handle );
	esk_flash( 'success', sprintf( __( '%d bank entries imported.', 'eskoofy' ), $count ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-bank-reconciliation' ) );
	exit;
}

if ( isset( $_GET['action'] ) && 'reconcile' === $_GET['action'] && ! empty( $_GET['id'] ) ) {
	$id = absint( $_GET['id'] );
	check_admin_referer( 'esk_br_reconcile_' . $id );
	$wpdb->update( $wpdb->prefix . 'esk_bank_statements', array( 'reconciled' => 1 ), array( 'id' => $id ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-bank-reconciliation' ) );
	exit;
}

$entries = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}esk_bank_statements ORDER BY transaction_date DESC LIMIT 100" );
$flash   = esk_get_flash( 'success' );
?>
<div class="wrap esk-admin-wrap">
	<h1><?php esc_html_e( 'Bank Reconciliation', 'eskoofy' ); ?></h1>

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<div class="esk-card esk-form-card" style="margin-bottom:1.5rem;">
		<h2><?php esc_html_e( 'Import Bank Statement (CSV)', 'eskoofy' ); ?></h2>
		<form method="post" enctype="multipart/form-data" class="esk-form">
			<?php wp_nonce_field( 'esk_br_form' ); ?>
			<p><small><?php esc_html_e( 'CSV columns: bank_account, transaction_date, description, amount, type', 'eskoofy' ); ?></small></p>
			<input type="file" name="csv" accept=".csv" required>
			<button type="submit" name="esk_br_import" class="button button-primary"><?php esc_html_e( 'Import', 'eskoofy' ); ?></button>
		</form>
	</div>

	<table class="wp-list-table widefat striped esk-table">
		<thead><tr>
			<th><?php esc_html_e( 'Account', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Date', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Description', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Amount', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Type', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Status', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Actions', 'eskoofy' ); ?></th>
		</tr></thead>
		<tbody>
			<?php if ( empty( $entries ) ) : ?>
				<tr><td colspan="7"><?php esc_html_e( 'No entries.', 'eskoofy' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $entries as $e ) : ?>
					<tr>
						<td><?php echo esc_html( $e->bank_account ); ?></td>
						<td><?php echo esc_html( esk_date_format( $e->transaction_date ) ); ?></td>
						<td><?php echo esc_html( $e->description ); ?></td>
						<td><?php echo esc_html( esk_format_currency( $e->amount ) ); ?></td>
						<td><?php echo esc_html( ucfirst( $e->type ) ); ?></td>
						<td><span class="esk-badge esk-badge-<?php echo $e->reconciled ? 'completed' : 'pending'; ?>"><?php echo $e->reconciled ? esc_html__( 'Reconciled', 'eskoofy' ) : esc_html__( 'Unreconciled', 'eskoofy' ); ?></span></td>
						<td>
							<?php if ( ! $e->reconciled ) : ?>
								<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=esk-bank-reconciliation&action=reconcile&id=' . $e->id ), 'esk_br_reconcile_' . $e->id ) ); ?>" class="button button-small"><?php esc_html_e( 'Reconcile', 'eskoofy' ); ?></a>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>