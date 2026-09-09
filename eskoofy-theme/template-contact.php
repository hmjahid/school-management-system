<?php
/**
 * Template Name: Contact
 *
 * @package Eskoofy
 */

declare(strict_types=1);

get_header();
?>
<div class="eskoofy-container">
	<?php
	if ( isset( $_POST['esk_contact_submit'] ) ) {
		check_admin_referer( 'esk_contact_form' );
		global $wpdb;
		$wpdb->insert( $wpdb->prefix . 'esk_contact_submissions', array(
			'name'    => sanitize_text_field( $_POST['contact_name'] ?? '' ),
			'email'   => sanitize_email( $_POST['contact_email'] ?? '' ),
			'phone'   => sanitize_text_field( $_POST['contact_phone'] ?? '' ),
			'subject' => sanitize_text_field( $_POST['contact_subject'] ?? '' ),
			'message' => sanitize_textarea_field( $_POST['contact_message'] ?? '' ),
		) );
		echo '<div class="esk-notice esk-notice-success"><p>' . esc_html__( 'Thank you for your message. We will get back to you soon.', 'eskoofy' ) . '</p></div>';
	}
	echo do_shortcode( '[eskoofy_contact_form]' );
	?>
</div>
<?php
get_footer();
