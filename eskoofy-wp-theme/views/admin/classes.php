<?php
/**
 * Classes list view.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

if ( isset( $_POST['esk_class_save'] ) ) {
	check_admin_referer( 'esk_class_form' );
	$wpdb->insert( $wpdb->prefix . 'esk_classes', array(
		'name' => sanitize_text_field( $_POST['name'] ?? '' ),
	) );
	esk_flash( 'success', __( 'Class added.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-classes' ) );
	exit;
}

$classes = $wpdb->get_results(
	"SELECT c.*, t.user_id, u.display_name AS teacher_name,
		(SELECT COUNT(*) FROM {$wpdb->prefix}esk_students s WHERE s.class_id = c.id AND s.deleted_at IS NULL) AS student_count
	FROM {$wpdb->prefix}esk_classes c
	LEFT JOIN {$wpdb->prefix}esk_teachers t ON c.teacher_id = t.id
	LEFT JOIN {$wpdb->prefix}users u ON t.user_id = u.ID
	ORDER BY c.name"
);

$flash = esk_get_flash( 'success' );
?>
<div class="wrap esk-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Classes', 'eskoofy' ); ?></h1>
	<hr class="wp-header-end">

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<div class="esk-card esk-form-card" style="margin-bottom:1.5rem;">
		<h2><?php esc_html_e( 'Add New Class', 'eskoofy' ); ?></h2>
		<form method="post" class="esk-form esk-inline-form">
			<?php wp_nonce_field( 'esk_class_form' ); ?>
			<input type="text" name="name" placeholder="<?php esc_attr_e( 'Class name...', 'eskoofy' ); ?>" required>
			<button type="submit" name="esk_class_save" class="button button-primary"><?php esc_html_e( 'Add Class', 'eskoofy' ); ?></button>
		</form>
	</div>

	<table class="wp-list-table widefat fixed striped esk-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Name', 'eskoofy' ); ?></th>
				<th><?php esc_html_e( 'Class Teacher', 'eskoofy' ); ?></th>
				<th><?php esc_html_e( 'Students', 'eskoofy' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $classes ) ) : ?>
				<tr><td colspan="3"><?php esc_html_e( 'No classes found.', 'eskoofy' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $classes as $c ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $c->name ); ?></strong></td>
						<td><?php echo esc_html( $c->teacher_name ?? '—' ); ?></td>
						<td><?php echo esc_html( $c->student_count ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
