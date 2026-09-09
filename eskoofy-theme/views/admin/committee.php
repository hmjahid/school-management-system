<?php
/**
 * Committee members management.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

if ( isset( $_POST['esk_committee_save'] ) ) {
	check_admin_referer( 'esk_committee_form' );
	$wpdb->insert( $wpdb->prefix . 'esk_committee_members', array(
		'name'          => sanitize_text_field( $_POST['name'] ?? '' ),
		'designation'   => sanitize_text_field( $_POST['designation'] ?? '' ),
		'phone'         => sanitize_text_field( $_POST['phone'] ?? '' ),
		'email'         => sanitize_email( $_POST['email'] ?? '' ),
		'bio'           => wp_kses_post( wp_unslash( $_POST['bio'] ?? '' ) ),
		'photo'         => sanitize_url( $_POST['photo_url'] ?? '' ),
		'sort_order'    => absint( $_POST['sort_order'] ?? 0 ),
		'is_active'     => 1,
	) );
	esk_flash( 'success', __( 'Committee member added.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-committee' ) );
	exit;
}

if ( isset( $_POST['esk_committee_delete'] ) ) {
	check_admin_referer( 'esk_committee_delete_' . absint( $_POST['member_id'] ?? 0 ) );
	$wpdb->delete( $wpdb->prefix . 'esk_committee_members', array( 'id' => absint( $_POST['member_id'] ?? 0 ) ) );
	esk_flash( 'success', __( 'Member removed.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-committee' ) );
	exit;
}

$members = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}esk_committee_members ORDER BY sort_order, id" );
$flash   = esk_get_flash( 'success' );
?>
<div class="wrap esk-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Committee', 'eskoofy' ); ?></h1>
	<hr class="wp-header-end">

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<div class="esk-card esk-form-card" style="margin-bottom:1.5rem;">
		<h2><?php esc_html_e( 'Add Committee Member', 'eskoofy' ); ?></h2>
		<form method="post" class="esk-form">
			<?php wp_nonce_field( 'esk_committee_form' ); ?>
			<div class="esk-form-row">
				<div class="esk-form-group"><label><?php esc_html_e( 'Name', 'eskoofy' ); ?> *</label><input type="text" name="name" required></div>
				<div class="esk-form-group"><label><?php esc_html_e( 'Designation', 'eskoofy' ); ?> *</label><input type="text" name="designation" required></div>
				<div class="esk-form-group"><label><?php esc_html_e( 'Phone', 'eskoofy' ); ?></label><input type="tel" name="phone"></div>
				<div class="esk-form-group"><label><?php esc_html_e( 'Email', 'eskoofy' ); ?></label><input type="email" name="email"></div>
			</div>
			<div class="esk-form-row">
				<div class="esk-form-group"><label><?php esc_html_e( 'Photo URL', 'eskoofy' ); ?></label><input type="url" name="photo_url" class="regular-text"></div>
				<div class="esk-form-group"><label><?php esc_html_e( 'Sort Order', 'eskoofy' ); ?></label><input type="number" name="sort_order" value="0" style="width:60px;"></div>
			</div>
			<div class="esk-form-group"><label><?php esc_html_e( 'Bio', 'eskoofy' ); ?></label><textarea name="bio" rows="3" class="large-text"></textarea></div>
			<button type="submit" name="esk_committee_save" class="button button-primary"><?php esc_html_e( 'Add Member', 'eskoofy' ); ?></button>
		</form>
	</div>

	<table class="wp-list-table widefat striped esk-table">
		<thead><tr>
			<th><?php esc_html_e( 'Name', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Designation', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Phone', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Email', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Sort', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Actions', 'eskoofy' ); ?></th>
		</tr></thead>
		<tbody>
			<?php if ( empty( $members ) ) : ?>
				<tr><td colspan="6"><?php esc_html_e( 'No committee members.', 'eskoofy' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $members as $m ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $m->name ); ?></strong></td>
						<td><?php echo esc_html( $m->designation ); ?></td>
						<td><?php echo esc_html( $m->phone ); ?></td>
						<td><?php echo esc_html( $m->email ); ?></td>
						<td><?php echo esc_html( $m->sort_order ); ?></td>
						<td>
							<form method="post" style="display:inline;" onsubmit="return confirm('<?php esc_attr_e( 'Remove this member?', 'eskoofy' ); ?>');">
								<?php wp_nonce_field( 'esk_committee_delete_' . $m->id ); ?>
								<input type="hidden" name="member_id" value="<?php echo esc_attr( $m->id ); ?>">
								<button type="submit" name="esk_committee_delete" class="button button-small"><?php esc_html_e( 'Remove', 'eskoofy' ); ?></button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
