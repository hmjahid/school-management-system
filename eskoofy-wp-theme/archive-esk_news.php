<?php
/**
 * Archive template for esk_news custom post type.
 *
 * Hero, featured first article (large card), remaining in responsive grid,
 * category badge, and pagination.
 *
 * @package Eskoofy
 */

declare(strict_types=1);

get_header();

$archive_title = esc_html__( 'News & Updates', 'eskoofy' );
$archive_desc  = (string) esk_site_ui( 'news.page_subtitle', '' );

get_template_part(
	'template-parts/inner-hero',
	null,
	array(
		'title'    => $archive_title,
		'subtitle' => $archive_desc,
	)
);

$paged = get_query_var( 'paged' ) ? (int) get_query_var( 'paged' ) : 1;

$args = array(
	'post_type'      => 'esk_news',
	'post_status'    => 'publish',
	'posts_per_page' => 10,
	'paged'          => $paged,
);

$query = new WP_Query( $args );
?>
<div class="esk-page-sections">
	<div class="esk-container">
		<?php if ( $query->have_posts() ) : ?>
			<?php
			$query->the_post();
			$featured_id    = get_the_ID();
			$featured_title = get_the_title();
			$featured_url   = get_permalink();
			$featured_img   = get_the_post_thumbnail_url( $featured_id, 'large' );
			$featured_date  = get_the_date( 'M j, Y' );
			$featured_excerpt = wp_trim_words( get_the_excerpt(), 30, '...' );
			$categories     = get_the_category();
			$featured_cat   = ! empty( $categories ) ? $categories[0]->name : '';
			?>

			<a href="<?php echo esc_url( $featured_url ); ?>" class="esk-card esk-news-card" style="display:block; text-decoration:none; margin-bottom:2.5rem; overflow:hidden; transition:box-shadow 0.3s, transform 0.3s;" onmouseover="this.style.boxShadow='var(--esk-shadow-lg)'; this.style.transform='translateY(-3px)'" onmouseout="this.style.boxShadow='var(--esk-shadow)'; this.style.transform='none'">
				<div style="display:grid; grid-template-columns:1fr 1fr; min-height:340px;">
					<div style="overflow:hidden; background:var(--esk-surface-alt);">
						<?php if ( $featured_img ) : ?>
							<img src="<?php echo esc_url( $featured_img ); ?>" alt="<?php echo esc_attr( $featured_title ); ?>" style="width:100%; height:100%; object-fit:cover; display:block; transition:transform 0.5s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='none'" loading="eager">
						<?php else : ?>
							<div style="width:100%; height:100%; min-height:340px; background:linear-gradient(135deg, var(--esk-accent-soft), var(--esk-surface-alt)); display:flex; align-items:center; justify-content:center;">
								<svg class="esk-icon" style="width:64px; height:64px; opacity:0.35;" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/></svg>
							</div>
						<?php endif; ?>
					</div>
					<div style="display:flex; flex-direction:column; justify-content:center; padding:2rem;">
						<?php if ( $featured_cat ) : ?>
							<span class="esk-news-badge" style="position:static; display:inline-block; margin-bottom:0.75rem; width:fit-content;"><?php echo esc_html( $featured_cat ); ?></span>
						<?php endif; ?>
						<time style="font-size:0.85rem; color:var(--esk-subtle);"><?php echo esc_html( $featured_date ); ?></time>
						<h2 style="font-size:clamp(1.5rem, 2.5vw, 2rem); font-weight:800; line-height:1.2; margin:0.5rem 0 0.75rem; color:var(--esk-ink); transition:color 0.2s;"><?php echo esc_html( $featured_title ); ?></h2>
						<p style="color:var(--esk-muted); font-size:0.95rem; line-height:1.7; margin:0;"><?php echo esc_html( $featured_excerpt ); ?></p>
						<span class="esk-link" style="margin-top:1rem;">
							<?php echo esc_html( (string) esk_site_ui( 'home.read_more', '' ) ?: __( 'Read more', 'eskoofy' ) ); ?>
							<svg class="esk-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
						</span>
					</div>
				</div>
			</a>

			<?php
			$remaining = array();
			while ( $query->have_posts() ) {
				$query->the_post();
				if ( get_the_ID() !== $featured_id ) {
					$remaining[] = get_the_ID();
				}
			}
			wp_reset_postdata();
			?>

			<?php if ( ! empty( $remaining ) ) : ?>
				<div class="esk-grid esk-grid-3" style="margin-top:1rem;">
					<?php foreach ( $remaining as $post_id ) :
						setup_postdata( $post_id );
						$img_url   = get_the_post_thumbnail_url( $post_id, 'medium_large' );
						$title     = get_the_title( $post_id );
						$url       = get_permalink( $post_id );
						$date      = get_the_date( 'M j, Y', $post_id );
						$excerpt   = wp_trim_words( get_the_excerpt( $post_id ), 18, '...' );
						$cats      = get_the_category( $post_id );
						$cat_name  = ! empty( $cats ) ? $cats[0]->name : '';
						?>
						<a href="<?php echo esc_url( $url ); ?>" class="esk-card esk-news-card" style="display:flex; flex-direction:column; text-decoration:none; overflow:hidden; transition:box-shadow 0.3s, transform 0.3s;" onmouseover="this.style.boxShadow='var(--esk-shadow-lg)'; this.style.transform='translateY(-4px)'" onmouseout="this.style.boxShadow='var(--esk-shadow)'; this.style.transform='none'">
							<div style="position:relative; overflow:hidden; aspect-ratio:16/9; background:var(--esk-surface-alt);">
								<?php if ( $img_url ) : ?>
									<img src="<?php echo esc_url( $img_url ); ?>" alt="" style="width:100%; height:100%; object-fit:cover; display:block; transition:transform 0.5s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='none'" loading="lazy">
								<?php else : ?>
									<div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; background:linear-gradient(135deg, var(--esk-surface-alt), var(--esk-accent-soft));">
										<svg class="esk-icon" style="width:36px; height:36px; opacity:0.4;" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/></svg>
									</div>
								<?php endif; ?>
								<?php if ( $cat_name ) : ?>
									<span class="esk-news-badge"><?php echo esc_html( $cat_name ); ?></span>
								<?php endif; ?>
							</div>
							<div class="esk-news-body">
								<time style="font-size:0.8rem; color:var(--esk-subtle);"><?php echo esc_html( $date ); ?></time>
								<h3 class="esk-news-title" style="transition:color 0.2s;"><?php echo esc_html( $title ); ?></h3>
								<p class="esk-news-excerpt"><?php echo esc_html( $excerpt ); ?></p>
								<span class="esk-link" style="margin-top:auto; padding-top:0.5rem;">
									<?php echo esc_html( (string) esk_site_ui( 'home.read_more', '' ) ?: __( 'Read more', 'eskoofy' ) ); ?>
									<svg class="esk-icon" style="width:16px; height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
								</span>
							</div>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<div class="eskoofy-pagination">
				<?php
				if ( $query->max_num_pages > 1 ) {
					$big = 999999999;
					echo wp_kses_post( paginate_links( array( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						'base'      => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
						'format'    => '?paged=%#%',
						'current'   => $paged,
						'total'     => $query->max_num_pages,
						'mid_size'  => 2,
						'prev_text' => '&laquo; ' . esc_html__( 'Previous', 'eskoofy' ),
						'next_text' => esc_html__( 'Next', 'eskoofy' ) . ' &raquo;',
					) ) );
				}
				?>
			</div>
		<?php else : ?>
			<div class="esk-empty">
				<svg class="esk-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
				<p><?php echo esc_html( (string) esk_site_ui( 'news.empty_news', '' ) ?: __( 'No news articles have been published yet.', 'eskoofy' ) ); ?></p>
			</div>
		<?php endif; ?>
	</div>
</div>
<?php
get_footer();
