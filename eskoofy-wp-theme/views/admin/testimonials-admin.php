<?php
/**
 * Testimonials admin management.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

if ( isset( $_POST['esk_testimonial_save'] ) ) {
	check_admin_referer( 'esk_testimonial_form' );
	$wpdb->insert( $wpdb->prefix . 'esk_testimonials', array(
		'author_name'        => sanitize_text_field( $_POST['author_name'] ?? '' ),
		'author_designation' => sanitize_text_field( $_POST['author_designation'] ?? '' ),
		'content'            => wp_kses_post( wp_unslash( $_POST['content'] ?? '' ) ),
		'rating'             => absint( $_POST['rating'] ?? 5 ),
		'is_visible'         => isset( $_POST['is_visible'] ) ? 1 : 0,
		'sort_order'         => absint( $_POST['sort_order'] ?? 0 ),
	) );
	esk_flash( 'success', __( 'Testimonial added.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-testimonials' ) );
	exit;
}

if ( isset( $_POST['esk_testimonial_update'] ) ) {
	check_admin_referer( 'esk_testimonial_form' );
	$testimonial_id = absint( $_POST['testimonial_id'] ?? 0 );
	if ( $testimonial_id ) {
		$wpdb->update( $wpdb->prefix . 'esk_testimonials', array(
			'author_name'        => sanitize_text_field( $_POST['author_name'] ?? '' ),
			'author_designation' => sanitize_text_field( $_POST['author_designation'] ?? '' ),
			'content'            => wp_kses_post( wp_unslash( $_POST['content'] ?? '' ) ),
			'rating'             => absint( $_POST['rating'] ?? 5 ),
			'is_visible'         => isset( $_POST['is_visible'] ) ? 1 : 0,
			'sort_order'         => absint( $_POST['sort_order'] ?? 0 ),
		), array( 'id' => $testimonial_id ) );
	}
	esk_flash( 'success', __( 'Testimonial updated.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-testimonials' ) );
	exit;
}

if ( isset( $_POST['esk_testimonial_delete'] ) ) {
	check_admin_referer( 'esk_testimonial_delete_' . absint( $_POST['testimonial_id'] ?? 0 ) );
	$wpdb->delete( $wpdb->prefix . 'esk_testimonials', array( 'id' => absint( $_POST['testimonial_id'] ?? 0 ) ) );
	esk_flash( 'success', __( 'Testimonial deleted.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-testimonials' ) );
	exit;
}

$testimonials = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}esk_testimonials ORDER BY sort_order, id DESC" );
$flash        = esk_get_flash( 'success' );

$edit_testimonial = null;
if ( isset( $_GET['edit_testimonial'] ) ) {
	$edit_testimonial = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}esk_testimonials WHERE id = %d", absint( $_GET['edit_testimonial'] ) ) );
}
?>
<div class="wrap esk-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Testimonials', 'eskoofy' ); ?></h1>
	<hr class="wp-header-end">

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<div class="esk-card esk-form-card" style="margin-bottom:1.5rem;">
		<h2><?php echo $edit_testimonial ? esc_html__( 'Edit Testimonial', 'eskoofy' ) : esc_html__( 'Add Testimonial', 'eskoofy' ); ?></h2>
		<form method="post" class="esk-form">
			<?php wp_nonce_field( 'esk_testimonial_form' ); ?>
			<?php if ( $edit_testimonial ) : ?>
				<input type="hidden" name="testimonial_id" value="<?php echo esc_attr( $edit_testimonial->id ); ?>">
			<?php endif; ?>
			<div class="esk-form-row">
				<div class="esk-form-group"><label><?php esc_html_e( 'Author Name', 'eskoofy' ); ?> *</label><input type="text" name="author_name" required value="<?php echo esc_attr( $edit_testimonial->author_name ?? '' ); ?>"></div>
				<div class="esk-form-group"><label><?php esc_html_e( 'Designation / Role', 'eskoofy' ); ?></label><input type="text" name="author_designation" value="<?php echo esc_attr( $edit_testimonial->author_designation ?? '' ); ?>"></div>
				<div class="esk-form-group"><label><?php esc_html_e( 'Rating (1-5)', 'eskoofy' ); ?></label><input type="number" name="rating" value="<?php echo esc_attr( $edit_testimonial->rating ?? 5 ); ?>" min="1" max="5" style="width:60px;"></div>
				<div class="esk-form-group"><label><?php esc_html_e( 'Sort', 'eskoofy' ); ?></label><input type="number" name="sort_order" value="<?php echo esc_attr( $edit_testimonial->sort_order ?? 0 ); ?>" style="width:60px;"></div>
			</div>
			<div class="esk-form-group"><label><?php esc_html_e( 'Content', 'eskoofy' ); ?> *</label><textarea name="content" class="large-text" rows="3" required><?php echo esc_textarea( $edit_testimonial->content ?? '' ); ?></textarea></div>
			<div class="esk-form-group"><label><input type="checkbox" name="is_visible" value="1" <?php checked( $edit_testimonial->is_visible ?? 1, 1 ); ?>> <?php esc_html_e( 'Visible on site', 'eskoofy' ); ?></label></div>
			<button type="submit" name="<?php echo $edit_testimonial ? 'esk_testimonial_update' : 'esk_testimonial_save'; ?>" class="button button-primary"><?php echo $edit_testimonial ? esc_html__( 'Update Testimonial', 'eskoofy' ) : esc_html__( 'Add Testimonial', 'eskoofy' ); ?></button>
			<?php if ( $edit_testimonial ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=esk-testimonials' ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'eskoofy' ); ?></a>
			<?php endif; ?>
		</form>
	</div>

	<table class="wp-list-table widefat striped esk-table">
		<thead><tr>
			<th><?php esc_html_e( 'Author', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Role', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Rating', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Visible', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Actions', 'eskoofy' ); ?></th>
		</tr></thead>
		<tbody>
			<?php if ( empty( $testimonials ) ) : ?>
				<tr><td colspan="5"><?php esc_html_e( 'No testimonials.', 'eskoofy' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $testimonials as $t ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $t->author_name ); ?></strong></td>
						<td><?php echo esc_html( $t->author_designation ); ?></td>
						<td><?php echo esc_html( $t->rating ); ?>/5</td>
						<td><?php echo $t->is_visible ? '✓' : '—'; ?></td>
						<td>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=esk-testimonials&edit_testimonial=' . $t->id ) ); ?>" class="button button-small"><?php esc_html_e( 'Edit', 'eskoofy' ); ?></a>
							<form method="post" style="display:inline;" onsubmit="return confirm('<?php esc_attr_e( 'Delete this testimonial?', 'eskoofy' ); ?>');">
								<?php wp_nonce_field( 'esk_testimonial_delete_' . $t->id ); ?>
								<input type="hidden" name="testimonial_id" value="<?php echo esc_attr( $t->id ); ?>">
								<button type="submit" name="esk_testimonial_delete" class="button button-small"><?php esc_html_e( 'Delete', 'eskoofy' ); ?></button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
