<?php
/**
 * Document designs admin page — per-type theme + watermark CRUD.
 *
 * Mirrors the Laravel app's dashboard › document designs screen: one card per
 * document type with its active design, plus a form to create/update the
 * default design (theme + watermark + custom CSS).
 *
 * @package Eskoofy
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

global $wpdb;
$table = $wpdb->prefix . 'esk_document_designs';
$types = esk_document_types();

$created = '';
$error   = '';

if ( isset( $_POST['esk_document_design_save'] ) && current_user_can( 'manage_options' ) ) {
	check_admin_referer( 'esk_document_design_nonce' );

	$document_type = sanitize_text_field( wp_unslash( $_POST['document_type'] ?? '' ) );
	if ( ! in_array( $document_type, $types, true ) ) {
		$error = __( 'Unknown document type.', 'eskoofy' );
	} else {
		$template   = in_array( (string) ( $_POST['template'] ?? '' ), esk_document_templates(), true )
			? sanitize_text_field( wp_unslash( $_POST['template'] ) )
			: 'classic';
		$name       = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
		$settings = esk_document_sanitize_theme( wp_unslash( $_POST['settings'] ?? array() ) ); // phpcs:ignore
		$watermark = esk_document_sanitize_watermark( wp_unslash( $_POST['watermark'] ?? array() ), $document_type ); // phpcs:ignore
		$custom     = esk_document_sanitize_css( (string) wp_unslash( $_POST['custom_css'] ?? '' ) );
		$is_default = ! empty( $_POST['is_default'] );
		$is_active  = ! empty( $_POST['is_active'] );

		$id  = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$row = array(
			'document_type' => $document_type,
			'name'          => '' !== $name ? $name : ucwords( str_replace( '_', ' ', $document_type ) ),
			'template'      => $template,
			'is_default'    => (int) $is_default,
			'settings'      => wp_json_encode( $settings ),
			'watermark'     => wp_json_encode( $watermark ),
			'custom_css'    => $custom,
			'is_active'     => (int) $is_active,
		);

		if ( $is_default ) {
			$wpdb->update( $table, array( 'is_default' => 0 ), array( 'document_type' => $document_type ) ); // phpcs:ignore
		}

		if ( $id > 0 ) {
			$wpdb->update( $table, $row, array( 'id' => $id ) ); // phpcs:ignore
			$created = __( 'Document design updated.', 'eskoofy' );
		} else {
			$wpdb->insert( $table, $row ); // phpcs:ignore
			$created = __( 'Document design saved.', 'eskoofy' );
		}
	}
}

if ( isset( $_GET['esk_document_design_delete'] ) && current_user_can( 'manage_options' ) ) {
	check_admin_referer( 'esk_document_design_delete' );
	$wpdb->delete( $table, array( 'id' => absint( $_GET['esk_document_design_delete'] ) ) ); // phpcs:ignore
	$created = __( 'Document design deleted.', 'eskoofy' );
}

// Editing state: ?edit=<id> loads that design into the form.
$editing = null;
if ( isset( $_GET['edit'] ) ) {
	$editing = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $table . ' WHERE id = %d', absint( $_GET['edit'] ) ), ARRAY_A ); // phpcs:ignore
}

$active_rows = array();
foreach ( $types as $type ) {
	$design = esk_document_active_design( $type );
	if ( $design ) {
		$active_rows[ $type ] = (array) $design;
	}
}
?>
<div class="space-y-6">
	<?php if ( $created ) : ?>
		<div data-esk-flash-toast data-message="<?php echo esc_attr( $created ); ?>" data-type="success"></div>
	<?php endif; ?>
	<?php if ( $error ) : ?>
		<div data-esk-flash-toast data-message="<?php echo esc_attr( $error ); ?>" data-type="error"></div>
	<?php endif; ?>

	<div class="grid gap-6 lg:grid-cols-3">
		<?php foreach ( $types as $type ) : ?>
			<div class="rounded-xl border border-slate-200 bg-white p-6">
				<h3 class="text-base font-semibold text-slate-900"><?php echo esc_html( ucwords( str_replace( '_', ' ', $type ) ) ); ?></h3>
				<?php if ( empty( $active_rows[ $type ] ) ) : ?>
					<p class="mt-1 text-sm text-slate-500"><?php esc_html_e( 'Using the shipped default design.', 'eskoofy' ); ?></p>
				<?php else : ?>
					<p class="mt-1 text-sm text-slate-600">
						<?php echo esc_html( (string) ( $active_rows[ $type ]['name'] ?? '' ) ); ?>
						<span class="text-xs text-slate-400">(<?php echo esc_html( (string) ( $active_rows[ $type ]['template'] ?? 'classic' ) ); ?>)</span>
					</p>
				<?php endif; ?>
				<div class="mt-4 flex gap-2">
					<a class="rounded-lg bg-brand-600 px-3 py-1.5 text-sm font-semibold text-white transition hover:bg-brand-700"
						href="<?php echo esc_url( add_query_arg( array( 'type' => $type ), admin_url( 'admin.php?page=esk-document-designs' ) ) ); ?>"><?php esc_html_e( 'Add', 'eskoofy' ); ?></a>
					<?php if ( ! empty( $active_rows[ $type ] ) ) : ?>
						<a class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
							href="<?php echo esc_url( add_query_arg( array( 'edit' => (int) $active_rows[ $type ]['id'] ), admin_url( 'admin.php?page=esk-document-designs' ) ) ); ?>"><?php esc_html_e( 'Edit', 'eskoofy' ); ?></a>
					<?php endif; ?>
				</div>
			</div>
		<?php endforeach; ?>
	</div>

	<?php $form_type = (string) ( $_GET['type'] ?? ( $editing['document_type'] ?? 'certificate' ) ); ?>
	<?php
	if ( ! in_array( $form_type, $types, true ) ) {
		$form_type = 'certificate'; }
	?>

	<div class="rounded-xl border border-slate-200 bg-white p-6">
		<h2 class="mb-4 text-lg font-semibold text-slate-900">
			<?php echo $editing ? esc_html__( 'Edit design', 'eskoofy' ) : esc_html__( 'New design', 'eskoofy' ); ?>
			— <?php echo esc_html( ucwords( str_replace( '_', ' ', $form_type ) ) ); ?>
		</h2>

		<form method="post" action="">
			<?php wp_nonce_field( 'esk_document_design_nonce' ); ?>
			<?php if ( $editing ) : ?>
				<input type="hidden" name="id" value="<?php echo esc_attr( (int) $editing['id'] ); ?>">
			<?php endif; ?>
			<input type="hidden" name="document_type" value="<?php echo esc_attr( $form_type ); ?>">

			<div class="grid gap-4 sm:grid-cols-2">
				<div>
					<label class="mb-1 block text-sm font-medium text-slate-700"><?php esc_html_e( 'Name', 'eskoofy' ); ?></label>
					<input name="name" type="text" value="<?php echo esc_attr( (string) ( $editing['name'] ?? '' ) ); ?>" class="w-full rounded-lg border-slate-300 text-sm" required>
				</div>
				<div>
					<label class="mb-1 block text-sm font-medium text-slate-700"><?php esc_html_e( 'Template', 'eskoofy' ); ?></label>
					<select name="template" class="w-full rounded-lg border-slate-300 text-sm">
						<?php foreach ( esk_document_templates() as $template ) : ?>
							<option value="<?php echo esc_attr( $template ); ?>" <?php selected( (string) ( $editing['template'] ?? 'classic' ), $template ); ?>>
								<?php echo esc_html( ucfirst( $template ) ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>

			<?php
			$theme           = esk_document_theme( $form_type );
			$stored_settings = array();
			if ( $editing && ! empty( $editing['settings'] ) ) {
				$decoded         = json_decode( (string) $editing['settings'], true );
				$stored_settings = is_array( $decoded ) ? $decoded : array();
			}
			$stored_watermark = array();
			if ( $editing && ! empty( $editing['watermark'] ) ) {
				$decoded          = json_decode( (string) $editing['watermark'], true );
				$stored_watermark = is_array( $decoded ) ? $decoded : array();
			}
			$wm = esk_document_watermark( $form_type );
			?>

			<h3 class="mt-6 mb-2 text-sm font-semibold text-slate-700"><?php esc_html_e( 'Theme colours & metrics', 'eskoofy' ); ?></h3>
			<div class="grid gap-4 sm:grid-cols-3">
				<?php
				$color_fields = array(
					'primary_color'    => __( 'Primary', 'eskoofy' ),
					'secondary_color'  => __( 'Secondary', 'eskoofy' ),
					'accent_color'     => __( 'Accent', 'eskoofy' ),
					'text_color'       => __( 'Text', 'eskoofy' ),
					'muted_color'      => __( 'Muted', 'eskoofy' ),
					'background_color' => __( 'Background', 'eskoofy' ),
					'border_color'     => __( 'Border', 'eskoofy' ),
				);
				foreach ( $color_fields as $field => $label ) :
					?>
					<div>
						<label class="mb-1 block text-sm font-medium text-slate-700"><?php echo esc_html( $label ); ?></label>
						<input name="settings[<?php echo esc_attr( $field ); ?>]" type="color"
							value="<?php echo esc_attr( (string) ( $stored_settings[ $field ] ?? $theme[ $field ] ) ); ?>" class="h-10 w-full rounded-lg border-slate-300">
					</div>
				<?php endforeach; ?>
				<div>
					<label class="mb-1 block text-sm font-medium text-slate-700"><?php esc_html_e( 'Base font size', 'eskoofy' ); ?></label>
					<input name="settings[base_font_size]" type="number" min="8" max="32"
						value="<?php echo esc_attr( (int) ( $stored_settings['base_font_size'] ?? $theme['base_font_size'] ) ); ?>" class="w-full rounded-lg border-slate-300 text-sm">
				</div>
				<div>
					<label class="mb-1 block text-sm font-medium text-slate-700"><?php esc_html_e( 'Title font size', 'eskoofy' ); ?></label>
					<input name="settings[title_font_size]" type="number" min="10" max="72"
						value="<?php echo esc_attr( (int) ( $stored_settings['title_font_size'] ?? $theme['title_font_size'] ) ); ?>" class="w-full rounded-lg border-slate-300 text-sm">
				</div>
				<div>
					<label class="mb-1 block text-sm font-medium text-slate-700"><?php esc_html_e( 'Border style', 'eskoofy' ); ?></label>
					<select name="settings[border_style]" class="w-full rounded-lg border-slate-300 text-sm">
						<?php foreach ( array( 'none', 'solid', 'double', 'dashed', 'dotted' ) as $style ) : ?>
							<option value="<?php echo esc_attr( $style ); ?>" <?php selected( (string) ( $stored_settings['border_style'] ?? $theme['border_style'] ), $style ); ?>><?php echo esc_html( ucfirst( $style ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div>
					<label class="mb-1 block text-sm font-medium text-slate-700"><?php esc_html_e( 'Border width', 'eskoofy' ); ?></label>
					<input name="settings[border_width]" type="number" min="0" max="12"
						value="<?php echo esc_attr( (int) ( $stored_settings['border_width'] ?? $theme['border_width'] ) ); ?>" class="w-full rounded-lg border-slate-300 text-sm">
				</div>
				<div>
					<label class="mb-1 block text-sm font-medium text-slate-700"><?php esc_html_e( 'Accent bar', 'eskoofy' ); ?></label>
					<select name="settings[accent_bar]" class="w-full rounded-lg border-slate-300 text-sm">
						<?php foreach ( array( 'none', 'top', 'bottom', 'left', 'right' ) as $bar ) : ?>
							<option value="<?php echo esc_attr( $bar ); ?>" <?php selected( (string) ( $stored_settings['accent_bar'] ?? $theme['accent_bar'] ), $bar ); ?>><?php echo esc_html( ucfirst( $bar ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>

			<h3 class="mt-6 mb-2 text-sm font-semibold text-slate-700"><?php esc_html_e( 'Watermark', 'eskoofy' ); ?></h3>
			<div class="grid gap-4 sm:grid-cols-3">
				<div>
					<label class="mb-1 block text-sm font-medium text-slate-700"><?php esc_html_e( 'Text', 'eskoofy' ); ?></label>
					<input name="watermark[text]" type="text" value="<?php echo esc_attr( (string) ( $stored_watermark['text'] ?? '' ) ); ?>" class="w-full rounded-lg border-slate-300 text-sm" maxlength="120">
				</div>
				<div>
					<label class="mb-1 block text-sm font-medium text-slate-700"><?php esc_html_e( 'Opacity', 'eskoofy' ); ?></label>
					<input name="watermark[opacity]" type="number" step="0.05" min="0.05" max="1"
						value="<?php echo esc_attr( (string) ( $stored_watermark['opacity'] ?? $wm['opacity'] ) ); ?>" class="w-full rounded-lg border-slate-300 text-sm">
				</div>
				<div>
					<label class="mb-1 block text-sm font-medium text-slate-700"><?php esc_html_e( 'Rotation', 'eskoofy' ); ?></label>
					<input name="watermark[rotation]" type="number" min="-180" max="180"
						value="<?php echo esc_attr( (int) ( $stored_watermark['rotation'] ?? $wm['rotation'] ) ); ?>" class="w-full rounded-lg border-slate-300 text-sm">
				</div>
				<div>
					<label class="mb-1 block text-sm font-medium text-slate-700"><?php esc_html_e( 'Colour', 'eskoofy' ); ?></label>
					<input name="watermark[color]" type="color"
						value="<?php echo esc_attr( (string) ( $stored_watermark['color'] ?? $wm['color'] ) ); ?>" class="h-10 w-full rounded-lg border-slate-300">
				</div>
				<div>
					<label class="mb-1 block text-sm font-medium text-slate-700"><?php esc_html_e( 'Position', 'eskoofy' ); ?></label>
					<select name="watermark[position]" class="w-full rounded-lg border-slate-300 text-sm">
						<?php foreach ( array( 'center', 'diagonal', 'tile', 'top', 'bottom' ) as $position ) : ?>
							<option value="<?php echo esc_attr( $position ); ?>" <?php selected( (string) ( $stored_watermark['position'] ?? $wm['position'] ), $position ); ?>><?php echo esc_html( ucfirst( $position ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>

			<h3 class="mt-6 mb-2 text-sm font-semibold text-slate-700"><?php esc_html_e( 'Custom CSS', 'eskoofy' ); ?></h3>
			<textarea name="custom_css" rows="4" class="w-full rounded-lg border-slate-300 text-sm font-mono"><?php echo esc_textarea( (string) ( $editing['custom_css'] ?? '' ) ); ?></textarea>

			<label class="mt-3 flex items-center gap-2 text-sm text-slate-700">
				<input name="is_default" type="checkbox" value="1" <?php checked( $editing ? (bool) $editing['is_default'] : true ); ?>>
				<?php esc_html_e( 'Make this the default design for this document type', 'eskoofy' ); ?>
			</label>
			<label class="flex items-center gap-2 text-sm text-slate-700">
				<input name="is_active" type="checkbox" value="1" <?php checked( $editing ? (bool) $editing['is_active'] : true ); ?>>
				<?php esc_html_e( 'Active', 'eskoofy' ); ?>
			</label>

			<div class="mt-4 flex flex-wrap gap-2">
				<button type="submit" name="esk_document_design_save" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700"><?php esc_html_e( 'Save design', 'eskoofy' ); ?></button>
				<a class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
					href="<?php echo esc_url( admin_url( 'admin.php?page=esk-document-designs' ) ); ?>"><?php esc_html_e( 'Cancel', 'eskoofy' ); ?></a>
			</div>
		</form>
	</div>
</div>