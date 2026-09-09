<?php
/**
 * Visitor logs.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

$logs = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}esk_visitor_logs ORDER BY created_at DESC LIMIT 200" );
?>
<div class="wrap esk-admin-wrap">
	<h1><?php esc_html_e( 'Visitor Logs', 'eskoofy' ); ?></h1>

	<table class="wp-list-table widefat striped esk-table">
		<thead><tr>
			<th><?php esc_html_e( 'IP', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'URL', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Method', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'User Agent', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Date', 'eskoofy' ); ?></th>
		</tr></thead>
		<tbody>
			<?php if ( empty( $logs ) ) : ?>
				<tr><td colspan="5"><?php esc_html_e( 'No logs yet.', 'eskoofy' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $logs as $l ) : ?>
					<tr>
						<td><?php echo esc_html( $l->ip ); ?></td>
						<td><small><?php echo esc_html( wp_trim_words( $l->url, 8 ) ); ?></small></td>
						<td><?php echo esc_html( $l->method ); ?></td>
						<td><small><?php echo esc_html( wp_trim_words( $l->user_agent, 6 ) ); ?></small></td>
						<td><?php echo esc_html( esk_date_format( $l->created_at ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>