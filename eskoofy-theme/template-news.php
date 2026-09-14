<?php
/**
 * Template Name: News
 *
 * Lists published news from the esk_news table.
 *
 * @package Eskoofy
 */

declare(strict_types=1);

get_header();

$news = array();
global $wpdb;
$table = $wpdb->prefix . 'esk_news';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
$exists = (string) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
if ( '' !== $exists ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$news = (array) $wpdb->get_results( "SELECT * FROM {$table} WHERE is_published = 1 AND deleted_at IS NULL ORDER BY published_at DESC, id DESC" );
}

$empty = (string) esk_site_ui( 'pages.news_empty', '' );

get_template_part(
	'template-parts/inner-hero',
	null,
	array(
		'title'    => (string) esk_site_ui( 'pages.news_heading', __( 'News & events', 'eskoofy' ) ),
		'subtitle' => (string) $empty,
	)
);
?>
<div class="esk-page-sections">
	<div class="esk-container">
		<?php if ( empty( $news ) ) : ?>
			<p class="esk-empty"><?php echo esc_html( $empty ); ?></p>
		<?php else : ?>
			<div class="esk-grid esk-grid-3">
				<?php foreach ( $news as $n ) : ?>
					<article class="esk-card esk-news-card reveal">
						<?php if ( ! empty( $n->image_url ) ) : ?>
							<img class="esk-news-image" src="<?php echo esc_url( $n->image_url ); ?>" alt="<?php echo esc_attr( $n->title ); ?>" loading="lazy">
						<?php endif; ?>
						<div class="esk-news-body">
							<?php if ( ! empty( $n->category ) ) : ?>
								<span class="esk-badge esk-badge-news"><?php echo esc_html( $n->category ); ?></span>
							<?php endif; ?>
							<h2 class="esk-news-title"><?php echo esc_html( $n->title ); ?></h2>
							<?php if ( ! empty( $n->content ) ) : ?>
								<p class="esk-news-excerpt"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $n->content ), 24 ) ); ?></p>
							<?php endif; ?>
							<p class="esk-news-meta">
								<?php if ( ! empty( $n->published_at ) ) : ?>
									<time datetime="<?php echo esc_attr( $n->published_at ); ?>"><?php echo esc_html( esk_date_format( $n->published_at ) ); ?></time>
								<?php endif; ?>
								<?php if ( ! empty( $n->author_name ) ) : ?>
									<span> · <?php echo esc_html( $n->author_name ); ?></span>
								<?php endif; ?>
							</p>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</div>
<?php
get_footer();