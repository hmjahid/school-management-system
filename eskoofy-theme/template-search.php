<?php
/**
 * Template Name: Search Results
 *
 * @package Eskoofy
 */

declare(strict_types=1);

get_header();

$query = trim( (string) get_search_query() );

get_template_part(
	'template-parts/inner-hero',
	null,
	array(
		'title'    => (string) esk_site_ui( 'search.heading', __( 'Search', 'eskoofy' ) ),
		'subtitle' => (string) esk_site_ui( 'pages.breadcrumb_home', '' ),
	)
);
?>
<div class="esk-page-sections">
	<div class="esk-container">
		<form role="search" method="get" class="esk-page-form" action="<?php echo esc_url( home_url( '/' ) ); ?>" style="max-width:42rem; margin:0 auto 2rem;">
			<label class="screen-reader-text" for="esk-search-input"><?php echo esc_html( (string) esk_site_ui( 'search.placeholder', __( 'Search', 'eskoofy' ) ) ); ?></label>
			<div style="display:flex; gap:0.75rem;">
				<input type="search" id="esk-search-input" name="s" value="<?php echo esc_attr( $query ); ?>" placeholder="<?php echo esc_attr( (string) esk_site_ui( 'search.placeholder', __( 'Search...', 'eskoofy' ) ) ); ?>" autofocus>
				<button type="submit" class="esk-btn"><?php echo esc_html( (string) esk_site_ui( 'search.heading', __( 'Search', 'eskoofy' ) ) ); ?></button>
			</div>
		</form>

		<?php
		if ( '' === $query ) :
			echo '<p class="esk-empty">' . esc_html( (string) esk_site_ui( 'search.min_chars', __( 'Please enter at least 2 characters to search.', 'eskoofy' ) ) ) . '</p>';
		elseif ( ! have_posts() ) :
			?>
			<p class="esk-empty">
				<?php echo esc_html( (string) esk_site_ui( 'search.no_results', __( 'No results found for', 'eskoofy' ) ) ); ?>
				&quot;<strong><?php echo esc_html( $query ); ?></strong>&quot;
			</p>
		<?php else : ?>
			<?php
			$count = (int) $GLOBALS['wp_query']->found_posts;
			if ( 0 === $count ) {
				$count = (int) $GLOBALS['wp_query']->post_count;
			}
			?>
			<p class="esk-search-count">
				<?php echo esc_html( (string) $count ); ?>
				<?php echo esc_html( (string) esk_site_ui( 'search.results_for', __( 'result(s) found for', 'eskoofy' ) ) ); ?>
				&quot;<strong><?php echo esc_html( $query ); ?></strong>&quot;
			</p>
			<div class="esk-search-results">
				<?php
				while ( have_posts() ) :
					the_post();

					$type_name = __( 'Post', 'eskoofy' );
					if ( 'page' === get_post_type() ) {
						$type_name = (string) esk_site_ui( 'search.page', __( 'Page', 'eskoofy' ) );
					} elseif ( 'post' === get_post_type() ) {
						$type_name = (string) esk_site_ui( 'search.post', __( 'Post', 'eskoofy' ) );
					} else {
						$type_obj = get_post_type_object( get_post_type() );
						if ( is_object( $type_obj ) && isset( $type_obj->labels->name ) ) {
							$type_name = $type_obj->labels->name;
						}
					}
					?>
					<article id="post-<?php the_ID(); ?>" <?php post_class( 'esk-search-item esk-card' ); ?>>
						<a href="<?php the_permalink(); ?>" class="esk-search-link">
							<span class="esk-search-type"><?php echo esc_html( $type_name ); ?></span>
							<?php if ( 'post' === get_post_type() ) : ?>
								<span class="esk-search-date"><?php echo esc_html( get_the_date() ); ?></span>
							<?php endif; ?>
							<h2 class="esk-search-title"><?php the_title(); ?></h2>
							<div class="esk-search-excerpt"><?php the_excerpt(); ?></div>
						</a>
					</article>
				<?php endwhile; ?>
			</div>

			<div class="eskoofy-pagination">
				<?php
				the_posts_pagination(
					array(
						'prev_text' => esc_html__( '&laquo; Previous', 'eskoofy' ),
						'next_text' => esc_html__( 'Next &raquo;', 'eskoofy' ),
					)
				);
				?>
			</div>
		<?php endif; ?>
	</div>
</div>
<?php
get_footer();