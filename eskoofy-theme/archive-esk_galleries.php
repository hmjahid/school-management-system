<?php
/**
 * Archive template for esk_galleries custom post type.
 *
 * Hero, gallery grid using esk-gallery-grid + esk-gallery-item with hover
 * overlay, lightbox (via existing main.js click handler), category filter
 * buttons if categories exist.
 *
 * @package Eskoofy
 */

declare(strict_types=1);

get_header();

$hero_title = esc_html__( 'Gallery', 'eskoofy' );
$hero_sub   = (string) esk_site_ui( 'gallery.page_subtitle', '' );

get_template_part(
	'template-parts/inner-hero',
	null,
	array(
		'title'    => $hero_title,
		'subtitle' => $hero_sub,
	)
);

$all_galleries = new WP_Query( array(
	'post_type'      => 'esk_galleries',
	'post_status'    => 'publish',
	'posts_per_page' => -1,
	'orderby'        => 'date',
	'order'          => 'DESC',
) );

$categories = array();
if ( $all_galleries->have_posts() ) {
	while ( $all_galleries->have_posts() ) {
		$all_galleries->the_post();
		$cats = get_the_category();
		if ( ! empty( $cats ) ) {
			foreach ( $cats as $cat ) {
				$categories[ $cat->slug ] = $cat->name;
			}
		}
	}
	wp_reset_postdata();
	$all_galleries->rewind_posts();
}
?>
<div class="esk-page-sections">
	<div class="esk-container">

		<?php if ( ! empty( $categories ) ) : ?>
			<div style="display:flex; flex-wrap:wrap; justify-content:center; gap:0.5rem; margin-bottom:2rem;" data-filter-tabs>
				<button type="button" data-filter="all" class="esk-filter-active" style="padding:0.5rem 1.25rem; border-radius:999px; border:1px solid var(--esk-border); background:var(--esk-accent); color:#fff; font-size:0.875rem; font-weight:600; cursor:pointer; transition:background 0.2s, border-color 0.2s, color 0.2s;">
					<?php esc_html_e( 'All', 'eskoofy' ); ?>
				</button>
				<?php foreach ( $categories as $slug => $name ) : ?>
					<button type="button" data-filter="<?php echo esc_attr( $slug ); ?>" style="padding:0.5rem 1.25rem; border-radius:999px; border:1px solid var(--esk-border); background:var(--esk-surface); color:var(--esk-muted); font-size:0.875rem; font-weight:600; cursor:pointer; transition:background 0.2s, border-color 0.2s, color 0.2s;">
						<?php echo esc_html( $name ); ?>
					</button>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php
		$gallery_urls = array();
		if ( $all_galleries->have_posts() ) {
			while ( $all_galleries->have_posts() ) {
				$all_galleries->the_post();
				$img  = get_the_post_thumbnail_url( get_the_ID(), 'large' );
				if ( $img ) {
					$gallery_urls[] = $img;
				}
			}
			wp_reset_postdata();
			$all_galleries->rewind_posts();
		}
		$gallery_urls_json = wp_json_encode( $gallery_urls );
		?>
		<?php if ( $all_galleries->have_posts() ) : ?>
			<div class="esk-gallery-grid">
				<?php
				$idx = 0;
				while ( $all_galleries->have_posts() ) :
					$all_galleries->the_post();
					$gal_id    = get_the_ID();
					$gal_img   = get_the_post_thumbnail_url( $gal_id, 'large' );
					$gal_title = get_the_title();
					$gal_desc  = get_the_excerpt();
					$gal_cats  = get_the_category();
					$cat_slug  = ! empty( $gal_cats ) ? $gal_cats[0]->slug : '';
					?>
					<figure class="esk-gallery-item" data-category="<?php echo esc_attr( $cat_slug ); ?>" data-lightbox="<?php echo esc_attr( $gallery_urls_json ); ?>" data-index="<?php echo esc_attr( (string) $idx ); ?>" style="position:relative; overflow:hidden; border-radius:0.75rem; aspect-ratio:4/3; background:var(--esk-surface-alt); cursor:pointer;">
						<?php if ( $gal_img ) : ?>
							<img src="<?php echo esc_url( $gal_img ); ?>" alt="<?php echo esc_attr( $gal_title ); ?>" style="width:100%; height:100%; object-fit:cover; display:block; transition:transform 0.3s;" loading="lazy">
						<?php endif; ?>
						<div style="position:absolute; inset:0; display:flex; flex-direction:column; justify-content:flex-end; background:linear-gradient(0deg, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0.1) 40%, transparent); opacity:0; transition:opacity 0.3s; padding:1.25rem;">
							<h3 style="color:#fff; font-size:1rem; font-weight:700; margin:0;"><?php echo esc_html( $gal_title ); ?></h3>
							<?php if ( $gal_desc ) : ?>
								<p style="color:rgba(255,255,255,0.8); font-size:0.82rem; margin:0.2rem 0 0; line-height:1.4;"><?php echo esc_html( wp_trim_words( $gal_desc, 10, '...' ) ); ?></p>
							<?php endif; ?>
							<span style="display:inline-flex; align-items:center; gap:0.3rem; margin-top:0.5rem; font-size:0.78rem; color:rgba(255,255,255,0.65); font-weight:500;">
								<svg class="esk-icon" style="width:14px; height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
								<?php esc_html_e( 'Click to view', 'eskoofy' ); ?>
							</span>
						</div>
					</figure>
					<?php
					$idx++;
				endwhile;
				wp_reset_postdata();
				?>
			</div>
		<?php else : ?>
			<div class="esk-empty">
				<svg class="esk-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
				<p><?php echo esc_html( (string) esk_site_ui( 'gallery.empty', '' ) ?: __( 'No gallery items have been published yet.', 'eskoofy' ) ); ?></p>
			</div>
		<?php endif; ?>
	</div>
</div>

<script>
(function(){
	/* Category filter buttons */
	var tabs = document.querySelector('[data-filter-tabs]');
	if (!tabs) return;
	var buttons = tabs.querySelectorAll('button[data-filter]');
	var items = document.querySelectorAll('.esk-gallery-item');

	buttons.forEach(function(btn){
		btn.addEventListener('click', function(){
			buttons.forEach(function(b){
				b.classList.remove('esk-filter-active');
				b.style.background = '';
				b.style.borderColor = '';
				b.style.color = '';
			});
			btn.classList.add('esk-filter-active');
			btn.style.background = 'var(--esk-accent)';
			btn.style.borderColor = 'var(--esk-accent)';
			btn.style.color = '#fff';

			var filter = btn.getAttribute('data-filter');
			items.forEach(function(item){
				if (filter === 'all' || item.getAttribute('data-category') === filter) {
					item.style.display = '';
				} else {
					item.style.display = 'none';
				}
			});
		});
	});
})();
</script>
<?php
get_footer();
