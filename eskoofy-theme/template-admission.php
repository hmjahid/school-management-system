<?php
/**
 * Template Name: Admission Page
 *
 * @package Eskoofy
 */

declare(strict_types=1);

get_header();
?>
<div class="eskoofy-container">
	<?php
	$settings = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}esk_admission_settings LIMIT 1" );
	if ( $settings && ! $settings->is_open ) :
		?>
		<div class="esk-notice esk-notice-warning">
			<p><?php echo esc_html( $settings->closed_message_en ?: 'Admissions are currently closed.' ); ?></p>
		</div>
	<?php else : ?>
		<?php echo do_shortcode( '[eskoofy_admission_form]' ); ?>
	<?php endif; ?>
</div>
<?php
get_footer();
