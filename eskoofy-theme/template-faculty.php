<?php
/**
 * Template Name: Faculty
 *
 * Lists teachers from the esk_teachers table joined with wp_users.
 *
 * @package Eskoofy
 */

declare(strict_types=1);

get_header();

$empty = (string) esk_site_ui( 'pages.faculty_empty', '' );

$teachers = array();
global $wpdb;
$table = $wpdb->prefix . 'esk_teachers';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
$exists = (string) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
if ( '' !== $exists ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$teachers = (array) $wpdb->get_results(
		"SELECT t.qualification, t.subjects, u.ID AS user_id, u.display_name
		FROM {$table} t
		INNER JOIN {$wpdb->users} u ON t.user_id = u.ID
		ORDER BY u.display_name ASC
		LIMIT 80"
	);
}

get_template_part(
	'template-parts/inner-hero',
	null,
	array(
		'title'    => (string) esk_site_ui( 'pages.faculty_heading', __( 'Teaching staff directory', 'eskoofy' ) ),
		'subtitle' => (string) $empty,
	)
);
?>
<div class="esk-page-sections">
	<div class="esk-container">
		<?php if ( empty( $teachers ) ) : ?>
			<p class="esk-empty"><?php echo esc_html( $empty ); ?></p>
		<?php else : ?>
			<div class="esk-avatar-grid">
				<?php foreach ( $teachers as $t ) : ?>
					<div class="esk-avatar-card esk-card">
						<span class="esk-avatar-img" aria-hidden="true"><?php echo esc_html( esk_initials( (string) $t->display_name ) ); ?></span>
						<p class="esk-avatar-name"><?php echo esc_html( $t->display_name ); ?></p>
						<?php if ( ! empty( $t->qualification ) ) : ?>
							<p class="esk-avatar-role"><?php echo esc_html( $t->qualification ); ?></p>
						<?php endif; ?>
						<?php if ( ! empty( $t->subjects ) ) : ?>
							<p class="esk-avatar-role"><?php echo esc_html( $t->subjects ); ?></p>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</div>
<?php
get_footer();