<?php
/**
 * Expense categories management.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

if ( isset( $_POST['esk_expense_category_save'] ) ) {
	check_admin_referer( 'esk_expense_category_form' );
	$wpdb->insert( $wpdb->prefix . 'esk_expense_categories', array(
		'name'        => sanitize_text_field( $_POST['name'] ?? '' ),
		'description' => sanitize_textarea_field( $_POST['description'] ?? '' ),
		'color'       => sanitize_text_field( $_POST['color'] ?? '' ) ?: null,
		'is_active'   => isset( $_POST['is_active'] ) ? 1 : 1,
	) );
	esk_flash( 'success', __( 'Expense category added.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-expense-categories' ) );
	exit;
}

if ( isset( $_POST['esk_expense_category_update'] ) ) {
	check_admin_referer( 'esk_expense_category_form' );
	$cat_id = absint( $_POST['cat_id'] ?? 0 );
	if ( $cat_id ) {
		$wpdb->update( $wpdb->prefix . 'esk_expense_categories', array(
			'name'        => sanitize_text_field( $_POST['name'] ?? '' ),
			'description' => sanitize_textarea_field( $_POST['description'] ?? '' ),
			'color'       => sanitize_text_field( $_POST['color'] ?? '' ) ?: null,
			'is_active'   => isset( $_POST['is_active'] ) ? 1 : 0,
		), array( 'id' => $cat_id ) );
	}
	esk_flash( 'success', __( 'Expense category updated.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-expense-categories' ) );
	exit;
}

if ( isset( $_POST['esk_expense_category_delete'] ) ) {
	check_admin_referer( 'esk_expense_category_delete_' . absint( $_POST['cat_id'] ?? 0 ) );
	$wpdb->update( $wpdb->prefix . 'esk_expense_categories', array( 'deleted_at' => current_time( 'mysql' ) ), array( 'id' => absint( $_POST['cat_id'] ?? 0 ) ) );
	esk_flash( 'success', __( 'Expense category deleted.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-expense-categories' ) );
	exit;
}

$categories = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}esk_expense_categories WHERE deleted_at IS NULL ORDER BY name" );
$flash      = esk_get_flash( 'success' );

$edit_cat = null;
if ( isset( $_GET['edit_cat'] ) ) {
	$edit_cat = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}esk_expense_categories WHERE id = %d", absint( $_GET['edit_cat'] ) ) );
}
?>
<div class="wrap esk-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Expense Categories', 'eskoofy' ); ?></h1>
	<hr class="wp-header-end">

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<div class="esk-card esk-form-card" style="margin-bottom:1.5rem;">
		<h2><?php echo $edit_cat ? esc_html__( 'Edit Category', 'eskoofy' ) : esc_html__( 'Add Category', 'eskoofy' ); ?></h2>
		<form method="post" class="esk-form esk-form-horizontal">
			<?php wp_nonce_field( 'esk_expense_category_form' ); ?>
			<?php if ( $edit_cat ) : ?>
				<input type="hidden" name="cat_id" value="<?php echo esc_attr( $edit_cat->id ); ?>">
			<?php endif; ?>
			<div class="esk-form-row">
				<div class="esk-form-group"><label><?php esc_html_e( 'Name', 'eskoofy' ); ?> *</label><input type="text" name="name" required value="<?php echo esc_attr( $edit_cat->name ?? '' ); ?>"></div>
				<div class="esk-form-group"><label><?php esc_html_e( 'Color', 'eskoofy' ); ?></label><input type="color" name="color" value="<?php echo esc_attr( $edit_cat->color ?? '#2563eb' ); ?>"></div>
				<div class="esk-form-group"><label><?php esc_html_e( 'Active', 'eskoofy' ); ?></label><label><input type="checkbox" name="is_active" value="1" <?php checked( $edit_cat->is_active ?? 1, 1 ); ?>> <?php esc_html_e( 'Yes', 'eskoofy' ); ?></label></div>
			</div>
			<div class="esk-form-group"><label><?php esc_html_e( 'Description', 'eskoofy' ); ?></label><input type="text" name="description" class="regular-text" value="<?php echo esc_attr( $edit_cat->description ?? '' ); ?>"></div>
			<button type="submit" name="<?php echo $edit_cat ? 'esk_expense_category_update' : 'esk_expense_category_save'; ?>" class="button button-primary"><?php echo $edit_cat ? esc_html__( 'Update Category', 'eskoofy' ) : esc_html__( 'Add Category', 'eskoofy' ); ?></button>
			<?php if ( $edit_cat ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=esk-expense-categories' ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'eskoofy' ); ?></a>
			<?php endif; ?>
		</form>
	</div>

	<table class="wp-list-table widefat striped esk-table">
		<thead><tr>
			<th><?php esc_html_e( 'Name', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Description', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Status', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Actions', 'eskoofy' ); ?></th>
		</tr></thead>
		<tbody>
			<?php if ( empty( $categories ) ) : ?>
				<tr><td colspan="4"><?php esc_html_e( 'No categories yet.', 'eskoofy' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $categories as $c ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $c->name ); ?></strong> <?php echo $c->color ? '<span style="display:inline-block;width:12px;height:12px;border-radius:3px;background:' . esc_attr( $c->color ) . ';"></span>' : ''; ?></td>
						<td><?php echo esc_html( $c->description ); ?></td>
						<td><?php echo $c->is_active ? '<span class="esk-badge esk-badge-active">' . esc_html__( 'Active', 'eskoofy' ) . '</span>' : '<span class="esk-badge esk-badge-pending">' . esc_html__( 'Inactive', 'eskoofy' ) . '</span>'; ?></td>
						<td>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=esk-expense-categories&edit_cat=' . $c->id ) ); ?>" class="button button-small"><?php esc_html_e( 'Edit', 'eskoofy' ); ?></a>
							<form method="post" style="display:inline;" onsubmit="return confirm('<?php esc_attr_e( 'Delete this category?', 'eskoofy' ); ?>');">
								<?php wp_nonce_field( 'esk_expense_category_delete_' . $c->id ); ?>
								<input type="hidden" name="cat_id" value="<?php echo esc_attr( $c->id ); ?>">
								<button type="submit" name="esk_expense_category_delete" class="button button-small"><?php esc_html_e( 'Delete', 'eskoofy' ); ?></button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>