<?php
/**
 * Ledger management — chart of accounts + journal entries.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

if ( isset( $_POST['esk_account_save'] ) ) {
	check_admin_referer( 'esk_account_form' );
	$wpdb->insert( $wpdb->prefix . 'esk_chart_of_accounts', array(
		'code'      => sanitize_text_field( $_POST['code'] ?? '' ),
		'name'      => sanitize_text_field( $_POST['name'] ?? '' ),
		'type'      => sanitize_text_field( $_POST['type'] ?? 'expense' ),
		'parent_id' => absint( $_POST['parent_id'] ?? 0 ) ?: null,
		'is_active' => isset( $_POST['is_active'] ) ? 1 : 1,
	) );
	esk_flash( 'success', __( 'Account created.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-ledger' ) );
	exit;
}

if ( isset( $_POST['esk_ledger_entry'] ) ) {
	check_admin_referer( 'esk_ledger_form' );
	$account = sanitize_text_field( $_POST['account'] ?? '' );
	$type    = in_array( $_POST['type'] ?? 'credit', array( 'credit', 'debit' ), true ) ? sanitize_text_field( $_POST['type'] ) : 'credit';
	$amount  = (float) ( $_POST['amount'] ?? 0 );
	$date    = sanitize_text_field( $_POST['date'] ?? gmdate( 'Y-m-d' ) );
	$desc    = sanitize_textarea_field( $_POST['description'] ?? '' );

	if ( $account && $amount > 0 ) {
		$wpdb->insert( $wpdb->prefix . 'esk_ledger_entries', array(
			'account'     => $account,
			'type'        => $type,
			'amount'      => $amount,
			'date'        => $date,
			'description' => $desc,
			'created_by'  => get_current_user_id(),
		) );
	}
	esk_flash( 'success', __( 'Ledger entry recorded.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-ledger' ) );
	exit;
}

$accounts = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}esk_chart_of_accounts ORDER BY code" );
$entries  = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}esk_ledger_entries ORDER BY date DESC, id DESC LIMIT 200" );
$flash    = esk_get_flash( 'success' );
$tab      = sanitize_text_field( $_GET['tab'] ?? 'entries' );
$types    = array( 'asset', 'liability', 'income', 'expense', 'equity' );
?>
<div class="wrap esk-admin-wrap">
	<h1><?php esc_html_e( 'Ledger', 'eskoofy' ); ?></h1>

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<nav class="nav-tab-wrapper esk-tabs">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=esk-ledger&tab=entries' ) ); ?>" class="nav-tab <?php echo 'entries' === $tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Journal Entries', 'eskoofy' ); ?></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=esk-ledger&tab=accounts' ) ); ?>" class="nav-tab <?php echo 'accounts' === $tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Chart of Accounts', 'eskoofy' ); ?></a>
	</nav>

	<?php if ( 'accounts' === $tab ) : ?>
		<div class="esk-card esk-form-card" style="margin-bottom:1.5rem;">
			<h2><?php esc_html_e( 'Add Account', 'eskoofy' ); ?></h2>
			<form method="post" class="esk-form esk-form-horizontal">
				<?php wp_nonce_field( 'esk_account_form' ); ?>
				<div class="esk-form-row">
					<div class="esk-form-group"><label><?php esc_html_e( 'Code', 'eskoofy' ); ?> *</label><input type="text" name="code" required style="width:90px;"></div>
					<div class="esk-form-group"><label><?php esc_html_e( 'Name', 'eskoofy' ); ?> *</label><input type="text" name="name" required></div>
					<div class="esk-form-group"><label><?php esc_html_e( 'Type', 'eskoofy' ); ?></label>
						<select name="type">
							<?php foreach ( $types as $t ) : ?>
								<option value="<?php echo esc_attr( $t ); ?>"><?php echo esc_html( ucfirst( $t ) ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="esk-form-group"><label><?php esc_html_e( 'Parent', 'eskoofy' ); ?></label>
						<select name="parent_id"><option value=""><?php esc_html_e( 'None', 'eskoofy' ); ?></option>
							<?php foreach ( $accounts as $a ) : ?>
								<option value="<?php echo esc_attr( $a->id ); ?>"><?php echo esc_html( $a->code . ' — ' . $a->name ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
				<button type="submit" name="esk_account_save" class="button button-primary"><?php esc_html_e( 'Add Account', 'eskoofy' ); ?></button>
			</form>
		</div>

		<div class="esk-table-scroll">
			<table class="wp-list-table widefat striped esk-table">
				<thead><tr>
					<th><?php esc_html_e( 'Code', 'eskoofy' ); ?></th>
					<th><?php esc_html_e( 'Name', 'eskoofy' ); ?></th>
					<th><?php esc_html_e( 'Type', 'eskoofy' ); ?></th>
					<th><?php esc_html_e( 'Status', 'eskoofy' ); ?></th>
				</tr></thead>
				<tbody>
					<?php if ( empty( $accounts ) ) : ?>
						<tr><td colspan="4"><?php esc_html_e( 'No accounts yet.', 'eskoofy' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $accounts as $a ) : ?>
							<tr>
								<td><code><?php echo esc_html( $a->code ); ?></code></td>
								<td><strong><?php echo esc_html( $a->name ); ?></strong></td>
								<td><span class="esk-badge esk-badge-<?php echo esc_attr( $a->type ); ?>"><?php echo esc_html( ucfirst( $a->type ) ); ?></span></td>
								<td><?php echo $a->is_active ? esc_html__( 'Active', 'eskoofy' ) : esc_html__( 'Inactive', 'eskoofy' ); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	<?php else : ?>
		<div class="esk-card esk-form-card" style="margin-bottom:1.5rem;">
			<h2><?php esc_html_e( 'Record Journal Entry', 'eskoofy' ); ?></h2>
			<form method="post" class="esk-form esk-form-horizontal">
				<?php wp_nonce_field( 'esk_ledger_form' ); ?>
				<div class="esk-form-row">
					<div class="esk-form-group"><label><?php esc_html_e( 'Account', 'eskoofy' ); ?> *</label><input type="text" name="account" required list="esk-accounts"></div>
					<div class="esk-form-group"><label><?php esc_html_e( 'Type', 'eskoofy' ); ?></label>
						<select name="type"><option value="credit"><?php esc_html_e( 'Credit (in)', 'eskoofy' ); ?></option><option value="debit"><?php esc_html_e( 'Debit (out)', 'eskoofy' ); ?></option></select>
					</div>
					<div class="esk-form-group"><label><?php esc_html_e( 'Amount', 'eskoofy' ); ?> *</label><input type="number" name="amount" step="0.01" min="0.01" required style="width:120px;"></div>
					<div class="esk-form-group"><label><?php esc_html_e( 'Date', 'eskoofy' ); ?></label><input type="date" name="date" value="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>"></div>
				</div>
				<div class="esk-form-group"><label><?php esc_html_e( 'Description', 'eskoofy' ); ?></label><input type="text" name="description" class="regular-text"></div>
				<datalist id="esk-accounts">
					<?php foreach ( $accounts as $a ) : ?>
						<option value="<?php echo esc_attr( $a->name ); ?>"></option>
					<?php endforeach; ?>
				</datalist>
				<button type="submit" name="esk_ledger_entry" class="button button-primary"><?php esc_html_e( 'Record Entry', 'eskoofy' ); ?></button>
			</form>
		</div>

		<div class="esk-table-scroll">
			<table class="wp-list-table widefat striped esk-table">
				<thead><tr>
					<th><?php esc_html_e( 'Date', 'eskoofy' ); ?></th>
					<th><?php esc_html_e( 'Account', 'eskoofy' ); ?></th>
					<th><?php esc_html_e( 'Type', 'eskoofy' ); ?></th>
					<th><?php esc_html_e( 'Amount', 'eskoofy' ); ?></th>
					<th><?php esc_html_e( 'Description', 'eskoofy' ); ?></th>
				</tr></thead>
				<tbody>
					<?php if ( empty( $entries ) ) : ?>
						<tr><td colspan="5"><?php esc_html_e( 'No ledger entries yet.', 'eskoofy' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $entries as $e ) : ?>
							<tr>
								<td><?php echo esc_html( esk_date_format( $e->date ) ); ?></td>
								<td><strong><?php echo esc_html( $e->account ); ?></strong></td>
								<td><span class="esk-badge esk-badge-<?php echo esc_attr( $e->type ); ?>"><?php echo esc_html( ucfirst( $e->type ) ); ?></span></td>
								<td><?php echo esc_html( esk_format_currency( $e->amount ) ); ?></td>
								<td><?php echo esc_html( $e->description ); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</div>