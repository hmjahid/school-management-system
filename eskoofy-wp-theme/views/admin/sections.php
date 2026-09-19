<?php
/**
 * Sections management.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

if ( isset( $_POST['esk_section_save'] ) ) {
	check_admin_referer( 'esk_section_form' );
	$name   = sanitize_text_field( $_POST['name'] ?? '' );
	$slug   = sanitize_title( $name );
	$class_id = absint( $_POST['class_id'] ?? 0 );
	$capacity = absint( $_POST['capacity'] ?? 30 );

	$wpdb->insert( $wpdb->prefix . 'esk_sections', array(
		'name'                => $name,
		'slug'                => $slug,
		'class_id'            => $class_id,
		'capacity'            => $capacity,
		'academic_year_id'    => 1,
		'is_active'           => 1,
	) );
	esk_flash( 'success', __( 'Section added.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-sections' ) );
	exit;
}

if ( isset( $_POST['esk_section_delete'] ) ) {
	check_admin_referer( 'esk_section_delete_' . absint( $_POST['section_id'] ?? 0 ) );
	$section_id = absint( $_POST['section_id'] ?? 0 );
	if ( $section_id ) {
		$wpdb->update( $wpdb->prefix . 'esk_sections', array( 'deleted_at' => current_time( 'mysql' ) ), array( 'id' => $section_id ) );
	}
	esk_flash( 'success', __( 'Section deleted.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-sections' ) );
	exit;
}

$all_classes = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}esk_classes ORDER BY name" );
$sections = $wpdb->get_results(
	"SELECT s.*, c.name AS class_name
	FROM {$wpdb->prefix}esk_sections s
	LEFT JOIN {$wpdb->prefix}esk_classes c ON s.class_id = c.id
	WHERE s.deleted_at IS NULL
	ORDER BY c.name, s.name"
);
$flash = esk_get_flash( 'success' );
?>
<div class="wrap esk-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Sections', 'eskoofy' ); ?></h1>
	<hr class="wp-header-end">

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<div class="esk-card esk-form-card" style="margin-bottom:1.5rem;">
		<h2><?php esc_html_e( 'Add New Section', 'eskoofy' ); ?></h2>
		<form method="post" class="esk-form esk-inline-form">
			<?php wp_nonce_field( 'esk_section_form' ); ?>
			<label><?php esc_html_e( 'Class', 'eskoofy' ); ?>:</label>
			<select name="class_id" required>
				<option value=""><?php esc_html_e( 'Select Class', 'eskoofy' ); ?></option>
				<?php foreach ( $all_classes as $c ) : ?>
					<option value="<?php echo esc_attr( $c->id ); ?>"><?php echo esc_html( $c->name ); ?></option>
				<?php endforeach; ?>
			</select>
			<label><?php esc_html_e( 'Name', 'eskoofy' ); ?>:</label>
			<input type="text" name="name" placeholder="<?php esc_attr_e( 'Section name', 'eskoofy' ); ?>" required>
			<label><?php esc_html_e( 'Capacity', 'eskoofy' ); ?>:</label>
			<input type="number" name="capacity" value="30" min="1" style="width:80px;">
			<button type="submit" name="esk_section_save" class="button button-primary"><?php esc_html_e( 'Add Section', 'eskoofy' ); ?></button>
		</form>
	</div>

	<table class="wp-list-table widefat striped esk-table">
		<thead><tr>
			<th><?php esc_html_e( 'Class', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Section', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Capacity', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Status', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Actions', 'eskoofy' ); ?></th>
		</tr></thead>
		<tbody>
			<?php if ( empty( $sections ) ) : ?>
				<tr><td colspan="5"><?php esc_html_e( 'No sections found.', 'eskoofy' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $sections as $s ) : ?>
					<tr>
						<td><?php echo esc_html( $s->class_name ?? '—' ); ?></td>
						<td><strong><?php echo esc_html( $s->name ); ?></strong></td>
						<td><?php echo esc_html( $s->capacity ); ?></td>
						<td><span class="esk-badge esk-badge-<?php echo $s->is_active ? 'active' : 'inactive'; ?>"><?php echo $s->is_active ? esc_html__( 'Active', 'eskoofy' ) : esc_html__( 'Inactive', 'eskoofy' ); ?></span></td>
						<td>
							<form method="post" style="display:inline;" onsubmit="return confirm('<?php esc_attr_e( 'Delete this section?', 'eskoofy' ); ?>');">
								<?php wp_nonce_field( 'esk_section_delete_' . $s->id ); ?>
								<input type="hidden" name="section_id" value="<?php echo esc_attr( $s->id ); ?>">
								<button type="submit" name="esk_section_delete" class="button button-small"><?php esc_html_e( 'Delete', 'eskoofy' ); ?></button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
