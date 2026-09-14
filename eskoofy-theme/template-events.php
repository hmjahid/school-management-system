<?php
/**
 * Template Name: Events
 *
 * Lists published events from the esk_events table.
 *
 * @package Eskoofy
 */

declare(strict_types=1);

get_header();

$events = array();
global $wpdb;
$table = $wpdb->prefix . 'esk_events';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
$exists = (string) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
if ( '' !== $exists ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$events = (array) $wpdb->get_results( "SELECT * FROM {$table} WHERE status = 'published' AND deleted_at IS NULL ORDER BY start_date ASC" );
}

$empty = (string) esk_site_ui( 'pages.events_empty', '' );

get_template_part(
	'template-parts/inner-hero',
	null,
	array(
		'title'    => (string) esk_site_ui( 'pages.events_heading', __( 'School events', 'eskoofy' ) ),
		'subtitle' => (string) $empty,
	)
);
?>
<div class="esk-page-sections">
	<div class="esk-container">
		<?php if ( empty( $events ) ) : ?>
			<p class="esk-empty"><?php echo esc_html( $empty ); ?></p>
		<?php else : ?>
			<ul class="esk-event-list">
				<?php
				foreach ( $events as $event ) :
					$start_ts = strtotime( (string) $event->start_date );
					$day      = $start_ts ? gmdate( 'd', $start_ts ) : '';
					$month    = $start_ts ? gmdate( 'M', $start_ts ) : '';
					?>
					<li class="esk-event-item">
						<div class="esk-event-date" aria-hidden="true">
							<?php if ( $month ) : ?>
								<span><?php echo esc_html( $month ); ?></span>
							<?php endif; ?>
							<strong><?php echo esc_html( $day ); ?></strong>
						</div>
						<div class="esk-event-body">
							<p class="esk-event-title"><?php echo esc_html( $event->title ); ?></p>
							<?php if ( ! empty( $event->location ) ) : ?>
								<p class="esk-event-meta"><?php echo esc_html( $event->location ); ?></p>
							<?php endif; ?>
							<?php if ( ! empty( $event->description ) ) : ?>
								<p class="esk-event-meta"><?php echo esc_html( $event->description ); ?></p>
							<?php endif; ?>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
</div>
<?php
get_footer();