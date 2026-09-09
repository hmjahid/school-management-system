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

$fees = $wpdb->get_results(
	"SELECT f.*, c.name AS class_name
	FROM {$wpdb->prefix}esk_fees f
	LEFT JOIN {$wpdb->prefix}esk_classes c ON f.class_id = c.id
	WHERE f.deleted_at IS NULL ORDER BY f.id DESC"
);

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
		<h2><?php esc_html_e( 'Add Fee', 'eskoofy' ); ?></h2>
		<form method="post" class="esk-form esk-form-horizontal">
			<?php wp_nonce_field( 'esk_fee_form' ); ?>
			<div class="esk-form-row">
				<div class="esk-form-group"><label><?php esc_html_e( 'Name', 'eskoofy' ); ?> *</label><input type="text" name="name" required></div>
				<div class="esk-form-group"><label><?php esc_html_e( 'Code', 'eskoofy' ); ?> *</label><input type="text" name="code" required></div>
				<div class="esk-form-group"><label><?php esc_html_e( 'Class', 'eskoofy' ); ?> *</label><select name="class_id" required>
					<option value=""><?php esc_html_e( 'Select', 'eskoofy' ); ?></option>
					<?php foreach ( $all_classes as $c ) : ?>
						<option value="<?php echo esc_attr( $c->id ); ?>"><?php echo esc_html( $c->name ); ?></option>
					<?php endforeach; ?>
				</select></div>
			</div>
			<div class="esk-form-row">
				<div class="esk-form-group"><label><?php esc_html_e( 'Amount', 'eskoofy' ); ?> *</label><input type="number" name="amount" step="0.01" required></div>
				<div class="esk-form-group"><label><?php esc_html_e( 'Fee Type', 'eskoofy' ); ?></label>
					<select name="fee_type"><option value="tuition"><?php esc_html_e( 'Tuition', 'eskoofy' ); ?></option><option value="admission"><?php esc_html_e( 'Admission', 'eskoofy' ); ?></option><option value="exam"><?php esc_html_e( 'Exam', 'eskoofy' ); ?></option><option value="transport"><?php esc_html_e( 'Transport', 'eskoofy' ); ?></option><option value="library"><?php esc_html_e( 'Library', 'eskoofy' ); ?></option></select>
				</div>
				<div class="esk-form-group"><label><?php esc_html_e( 'Frequency', 'eskoofy' ); ?></label>
					<select name="frequency"><option value="monthly"><?php esc_html_e( 'Monthly', 'eskoofy' ); ?></option><option value="one_time"><?php esc_html_e( 'One Time', 'eskoofy' ); ?></option><option value="yearly"><?php esc_html_e( 'Yearly', 'eskoofy' ); ?></option></select>
				</div>
			</div>
			<button type="submit" name="esk_fee_save" class="button button-primary"><?php esc_html_e( 'Add Fee', 'eskoofy' ); ?></button>
		</form>
	</div>

	<table class="wp-list-table widefat striped esk-table">
		<thead><tr><th><?php esc_html_e( 'Name', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Code', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Class', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Amount', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Type', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Frequency', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Status', 'eskoofy' ); ?></th></tr></thead>
		<tbody>
			<?php if ( empty( $fees ) ) : ?>
				<tr><td colspan="7"><?php esc_html_e( 'No fees found.', 'eskoofy' ); ?></td></tr>
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
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
