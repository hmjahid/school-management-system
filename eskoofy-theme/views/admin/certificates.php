<?php
/**
 * Certificates management.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

// ── Print view ────────────────────────────────────────────────────
if ( isset( $_GET['print_cert'] ) ) {
	$pc = $wpdb->get_row( $wpdb->prepare(
		"SELECT sc.*, u.display_name AS student_name, c.name AS class_name, s.admission_number
		FROM {$wpdb->prefix}esk_student_certificates sc
		LEFT JOIN {$wpdb->prefix}esk_students s ON sc.student_id = s.id
		LEFT JOIN {$wpdb->prefix}users u ON s.user_id = u.ID
		LEFT JOIN {$wpdb->prefix}esk_classes c ON s.class_id = c.id
		WHERE sc.id = %d AND sc.deleted_at IS NULL",
		absint( $_GET['print_cert'] )
	) );
	if ( $pc ) {
		$details = json_decode( (string) $pc->details, true );
		$header  = $details['header_text'] ?? '';
		$footer  = $details['footer_text'] ?? '';
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="utf-8">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<title><?php echo esc_html( $pc->certificate_number ); ?></title>
			<style>
				body { font-family: Georgia, 'Times New Roman', serif; color: #0f172a; margin: 0; padding: 3rem 2rem; }
				.cert { max-width: 720px; margin: 0 auto; border: 3px double #1e3a8a; padding: 2.5rem; text-align: center; }
				.cert-logo { font-size: 1.5rem; font-weight: 800; letter-spacing: 0.1em; color: #1e3a8a; margin-bottom: 0.5rem; }
				.cert-no { font-size: 0.8rem; color: #64748b; margin-bottom: 1.5rem; }
				.cert-title { font-size: 1.75rem; font-weight: 800; letter-spacing: 0.08em; color: #1e3a8a; margin: 0 0 1.25rem; }
				.cert-body { font-size: 1.05rem; line-height: 1.8; color: #334155; }
				.cert-body strong { color: #0f172a; }
				.cert-footer { margin-top: 2rem; font-size: 0.9rem; color: #64748b; }
				@media print { body { padding: 0; } .cert { border-width: 2px; } }
			</style>
		</head>
		<body>
			<div class="cert">
				<div class="cert-logo"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></div>
				<div class="cert-no"><?php echo esc_html( $pc->certificate_number ); ?></div>
				<h1 class="cert-title"><?php echo esc_html( ucfirst( $pc->certificate_type ) ); ?> Certificate</h1>
				<?php if ( $header ) : ?><p style="font-style:italic;color:#475569;"><?php echo esc_html( $header ); ?></p><?php endif; ?>
				<div class="cert-body">
					<p><?php echo esc_html__( 'This certifies that', 'eskoofy' ); ?> <strong><?php echo esc_html( $pc->student_name ); ?></strong>
					<?php if ( $pc->class_name ) : ?>(<?php echo esc_html( $pc->class_name ); ?>)<?php endif; ?>
					<?php if ( $pc->admission_number ) : ?>— <?php echo esc_html__( 'Admission No.', 'eskoofy' ); ?> <?php echo esc_html( $pc->admission_number ); ?><?php endif; ?></p>
					<?php if ( $pc->body ) : ?><?php echo wp_kses_post( $pc->body ); ?><?php endif; ?>
				</div>
				<p class="cert-footer"><?php echo esc_html__( 'Issued on', 'eskoofy' ); ?> <?php echo esc_html( esk_date_format( $pc->issue_date ) ); ?><?php echo $footer ? ' — ' . esc_html( $footer ) : ''; ?></p>
			</div>
			<script>window.onload = function(){ window.print(); };</script>
		</body>
		</html>
		<?php
		exit;
	}
}

if ( isset( $_POST['esk_certificate_save'] ) ) {
	check_admin_referer( 'esk_certificate_form' );
	$wpdb->insert( $wpdb->prefix . 'esk_certificates', array(
		'name'       => sanitize_text_field( $_POST['name'] ?? '' ),
		'template'   => wp_kses_post( wp_unslash( $_POST['template'] ?? '' ) ),
		'created_by' => get_current_user_id(),
	) );
	esk_flash( 'success', __( 'Certificate template added.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-certificates' ) );
	exit;
}

if ( isset( $_POST['esk_certificate_save'] ) ) {
	check_admin_referer( 'esk_certificate_form' );
	$wpdb->insert( $wpdb->prefix . 'esk_certificates', array(
		'name'       => sanitize_text_field( $_POST['name'] ?? '' ),
		'template'   => wp_kses_post( wp_unslash( $_POST['template'] ?? '' ) ),
		'created_by' => get_current_user_id(),
	) );
	esk_flash( 'success', __( 'Certificate template added.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-certificates' ) );
	exit;
}

if ( isset( $_POST['esk_certificate_update'] ) ) {
	check_admin_referer( 'esk_certificate_form' );
	$cert_id = absint( $_POST['cert_id'] ?? 0 );
	if ( $cert_id ) {
		$wpdb->update( $wpdb->prefix . 'esk_certificates', array(
			'name'     => sanitize_text_field( $_POST['name'] ?? '' ),
			'template' => wp_kses_post( wp_unslash( $_POST['template'] ?? '' ) ),
		), array( 'id' => $cert_id ) );
	}
	esk_flash( 'success', __( 'Certificate template updated.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-certificates' ) );
	exit;
}

if ( isset( $_POST['esk_certificate_delete'] ) ) {
	check_admin_referer( 'esk_certificate_delete_' . absint( $_POST['cert_id'] ?? 0 ) );
	$wpdb->delete( $wpdb->prefix . 'esk_certificates', array( 'id' => absint( $_POST['cert_id'] ?? 0 ) ) );
	esk_flash( 'success', __( 'Certificate template deleted.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-certificates' ) );
	exit;
}

// ── Issued (per-student) certificates ──────────────────────────────
if ( isset( $_POST['esk_issue_certificate'] ) ) {
	check_admin_referer( 'esk_issue_certificate_form' );
	$student_id = absint( $_POST['student_id'] ?? 0 );
	$cert_type  = sanitize_text_field( $_POST['certificate_type'] ?? 'character' );
	$issue_date = sanitize_text_field( $_POST['issue_date'] ?? gmdate( 'Y-m-d' ) );
	$status     = in_array( $_POST['status'] ?? 'issued', array( 'draft', 'issued' ), true ) ? sanitize_text_field( $_POST['status'] ) : 'issued';
	$body       = wp_kses_post( wp_unslash( $_POST['body'] ?? '' ) );

	if ( $student_id ) {
		$number = 'CERT-' . gmdate( 'Y' ) . '-' . strtoupper( wp_generate_password( 6, false ) );
		$wpdb->insert( $wpdb->prefix . 'esk_student_certificates', array(
			'certificate_number' => $number,
			'student_id'         => $student_id,
			'certificate_type'   => $cert_type,
			'issue_date'         => $issue_date,
			'status'             => $status,
			'body'               => $body,
			'details'            => wp_json_encode( array(
				'header_text' => sanitize_text_field( $_POST['header_text'] ?? '' ),
				'footer_text' => sanitize_text_field( $_POST['footer_text'] ?? '' ),
			) ),
			'created_by'         => get_current_user_id(),
			'generated_by'       => get_current_user_id(),
		) );
	}
	esk_flash( 'success', __( 'Certificate issued.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-certificates' ) );
	exit;
}

if ( isset( $_POST['esk_issued_cert_delete'] ) ) {
	check_admin_referer( 'esk_issued_cert_delete_' . absint( $_POST['issued_id'] ?? 0 ) );
	$wpdb->update( $wpdb->prefix . 'esk_student_certificates', array( 'deleted_at' => current_time( 'mysql' ) ), array( 'id' => absint( $_POST['issued_id'] ?? 0 ) ) );
	esk_flash( 'success', __( 'Certificate removed.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-certificates' ) );
	exit;
}

$certificates = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}esk_certificates ORDER BY id DESC" );
$students     = $wpdb->get_results(
	"SELECT s.id, u.display_name FROM {$wpdb->prefix}esk_students s JOIN {$wpdb->prefix}users u ON s.user_id = u.ID WHERE s.status = 'active' AND s.deleted_at IS NULL ORDER BY u.display_name"
);
$issued = $wpdb->get_results(
	"SELECT sc.*, u.display_name AS student_name, c.name AS class_name
	FROM {$wpdb->prefix}esk_student_certificates sc
	LEFT JOIN {$wpdb->prefix}esk_students st ON sc.student_id = st.id
	LEFT JOIN {$wpdb->prefix}users u ON st.user_id = u.ID
	LEFT JOIN {$wpdb->prefix}esk_classes c ON st.class_id = c.id
	WHERE sc.deleted_at IS NULL ORDER BY sc.id DESC"
);
$flash        = esk_get_flash( 'success' );

$edit_cert = null;
if ( isset( $_GET['edit_cert'] ) ) {
	$edit_cert = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}esk_certificates WHERE id = %d", absint( $_GET['edit_cert'] ) ) );
}
$cert_types = array( 'transfer', 'character', 'achievement', 'participation', 'completion' );
?>
<div class="wrap esk-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Certificates', 'eskoofy' ); ?></h1>
	<hr class="wp-header-end">

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<div class="esk-card esk-form-card" style="margin-bottom:1.5rem;">
		<h2><?php esc_html_e( 'Issue Certificate', 'eskoofy' ); ?></h2>
		<form method="post" class="esk-form esk-form-horizontal">
			<?php wp_nonce_field( 'esk_issue_certificate_form' ); ?>
			<div class="esk-form-row">
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Student', 'eskoofy' ); ?> *</label>
					<select name="student_id" required>
						<option value=""><?php esc_html_e( 'Select', 'eskoofy' ); ?></option>
						<?php foreach ( $students as $s ) : ?>
							<option value="<?php echo esc_attr( $s->id ); ?>"><?php echo esc_html( $s->display_name ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Type', 'eskoofy' ); ?></label>
					<select name="certificate_type">
						<?php foreach ( $cert_types as $ct ) : ?>
							<option value="<?php echo esc_attr( $ct ); ?>"><?php echo esc_html( ucfirst( $ct ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Issue Date', 'eskoofy' ); ?></label>
					<input type="date" name="issue_date" value="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>">
				</div>
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Status', 'eskoofy' ); ?></label>
					<select name="status">
						<option value="issued"><?php esc_html_e( 'Issued', 'eskoofy' ); ?></option>
						<option value="draft"><?php esc_html_e( 'Draft', 'eskoofy' ); ?></option>
					</select>
				</div>
			</div>
			<div class="esk-form-row">
				<div class="esk-form-group"><label><?php esc_html_e( 'Header Text', 'eskoofy' ); ?></label><input type="text" name="header_text" class="regular-text"></div>
				<div class="esk-form-group"><label><?php esc_html_e( 'Footer Text', 'eskoofy' ); ?></label><input type="text" name="footer_text" class="regular-text"></div>
			</div>
			<div class="esk-form-group"><label><?php esc_html_e( 'Body (HTML)', 'eskoofy' ); ?></label><textarea name="body" class="large-text code" rows="4"></textarea></div>
			<button type="submit" name="esk_issue_certificate" class="button button-primary"><?php esc_html_e( 'Issue Certificate', 'eskoofy' ); ?></button>
		</form>
	</div>

	<div class="esk-table-scroll">
		<table class="wp-list-table widefat striped esk-table">
			<thead><tr>
				<th><?php esc_html_e( 'No.', 'eskoofy' ); ?></th>
				<th><?php esc_html_e( 'Student', 'eskoofy' ); ?></th>
				<th><?php esc_html_e( 'Type', 'eskoofy' ); ?></th>
				<th><?php esc_html_e( 'Issue Date', 'eskoofy' ); ?></th>
				<th><?php esc_html_e( 'Status', 'eskoofy' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'eskoofy' ); ?></th>
			</tr></thead>
			<tbody>
				<?php if ( empty( $issued ) ) : ?>
					<tr><td colspan="6">
						<div class="esk-empty-state">
							<div class="esk-empty-state-icon"><span class="dashicons dashicons-awards"></span></div>
							<p class="esk-empty-state-title"><?php esc_html_e( 'No certificates issued', 'eskoofy' ); ?></p>
							<p class="esk-empty-state-message"><?php esc_html_e( 'Issue a certificate to a student above.', 'eskoofy' ); ?></p>
						</div>
					</td></tr>
				<?php else : ?>
					<?php foreach ( $issued as $ic ) : ?>
						<tr>
							<td><?php echo esc_html( $ic->certificate_number ); ?></td>
							<td><strong><?php echo esc_html( $ic->student_name ); ?></strong><?php echo $ic->class_name ? ' <small>(' . esc_html( $ic->class_name ) . ')</small>' : ''; ?></td>
							<td><?php echo esc_html( ucfirst( $ic->certificate_type ) ); ?></td>
							<td><?php echo esc_html( esk_date_format( $ic->issue_date ) ); ?></td>
							<td><span class="esk-badge esk-badge-<?php echo esc_attr( $ic->status ); ?>"><?php echo esc_html( ucfirst( $ic->status ) ); ?></span></td>
							<td>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=esk-certificates&print_cert=' . $ic->id ) ); ?>" target="_blank" class="button button-small"><?php esc_html_e( 'Print', 'eskoofy' ); ?></a>
								<form method="post" style="display:inline;" onsubmit="return confirm('<?php esc_attr_e( 'Remove this certificate?', 'eskoofy' ); ?>');">
									<?php wp_nonce_field( 'esk_issued_cert_delete_' . $ic->id ); ?>
									<input type="hidden" name="issued_id" value="<?php echo esc_attr( $ic->id ); ?>">
									<button type="submit" name="esk_issued_cert_delete" class="button button-small"><?php esc_html_e( 'Delete', 'eskoofy' ); ?></button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>

	<div class="esk-card esk-form-card" style="margin:1.5rem 0;">
		<h2><?php echo $edit_cert ? esc_html__( 'Edit Certificate Template', 'eskoofy' ) : esc_html__( 'Add Certificate Template', 'eskoofy' ); ?></h2>
		<form method="post" class="esk-form">
			<?php wp_nonce_field( 'esk_certificate_form' ); ?>
			<?php if ( $edit_cert ) : ?>
				<input type="hidden" name="cert_id" value="<?php echo esc_attr( $edit_cert->id ); ?>">
			<?php endif; ?>
			<div class="esk-form-group"><label><?php esc_html_e( 'Name', 'eskoofy' ); ?> *</label><input type="text" name="name" class="regular-text" required value="<?php echo esc_attr( $edit_cert->name ?? '' ); ?>"></div>
			<div class="esk-form-group"><label><?php esc_html_e( 'Template (HTML)', 'eskoofy' ); ?> *</label><textarea name="template" class="large-text code" rows="8" required><?php echo esc_textarea( $edit_cert->template ?? '' ); ?></textarea></div>
			<button type="submit" name="<?php echo $edit_cert ? 'esk_certificate_update' : 'esk_certificate_save'; ?>" class="button button-primary"><?php echo $edit_cert ? esc_html__( 'Update Template', 'eskoofy' ) : esc_html__( 'Add Template', 'eskoofy' ); ?></button>
			<?php if ( $edit_cert ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=esk-certificates' ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'eskoofy' ); ?></a>
			<?php endif; ?>
		</form>
	</div>

	<table class="wp-list-table widefat striped esk-table">
		<thead><tr>
			<th><?php esc_html_e( 'Template', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Created', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Actions', 'eskoofy' ); ?></th>
		</tr></thead>
		<tbody>
			<?php if ( empty( $certificates ) ) : ?>
				<tr><td colspan="3"><?php esc_html_e( 'No certificate templates.', 'eskoofy' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $certificates as $c ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $c->name ); ?></strong></td>
						<td><?php echo esc_html( esk_date_format( $c->created_at ) ); ?></td>
						<td>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=esk-certificates&edit_cert=' . $c->id ) ); ?>" class="button button-small"><?php esc_html_e( 'Edit', 'eskoofy' ); ?></a>
							<form method="post" style="display:inline;" onsubmit="return confirm('<?php esc_attr_e( 'Delete this template?', 'eskoofy' ); ?>');">
								<?php wp_nonce_field( 'esk_certificate_delete_' . $c->id ); ?>
								<input type="hidden" name="cert_id" value="<?php echo esc_attr( $c->id ); ?>">
								<button type="submit" name="esk_certificate_delete" class="button button-small"><?php esc_html_e( 'Delete', 'eskoofy' ); ?></button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
