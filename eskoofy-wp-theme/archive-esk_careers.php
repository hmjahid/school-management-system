<?php
/**
 * Careers archive — reads from esk_careers table.
 *
 * @package Eskoofy
 */

declare(strict_types=1);

get_header();
?>
<div class="eskoofy-container">
	<h1><?php esc_html_e( 'Careers', 'eskoofy' ); ?></h1>

	<?php
	global $wpdb;
	$careers = $wpdb->get_results(
		"SELECT * FROM {$wpdb->prefix}esk_careers
		WHERE is_published = 1 AND deadline >= CURDATE() ORDER BY id DESC"
	);

	if ( empty( $careers ) ) {
		echo '<p>' . esc_html__( 'No open positions.', 'eskoofy' ) . '</p>';
	} else {
		foreach ( $careers as $c ) {
			echo '<article style="padding:1.5rem;border:1px solid #e5e7eb;border-radius:8px;margin-bottom:1rem;">';
			echo '<h2>' . esc_html( $c->title ) . '</h2>';
			echo '<p><strong>' . esc_html( $c->type ) . '</strong> &middot; ' . esc_html( $c->location );
			if ( $c->salary_min ) {
				echo ' &middot; ' . esc_html( esk_format_currency( $c->salary_min ) ) . ' – ' . esc_html( esk_format_currency( $c->salary_max ) );
			}
			echo '</p>';
			echo '<p>' . esc_html__( 'Deadline:', 'eskoofy' ) . ' ' . esc_html( esk_date_format( $c->deadline ) ) . '</p>';
			echo '<div>' . wp_kses_post( wp_trim_words( $c->description, 40 ) ) . '</div>';
			echo '</article>';
		}
	}
	?>
</div>
<?php get_footer();