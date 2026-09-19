<?php
/**
 * Single template for esk_news custom post type.
 *
 * Hero with featured image overlay, breadcrumb/back link, meta row
 * (date, reading time), share buttons, related articles, article body.
 *
 * @package Eskoofy
 */

declare(strict_types=1);

get_header();

while ( have_posts() ) :
	the_post();

	$post_id    = get_the_ID();
	$title      = get_the_title();
	$img_url    = get_the_post_thumbnail_url( $post_id, 'full' );
	$content    = get_the_content();
	$date       = get_the_date( 'F j, Y' );
	$date_atom  = get_the_date( 'c' );
	$modified   = get_the_modified_date( 'c' );
	$author     = get_the_author();
	$categories = get_the_category();
	$cat_name   = ! empty( $categories ) ? $categories[0]->name : '';
	$cat_slug   = ! empty( $categories ) ? $categories[0]->slug : '';
	$word_count = str_word_count( wp_strip_all_tags( $content ) );
	$read_mins  = max( 1, (int) ceil( $word_count / 200 ) );
	$current_url = get_permalink();
	$school_name = esk_school( 'school_name' ) ?: get_bloginfo( 'name' );
	?>
	<article>
		<?php if ( $img_url ) : ?>
			<div style="position:relative; height:clamp(250px, 40vh, 500px); overflow:hidden;">
				<img src="<?php echo esc_url( $img_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" style="width:100%; height:100%; object-fit:cover; display:block;" loading="eager">
				<div style="position:absolute; inset:0; background:linear-gradient(0deg, rgba(2,6,23,0.65) 0%, rgba(2,6,23,0.2) 40%, transparent);"></div>
				<div style="position:absolute; bottom:0; left:0; right:0; padding:1.5rem;">
					<div style="max-width:48rem; margin:0 auto;">
						<?php if ( $cat_name ) : ?>
							<span class="esk-news-badge" style="position:static; display:inline-block; margin-bottom:0.5rem;"><?php echo esc_html( $cat_name ); ?></span>
						<?php endif; ?>
					</div>
				</div>
			</div>
		<?php endif; ?>

		<div style="max-width:48rem; margin:0 auto; padding:2rem 1.25rem 4rem;">
			<a href="<?php echo esc_url( home_url( '/news/' ) ); ?>" class="esk-link" style="font-size:0.9rem;">
				<svg class="esk-icon" style="width:16px; height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
				<?php echo esc_html( (string) esk_site_ui( 'news_show.back', '' ) ?: __( 'Back to News', 'eskoofy' ) ); ?>
			</a>

			<header style="margin-top:1.5rem;">
				<h1 style="font-size:clamp(1.75rem, 4vw, 2.5rem); font-weight:800; line-height:1.15; letter-spacing:-0.02em; color:var(--esk-ink);"><?php echo esc_html( $title ); ?></h1>

				<div style="display:flex; flex-wrap:wrap; align-items:center; gap:0.75rem; margin-top:1rem; font-size:0.875rem; color:var(--esk-subtle);">
					<?php if ( $author ) : ?>
						<span style="display:inline-flex; align-items:center; gap:0.35rem;">
							<svg class="esk-icon" style="width:16px; height:16px;" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>
							<?php echo esc_html( $author ); ?>
						</span>
						<span aria-hidden="true">&middot;</span>
					<?php endif; ?>
					<time datetime="<?php echo esc_attr( $date_atom ); ?>"><?php echo esc_html( $date ); ?></time>
					<span aria-hidden="true">&middot;</span>
					<span style="display:inline-flex; align-items:center; gap:0.35rem;">
						<svg class="esk-icon" style="width:16px; height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
						<?php
						/* translators: %d: number of minutes */
						printf( esc_html__( '%d min read', 'eskoofy' ), $read_mins );
						?>
					</span>
				</div>
			</header>

			<div class="esk-article-body" style="margin-top:2.5rem; font-size:1.05rem; line-height:1.8; color:var(--esk-muted);">
				<style>
					.esk-article-body > p:first-of-type::first-letter {
						float: left;
						font-size: 3.2rem;
						font-weight: 700;
						line-height: 1;
						margin: 0.1rem 0.6rem 0 0;
						color: var(--esk-accent);
					}
				</style>
				<?php the_content(); ?>
			</div>

			<div style="margin-top:2.5rem; border-top:1px solid var(--esk-border); padding-top:2rem;">
				<div style="display:flex; flex-wrap:wrap; align-items:center; gap:0.6rem;">
					<span style="font-size:0.875rem; font-weight:600; color:var(--esk-muted);"><?php esc_html_e( 'Share this article', 'eskoofy' ); ?>:</span>
					<a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo esc_attr( rawurlencode( $current_url ) ); ?>" target="_blank" rel="noopener noreferrer" style="display:inline-flex; align-items:center; gap:0.35rem; padding:0.4rem 0.9rem; border-radius:999px; background:#1877f2; color:#fff; font-size:0.78rem; font-weight:600; text-decoration:none;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
						<svg class="esk-icon" style="width:14px; height:14px;" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
						Facebook
					</a>
					<a href="https://twitter.com/intent/tweet?text=<?php echo esc_attr( rawurlencode( $title ) ); ?>&url=<?php echo esc_attr( rawurlencode( $current_url ) ); ?>" target="_blank" rel="noopener noreferrer" style="display:inline-flex; align-items:center; gap:0.35rem; padding:0.4rem 0.9rem; border-radius:999px; background:#0f172a; color:#fff; font-size:0.78rem; font-weight:600; text-decoration:none;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
						<svg class="esk-icon" style="width:14px; height:14px;" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
						X
					</a>
					<a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo esc_attr( rawurlencode( $current_url ) ); ?>&title=<?php echo esc_attr( rawurlencode( $title ) ); ?>" target="_blank" rel="noopener noreferrer" style="display:inline-flex; align-items:center; gap:0.35rem; padding:0.4rem 0.9rem; border-radius:999px; background:#0a66c2; color:#fff; font-size:0.78rem; font-weight:600; text-decoration:none;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
						<svg class="esk-icon" style="width:14px; height:14px;" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
						LinkedIn
					</a>
					<button type="button" onclick="navigator.clipboard.writeText(window.location.href);this.textContent='<?php echo esc_js( __( 'Copied!', 'eskoofy' ) ); ?>';setTimeout(()=>this.textContent='<?php echo esc_js( __( 'Copy Link', 'eskoofy' ) ); ?>',2000)" style="display:inline-flex; align-items:center; gap:0.35rem; padding:0.4rem 0.9rem; border-radius:999px; border:1px solid var(--esk-border); background:var(--esk-surface); color:var(--esk-ink); font-size:0.78rem; font-weight:600; cursor:pointer; transition:border-color 0.2s, background 0.2s;">
						<svg class="esk-icon" style="width:14px; height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
						<?php esc_html_e( 'Copy Link', 'eskoofy' ); ?>
					</button>
				</div>
			</div>

			<?php
			$related_args = array(
				'post_type'      => 'esk_news',
				'post_status'    => 'publish',
				'posts_per_page' => 3,
				'post__not_in'   => array( $post_id ),
				'orderby'        => 'date',
				'order'          => 'DESC',
			);
			if ( ! empty( $cat_slug ) ) {
				$related_args['category_name'] = $cat_slug;
			}
			$related_query = new WP_Query( $related_args );
			?>

			<?php if ( $related_query->have_posts() ) : ?>
				<div style="margin-top:3rem; border-top:1px solid var(--esk-border); padding-top:2.5rem;">
					<h2 style="font-size:1.5rem; font-weight:800; color:var(--esk-ink);"><?php esc_html_e( 'Related Articles', 'eskoofy' ); ?></h2>
					<div style="width:80px; height:4px; border-radius:999px; background:linear-gradient(90deg, var(--esk-accent), var(--esk-accent-dark)); margin-top:0.75rem;"></div>
					<div class="esk-grid esk-grid-3" style="margin-top:1.5rem;">
						<?php while ( $related_query->have_posts() ) :
							$related_query->the_post();
							$rel_id   = get_the_ID();
							$rel_img  = get_the_post_thumbnail_url( $rel_id, 'medium' );
							$rel_date = get_the_date( 'M j, Y' );
							?>
							<a href="<?php the_permalink(); ?>" class="esk-card esk-news-card" style="display:flex; flex-direction:column; text-decoration:none; overflow:hidden; transition:box-shadow 0.3s, transform 0.3s;" onmouseover="this.style.boxShadow='var(--esk-shadow-lg)'; this.style.transform='translateY(-3px)'" onmouseout="this.style.boxShadow='var(--esk-shadow)'; this.style.transform='none'">
								<div style="height:10rem; overflow:hidden; background:var(--esk-surface-alt);">
									<?php if ( $rel_img ) : ?>
										<img src="<?php echo esc_url( $rel_img ); ?>" alt="" style="width:100%; height:100%; object-fit:cover; display:block; transition:transform 0.5s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='none'" loading="lazy">
									<?php endif; ?>
								</div>
								<div style="padding:1rem;">
									<time style="font-size:0.8rem; color:var(--esk-subtle);"><?php echo esc_html( $rel_date ); ?></time>
									<h3 style="margin:0.25rem 0 0; font-size:1rem; font-weight:700; color:var(--esk-ink); line-height:1.35;"><?php the_title(); ?></h3>
								</div>
							</a>
						<?php endwhile; ?>
					</div>
				</div>
			<?php endif; ?>
			<?php wp_reset_postdata(); ?>
		</div>
	</article>

	<script type="application/ld+json">
	<?php
	echo wp_json_encode(
		array(
			'@context'         => 'https://schema.org',
			'@type'            => 'Article',
			'headline'         => $title,
			'datePublished'    => $date_atom,
			'dateModified'     => $modified,
			'author'           => array(
				'@type' => 'Person',
				'name'  => $author ?: $school_name,
			),
			'publisher'        => array(
				'@type' => 'Organization',
				'name'  => $school_name,
			),
			'image'            => $img_url ? array( $img_url ) : null,
			'mainEntityOfPage' => $current_url,
		),
		JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
	);
	?>
	</script>
<?php
endwhile;

get_footer();
