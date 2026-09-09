<?php
/**
 * CMS pages editor — edits esk_website_contents.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

if ( isset( $_POST['esk_cms_save'] ) ) {
	check_admin_referer( 'esk_cms_form' );
	$page    = sanitize_text_field( wp_unslash( $_POST['page'] ?? '' ) );
	$section = sanitize_text_field( wp_unslash( $_POST['section'] ?? '' ) );
	$title   = sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) );
	$content = wp_kses_post( wp_unslash( $_POST['content'] ?? '' ) );

	if ( $page && $section ) {
		$wpdb->replace( $wpdb->prefix . 'esk_website_contents', array(
			'page'    => $page,
			'section' => $section,
			'title'   => $title,
			'content' => $content,
		), array( '%s', '%s', '%s', '%s' ) );
		esk_flash( 'success', __( 'Content saved.', 'eskoofy' ) );
	}
	wp_safe_redirect( admin_url( 'admin.php?page=esk-cms' ) );
	exit;
}

$contents = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}esk_website_contents ORDER BY page, sort_order" );
$flash    = esk_get_flash( 'success' );
?>
<div class="wrap esk-admin-wrap">
	<h1><?php esc_html_e( 'CMS Pages', 'eskoofy' ); ?></h1>

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<div class="esk-card esk-form-card" style="margin-bottom:1.5rem;">
		<h2><?php esc_html_e( 'Edit Content', 'eskoofy' ); ?></h2>
		<form method="post" class="esk-form">
			<?php wp_nonce_field( 'esk_cms_form' ); ?>
			<div class="esk-form-row">
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Page', 'eskoofy' ); ?> *</label>
					<select name="page" required>
						<?php foreach ( array( 'about', 'academics', 'admissions', 'students-life', 'faculty', 'transport', 'committee', 'terms', 'privacy', 'portal' ) as $p ) : ?>
							<option value="<?php echo esc_attr( $p ); ?>"><?php echo esc_html( $p ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Section', 'eskoofy' ); ?> *</label>
					<input type="text" name="section" placeholder="hero" required>
				</div>
			</div>
			<div class="esk-form-group">
				<label><?php esc_html_e( 'Title', 'eskoofy' ); ?></label>
				<input type="text" name="title" class="regular-text">
			</div>
			<div class="esk-form-group">
				<label><?php esc_html_e( 'Content', 'eskoofy' ); ?></label>
				<?php wp_editor( '', 'esk_cms_content', array( 'textarea_name' => 'content', 'textarea_rows' => 8 ) ); ?>
			</div>
			<button type="submit" name="esk_cms_save" class="button button-primary"><?php esc_html_e( 'Save', 'eskoofy' ); ?></button>
		</form>
	</div>

	<table class="wp-list-table widefat striped esk-table">
		<thead><tr>
			<th><?php esc_html_e( 'Page', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Section', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Title', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Updated', 'eskoofy' ); ?></th>
		</tr></thead>
		<tbody>
			<?php if ( empty( $contents ) ) : ?>
				<tr><td colspan="4"><?php esc_html_e( 'No content yet.', 'eskoofy' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $contents as $c ) : ?>
					<tr>
						<td><?php echo esc_html( $c->page ); ?></td>
						<td><?php echo esc_html( $c->section ); ?></td>
						<td><?php echo esc_html( $c->title ); ?></td>
						<td><?php echo esc_html( esk_date_format( $c->updated_at ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>