<?php
/**
 * Template Name: Notices
 *
 * Lists notices from the esk_notices table.
 *
 * @package Eskoofy
 */

declare(strict_types=1);

get_header();

$notices = array();
global $wpdb;
$table = $wpdb->prefix . 'esk_notices';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
$exists = (string) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
if ( '' !== $exists ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$notices = (array) $wpdb->get_results( "SELECT * FROM {$table} ORDER BY pinned DESC, created_at DESC, id DESC" );
}

$empty = (string) esk_site_ui( 'pages.notices_empty', '' );

get_template_part(
	'template-parts/inner-hero',
	null,
	array(
		'title'    => (string) esk_site_ui( 'pages.notices_heading', __( 'Notices', 'eskoofy' ) ),
		'subtitle' => (string) $empty,
	)
);
?>
<div class="esk-page-sections">
	<div class="esk-container">
		<?php if ( empty( $notices ) ) : ?>
			<p class="esk-empty"><?php echo esc_html( $empty ); ?></p>
		<?php else : ?>
			<ul class="esk-event-list">
				<?php foreach ( $notices as $n ) : ?>
					<li class="esk-event-item">
						<div class="esk-notices-icon" aria-hidden="true">
							<?php echo $n->pinned ? '📌' : '•'; ?>
						</div>
						<div class="esk-event-body">
							<p class="esk-event-title"><?php echo esc_html( $n->title ); ?></p>
							<?php if ( ! empty( $n->content ) ) : ?>
								<p class="esk-event-meta"><?php echo esc_html( wp_strip_all_tags( $n->content ) ); ?></p>
							<?php endif; ?>
							<?php if ( ! empty( $n->created_at ) ) : ?>
								<p class="esk-event-meta"><?php echo esc_html( esk_date_format( $n->created_at ) ); ?></p>
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