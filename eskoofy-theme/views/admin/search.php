<?php
/**
 * Global admin search.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

$q = sanitize_text_field( $_GET['q'] ?? '' );
$results = array();
if ( strlen( $q ) >= 2 ) {
	$like = '%' . $wpdb->esc_like( $q ) . '%';

	$results['students'] = $wpdb->get_results( $wpdb->prepare(
		"SELECT s.id, s.admission_number, u.display_name AS title
		FROM {$wpdb->prefix}esk_students s JOIN {$wpdb->prefix}users u ON s.user_id = u.ID
		WHERE s.deleted_at IS NULL AND (u.display_name LIKE %s OR s.admission_number LIKE %s) LIMIT 10",
		$like, $like
	) );

	$results['teachers'] = $wpdb->get_results( $wpdb->prepare(
		"SELECT t.id, u.display_name AS title FROM {$wpdb->prefix}esk_teachers t
		JOIN {$wpdb->prefix}users u ON t.user_id = u.ID
		WHERE u.display_name LIKE %s LIMIT 10",
		$like
	) );

	$results['classes'] = $wpdb->get_results( $wpdb->prepare(
		"SELECT id, name AS title FROM {$wpdb->prefix}esk_classes WHERE name LIKE %s LIMIT 10",
		$like
	) );

	$results['exams'] = $wpdb->get_results( $wpdb->prepare(
		"SELECT id, name AS title FROM {$wpdb->prefix}esk_exams WHERE deleted_at IS NULL AND name LIKE %s LIMIT 10",
		$like
	) );

	$results['fees'] = $wpdb->get_results( $wpdb->prepare(
		"SELECT id, name AS title FROM {$wpdb->prefix}esk_fees WHERE deleted_at IS NULL AND name LIKE %s LIMIT 10",
		$like
	) );

	$results['news'] = $wpdb->get_results( $wpdb->prepare(
		"SELECT id, title FROM {$wpdb->prefix}esk_news WHERE deleted_at IS NULL AND title LIKE %s LIMIT 10",
		$like
	) );

	$results['notices'] = $wpdb->get_results( $wpdb->prepare(
		"SELECT id, title FROM {$wpdb->prefix}esk_notices WHERE title LIKE %s LIMIT 10",
		$like
	) );
}
?>
<div class="wrap esk-admin-wrap">
	<h1><?php esc_html_e( 'Global Search', 'eskoofy' ); ?></h1>
	<form method="get" class="esk-form esk-inline-form">
		<input type="hidden" name="page" value="esk-search">
		<input type="search" name="q" value="<?php echo esc_attr( $q ); ?>" placeholder="<?php esc_attr_e( 'Search...', 'eskoofy' ); ?>" style="min-width:300px;">
		<button type="submit" class="button button-primary"><?php esc_html_e( 'Search', 'eskoofy' ); ?></button>
	</form>

	<?php if ( strlen( $q ) >= 2 ) : ?>
		<?php $total_results = array_sum( array_map( 'count', $results ) ); ?>
		<p><?php printf( esc_html__( '%d results found.', 'eskoofy' ), $total_results ); ?></p>

		<?php foreach ( $results as $section => $rows ) : ?>
			<?php if ( empty( $rows ) ) continue; ?>
			<div class="esk-card">
				<h2><?php echo esc_html( ucfirst( $section ) ); ?></h2>
				<ul>
					<?php foreach ( $rows as $r ) : ?>
						<li><?php echo esc_html( $r->title ); ?> <small>(#<?php echo (int) $r->id; ?>)</small></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endforeach; ?>
	<?php endif; ?>
</div>