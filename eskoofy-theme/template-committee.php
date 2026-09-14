<?php
/**
 * Template Name: Committee
 *
 * Lists active committee members from the esk_committee_members table.
 *
 * @package Eskoofy
 */

declare(strict_types=1);

get_header();

$is_bn = 'bn_BD' === get_option( 'esk_locale', 'en' ) || 0 === strpos( get_locale(), 'bn' );
$t_loc = static function ( string $en, string $bn ) use ( $is_bn ): string {
	return $is_bn && '' !== $bn ? $bn : $en;
};

$members = array();
global $wpdb;
$table = $wpdb->prefix . 'esk_committee_members';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
$exists = (string) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
if ( '' !== $exists ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$members = (array) $wpdb->get_results( "SELECT * FROM {$table} WHERE is_active = 1 ORDER BY sort_order ASC, id ASC" );
}

get_template_part(
	'template-parts/inner-hero',
	null,
	array(
		'title'    => (string) esk_site_ui( 'pages.committee_heading', __( 'School committee', 'eskoofy' ) ),
		'subtitle' => '',
	)
);
?>
<div class="esk-page-sections">
	<div class="esk-container">
		<?php if ( empty( $members ) ) : ?>
			<p class="esk-empty"><?php echo esc_html( esk_site_ui( 'pages.committee_empty', '' ) ); ?></p>
		<?php else : ?>
			<div class="esk-avatar-grid">
				<?php foreach ( $members as $m ) : ?>
					<div class="esk-avatar-card esk-card">
						<?php if ( ! empty( $m->photo ) ) : ?>
							<img class="esk-avatar-img" src="<?php echo esc_url( $m->photo ); ?>" alt="<?php echo esc_attr( $t_loc( (string) $m->name, (string) $m->name_bn ) ); ?>" loading="lazy">
						<?php else : ?>
							<span class="esk-avatar-img" aria-hidden="true"><?php echo esc_html( esk_initials( (string) $m->name ) ); ?></span>
						<?php endif; ?>
						<p class="esk-avatar-name"><?php echo esc_html( $t_loc( (string) $m->name, (string) $m->name_bn ) ); ?></p>
						<p class="esk-avatar-role"><?php echo esc_html( $t_loc( (string) $m->designation, (string) $m->designation_bn ) ); ?></p>
						<?php if ( ! empty( $m->phone ) ) : ?>
							<p class="esk-avatar-role"><a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', (string) $m->phone ) ); ?>"><?php echo esc_html( $m->phone ); ?></a></p>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</div>
<?php
get_footer();