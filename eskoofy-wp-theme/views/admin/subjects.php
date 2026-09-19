<?php
/**
 * Subjects management.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

if ( isset( $_POST['esk_subject_save'] ) ) {
	check_admin_referer( 'esk_subject_form' );
	$wpdb->insert( $wpdb->prefix . 'esk_subjects', array(
		'name' => sanitize_text_field( $_POST['name'] ?? '' ),
		'code' => sanitize_text_field( $_POST['code'] ?? '' ),
	) );
	esk_flash( 'success', __( 'Subject added.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-subjects' ) );
	exit;
}

if ( isset( $_POST['esk_subject_delete'] ) ) {
	check_admin_referer( 'esk_subject_delete_' . absint( $_POST['subject_id'] ?? 0 ) );
	$subject_id = absint( $_POST['subject_id'] ?? 0 );
	if ( $subject_id ) {
		$wpdb->update( $wpdb->prefix . 'esk_subjects', array( 'deleted_at' => current_time( 'mysql' ) ), array( 'id' => $subject_id ) );
	}
	esk_flash( 'success', __( 'Subject deleted.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-subjects' ) );
	exit;
}

$subjects = $wpdb->get_results(
	"SELECT s.*, u.display_name AS teacher_name
	FROM {$wpdb->prefix}esk_subjects s
	LEFT JOIN {$wpdb->prefix}esk_teachers t ON s.teacher_id = t.id
	LEFT JOIN {$wpdb->prefix}users u ON t.user_id = u.ID
	WHERE s.deleted_at IS NULL
	ORDER BY s.name"
);
$flash = esk_get_flash( 'success' );
?>
<div class="wrap esk-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Subjects', 'eskoofy' ); ?></h1>
	<hr class="wp-header-end">

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<div class="esk-card esk-form-card" style="margin-bottom:1.5rem;">
		<h2><?php esc_html_e( 'Add New Subject', 'eskoofy' ); ?></h2>
		<form method="post" class="esk-form esk-inline-form">
			<?php wp_nonce_field( 'esk_subject_form' ); ?>
			<label><?php esc_html_e( 'Name', 'eskoofy' ); ?>:</label>
			<input type="text" name="name" placeholder="<?php esc_attr_e( 'Subject name', 'eskoofy' ); ?>" required>
			<label><?php esc_html_e( 'Code', 'eskoofy' ); ?>:</label>
			<input type="text" name="code" placeholder="<?php esc_attr_e( 'Code', 'eskoofy' ); ?>" required>
			<button type="submit" name="esk_subject_save" class="button button-primary"><?php esc_html_e( 'Add Subject', 'eskoofy' ); ?></button>
		</form>
	</div>

	<table class="wp-list-table widefat striped esk-table">
		<thead><tr>
			<th><?php esc_html_e( 'Name', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Code', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Teacher', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Actions', 'eskoofy' ); ?></th>
		</tr></thead>
		<tbody>
			<?php if ( empty( $subjects ) ) : ?>
				<tr><td colspan="4"><?php esc_html_e( 'No subjects found.', 'eskoofy' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $subjects as $s ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $s->name ); ?></strong></td>
						<td><?php echo esc_html( $s->code ); ?></td>
						<td><?php echo esc_html( $s->teacher_name ?? '—' ); ?></td>
						<td>
							<form method="post" style="display:inline;" onsubmit="return confirm('<?php esc_attr_e( 'Delete this subject?', 'eskoofy' ); ?>');">
								<?php wp_nonce_field( 'esk_subject_delete_' . $s->id ); ?>
								<input type="hidden" name="subject_id" value="<?php echo esc_attr( $s->id ); ?>">
								<button type="submit" name="esk_subject_delete" class="button button-small"><?php esc_html_e( 'Delete', 'eskoofy' ); ?></button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
