<?php
/**
 * Fee structure management.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

if ( isset( $_POST['esk_fee_save'] ) ) {
	check_admin_referer( 'esk_fee_form' );
	$wpdb->insert( $wpdb->prefix . 'esk_fees', array(
		'name'       => sanitize_text_field( $_POST['name'] ?? '' ),
		'code'       => sanitize_text_field( $_POST['code'] ?? '' ),
		'class_id'   => absint( $_POST['class_id'] ?? 0 ),
		'amount'     => floatval( $_POST['amount'] ?? 0 ),
		'fee_type'   => sanitize_text_field( $_POST['fee_type'] ?? 'tuition' ),
		'frequency'  => sanitize_text_field( $_POST['frequency'] ?? 'monthly' ),
		'created_by' => get_current_user_id(),
	) );
	esk_flash( 'success', __( 'Fee created.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-fees' ) );
	exit;
}

if ( isset( $_POST['esk_fee_update'] ) ) {
	check_admin_referer( 'esk_fee_form' );
	$fee_id = absint( $_POST['fee_id'] ?? 0 );
	if ( $fee_id ) {
		$wpdb->update( $wpdb->prefix . 'esk_fees', array(
			'name'      => sanitize_text_field( $_POST['name'] ?? '' ),
			'code'      => sanitize_text_field( $_POST['code'] ?? '' ),
			'class_id'  => absint( $_POST['class_id'] ?? 0 ),
			'amount'    => floatval( $_POST['amount'] ?? 0 ),
			'fee_type'  => sanitize_text_field( $_POST['fee_type'] ?? 'tuition' ),
			'frequency' => sanitize_text_field( $_POST['frequency'] ?? 'monthly' ),
		), array( 'id' => $fee_id ) );
	}
	esk_flash( 'success', __( 'Fee updated.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-fees' ) );
	exit;
}

if ( isset( $_POST['esk_fee_delete'] ) ) {
	check_admin_referer( 'esk_fee_delete_' . absint( $_POST['fee_id'] ?? 0 ) );
	$fee_id = absint( $_POST['fee_id'] ?? 0 );
	if ( $fee_id ) {
		$wpdb->update( $wpdb->prefix . 'esk_fees', array( 'deleted_at' => current_time( 'mysql' ) ), array( 'id' => $fee_id ) );
	}
	esk_flash( 'success', __( 'Fee deleted.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-fees' ) );
	exit;
}

$edit_fee = null;
if ( isset( $_GET['edit_fee'] ) ) {
	$edit_fee = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}esk_fees WHERE id = %d", absint( $_GET['edit_fee'] ) ) );
}

$page     = max( 1, (int) ( $_GET['paged'] ?? 1 ) );
$per_page = 20;
$offset   = ( $page - 1 ) * $per_page;

$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}esk_fees WHERE deleted_at IS NULL" );

$fees = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT f.*, c.name AS class_name
		FROM {$wpdb->prefix}esk_fees f
		LEFT JOIN {$wpdb->prefix}esk_classes c ON f.class_id = c.id
		WHERE f.deleted_at IS NULL ORDER BY f.id DESC LIMIT %d OFFSET %d",
		$per_page,
		$offset
	)
);
$pages = (int) ceil( $total / $per_page );

$all_classes = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}esk_classes ORDER BY name" );
$flash       = esk_get_flash( 'success' );
?>
<div class="wrap esk-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Fee Structure', 'eskoofy' ); ?></h1>
	<hr class="wp-header-end">

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<div class="esk-card esk-form-card" style="margin-bottom:1.5rem;">
		<h2><?php echo $edit_fee ? esc_html__( 'Edit Fee', 'eskoofy' ) : esc_html__( 'Add Fee', 'eskoofy' ); ?></h2>
		<form method="post" class="esk-form esk-form-horizontal">
			<?php wp_nonce_field( 'esk_fee_form' ); ?>
			<?php if ( $edit_fee ) : ?>
				<input type="hidden" name="fee_id" value="<?php echo esc_attr( $edit_fee->id ); ?>">
			<?php endif; ?>
			<div class="esk-form-row">
				<div class="esk-form-group"><label><?php esc_html_e( 'Name', 'eskoofy' ); ?> *</label><input type="text" name="name" required value="<?php echo esc_attr( $edit_fee->name ?? '' ); ?>"></div>
				<div class="esk-form-group"><label><?php esc_html_e( 'Code', 'eskoofy' ); ?> *</label><input type="text" name="code" required value="<?php echo esc_attr( $edit_fee->code ?? '' ); ?>"></div>
				<div class="esk-form-group"><label><?php esc_html_e( 'Class', 'eskoofy' ); ?> *</label><select name="class_id" required>
					<option value=""><?php esc_html_e( 'Select', 'eskoofy' ); ?></option>
					<?php foreach ( $all_classes as $c ) : ?>
						<option value="<?php echo esc_attr( $c->id ); ?>" <?php selected( $edit_fee->class_id ?? 0, $c->id ); ?>><?php echo esc_html( $c->name ); ?></option>
					<?php endforeach; ?>
				</select></div>
			</div>
			<div class="esk-form-row">
				<div class="esk-form-group"><label><?php esc_html_e( 'Amount', 'eskoofy' ); ?> *</label><input type="number" name="amount" step="0.01" required value="<?php echo esc_attr( $edit_fee->amount ?? '' ); ?>"></div>
				<div class="esk-form-group"><label><?php esc_html_e( 'Fee Type', 'eskoofy' ); ?></label>
					<select name="fee_type"><option value="tuition" <?php selected( $edit_fee->fee_type ?? '', 'tuition' ); ?>><?php esc_html_e( 'Tuition', 'eskoofy' ); ?></option><option value="admission" <?php selected( $edit_fee->fee_type ?? '', 'admission' ); ?>><?php esc_html_e( 'Admission', 'eskoofy' ); ?></option><option value="exam" <?php selected( $edit_fee->fee_type ?? '', 'exam' ); ?>><?php esc_html_e( 'Exam', 'eskoofy' ); ?></option><option value="transport" <?php selected( $edit_fee->fee_type ?? '', 'transport' ); ?>><?php esc_html_e( 'Transport', 'eskoofy' ); ?></option><option value="library" <?php selected( $edit_fee->fee_type ?? '', 'library' ); ?>><?php esc_html_e( 'Library', 'eskoofy' ); ?></option></select>
				</div>
				<div class="esk-form-group"><label><?php esc_html_e( 'Frequency', 'eskoofy' ); ?></label>
					<select name="frequency"><option value="monthly" <?php selected( $edit_fee->frequency ?? '', 'monthly' ); ?>><?php esc_html_e( 'Monthly', 'eskoofy' ); ?></option><option value="one_time" <?php selected( $edit_fee->frequency ?? '', 'one_time' ); ?>><?php esc_html_e( 'One Time', 'eskoofy' ); ?></option><option value="yearly" <?php selected( $edit_fee->frequency ?? '', 'yearly' ); ?>><?php esc_html_e( 'Yearly', 'eskoofy' ); ?></option></select>
				</div>
			</div>
			<button type="submit" name="<?php echo $edit_fee ? 'esk_fee_update' : 'esk_fee_save'; ?>" class="button button-primary"><?php echo $edit_fee ? esc_html__( 'Update Fee', 'eskoofy' ) : esc_html__( 'Add Fee', 'eskoofy' ); ?></button>
			<?php if ( $edit_fee ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=esk-fees' ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'eskoofy' ); ?></a>
			<?php endif; ?>
		</form>
	</div>

	<div class="esk-table-scroll">
	<table class="wp-list-table widefat striped esk-table">
		<thead><tr><th><?php esc_html_e( 'Name', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Code', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Class', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Amount', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Type', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Frequency', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Status', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Actions', 'eskoofy' ); ?></th></tr></thead>
		<tbody>
			<?php if ( empty( $fees ) ) : ?>
				<tr><td colspan="8">
					<div class="esk-empty-state">
						<div class="esk-empty-state-icon"><span class="dashicons dashicons-money-alt"></span></div>
						<p class="esk-empty-state-title"><?php esc_html_e( 'No fees yet', 'eskoofy' ); ?></p>
						<p class="esk-empty-state-message"><?php esc_html_e( 'Add your first fee structure above to get started.', 'eskoofy' ); ?></p>
					</div>
				</td></tr>
			<?php else : ?>
				<?php foreach ( $fees as $f ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $f->name ); ?></strong></td>
						<td><?php echo esc_html( $f->code ); ?></td>
						<td><?php echo esc_html( $f->class_name ); ?></td>
						<td><?php echo esc_html( esk_format_currency( $f->amount ) ); ?></td>
						<td><?php echo esc_html( ucfirst( $f->fee_type ) ); ?></td>
						<td><?php echo esc_html( ucfirst( str_replace( '_', ' ', $f->frequency ) ) ); ?></td>
						<td><span class="esk-badge esk-badge-<?php echo esc_attr( $f->status ); ?>"><?php echo esc_html( ucfirst( $f->status ) ); ?></span></td>
						<td>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=esk-fees&edit_fee=' . $f->id ) ); ?>" class="button button-small"><?php esc_html_e( 'Edit', 'eskoofy' ); ?></a>
							<form method="post" style="display:inline;" onsubmit="return confirm('<?php esc_attr_e( 'Delete this fee?', 'eskoofy' ); ?>')">
								<?php wp_nonce_field( 'esk_fee_delete_' . $f->id ); ?>
								<input type="hidden" name="fee_id" value="<?php echo esc_attr( $f->id ); ?>">
								<button type="submit" name="esk_fee_delete" class="button button-small"><?php esc_html_e( 'Delete', 'eskoofy' ); ?></button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
	</div>

	<?php if ( $pages > 1 ) : ?>
		<div class="esk-pagination">
			<?php
			echo wp_kses_post( paginate_links( array(
				'base'    => add_query_arg( 'paged', '%#%' ),
				'format'  => '',
				'current' => $page,
				'total'   => $pages,
			) ) );
			?>
		</div>
	<?php endif; ?>
</div>
