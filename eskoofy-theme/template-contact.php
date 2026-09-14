<?php
/**
 * Template Name: Contact
 *
 * @package Eskoofy
 */

declare(strict_types=1);

get_header();

$phone    = esk_school( 'phone', '' );
$email    = esk_school( 'email', '' );
$address  = esk_school( 'full_address', '' );
if ( '' === $address ) {
	$address = esk_school( 'address', '' );
}
$hero_title    = (string) esk_page_title( 'contact', __( 'Contact Us', 'eskoofy' ) );
$hero_subtitle = '';

get_template_part(
	'template-parts/inner-hero',
	null,
	array(
		'title'    => $hero_title,
		'subtitle' => $hero_subtitle,
	)
);

$flash = esk_get_flash( 'success' );

$emergency = array();
$contact_rows = esk_page_rows( 'contact' );
foreach ( $contact_rows as $row ) {
	if ( 'emergency_contacts' === (string) $row->section || 'emergency' === (string) $row->section ) {
		$lines = array_filter( array_map( 'trim', explode( "\n", (string) $row->content ) ) );
		foreach ( $lines as $line ) {
			$parts = array_map( 'trim', explode( '|', $line, 2 ) );
			if ( count( $parts ) === 2 && '' !== $parts[1] ) {
				$emergency[] = array(
					'label' => $parts[0],
					'phone' => $parts[1],
				);
			}
		}
		break;
	}
}
?>
<div class="esk-page-sections">
	<div class="esk-container">
		<?php if ( '' !== $flash ) : ?>
			<div class="esk-message-box esk-message-success" role="status">
				<strong><?php echo esc_html( (string) esk_site_ui( 'pages.contact_thanks', __( 'Thank you!', 'eskoofy' ) ) ); ?></strong>
				<p><?php echo esc_html( esk_site_ui( 'pages.contact_thanks_message', '' ) ); ?></p>
			</div>
		<?php endif; ?>

		<div class="esk-card-row">
			<?php if ( '' !== $phone ) : ?>
				<div class="esk-card">
					<h3 class="esk-card-title"><?php echo esc_html( esk_site_ui( 'pages.contact_phone_card', '' ) ); ?></h3>
					<p class="esk-card-text"><a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a></p>
				</div>
			<?php endif; ?>
			<?php if ( '' !== $email ) : ?>
				<div class="esk-card">
					<h3 class="esk-card-title"><?php echo esc_html( esk_site_ui( 'pages.contact_mail_card', '' ) ); ?></h3>
					<p class="esk-card-text"><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></p>
				</div>
			<?php endif; ?>
			<?php if ( '' !== $address ) : ?>
				<div class="esk-card">
					<h3 class="esk-card-title"><?php echo esc_html( esk_site_ui( 'pages.contact_address_card', '' ) ); ?></h3>
					<p class="esk-card-text"><?php echo esc_html( $address ); ?></p>
				</div>
			<?php endif; ?>
			<div class="esk-card">
				<h3 class="esk-card-title"><?php echo esc_html( esk_site_ui( 'pages.contact_hours_card', '' ) ); ?></h3>
				<p class="esk-card-text"><?php echo esc_html( esk_site_ui( 'pages.contact_hours_value', '' ) ); ?></p>
			</div>
		</div>

		<?php if ( ! empty( $emergency ) ) : ?>
			<div class="esk-message-box esk-message-error">
				<strong><?php echo esc_html( esk_site_ui( 'pages.contact_emergency', '' ) ); ?></strong>
				<ul class="esk-event-list" style="margin:0.5rem 0 0; padding-left:1.125rem;">
					<?php foreach ( $emergency as $row ) : ?>
						<li style="list-style: disc;">
							<strong><?php echo esc_html( $row['label'] ); ?></strong>
							<a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $row['phone'] ) ); ?>"><?php echo esc_html( $row['phone'] ); ?></a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<div class="esk-page-form" style="margin-top:2rem;">
			<h2 class="esk-card-title" style="font-size:1.25rem;"><?php echo esc_html( esk_site_ui( 'pages.contact_form_heading', '' ) ); ?></h2>
			<form method="post" action="<?php echo esc_url( home_url( '/contact/' ) ); ?>" style="max-width:36rem;">
				<?php esk_csrf_field( 'esk_contact_form' ); ?>
				<div class="esk-field">
					<label for="contact_name"><?php echo esc_html( esk_site_ui( 'pages.contact_name', '' ) ); ?> <span style="color:#ef4444;">*</span></label>
					<input type="text" id="contact_name" name="contact_name" required>
				</div>
				<div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;" class="esk-field">
					<div>
						<label for="contact_email"><?php echo esc_html( esk_site_ui( 'pages.contact_email', '' ) ); ?> <span style="color:#ef4444;">*</span></label>
						<input type="email" id="contact_email" name="contact_email" required>
					</div>
					<div>
						<label for="contact_phone"><?php echo esc_html( esk_site_ui( 'pages.contact_phone', '' ) ); ?></label>
						<input type="tel" id="contact_phone" name="contact_phone">
					</div>
				</div>
				<div class="esk-field">
					<label for="contact_subject"><?php echo esc_html( esk_site_ui( 'pages.contact_subject', '' ) ); ?> <span style="color:#ef4444;">*</span></label>
					<input type="text" id="contact_subject" name="contact_subject" required>
				</div>
				<div class="esk-field">
					<label for="contact_message"><?php echo esc_html( esk_site_ui( 'pages.contact_message', '' ) ); ?> <span style="color:#ef4444;">*</span></label>
					<textarea id="contact_message" name="contact_message" rows="5" required></textarea>
				</div>
				<button type="submit" name="esk_contact_submit" class="esk-btn"><?php echo esc_html( esk_site_ui( 'pages.contact_send', '' ) ); ?></button>
			</form>
		</div>

		<?php if ( (bool) esk_site_ui( 'pages.contact_faq', true ) ) : ?>
			<section class="esk-faq" style="margin-top:2rem;">
				<h2 class="esk-card-title" style="font-size:1.25rem; text-align:center;"><?php echo esc_html( esk_site_ui( 'pages.contact_faq', '' ) ); ?></h2>
				<details>
					<summary><?php echo esc_html( esk_site_ui( 'pages.contact_faq_hours', '' ) ); ?></summary>
					<p><?php echo esc_html( esk_site_ui( 'pages.contact_faq_hours_answer', '' ) ); ?></p>
				</details>
				<details>
					<summary><?php echo esc_html( esk_site_ui( 'pages.contact_faq_apply', '' ) ); ?></summary>
					<p><?php echo esc_html( esk_site_ui( 'pages.contact_faq_apply_answer', '' ) ); ?></p>
				</details>
				<details>
					<summary><?php echo esc_html( esk_site_ui( 'pages.contact_faq_docs', '' ) ); ?></summary>
					<p><?php echo esc_html( esk_site_ui( 'pages.contact_faq_docs_answer', '' ) ); ?></p>
				</details>
			</section>
		<?php endif; ?>
	</div>
</div>
<?php
get_template_part( 'template-parts/page-sections', null, array( 'page' => 'contact', 'fallback_option' => '' ) );
get_footer();