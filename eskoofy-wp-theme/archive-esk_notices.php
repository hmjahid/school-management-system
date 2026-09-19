<?php
/**
 * Archive template for esk_notices custom post type.
 *
 * Hero, pinned notices styled distinctly (badge), list with dates, pagination.
 *
 * @package Eskoofy
 */

declare(strict_types=1);

get_header();

$hero_title = esc_html__( 'Notices & Announcements', 'eskoofy' );
$hero_sub   = esc_html__( 'Stay informed with the latest notices and announcements.', 'eskoofy' );

get_template_part(
	'template-parts/inner-hero',
	null,
	array(
		'title'    => $hero_title,
		'subtitle' => $hero_sub,
	)
);

$paged = get_query_var( 'paged' ) ? (int) get_query_var( 'paged' ) : 1;

$args = array(
	'post_type'      => 'esk_notices',
	'post_status'    => 'publish',
	'posts_per_page' => 12,
	'paged'          => $paged,
	'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
		'relation' => 'OR',
		array(
			'key'     => 'pinned',
			'value'   => '1',
			'compare' => '=',
		),
		array(
			'key'     => 'pinned',
			'compare' => 'NOT EXISTS',
		),
	),
	'meta_key'       => 'pinned', // phpcs:ignore WordPress.DB.SlowDBQuery
	'orderby'        => array(
		'meta_value_num' => 'DESC',
		'date'           => 'DESC',
	),
);
$notices_query = new WP_Query( $args );
?>
<div class="esk-page-sections">
	<div class="esk-container" style="max-width:48rem;">

		<?php if ( $notices_query->have_posts() ) : ?>
			<div style="display:grid; gap:1rem;">
				<?php while ( $notices_query->have_posts() ) :
					$notices_query->the_post();
					$notice_id   = get_the_ID();
					$notice_title = get_the_title();
					$notice_content = get_the_content();
					$notice_date = get_the_date( 'M j, Y \a\t g:i A' );
					$is_pinned   = ( '1' === (string) get_post_meta( $notice_id, 'pinned', true ) );
					?>
					<div class="esk-card" style="padding:1.5rem; <?php echo $is_pinned ? 'border-left:4px solid #f59e0b; background:linear-gradient(135deg, rgba(245,158,11,0.04), var(--esk-surface));' : ''; ?>">
						<div style="display:flex; gap:1rem; align-items:flex-start;">
							<div style="flex-shrink:0; padding-top:2px;">
								<?php if ( $is_pinned ) : ?>
									<svg class="esk-icon" style="width:20px; height:20px; color:#f59e0b;" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a5 5 0 00-5 5v2a2 2 0 00-2 2v5a2 2 0 002 2h10a2 2 0 002-2v-5a2 2 0 00-2-2V7a5 5 0 00-5-5zm3 7V7a3 3 0 00-6 0v2h6z"/></svg>
								<?php else : ?>
									<span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:var(--esk-accent); margin-top:6px;"></span>
								<?php endif; ?>
							</div>
							<div style="min-width:0; flex:1;">
								<div style="display:flex; flex-wrap:wrap; align-items:center; gap:0.5rem;">
									<h2 style="font-size:1.1rem; font-weight:700; color:var(--esk-ink); margin:0;"><?php echo esc_html( $notice_title ); ?></h2>
									<?php if ( $is_pinned ) : ?>
										<span style="display:inline-block; padding:0.15rem 0.55rem; border-radius:999px; background:rgba(245,158,11,0.12); color:#b45309; font-size:0.72rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em;"><?php esc_html_e( 'Pinned', 'eskoofy' ); ?></span>
									<?php endif; ?>
								</div>
								<p style="margin:0.25rem 0 0; font-size:0.8rem; color:var(--esk-subtle);"><?php echo esc_html( $notice_date ); ?></p>
								<div style="margin-top:0.75rem; font-size:0.9375rem; line-height:1.7; color:var(--esk-muted);">
									<?php echo wp_kses_post( $notice_content ); ?>
								</div>
							</div>
						</div>
					</div>
				<?php endwhile; ?>
			</div>

			<div class="eskoofy-pagination">
				<?php
				if ( $notices_query->max_num_pages > 1 ) {
					$big = 999999999;
					echo wp_kses_post( paginate_links( array( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						'base'      => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
						'format'    => '?paged=%#%',
						'current'   => $paged,
						'total'     => $notices_query->max_num_pages,
						'mid_size'  => 2,
						'prev_text' => '&laquo; ' . esc_html__( 'Previous', 'eskoofy' ),
						'next_text' => esc_html__( 'Next', 'eskoofy' ) . ' &raquo;',
					) ) );
				}
				?>
			</div>
		<?php else : ?>
			<div class="esk-empty">
				<svg class="esk-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
				<p><?php esc_html_e( 'No notices have been published yet.', 'eskoofy' ); ?></p>
			</div>
		<?php endif; ?>
	</div>
</div>
<?php
wp_reset_postdata();
get_footer();
