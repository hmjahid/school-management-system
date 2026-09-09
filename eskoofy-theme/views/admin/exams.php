<?php
/**
 * Exams list view.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

if ( isset( $_GET['publish_exam'] ) ) {
	$exam_id = absint( $_GET['publish_exam'] );
	$wpdb->update( $wpdb->prefix . 'esk_exams', array( 'is_published' => 1, 'status' => 'completed' ), array( 'id' => $exam_id ) );
	esk_flash( 'success', __( 'Exam results published.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-exams' ) );
	exit;
}

$exams = $wpdb->get_results(
	"SELECT e.*, c.name AS class_name, s.name AS subject_name
	FROM {$wpdb->prefix}esk_exams e
	LEFT JOIN {$wpdb->prefix}esk_classes c ON e.class_id = c.id
	LEFT JOIN {$wpdb->prefix}esk_subjects s ON e.subject_id = s.id
	WHERE e.deleted_at IS NULL
	ORDER BY e.id DESC"
);

$flash = esk_get_flash( 'success' );
?>
<div class="wrap esk-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Exams', 'eskoofy' ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=esk-exams&action=add' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'eskoofy' ); ?></a>
	<hr class="wp-header-end">

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<table class="wp-list-table widefat striped esk-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Name', 'eskoofy' ); ?></th>
				<th><?php esc_html_e( 'Class', 'eskoofy' ); ?></th>
				<th><?php esc_html_e( 'Subject', 'eskoofy' ); ?></th>
				<th><?php esc_html_e( 'Dates', 'eskoofy' ); ?></th>
				<th><?php esc_html_e( 'Marks', 'eskoofy' ); ?></th>
				<th><?php esc_html_e( 'Status', 'eskoofy' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'eskoofy' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $exams ) ) : ?>
				<tr><td colspan="7"><?php esc_html_e( 'No exams found.', 'eskoofy' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $exams as $e ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $e->name ); ?></strong> <small>(<?php echo esc_html( $e->code ); ?>)</small></td>
						<td><?php echo esc_html( $e->class_name ); ?></td>
						<td><?php echo esc_html( $e->subject_name ); ?></td>
						<td><?php echo esc_html( esk_date_format( $e->start_date ) . ' — ' . esk_date_format( $e->end_date ) ); ?></td>
						<td><?php echo esc_html( $e->total_marks . '/' . $e->passing_marks ); ?></td>
						<td><span class="esk-badge esk-badge-<?php echo esc_attr( $e->status ); ?>"><?php echo esc_html( ucfirst( $e->status ) ); ?></span></td>
						<td>
							<?php if ( ! $e->is_published ) : ?>
								<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=esk-exams&publish_exam=' . $e->id ), 'publish_exam_' . $e->id ) ); ?>" class="button button-small" onclick="return confirm('<?php esc_attr_e( 'Publish results?', 'eskoofy' ); ?>')"><?php esc_html_e( 'Publish', 'eskoofy' ); ?></a>
							<?php else : ?>
								<span class="esk-text-success"><?php esc_html_e( 'Published', 'eskoofy' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
