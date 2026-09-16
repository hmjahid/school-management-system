<?php
/**
 * Template Name: Contact
 *
 * Hero, contact info cards, Google Maps embed, opening hours, emergency
 * contacts, native contact form (with honeypot), and FAQ accordion.
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

$map_embed_url = (string) esk_school( 'google_maps_embed_url', '' );
if ( '' === $map_embed_url && '' !== $address ) {
	$map_embed_url = 'https://maps.google.com/maps?q=' . rawurlencode( $address ) . '&z=15&output=embed';
}

$opening_hours = array(
	esc_html__( 'Sunday', 'eskoofy' )     => '8:00 AM – 4:00 PM',
	esc_html__( 'Monday', 'eskoofy' )     => '8:00 AM – 4:00 PM',
	esc_html__( 'Tuesday', 'eskoofy' )    => '8:00 AM – 4:00 PM',
	esc_html__( 'Wednesday', 'eskoofy' )  => '8:00 AM – 4:00 PM',
	esc_html__( 'Thursday', 'eskoofy' )   => '8:00 AM – 4:00 PM',
	esc_html__( 'Friday', 'eskoofy' )     => esc_html__( 'Closed', 'eskoofy' ),
	esc_html__( 'Saturday', 'eskoofy' )   => esc_html__( 'Closed', 'eskoofy' ),
);
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
					<h3 class="esk-card-title"><?php echo esc_html( esk_site_ui( 'pages.contact_phone_card', __( 'Phone', 'eskoofy' ) ) ); ?></h3>
					<p class="esk-card-text"><a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a></p>
				</div>
			<?php endif; ?>
			<?php if ( '' !== $email ) : ?>
				<div class="esk-card">
					<h3 class="esk-card-title"><?php echo esc_html( esk_site_ui( 'pages.contact_mail_card', __( 'Email', 'eskoofy' ) ) ); ?></h3>
					<p class="esk-card-text"><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></p>
				</div>
			<?php endif; ?>
			<?php if ( '' !== $address ) : ?>
				<div class="esk-card">
					<h3 class="esk-card-title"><?php echo esc_html( esk_site_ui( 'pages.contact_address_card', __( 'Address', 'eskoofy' ) ) ); ?></h3>
					<p class="esk-card-text"><?php echo esc_html( $address ); ?></p>
				</div>
			<?php endif; ?>
			<div class="esk-card">
				<h3 class="esk-card-title"><?php echo esc_html( esk_site_ui( 'pages.contact_hours_card', __( 'Office hours', 'eskoofy' ) ) ); ?></h3>
				<p class="esk-card-text"><?php echo esc_html( esk_site_ui( 'pages.contact_hours_value', '' ) ); ?></p>
			</div>
		</div>

		<div style="display:grid; gap:2rem; margin-top:2rem;" class="esk-contact-split">
			<div style="display:grid; gap:2rem; grid-template-columns:1.2fr 1fr;">
				<div class="esk-page-form">
					<h2 class="esk-card-title" style="font-size:1.25rem;"><?php echo esc_html( esk_site_ui( 'pages.contact_form_heading', __( 'Send us a message', 'eskoofy' ) ) ); ?></h2>
					<form method="post" action="<?php echo esc_url( home_url( '/contact/' ) ); ?>" style="max-width:36rem;" novalidate>
						<?php esk_csrf_field( 'esk_contact_form' ); ?>
						<div style="position:absolute; left:-9999px; top:-9999px;" aria-hidden="true">
							<label for="contact_website">Website</label>
							<input type="text" id="contact_website" name="contact_website" tabindex="-1" autocomplete="off" value="">
						</div>
						<div class="esk-field">
							<label for="contact_name"><?php echo esc_html( esk_site_ui( 'pages.contact_name', __( 'Name', 'eskoofy' ) ) ); ?> <span style="color:#ef4444;">*</span></label>
							<input type="text" id="contact_name" name="contact_name" required>
						</div>
						<div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;" class="esk-field">
							<div>
								<label for="contact_email"><?php echo esc_html( esk_site_ui( 'pages.contact_email', __( 'Email', 'eskoofy' ) ) ); ?> <span style="color:#ef4444;">*</span></label>
								<input type="email" id="contact_email" name="contact_email" required>
							</div>
							<div>
								<label for="contact_phone"><?php echo esc_html( esk_site_ui( 'pages.contact_phone', __( 'Phone', 'eskoofy' ) ) ); ?></label>
								<input type="tel" id="contact_phone" name="contact_phone">
							</div>
						</div>
						<div class="esk-field">
							<label for="contact_subject"><?php echo esc_html( esk_site_ui( 'pages.contact_subject', __( 'Subject', 'eskoofy' ) ) ); ?> <span style="color:#ef4444;">*</span></label>
							<input type="text" id="contact_subject" name="contact_subject" required>
						</div>
						<div class="esk-field">
							<label for="contact_message"><?php echo esc_html( esk_site_ui( 'pages.contact_message', __( 'Message', 'eskoofy' ) ) ); ?> <span style="color:#ef4444;">*</span></label>
							<textarea id="contact_message" name="contact_message" rows="5" required></textarea>
						</div>
						<button type="submit" name="esk_contact_submit" class="esk-btn"><?php echo esc_html( esk_site_ui( 'pages.contact_send', __( 'Send message', 'eskoofy' ) ) ); ?></button>
					</form>
				</div>

				<div style="display:grid; align-content:start; gap:1.5rem;">
					<div class="esk-card">
						<h3 class="esk-card-title"><?php echo esc_html( esk_site_ui( 'pages.contact_hours_card', __( 'Opening hours', 'eskoofy' ) ) ); ?></h3>
						<ul style="list-style:none; margin:0; padding:0;">
							<?php foreach ( $opening_hours as $day => $hours ) : ?>
								<li style="display:flex; justify-content:space-between; padding:0.35rem 0; font-size:0.9rem; color:var(--esk-muted); border-bottom:1px solid var(--esk-border);">
									<span style="font-weight:600; color:var(--esk-ink); text-transform:capitalize;"><?php echo esc_html( $day ); ?></span>
									<span><?php echo esc_html( $hours ); ?></span>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>

					<?php if ( '' !== $map_embed_url ) : ?>
						<div class="esk-card" style="overflow:hidden; padding:0;">
							<iframe
								src="<?php echo esc_url( $map_embed_url ); ?>"
								title="<?php echo esc_attr( esk_site_ui( 'pages.contact_address_card', __( 'Location on map', 'eskoofy' ) ) ); ?>"
								style="width:100%; height:16rem; border:0; display:block;"
								loading="lazy"
								allowfullscreen
								referrerpolicy="no-referrer-when-downgrade"></iframe>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<?php if ( ! empty( $emergency ) ) : ?>
			<div class="esk-message-box esk-message-error" style="margin-top:2rem;">
				<strong><?php echo esc_html( esk_site_ui( 'pages.contact_emergency', __( 'Emergency contacts', 'eskoofy' ) ) ); ?></strong>
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

		<?php if ( (bool) esk_site_ui( 'pages.contact_faq', true ) ) : ?>
			<section class="esk-faq" style="margin-top:2.5rem; max-width:48rem; margin-left:auto; margin-right:auto;">
				<h2 class="esk-card-title" style="font-size:1.25rem; text-align:center;"><?php echo esc_html( esk_site_ui( 'pages.contact_faq', __( 'Frequently Asked Questions', 'eskoofy' ) ) ); ?></h2>
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