<?php
/**
 * Careers management — job listings and applications.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

if ( isset( $_POST['esk_career_save'] ) ) {
	check_admin_referer( 'esk_career_form' );
	$wpdb->insert( $wpdb->prefix . 'esk_careers', array(
		'title'        => sanitize_text_field( $_POST['title'] ?? '' ),
		'description'  => wp_kses_post( wp_unslash( $_POST['description'] ?? '' ) ),
		'requirements' => wp_kses_post( wp_unslash( $_POST['requirements'] ?? '' ) ),
		'type'         => sanitize_text_field( $_POST['type'] ?? 'full_time' ),
		'location'     => sanitize_text_field( $_POST['location'] ?? '' ),
		'salary_min'   => (float) ( $_POST['salary_min'] ?? 0 ),
		'salary_max'   => (float) ( $_POST['salary_max'] ?? 0 ),
		'deadline'     => sanitize_text_field( $_POST['deadline'] ?? gmdate( 'Y-m-d' ) ),
		'is_published' => isset( $_POST['is_published'] ) ? 1 : 0,
	) );
	esk_flash( 'success', __( 'Job listing added.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-careers' ) );
	exit;
}

if ( isset( $_POST['esk_career_delete'] ) ) {
	check_admin_referer( 'esk_career_delete_' . absint( $_POST['career_id'] ?? 0 ) );
	$wpdb->delete( $wpdb->prefix . 'esk_careers', array( 'id' => absint( $_POST['career_id'] ?? 0 ) ) );
	esk_flash( 'success', __( 'Job listing deleted.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-careers' ) );
	exit;
}

$careers      = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}esk_careers ORDER BY id DESC" );
$applications = $wpdb->get_results(
	"SELECT ja.*, c.title AS career_title
	FROM {$wpdb->prefix}esk_job_applications ja
	JOIN {$wpdb->prefix}esk_careers c ON ja.career_id = c.id
	ORDER BY ja.id DESC
	LIMIT 50"
);
$flash = esk_get_flash( 'success' );
?>
<div class="wrap esk-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Careers', 'eskoofy' ); ?></h1>
	<hr class="wp-header-end">

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<div class="esk-card esk-form-card" style="margin-bottom:1.5rem;">
		<h2><?php esc_html_e( 'Add Job Listing', 'eskoofy' ); ?></h2>
		<form method="post" class="esk-form">
			<?php wp_nonce_field( 'esk_career_form' ); ?>
			<div class="esk-form-row">
				<div class="esk-form-group"><label><?php esc_html_e( 'Title', 'eskoofy' ); ?> *</label><input type="text" name="title" class="regular-text" required></div>
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Type', 'eskoofy' ); ?> *</label>
					<select name="type" required>
						<option value="full_time"><?php esc_html_e( 'Full Time', 'eskoofy' ); ?></option>
						<option value="part_time"><?php esc_html_e( 'Part Time', 'eskoofy' ); ?></option>
						<option value="contract"><?php esc_html_e( 'Contract', 'eskoofy' ); ?></option>
					</select>
				</div>
				<div class="esk-form-group"><label><?php esc_html_e( 'Location', 'eskoofy' ); ?> *</label><input type="text" name="location" required></div>
			</div>
			<div class="esk-form-row">
				<div class="esk-form-group"><label><?php esc_html_e( 'Salary Min', 'eskoofy' ); ?></label><input type="number" name="salary_min" step="0.01"></div>
				<div class="esk-form-group"><label><?php esc_html_e( 'Salary Max', 'eskoofy' ); ?></label><input type="number" name="salary_max" step="0.01"></div>
				<div class="esk-form-group"><label><?php esc_html_e( 'Deadline', 'eskoofy' ); ?> *</label><input type="date" name="deadline" required></div>
				<div class="esk-form-group"><label><input type="checkbox" name="is_published" value="1"> <?php esc_html_e( 'Published', 'eskoofy' ); ?></label></div>
			</div>
			<div class="esk-form-group"><label><?php esc_html_e( 'Description', 'eskoofy' ); ?> *</label><textarea name="description" class="large-text" rows="4" required></textarea></div>
			<div class="esk-form-group"><label><?php esc_html_e( 'Requirements', 'eskoofy' ); ?> *</label><textarea name="requirements" class="large-text" rows="3" required></textarea></div>
			<button type="submit" name="esk_career_save" class="button button-primary"><?php esc_html_e( 'Add Job', 'eskoofy' ); ?></button>
		</form>
	</div>

	<h2><?php esc_html_e( 'Job Listings', 'eskoofy' ); ?></h2>
	<table class="wp-list-table widefat striped esk-table">
		<thead><tr>
			<th><?php esc_html_e( 'Title', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Type', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Location', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Deadline', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Published', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Actions', 'eskoofy' ); ?></th>
		</tr></thead>
		<tbody>
			<?php if ( empty( $careers ) ) : ?>
				<tr><td colspan="6"><?php esc_html_e( 'No job listings.', 'eskoofy' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $careers as $c ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $c->title ); ?></strong></td>
						<td><?php echo esc_html( ucfirst( str_replace( '_', ' ', $c->type ) ) ); ?></td>
						<td><?php echo esc_html( $c->location ); ?></td>
						<td><?php echo esc_html( esk_date_format( $c->deadline ) ); ?></td>
						<td><?php echo $c->is_published ? '✓' : '—'; ?></td>
						<td>
							<form method="post" style="display:inline;" onsubmit="return confirm('<?php esc_attr_e( 'Delete this listing?', 'eskoofy' ); ?>');">
								<?php wp_nonce_field( 'esk_career_delete_' . $c->id ); ?>
								<input type="hidden" name="career_id" value="<?php echo esc_attr( $c->id ); ?>">
								<button type="submit" name="esk_career_delete" class="button button-small"><?php esc_html_e( 'Delete', 'eskoofy' ); ?></button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>

	<h2 style="margin-top:1.5rem;"><?php esc_html_e( 'Recent Applications', 'eskoofy' ); ?></h2>
	<table class="wp-list-table widefat striped esk-table">
		<thead><tr>
			<th><?php esc_html_e( 'Name', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Email', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Job', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Status', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Date', 'eskoofy' ); ?></th>
		</tr></thead>
		<tbody>
			<?php if ( empty( $applications ) ) : ?>
				<tr><td colspan="5"><?php esc_html_e( 'No applications.', 'eskoofy' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $applications as $app ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $app->name ); ?></strong></td>
						<td><?php echo esc_html( $app->email ); ?></td>
						<td><?php echo esc_html( $app->career_title ); ?></td>
						<td><span class="esk-badge esk-badge-<?php echo esc_attr( $app->status ); ?>"><?php echo esc_html( ucfirst( $app->status ) ); ?></span></td>
						<td><?php echo esc_html( esk_date_format( $app->created_at ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
