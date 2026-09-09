<?php
/**
 * Class add/edit form.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

$edit_id = absint( $_GET['id'] ?? 0 );
$class   = $edit_id ? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}esk_classes WHERE id = %d", $edit_id ) ) : null;

if ( isset( $_POST['esk_class_save'] ) ) {
	check_admin_referer( 'esk_class_form' );
	$data = array(
		'name'              => sanitize_text_field( $_POST['name'] ?? '' ),
		'seating_capacity'  => absint( $_POST['seating_capacity'] ?? 0 ),
		'teacher_id'        => absint( $_POST['teacher_id'] ?? 0 ) ?: null,
	);
	if ( $edit_id ) {
		$wpdb->update( $wpdb->prefix . 'esk_classes', $data, array( 'id' => $edit_id ) );
	} else {
		$wpdb->insert( $wpdb->prefix . 'esk_classes', $data );
	}
	esk_flash( 'success', __( 'Class saved.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-classes' ) );
	exit;
}

$all_teachers = $wpdb->get_results( "SELECT t.id, u.display_name FROM {$wpdb->prefix}esk_teachers t JOIN {$wpdb->prefix}users u ON t.user_id = u.ID" );
$flash        = esk_get_flash( 'success' );
?>
<div class="wrap esk-admin-wrap">
	<h1><?php echo $edit_id ? esc_html__( 'Edit Class', 'eskoofy' ) : esc_html__( 'Add Class', 'eskoofy' ); ?></h1>
	<?php if ( $flash ) : ?><div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div><?php endif; ?>

	<form method="post" class="esk-form">
		<?php wp_nonce_field( 'esk_class_form' ); ?>
		<table class="form-table">
			<tr><th><label><?php esc_html_e( 'Name', 'eskoofy' ); ?> *</label></th>
				<td><input type="text" name="name" value="<?php echo esc_attr( $class->name ?? '' ); ?>" required class="regular-text"></td></tr>
			<tr><th><label><?php esc_html_e( 'Seating Capacity', 'eskoofy' ); ?></label></th>
				<td><input type="number" name="seating_capacity" value="<?php echo esc_attr( $class->seating_capacity ?? '' ); ?>"></td></tr>
			<tr><th><label><?php esc_html_e( 'Class Teacher', 'eskoofy' ); ?></label></th>
				<td><select name="teacher_id"><option value=""><?php esc_html_e( 'None', 'eskoofy' ); ?></option>
					<?php foreach ( $all_teachers as $t ) : ?>
						<option value="<?php echo esc_attr( $t->id ); ?>" <?php selected( $class->teacher_id ?? 0, $t->id ); ?>><?php echo esc_html( $t->display_name ); ?></option>
					<?php endforeach; ?>
				</select></td></tr>
		</table>
		<p class="submit"><button type="submit" name="esk_class_save" class="button button-primary"><?php esc_html_e( 'Save Class', 'eskoofy' ); ?></button></p>
	</form>
</div>
