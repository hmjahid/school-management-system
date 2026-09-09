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
		$nonce = isset( $_POST['esk_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['esk_nonce'] ) ) : '';
		if ( wp_verify_nonce( $nonce, 'esk_contact_form' ) ) {
			global $wpdb;
			$wpdb->insert( $wpdb->prefix . 'esk_contact_submissions', array(
				'name'    => sanitize_text_field( wp_unslash( $_POST['contact_name'] ?? '' ) ),
				'email'   => sanitize_email( wp_unslash( $_POST['contact_email'] ?? '' ) ),
				'phone'   => sanitize_text_field( wp_unslash( $_POST['contact_phone'] ?? '' ) ),
				'subject' => sanitize_text_field( wp_unslash( $_POST['contact_subject'] ?? '' ) ),
				'message' => sanitize_textarea_field( wp_unslash( $_POST['contact_message'] ?? '' ) ),
			) );
			echo '<div class="esk-notice esk-notice-success"><p>' . esc_html__( 'Thank you for your message. We will get back to you soon.', 'eskoofy' ) . '</p></div>';
		} else {
			echo '<div class="esk-notice esk-notice-error"><p>' . esc_html__( 'Security check failed.', 'eskoofy' ) . '</p></div>';
		}
	}
	echo do_shortcode( '[eskoofy_contact_form]' );
	?>
</div>
<?php
get_footer();