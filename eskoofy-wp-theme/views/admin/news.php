<?php
/**
 * News management (esk_news table).
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

if ( isset( $_POST['esk_news_save'] ) ) {
	check_admin_referer( 'esk_news_form' );

	$title = sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) );
	$slug  = sanitize_title( $title );
	if ( '' === $slug ) {
		$slug = 'news-' . wp_generate_password( 6, false );
	}
	// Ensure a unique slug.
	$base  = $slug;
	$i     = 2;
	while ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}esk_news WHERE slug = %s AND deleted_at IS NULL", $slug ) ) ) {
		$slug = $base . '-' . $i++;
	}

	$wpdb->insert( $wpdb->prefix . 'esk_news', array(
		'title'        => $title,
		'slug'         => $slug,
		'content'      => wp_kses_post( wp_unslash( $_POST['content'] ?? '' ) ),
		'image_url'    => esc_url_raw( wp_unslash( $_POST['image_url'] ?? '' ) ),
		'category'     => sanitize_text_field( wp_unslash( $_POST['category'] ?? '' ) ),
		'is_published' => isset( $_POST['is_published'] ) ? 1 : 0,
		'published_at' => current_time( 'mysql' ),
		'author_name'  => sanitize_text_field( wp_unslash( $_POST['author_name'] ?? '' ) ),
	) );
	esk_flash( 'success', __( 'News item added.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-news' ) );
	exit;
}

if ( isset( $_POST['esk_news_update'] ) ) {
	check_admin_referer( 'esk_news_form' );
	$news_id = absint( $_POST['news_id'] ?? 0 );

	$title = sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) );
	$slug  = sanitize_title( $title );
	if ( '' === $slug ) {
		$slug = 'news-' . wp_generate_password( 6, false );
	}
	// Ensure a unique slug (excluding self).
	$base  = $slug;
	$i     = 2;
	while ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}esk_news WHERE slug = %s AND id <> %d AND deleted_at IS NULL", $slug, $news_id ) ) ) {
		$slug = $base . '-' . $i++;
	}

	if ( $news_id ) {
		$wpdb->update( $wpdb->prefix . 'esk_news', array(
			'title'        => $title,
			'slug'         => $slug,
			'content'      => wp_kses_post( wp_unslash( $_POST['content'] ?? '' ) ),
			'image_url'    => esc_url_raw( wp_unslash( $_POST['image_url'] ?? '' ) ),
			'category'     => sanitize_text_field( wp_unslash( $_POST['category'] ?? '' ) ),
			'is_published' => isset( $_POST['is_published'] ) ? 1 : 0,
			'author_name'  => sanitize_text_field( wp_unslash( $_POST['author_name'] ?? '' ) ),
		), array( 'id' => $news_id ) );
	}
	esk_flash( 'success', __( 'News item updated.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-news' ) );
	exit;
}

if ( isset( $_POST['esk_news_delete'] ) ) {
	check_admin_referer( 'esk_news_delete_' . absint( $_POST['news_id'] ?? 0 ) );
	$wpdb->delete( $wpdb->prefix . 'esk_news', array( 'id' => absint( $_POST['news_id'] ?? 0 ) ) );
	esk_flash( 'success', __( 'News item removed.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-news' ) );
	exit;
}

$news  = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}esk_news WHERE deleted_at IS NULL ORDER BY published_at DESC, id DESC" );
$flash = esk_get_flash( 'success' );

$edit_news = null;
if ( isset( $_GET['edit_news'] ) ) {
	$edit_news = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}esk_news WHERE id = %d", absint( $_GET['edit_news'] ) ) );
}
?>
<div class="wrap esk-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'News', 'eskoofy' ); ?></h1>
	<hr class="wp-header-end">

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<div class="esk-card esk-form-card" style="margin-bottom:1.5rem;">
		<h2><?php echo $edit_news ? esc_html__( 'Edit News', 'eskoofy' ) : esc_html__( 'Add News', 'eskoofy' ); ?></h2>
		<form method="post" class="esk-form">
			<?php wp_nonce_field( 'esk_news_form' ); ?>
			<?php if ( $edit_news ) : ?>
				<input type="hidden" name="news_id" value="<?php echo esc_attr( $edit_news->id ); ?>">
			<?php endif; ?>
			<div class="esk-form-row">
				<div class="esk-form-group"><label><?php esc_html_e( 'Title', 'eskoofy' ); ?> *</label><input type="text" name="title" required value="<?php echo esc_attr( $edit_news->title ?? '' ); ?>"></div>
				<div class="esk-form-group"><label><?php esc_html_e( 'Category', 'eskoofy' ); ?></label><input type="text" name="category" class="regular-text" value="<?php echo esc_attr( $edit_news->category ?? '' ); ?>"></div>
			</div>
			<div class="esk-form-row">
				<div class="esk-form-group"><label><?php esc_html_e( 'Image URL', 'eskoofy' ); ?></label><input type="url" name="image_url" class="regular-text" value="<?php echo esc_attr( $edit_news->image_url ?? '' ); ?>"></div>
				<div class="esk-form-group"><label><?php esc_html_e( 'Author', 'eskoofy' ); ?></label><input type="text" name="author_name" class="regular-text" value="<?php echo esc_attr( $edit_news->author_name ?? '' ); ?>"></div>
			</div>
			<div class="esk-form-group"><label><?php esc_html_e( 'Content', 'eskoofy' ); ?></label><textarea name="content" rows="4" class="large-text"><?php echo esc_textarea( $edit_news->content ?? '' ); ?></textarea></div>
			<div class="esk-form-group"><label><input type="checkbox" name="is_published" value="1" <?php checked( $edit_news->is_published ?? 1, 1 ); ?>> <?php esc_html_e( 'Published', 'eskoofy' ); ?></label></div>
			<button type="submit" name="<?php echo $edit_news ? 'esk_news_update' : 'esk_news_save'; ?>" class="button button-primary"><?php echo $edit_news ? esc_html__( 'Update News', 'eskoofy' ) : esc_html__( 'Add News', 'eskoofy' ); ?></button>
			<?php if ( $edit_news ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=esk-news' ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'eskoofy' ); ?></a>
			<?php endif; ?>
		</form>
	</div>

	<table class="wp-list-table widefat striped esk-table">
		<thead><tr>
			<th><?php esc_html_e( 'Title', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Category', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Published', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Date', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Actions', 'eskoofy' ); ?></th>
		</tr></thead>
		<tbody>
			<?php if ( empty( $news ) ) : ?>
				<tr><td colspan="5"><?php esc_html_e( 'No news.', 'eskoofy' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $news as $n ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $n->title ); ?></strong></td>
						<td><?php echo esc_html( $n->category ); ?></td>
						<td><?php echo $n->is_published ? '<span class="esk-badge esk-badge-active">' . esc_html__( 'Yes', 'eskoofy' ) . '</span>' : '<span class="esk-badge esk-badge-pending">' . esc_html__( 'Draft', 'eskoofy' ) . '</span>'; ?></td>
						<td><?php echo esc_html( $n->published_at ? esk_date_format( $n->published_at ) : '—' ); ?></td>
						<td>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=esk-news&edit_news=' . $n->id ) ); ?>" class="button button-small"><?php esc_html_e( 'Edit', 'eskoofy' ); ?></a>
							<form method="post" style="display:inline;" onsubmit="return confirm('<?php esc_attr_e( 'Delete this news item?', 'eskoofy' ); ?>');">
								<?php wp_nonce_field( 'esk_news_delete_' . $n->id ); ?>
								<input type="hidden" name="news_id" value="<?php echo esc_attr( $n->id ); ?>">
								<button type="submit" name="esk_news_delete" class="button button-small"><?php esc_html_e( 'Delete', 'eskoofy' ); ?></button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>