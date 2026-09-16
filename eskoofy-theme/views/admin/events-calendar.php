<?php
/**
 * Events calendar — month grid of published events.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

$year  = absint( $_GET['year'] ?? gmdate( 'Y' ) );
$month = absint( $_GET['month'] ?? gmdate( 'n' ) );
$month = min( 12, max( 1, $month ) );

$first_day_ts = mktime( 0, 0, 0, $month, 1, $year );
$days_in_month = (int) gmdate( 't', $first_day_ts );
$start_wday    = (int) gmdate( 'w', $first_day_ts );
$prev_month    = mktime( 0, 0, 0, $month - 1, 1, $year );
$next_month    = mktime( 0, 0, 0, $month + 1, 1, $year );

$events = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT * FROM {$wpdb->prefix}esk_events
		WHERE status = 'published' AND deleted_at IS NULL
		AND MONTH(start_date) = %d AND YEAR(start_date) = %d
		ORDER BY start_date ASC",
		$month,
		$year
	)
);

$by_day = array();
foreach ( $events as $ev ) {
	$by_day[ (int) gmdate( 'j', strtotime( $ev->start_date ) ) ][] = $ev;
}

$flash = esk_get_flash( 'success' );
?>
<div class="wrap esk-admin-wrap">
	<h1><?php esc_html_e( 'Events Calendar', 'eskoofy' ); ?></h1>

	<div class="esk-toolbar" style="justify-content:space-between;">
		<div>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=esk-events-calendar&year=' . gmdate( 'Y', $prev_month ) . '&month=' . gmdate( 'n', $prev_month ) ) ); ?>" class="button">&larr; <?php esc_html_e( 'Prev', 'eskoofy' ); ?></a>
			<span style="margin:0 1rem;font-weight:700;"><?php echo esc_html( gmdate( 'F Y', $first_day_ts ) ); ?></span>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=esk-events-calendar&year=' . gmdate( 'Y', $next_month ) . '&month=' . gmdate( 'n', $next_month ) ) ); ?>" class="button"><?php esc_html_e( 'Next', 'eskoofy' ); ?> &rarr;</a>
		</div>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=esk-events' ) ); ?>" class="button"><?php esc_html_e( 'Manage Events', 'eskoofy' ); ?></a>
	</div>

	<table class="wp-list-table widefat esk-calendar">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Sun', 'eskoofy' ); ?></th>
				<th><?php esc_html_e( 'Mon', 'eskoofy' ); ?></th>
				<th><?php esc_html_e( 'Tue', 'eskoofy' ); ?></th>
				<th><?php esc_html_e( 'Wed', 'eskoofy' ); ?></th>
				<th><?php esc_html_e( 'Thu', 'eskoofy' ); ?></th>
				<th><?php esc_html_e( 'Fri', 'eskoofy' ); ?></th>
				<th><?php esc_html_e( 'Sat', 'eskoofy' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
			$cell = 0;
			echo '<tr>';
			for ( $blank = 0; $blank < $start_wday; $blank++ ) {
				echo '<td class="esk-calendar-blank"></td>';
				++$cell;
			}
			for ( $d = 1; $d <= $days_in_month; $d++ ) {
				if ( 0 === $cell % 7 && 0 !== $cell ) {
					echo '</tr><tr>';
				}
				echo '<td class="esk-calendar-day">';
				echo '<div class="esk-calendar-date">' . esc_html( $d ) . '</div>';
				if ( ! empty( $by_day[ $d ] ) ) {
					echo '<ul class="esk-calendar-events">';
					foreach ( $by_day[ $d ] as $ev ) {
						$t = gmdate( 'g:i A', strtotime( $ev->start_date ) );
						echo '<li><a href="' . esc_url( admin_url( 'admin.php?page=esk-events&edit_event=' . $ev->id ) ) . '" title="' . esc_attr( $ev->title ) . '"><span class="esk-calendar-time">' . esc_html( $t ) . '</span> ' . esc_html( $ev->title ) . '</a></li>';
					}
					echo '</ul>';
				}
				echo '</td>';
				++$cell;
			}
			while ( 0 !== $cell % 7 ) {
				echo '<td class="esk-calendar-blank"></td>';
				++$cell;
			}
			echo '</tr>';
			?>
		</tbody>
	</table>
</div>