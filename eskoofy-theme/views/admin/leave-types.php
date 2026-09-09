<?php
/**
 * Leave types CRUD.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

if ( isset( $_POST['esk_leave_type_save'] ) ) {
	check_admin_referer( 'esk_leave_type_form' );
	$name          = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
	$days_per_year = absint( $_POST['days_per_year'] ?? 0 );
	$is_paid       = isset( $_POST['is_paid'] ) ? 1 : 0;
	$edit_id       = absint( $_POST['edit_id'] ?? 0 );

	if ( $name ) {
		if ( $edit_id ) {
			$wpdb->update( $wpdb->prefix . 'esk_leave_types', array(
				'name'          => $name,
				'days_per_year' => $days_per_year,
				'is_paid'       => $is_paid,
			), array( 'id' => $edit_id ) );
		} else {
			$wpdb->insert( $wpdb->prefix . 'esk_leave_types', array(
				'name'          => $name,
				'days_per_year' => $days_per_year,
				'is_paid'       => $is_paid,
			) );
		}
		esk_flash( 'success', __( 'Leave type saved.', 'eskoofy' ) );
	}
	wp_safe_redirect( admin_url( 'admin.php?page=esk-leave-types' ) );
	exit;
}

if ( isset( $_GET['action'] ) && 'delete' === $_GET['action'] && ! empty( $_GET['id'] ) ) {
	$id = absint( $_GET['id'] );
	check_admin_referer( 'esk_lt_delete_' . $id );
	$wpdb->delete( $wpdb->prefix . 'esk_leave_types', array( 'id' => $id ) );
	esk_flash( 'success', __( 'Leave type deleted.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-leave-types' ) );
	exit;
}

$edit_id = absint( $_GET['edit'] ?? 0 );
$edit    = null;
if ( $edit_id ) {
	$edit = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}esk_leave_types WHERE id = %d", $edit_id ) );
}

$types = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}esk_leave_types ORDER BY name" );
$flash = esk_get_flash( 'success' );
?>
<div class="wrap esk-admin-wrap">
	<h1><?php esc_html_e( 'Leave Types', 'eskoofy' ); ?></h1>

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<div class="esk-card esk-form-card" style="margin-bottom:1.5rem;">
		<h2><?php echo $edit ? esc_html__( 'Edit Leave Type', 'eskoofy' ) : esc_html__( 'Add Leave Type', 'eskoofy' ); ?></h2>
		<form method="post" class="esk-form">
			<?php wp_nonce_field( 'esk_leave_type_form' ); ?>
			<?php if ( $edit ) : ?>
				<input type="hidden" name="edit_id" value="<?php echo esc_attr( $edit->id ); ?>">
			<?php endif; ?>
			<div class="esk-form-row">
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Name', 'eskoofy' ); ?> *</label>
					<input type="text" name="name" value="<?php echo esc_attr( $edit->name ?? '' ); ?>" required>
				</div>
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Days / Year', 'eskoofy' ); ?></label>
					<input type="number" name="days_per_year" value="<?php echo esc_attr( $edit->days_per_year ?? 14 ); ?>" min="0">
				</div>
				<div class="esk-form-group">
					<label><input type="checkbox" name="is_paid" value="1" <?php checked( $edit ? $edit->is_paid : 1, 1 ); ?>> <?php esc_html_e( 'Paid', 'eskoofy' ); ?></label>
				</div>
			</div>
			<button type="submit" name="esk_leave_type_save" class="button button-primary"><?php esc_html_e( 'Save', 'eskoofy' ); ?></button>
		</form>
	</div>

	<table class="wp-list-table widefat striped esk-table">
		<thead><tr>
			<th><?php esc_html_e( 'Name', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Days / Year', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Paid', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Actions', 'eskoofy' ); ?></th>
		</tr></thead>
		<tbody>
			<?php if ( empty( $types ) ) : ?>
				<tr><td colspan="4"><?php esc_html_e( 'No leave types.', 'eskoofy' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $types as $t ) : ?>
					<tr>
						<td><?php echo esc_html( $t->name ); ?></td>
						<td><?php echo esc_html( $t->days_per_year ); ?></td>
						<td><?php echo $t->is_paid ? '✓' : '—'; ?></td>
						<td>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=esk-leave-types&edit=' . $t->id ) ); ?>" class="button button-small"><?php esc_html_e( 'Edit', 'eskoofy' ); ?></a>
							<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=esk-leave-types&action=delete&id=' . $t->id ), 'esk_lt_delete_' . $t->id ) ); ?>" class="button button-small" onclick="return confirm('Delete?');"><?php esc_html_e( 'Delete', 'eskoofy' ); ?></a>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>