<?php
/**
 * Contact submissions inbox.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

if ( isset( $_GET['action'] ) && 'mark_read' === $_GET['action'] && ! empty( $_GET['id'] ) ) {
	$id = absint( $_GET['id'] );
	check_admin_referer( 'esk_cs_read_' . $id );
	$wpdb->update( $wpdb->prefix . 'esk_contact_submissions', array( 'meta' => 'read' ), array( 'id' => $id ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-contact-submissions' ) );
	exit;
}

if ( isset( $_GET['action'] ) && 'delete' === $_GET['action'] && ! empty( $_GET['id'] ) ) {
	$id = absint( $_GET['id'] );
	check_admin_referer( 'esk_cs_delete_' . $id );
	$wpdb->delete( $wpdb->prefix . 'esk_contact_submissions', array( 'id' => $id ) );
	esk_flash( 'success', __( 'Submission deleted.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-contact-submissions' ) );
	exit;
}

if ( isset( $_POST['esk_cs_export'] ) ) {
	check_admin_referer( 'esk_cs_export_form' );
	$rows = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}esk_contact_submissions ORDER BY created_at DESC", ARRAY_A );
	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=contact-submissions.csv' );
	$out = fopen( 'php://output', 'w' );
	fputcsv( $out, array( 'ID', 'Name', 'Email', 'Phone', 'Subject', 'Message', 'Date' ) );
	foreach ( $rows as $r ) {
		fputcsv( $out, array( $r['id'], $r['name'], $r['email'], $r['phone'], $r['subject'], wp_strip_all_tags( $r['message'] ), $r['created_at'] ) );
	}
	fclose( $out );
	exit;
}

$subs = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}esk_contact_submissions ORDER BY created_at DESC" );
?>
<div class="wrap esk-admin-wrap">
	<h1><?php esc_html_e( 'Contact Submissions', 'eskoofy' ); ?></h1>

	<form method="post" style="margin-bottom:1rem;">
		<?php wp_nonce_field( 'esk_cs_export_form' ); ?>
		<button type="submit" name="esk_cs_export" class="button"><?php esc_html_e( 'Export CSV', 'eskoofy' ); ?></button>
	</form>

	<table class="wp-list-table widefat striped esk-table">
		<thead><tr>
			<th><?php esc_html_e( 'Name', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Email', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Subject', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Message', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Date', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Actions', 'eskoofy' ); ?></th>
		</tr></thead>
		<tbody>
			<?php if ( empty( $subs ) ) : ?>
				<tr><td colspan="6"><?php esc_html_e( 'No submissions yet.', 'eskoofy' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $subs as $s ) : ?>
					<tr <?php echo 'read' === ( $s->meta ?? '' ) ? 'style="opacity:.6;"' : ''; ?>>
						<td><strong><?php echo esc_html( $s->name ); ?></strong><br><small><?php echo esc_html( $s->phone ); ?></small></td>
						<td><a href="mailto:<?php echo esc_attr( $s->email ); ?>"><?php echo esc_html( $s->email ); ?></a></td>
						<td><?php echo esc_html( $s->subject ); ?></td>
						<td><?php echo esc_html( wp_trim_words( $s->message, 15 ) ); ?></td>
						<td><?php echo esc_html( esk_date_format( $s->created_at ) ); ?></td>
						<td>
							<?php if ( 'read' !== ( $s->meta ?? '' ) ) : ?>
								<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=esk-contact-submissions&action=mark_read&id=' . $s->id ), 'esk_cs_read_' . $s->id ) ); ?>" class="button button-small"><?php esc_html_e( 'Mark Read', 'eskoofy' ); ?></a>
							<?php endif; ?>
							<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=esk-contact-submissions&action=delete&id=' . $s->id ), 'esk_cs_delete_' . $s->id ) ); ?>" class="button button-small" onclick="return confirm('Delete?');"><?php esc_html_e( 'Delete', 'eskoofy' ); ?></a>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>