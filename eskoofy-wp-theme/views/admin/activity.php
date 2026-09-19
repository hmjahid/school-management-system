<?php
/**
 * Activity log.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

$logs = $wpdb->get_results(
	"SELECT a.*, u.display_name FROM {$wpdb->prefix}esk_activities a
	JOIN {$wpdb->prefix}users u ON a.user_id = u.ID
	ORDER BY a.created_at DESC LIMIT 200"
);
?>
<div class="wrap esk-admin-wrap">
	<h1><?php esc_html_e( 'Activity Log', 'eskoofy' ); ?></h1>

	<table class="wp-list-table widefat striped esk-table">
		<thead><tr>
			<th><?php esc_html_e( 'User', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Type', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Title', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Message', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Date', 'eskoofy' ); ?></th>
		</tr></thead>
		<tbody>
			<?php if ( empty( $logs ) ) : ?>
				<tr><td colspan="5"><?php esc_html_e( 'No activity yet.', 'eskoofy' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $logs as $l ) : ?>
					<tr>
						<td><?php echo esc_html( $l->display_name ); ?></td>
						<td><?php echo esc_html( $l->type ); ?></td>
						<td><strong><?php echo esc_html( $l->title ); ?></strong></td>
						<td><?php echo esc_html( wp_trim_words( $l->message, 12 ) ); ?></td>
						<td><?php echo esc_html( esk_date_format( $l->created_at ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>