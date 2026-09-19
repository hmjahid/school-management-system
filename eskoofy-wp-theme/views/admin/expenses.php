<?php
/**
 * Expenses management.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

if ( isset( $_POST['esk_expense_save'] ) ) {
	check_admin_referer( 'esk_expense_form' );
	$wpdb->insert( $wpdb->prefix . 'esk_expenses', array(
		'category'   => sanitize_text_field( $_POST['category'] ?? '' ),
		'amount'     => (float) ( $_POST['amount'] ?? 0 ),
		'date'       => sanitize_text_field( $_POST['date'] ?? gmdate( 'Y-m-d' ) ),
		'vendor'     => sanitize_text_field( $_POST['vendor'] ?? '' ),
		'payment_method' => sanitize_text_field( $_POST['payment_method'] ?? 'cash' ),
		'note'       => sanitize_textarea_field( $_POST['note'] ?? '' ),
		'created_by' => get_current_user_id(),
	) );
	esk_flash( 'success', __( 'Expense added.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-expenses' ) );
	exit;
}

if ( isset( $_POST['esk_expense_update'] ) ) {
	check_admin_referer( 'esk_expense_form' );
	$expense_id = absint( $_POST['expense_id'] ?? 0 );
	if ( $expense_id ) {
		$wpdb->update( $wpdb->prefix . 'esk_expenses', array(
			'category'       => sanitize_text_field( $_POST['category'] ?? '' ),
			'amount'         => (float) ( $_POST['amount'] ?? 0 ),
			'date'           => sanitize_text_field( $_POST['date'] ?? gmdate( 'Y-m-d' ) ),
			'vendor'         => sanitize_text_field( $_POST['vendor'] ?? '' ),
			'payment_method' => sanitize_text_field( $_POST['payment_method'] ?? 'cash' ),
			'note'           => sanitize_textarea_field( $_POST['note'] ?? '' ),
		), array( 'id' => $expense_id ) );
	}
	esk_flash( 'success', __( 'Expense updated.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-expenses' ) );
	exit;
}

if ( isset( $_POST['esk_expense_delete'] ) ) {
	check_admin_referer( 'esk_expense_delete_' . absint( $_POST['expense_id'] ?? 0 ) );
	$expense_id = absint( $_POST['expense_id'] ?? 0 );
	if ( $expense_id ) {
		$wpdb->delete( $wpdb->prefix . 'esk_expenses', array( 'id' => $expense_id ) );
	}
	esk_flash( 'success', __( 'Expense deleted.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-expenses' ) );
	exit;
}

$categories = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}esk_expense_categories WHERE is_active = 1 AND deleted_at IS NULL ORDER BY name" );
$expenses   = $wpdb->get_results(
	"SELECT * FROM {$wpdb->prefix}esk_expenses ORDER BY date DESC LIMIT 100"
);
$flash = esk_get_flash( 'success' );

$edit_expense = null;
if ( isset( $_GET['edit_expense'] ) ) {
	$edit_expense = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}esk_expenses WHERE id = %d", absint( $_GET['edit_expense'] ) ) );
}
?>
<div class="wrap esk-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Expenses', 'eskoofy' ); ?></h1>
	<hr class="wp-header-end">

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<div class="esk-card esk-form-card" style="margin-bottom:1.5rem;">
		<h2><?php echo $edit_expense ? esc_html__( 'Edit Expense', 'eskoofy' ) : esc_html__( 'Add New Expense', 'eskoofy' ); ?></h2>
		<form method="post" class="esk-form">
			<?php wp_nonce_field( 'esk_expense_form' ); ?>
			<?php if ( $edit_expense ) : ?>
				<input type="hidden" name="expense_id" value="<?php echo esc_attr( $edit_expense->id ); ?>">
			<?php endif; ?>
			<div class="esk-form-row">
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Date', 'eskoofy' ); ?> *</label>
					<input type="date" name="date" value="<?php echo esc_attr( $edit_expense->date ?? gmdate( 'Y-m-d' ) ); ?>" required>
				</div>
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Category', 'eskoofy' ); ?> *</label>
					<select name="category" required>
						<option value=""><?php esc_html_e( 'Select', 'eskoofy' ); ?></option>
						<?php foreach ( $categories as $cat ) : ?>
							<option value="<?php echo esc_attr( $cat->name ); ?>" <?php selected( $edit_expense->category ?? '', $cat->name ); ?>><?php echo esc_html( $cat->name ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Amount', 'eskoofy' ); ?> *</label>
					<input type="number" name="amount" step="0.01" min="0" required style="width:120px;" value="<?php echo esc_attr( $edit_expense->amount ?? '' ); ?>">
				</div>
			</div>
			<div class="esk-form-row">
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Vendor', 'eskoofy' ); ?></label>
					<input type="text" name="vendor" value="<?php echo esc_attr( $edit_expense->vendor ?? '' ); ?>">
				</div>
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Payment Method', 'eskoofy' ); ?></label>
					<select name="payment_method">
						<option value="cash" <?php selected( $edit_expense->payment_method ?? '', 'cash' ); ?>><?php esc_html_e( 'Cash', 'eskoofy' ); ?></option>
						<option value="bank" <?php selected( $edit_expense->payment_method ?? '', 'bank' ); ?>><?php esc_html_e( 'Bank', 'eskoofy' ); ?></option>
						<option value="cheque" <?php selected( $edit_expense->payment_method ?? '', 'cheque' ); ?>><?php esc_html_e( 'Cheque', 'eskoofy' ); ?></option>
						<option value="mobile" <?php selected( $edit_expense->payment_method ?? '', 'mobile' ); ?>><?php esc_html_e( 'Mobile', 'eskoofy' ); ?></option>
					</select>
				</div>
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Note', 'eskoofy' ); ?></label>
					<input type="text" name="note" value="<?php echo esc_attr( $edit_expense->note ?? '' ); ?>">
				</div>
			</div>
			<button type="submit" name="<?php echo $edit_expense ? 'esk_expense_update' : 'esk_expense_save'; ?>" class="button button-primary"><?php echo $edit_expense ? esc_html__( 'Update Expense', 'eskoofy' ) : esc_html__( 'Add Expense', 'eskoofy' ); ?></button>
			<?php if ( $edit_expense ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=esk-expenses' ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'eskoofy' ); ?></a>
			<?php endif; ?>
		</form>
	</div>

	<table class="wp-list-table widefat striped esk-table">
		<thead><tr>
			<th><?php esc_html_e( 'Date', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Category', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Amount', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Vendor', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Method', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Note', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Actions', 'eskoofy' ); ?></th>
		</tr></thead>
		<tbody>
			<?php if ( empty( $expenses ) ) : ?>
				<tr><td colspan="7"><?php esc_html_e( 'No expenses found.', 'eskoofy' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $expenses as $e ) : ?>
					<tr>
						<td><?php echo esc_html( esk_date_format( $e->date ) ); ?></td>
						<td><?php echo esc_html( $e->category ); ?></td>
						<td><?php echo esc_html( esk_format_currency( $e->amount ) ); ?></td>
						<td><?php echo esc_html( $e->vendor ); ?></td>
						<td><?php echo esc_html( ucfirst( $e->payment_method ) ); ?></td>
						<td><?php echo esc_html( $e->note ); ?></td>
						<td>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=esk-expenses&edit_expense=' . $e->id ) ); ?>" class="button button-small"><?php esc_html_e( 'Edit', 'eskoofy' ); ?></a>
							<form method="post" style="display:inline;" onsubmit="return confirm('<?php esc_attr_e( 'Delete this expense?', 'eskoofy' ); ?>');">
								<?php wp_nonce_field( 'esk_expense_delete_' . $e->id ); ?>
								<input type="hidden" name="expense_id" value="<?php echo esc_attr( $e->id ); ?>">
								<button type="submit" name="esk_expense_delete" class="button button-small"><?php esc_html_e( 'Delete', 'eskoofy' ); ?></button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
