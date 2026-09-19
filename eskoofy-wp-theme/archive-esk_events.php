<?php
/**
 * Archive template for esk_events custom post type.
 *
 * Hero, upcoming/past filtered list using esk-event-list + esk-event-item,
 * countdown badge for upcoming events, no-JS fallback showing all.
 *
 * @package Eskoofy
 */

declare(strict_types=1);

get_header();

$hero_title = esc_html__( 'Events & Calendar', 'eskoofy' );
$hero_sub   = esc_html__( 'Upcoming school events, open days, and important dates.', 'eskoofy' );

get_template_part(
	'template-parts/inner-hero',
	null,
	array(
		'title'    => $hero_title,
		'subtitle' => $hero_sub,
	)
);

$now = current_time( 'mysql' );

$upcoming_query = new WP_Query( array(
	'post_type'      => 'esk_events',
	'post_status'    => 'publish',
	'posts_per_page' => 12,
	'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
		array(
			'key'     => 'event_date',
			'value'   => $now,
			'compare' => '>=',
			'type'    => 'DATETIME',
		),
	),
	'meta_key'       => 'event_date', // phpcs:ignore WordPress.DB.SlowDBQuery
	'orderby'        => 'meta_value',
	'order'          => 'ASC',
) );

$past_query = new WP_Query( array(
	'post_type'      => 'esk_events',
	'post_status'    => 'publish',
	'posts_per_page' => 10,
	'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
		'relation' => 'OR',
		array(
			'key'     => 'event_date',
			'value'   => $now,
			'compare' => '<',
			'type'    => 'DATETIME',
		),
		array(
			'key'     => 'event_date',
			'compare' => 'NOT EXISTS',
		),
	),
	'meta_key'       => 'event_date', // phpcs:ignore WordPress.DB.SlowDBQuery
	'orderby'        => 'meta_value',
	'order'          => 'DESC',
) );
?>
<div class="esk-page-sections">
	<div class="esk-container">

		<div style="display:flex; flex-wrap:wrap; gap:0.5rem; margin-bottom:2rem; justify-content:center;">
			<button type="button" data-filter="all" class="esk-btn esk-btn-primary" style="padding:0.5rem 1.25rem; border-radius:999px; font-size:0.875rem;"><?php esc_html_e( 'All', 'eskoofy' ); ?></button>
			<button type="button" data-filter="upcoming" class="esk-btn esk-btn-plain" style="padding:0.5rem 1.25rem; border-radius:999px; font-size:0.875rem;"><?php esc_html_e( 'Upcoming', 'eskoofy' ); ?></button>
			<button type="button" data-filter="past" class="esk-btn esk-btn-plain" style="padding:0.5rem 1.25rem; border-radius:999px; font-size:0.875rem;"><?php esc_html_e( 'Past', 'eskoofy' ); ?></button>
		</div>

		<?php if ( $upcoming_query->have_posts() ) : ?>
			<section data-filter-section="upcoming">
				<h2 style="font-size:1.5rem; font-weight:800; color:var(--esk-ink); margin-bottom:0.5rem;"><?php esc_html_e( 'Upcoming Events', 'eskoofy' ); ?></h2>
				<div style="width:80px; height:4px; border-radius:999px; background:linear-gradient(90deg, #f97316, #ea580c); margin-bottom:1.5rem;"></div>
				<ul class="esk-event-list">
					<?php while ( $upcoming_query->have_posts() ) :
						$upcoming_query->the_post();
						$ev_id      = get_the_ID();
						$ev_date    = get_post_meta( $ev_id, 'event_date', true );
						$ev_end     = get_post_meta( $ev_id, 'event_end_date', true );
						$ev_time    = get_post_meta( $ev_id, 'event_time', true );
						$ev_loc     = get_post_meta( $ev_id, 'event_location', true );
						$ev_virtual = (bool) get_post_meta( $ev_id, 'is_virtual', true );
						$ev_ts      = $ev_date ? strtotime( $ev_date ) : 0;
						$day_num    = $ev_date ? wp_date( 'd', $ev_ts ) : '';
						$month_abbr = $ev_date ? wp_date( 'M', $ev_ts ) : '';
						?>
						<li class="esk-event-item" data-event-type="upcoming">
							<div class="esk-event-date" style="background:var(--esk-warm); flex:0 0 3.75rem; text-align:center; border-radius:0.625rem; padding:0.375rem 0; color:#fff; font-size:0.6875rem; text-transform:uppercase; letter-spacing:0.06em;">
								<strong style="display:block; font-size:1.25rem; line-height:1.1;"><?php echo esc_html( $day_num ); ?></strong>
								<?php echo esc_html( $month_abbr ); ?>
							</div>
							<div class="esk-event-body" style="flex:1; min-width:0;">
								<h3 class="esk-event-title"><?php the_title(); ?></h3>
								<p class="esk-event-meta" style="margin:0.25rem 0 0; font-size:0.8125rem; color:var(--esk-subtle);">
									<?php if ( $ev_date ) : ?>
										<time datetime="<?php echo esc_attr( $ev_date ); ?>"><?php echo esc_html( wp_date( 'D, M j, Y', $ev_ts ) ); ?></time>
										<?php if ( $ev_time ) : ?>
											&middot; <?php echo esc_html( $ev_time ); ?>
										<?php endif; ?>
									<?php endif; ?>
									<?php if ( $ev_loc ) : ?>
										&middot; <?php echo esc_html( $ev_loc ); ?>
										<?php if ( $ev_virtual ) : ?>
											&middot; <?php esc_html_e( 'Virtual', 'eskoofy' ); ?>
										<?php endif; ?>
									<?php endif; ?>
								</p>
								<?php if ( $ev_date && $ev_ts > time() ) : ?>
									<p data-countdown="<?php echo esc_attr( $ev_date ); ?>" style="margin:0.35rem 0 0; font-size:0.8125rem; font-weight:600; color:var(--esk-accent); display:inline-flex; align-items:center; gap:0.35rem;">
										<svg class="esk-icon" style="width:14px; height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
										<span data-countdown-display></span>
									</p>
								<?php endif; ?>
							</div>
						</li>
					<?php endwhile; ?>
				</ul>
			</section>
		<?php else : ?>
			<div class="esk-empty" data-filter-section="upcoming">
				<svg class="esk-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
				<p><?php esc_html_e( 'No upcoming events published yet.', 'eskoofy' ); ?></p>
			</div>
		<?php endif; ?>
		<?php wp_reset_postdata(); ?>

		<?php if ( $past_query->have_posts() ) : ?>
			<section style="margin-top:3rem;" data-filter-section="past">
				<h2 style="font-size:1.5rem; font-weight:800; color:var(--esk-ink); margin-bottom:0.5rem;"><?php esc_html_e( 'Past Events', 'eskoofy' ); ?></h2>
				<div style="width:80px; height:4px; border-radius:999px; background:linear-gradient(90deg, #94a3b8, #64748b); margin-bottom:1.5rem;"></div>
				<div style="border:1px solid var(--esk-border); border-radius:0.75rem; background:var(--esk-surface); overflow:hidden;">
					<?php while ( $past_query->have_posts() ) :
						$past_query->the_post();
						$ev_id   = get_the_ID();
						$ev_date = get_post_meta( $ev_id, 'event_date', true );
						$ev_loc  = get_post_meta( $ev_id, 'event_location', true );
						$ev_ts   = $ev_date ? strtotime( $ev_date ) : 0;
						$day_num = $ev_date ? wp_date( 'd', $ev_ts ) : '';
						$month   = $ev_date ? wp_date( 'M', $ev_ts ) : '';
						?>
						<div style="display:flex; gap:1rem; align-items:center; padding:0.85rem 1.25rem; border-bottom:1px solid var(--esk-border); transition:background 0.2s;" onmouseover="this.style.background='var(--esk-surface-alt)'" onmouseout="this.style.background='transparent'" data-event-type="past">
							<div style="flex:0 0 3rem; text-align:center; background:var(--esk-surface-alt); border-radius:0.5rem; padding:0.35rem 0; color:var(--esk-subtle); font-size:0.7rem; text-transform:uppercase; font-weight:700;">
								<strong style="display:block; font-size:1rem; line-height:1.1; color:var(--esk-ink);"><?php echo esc_html( $day_num ); ?></strong>
								<?php echo esc_html( $month ); ?>
							</div>
							<div style="flex:1; min-width:0;">
								<p style="font-weight:600; color:var(--esk-ink); margin:0; font-size:0.9375rem;"><?php the_title(); ?></p>
								<?php if ( $ev_loc ) : ?>
									<p style="margin:0.15rem 0 0; font-size:0.8125rem; color:var(--esk-subtle);"><?php echo esc_html( $ev_loc ); ?></p>
								<?php endif; ?>
							</div>
							<p style="flex-shrink:0; font-size:0.8125rem; color:var(--esk-subtle); margin:0;"><?php echo esc_html( $ev_date ? wp_date( 'M j, Y', $ev_ts ) : '' ); ?></p>
						</div>
					<?php endwhile; ?>
				</div>
			</section>
		<?php endif; ?>
		<?php wp_reset_postdata(); ?>
	</div>
</div>

<noscript>
	<style>
		[data-filter] { pointer-events: none; opacity: 0.5; }
		[data-filter="all"] { pointer-events: auto; opacity: 1; }
	</style>
</noscript>

<script>
(function(){
	var buttons = document.querySelectorAll('button[data-filter]');
	var sections = document.querySelectorAll('[data-filter-section]');
	if (!buttons.length || !sections.length) return;

	function apply(filter) {
		sections.forEach(function(sec) {
			sec.style.display = (filter === 'all' || sec.getAttribute('data-filter-section') === filter) ? '' : 'none';
		});
	}

	buttons.forEach(function(btn) {
		btn.addEventListener('click', function() {
			buttons.forEach(function(b) {
				b.classList.remove('esk-btn-primary');
				b.classList.add('esk-btn-plain');
			});
			btn.classList.add('esk-btn-primary');
			btn.classList.remove('esk-btn-plain');
			apply(btn.getAttribute('data-filter'));
		});
	});
})();
</script>
<?php
get_footer();
