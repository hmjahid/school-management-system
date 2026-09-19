<?php
/**
 * Budgets management.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

if ( isset( $_POST['esk_budget_save'] ) ) {
	check_admin_referer( 'esk_budget_form' );
	$wpdb->insert( $wpdb->prefix . 'esk_budgets', array(
		'expense_category_id' => absint( $_POST['expense_category_id'] ?? 0 ) ?: null,
		'period_type'         => in_array( $_POST['period_type'] ?? 'monthly', array( 'monthly', 'yearly', 'custom' ), true ) ? sanitize_text_field( $_POST['period_type'] ) : 'monthly',
		'period_start'        => sanitize_text_field( $_POST['period_start'] ?? gmdate( 'Y-m-01' ) ),
		'period_end'          => sanitize_text_field( $_POST['period_end'] ?? gmdate( 'Y-m-t' ) ),
		'amount'              => (float) ( $_POST['amount'] ?? 0 ),
		'notes'               => sanitize_textarea_field( $_POST['notes'] ?? '' ),
		'created_by'          => get_current_user_id(),
	) );
	esk_flash( 'success', __( 'Budget created.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-budgets' ) );
	exit;
}

if ( isset( $_POST['esk_budget_delete'] ) ) {
	check_admin_referer( 'esk_budget_delete_' . absint( $_POST['budget_id'] ?? 0 ) );
	$wpdb->delete( $wpdb->prefix . 'esk_budgets', array( 'id' => absint( $_POST['budget_id'] ?? 0 ) ) );
	esk_flash( 'success', __( 'Budget deleted.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-budgets' ) );
	exit;
}

$categories = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}esk_expense_categories WHERE is_active = 1 AND deleted_at IS NULL ORDER BY name" );
$budgets    = $wpdb->get_results( "SELECT b.*, c.name AS category_name FROM {$wpdb->prefix}esk_budgets b LEFT JOIN {$wpdb->prefix}esk_expense_categories c ON b.expense_category_id = c.id ORDER BY b.period_start DESC" );
$flash      = esk_get_flash( 'success' );
?>
<div class="wrap esk-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Budgets', 'eskoofy' ); ?></h1>
	<hr class="wp-header-end">

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<div class="esk-card esk-form-card" style="margin-bottom:1.5rem;">
		<h2><?php esc_html_e( 'Add Budget', 'eskoofy' ); ?></h2>
		<form method="post" class="esk-form esk-form-horizontal">
			<?php wp_nonce_field( 'esk_budget_form' ); ?>
			<div class="esk-form-row">
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Expense Category', 'eskoofy' ); ?></label>
					<select name="expense_category_id">
						<option value=""><?php esc_html_e( 'All Categories', 'eskoofy' ); ?></option>
						<?php foreach ( $categories as $c ) : ?>
							<option value="<?php echo esc_attr( $c->id ); ?>"><?php echo esc_html( $c->name ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="esk-form-group"><label><?php esc_html_e( 'Period Type', 'eskoofy' ); ?></label>
					<select name="period_type"><option value="monthly"><?php esc_html_e( 'Monthly', 'eskoofy' ); ?></option><option value="yearly"><?php esc_html_e( 'Yearly', 'eskoofy' ); ?></option><option value="custom"><?php esc_html_e( 'Custom', 'eskoofy' ); ?></option></select>
				</div>
				<div class="esk-form-group"><label><?php esc_html_e( 'Start', 'eskoofy' ); ?></label><input type="date" name="period_start" value="<?php echo esc_attr( gmdate( 'Y-m-01' ) ); ?>"></div>
				<div class="esk-form-group"><label><?php esc_html_e( 'End', 'eskoofy' ); ?></label><input type="date" name="period_end" value="<?php echo esc_attr( gmdate( 'Y-m-t' ) ); ?>"></div>
				<div class="esk-form-group"><label><?php esc_html_e( 'Amount', 'eskoofy' ); ?> *</label><input type="number" name="amount" step="0.01" min="0" required style="width:120px;"></div>
			</div>
			<div class="esk-form-group"><label><?php esc_html_e( 'Notes', 'eskoofy' ); ?></label><input type="text" name="notes" class="regular-text"></div>
			<button type="submit" name="esk_budget_save" class="button button-primary"><?php esc_html_e( 'Add Budget', 'eskoofy' ); ?></button>
		</form>
	</div>

	<div class="esk-table-scroll">
		<table class="wp-list-table widefat striped esk-table">
			<thead><tr>
				<th><?php esc_html_e( 'Category', 'eskoofy' ); ?></th>
				<th><?php esc_html_e( 'Period', 'eskoofy' ); ?></th>
				<th><?php esc_html_e( 'Amount', 'eskoofy' ); ?></th>
				<th><?php esc_html_e( 'Notes', 'eskoofy' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'eskoofy' ); ?></th>
			</tr></thead>
			<tbody>
				<?php if ( empty( $budgets ) ) : ?>
					<tr><td colspan="5"><?php esc_html_e( 'No budgets yet.', 'eskoofy' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $budgets as $b ) : ?>
						<tr>
							<td><strong><?php echo esc_html( $b->category_name ?? __( 'All Categories', 'eskoofy' ) ); ?></strong></td>
							<td><?php echo esc_html( $b->period_start . ' → ' . $b->period_end ); ?> <small>(<?php echo esc_html( ucfirst( $b->period_type ) ); ?>)</small></td>
							<td><?php echo esc_html( esk_format_currency( $b->amount ) ); ?></td>
							<td><?php echo esc_html( $b->notes ); ?></td>
							<td>
								<form method="post" style="display:inline;" onsubmit="return confirm('<?php esc_attr_e( 'Delete this budget?', 'eskoofy' ); ?>');">
									<?php wp_nonce_field( 'esk_budget_delete_' . $b->id ); ?>
									<input type="hidden" name="budget_id" value="<?php echo esc_attr( $b->id ); ?>">
									<button type="submit" name="esk_budget_delete" class="button button-small"><?php esc_html_e( 'Delete', 'eskoofy' ); ?></button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>