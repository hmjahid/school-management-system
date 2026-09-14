<?php
/**
 * Footer template — Eskoofy WordPress theme.
 *
 * About + social, quick links, important/ministry links, contact block and
 * a newsletter subscribe form, then the bottom bar. Mirrors the Laravel
 * app's public footer.
 *
 * @package Eskoofy
 */

declare(strict_types=1);

$school_name = esk_school( 'school_name' ) ?: get_bloginfo( 'name' );
$is_bn       = 'bn_BD' === get_option( 'esk_locale', 'en' ) || 0 === strpos( get_locale(), 'bn' );

$about_text = esk_school( 'school_about' ) ?: (string) esk_site_ui( 'footer.about_fallback', '' );

$socials = array(
	'facebook' => esk_school( 'social_facebook' ),
	'twitter'  => esk_school( 'social_twitter' ),
	'youtube'  => esk_school( 'social_youtube' ),
	'instagram' => esk_school( 'social_instagram' ),
);
$socials = array_filter( $socials );

$social_svgs = array(
	'facebook' => '<svg class="esk-icon" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M13 22v-8h2.7l.4-3H13V9.2c0-.9.3-1.5 1.6-1.5H16V5c-.3 0-1.2-.1-2.3-.1-2.3 0-3.9 1.4-3.9 4v2H7v3h2.8v8H13z"/></svg>',
	'twitter'  => '<svg class="esk-icon" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M18.2 2h3.3l-7.3 8.3L22.8 22h-6.7l-5.3-6.9L4.8 22H1.5l7.8-8.9L1.5 2h6.9l4.8 6.3L18.2 2zm-1.2 18h1.9L6.9 3.8H4.9L17 20z"/></svg>',
	'youtube'  => '<svg class="esk-icon" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M23 12s0-3.9-.5-5.6c-.3-1-1-1.8-2-2C18.8 4 12 4 12 4s-6.8 0-8.5.4c-1 .2-1.7 1-2 2C1 8.1 1 12 1 12s0 3.9.5 5.6c.3 1 1 1.8 2 2 1.7.4 8.5.4 8.5.4s6.8 0 8.5-.4c1-.2 1.7-1 2-2 .5-1.7.5-5.6.5-5.6zM10 15.5v-7l6 3.5-6 3.5z"/></svg>',
	'instagram' => '<svg class="esk-icon" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2c2.7 0 3 0 4.1.1 1.1.1 1.8.2 2.4.4.7.3 1.2.6 1.7 1.1.5.5.9 1 1.1 1.7.2.6.4 1.3.4 2.4.1 1.1.1 1.3.1 4.1s0 3-.1 4.1c-.1 1.1-.2 1.8-.4 2.4-.3.7-.6 1.2-1.1 1.7-.5.5-1 .9-1.7 1.1-.6.2-1.3.4-2.4.4-1.1.1-1.3.1-4.1.1s-3 0-4.1-.1c-1.1-.1-1.8-.2-2.4-.4-.7-.3-1.2-.6-1.7-1.1-.5-.5-.9-1-1.1-1.7-.2-.6-.4-1.3-.4-2.4-.1-1.1-.1-1.3-.1-4.1s0-3 .1-4.1c.1-1.1.2-1.8.4-2.4.3-.7.6-1.2 1.1-1.7.5-.5 1-.9 1.7-1.1.6-.2 1.3-.4 2.4-.4C9 2 9.3 2 12 2zm0 3.6a6.4 6.4 0 100 12.8A6.4 6.4 0 0012 5.6zm0 10.6a4.2 4.2 0 110-8.4 4.2 4.2 0 010 8.4zM19.6 5.2a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/></svg>',
);

$quick_links = array(
	'link_about_school'  => home_url( '/about/' ),
	'link_academics'     => home_url( '/academics/' ),
	'link_admissions'    => home_url( '/admission/' ),
	'link_apply_online'  => home_url( '/admission/' ),
	'link_news'          => home_url( '/news/' ),
	'link_gallery'       => home_url( '/gallery/' ),
	'link_contact'       => home_url( '/contact/' ),
	'link_payments'      => home_url( '/fees/' ),
	'link_routine'       => home_url( '/routine/' ),
	'link_certificates'  => home_url( '/certificates/' ),
);

$ministry_links = (array) esk_site_ui( 'footer.ministry_links', array() );

$contact_rows = array_filter(
	array(
		'phone'   => (string) esk_school( 'school_phone' ),
		'email'   => (string) esk_school( 'school_email' ),
		'address' => (string) esk_school( 'school_address' ),
	)
);

$esc = static fn( string $svg ): string => wp_kses_post( $svg );
?>
</main>

<footer id="colophon" class="esk-footer" role="contentinfo">
	<div class="esk-container">
		<div class="esk-footer-grid">
			<div class="esk-footer-col esk-footer-about">
				<h3 class="esk-footer-head"><?php echo esc_html( (string) esk_site_ui( 'footer.about_title', '' ) ); ?></h3>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="esk-brand">
					<span class="esk-brand-logo" aria-hidden="true"><?php echo esc_html( esk_initials( $school_name ) ); ?></span>
					<span class="esk-brand-name"><?php echo esc_html( $school_name ); ?></span>
				</a>
				<p><?php echo esc_html( $about_text ); ?></p>
				<?php if ( ! empty( $socials ) ) : ?>
					<div class="esk-footer-social">
						<?php foreach ( $socials as $key => $url ) : ?>
							<a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( ucfirst( $key ) ); ?>">
								<?php echo $esc( $social_svgs[ $key ] ?? $social_svgs['facebook'] ); // phpcs:ignore ?>
							</a>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>

			<div class="esk-footer-col">
				<h3 class="esk-footer-head"><?php echo esc_html( (string) esk_site_ui( 'footer.quick_links_title', '' ) ); ?></h3>
				<ul class="esk-footer-links">
					<?php foreach ( $quick_links as $key => $url ) : ?>
						<?php $label = esk_site_ui( 'footer.' . $key, '' ); ?>
						<?php if ( $label ) : ?>
							<li><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( (string) $label ); ?></a></li>
						<?php endif; ?>
					<?php endforeach; ?>
				</ul>
			</div>

			<div class="esk-footer-col">
				<h3 class="esk-footer-head"><?php echo esc_html( (string) esk_site_ui( 'footer.important_title', '' ) ); ?></h3>
				<ul class="esk-footer-links">
					<?php foreach ( $ministry_links as $entry ) : ?>
						<?php
						$parts = explode( '|', (string) $entry );
						if ( count( $parts ) < 2 || ! $parts[0] ) {
							continue;
						}
						?>
						<li><a href="<?php echo esc_url( $parts[1] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $parts[0] ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</div>

			<div class="esk-footer-col">
				<h3 class="esk-footer-head"><?php echo esc_html( (string) esk_site_ui( 'footer.contact_title', '' ) ); ?></h3>
				<ul class="esk-footer-links esk-footer-contact">
					<?php foreach ( $contact_rows as $key => $value ) : ?>
						<li>
							<?php
							if ( 'phone' === $key ) {
								echo $esc( '<svg class="esk-icon" fill="currentColor" viewBox="0 0 20 20"><path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/></svg>' ); // phpcs:ignore
							} elseif ( 'email' === $key ) {
								echo $esc( '<svg class="esk-icon" fill="currentColor" viewBox="0 0 20 20"><path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/><path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/></svg>' ); // phpcs:ignore
							} else {
								echo $esc( '<svg class="esk-icon" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a5 5 0 00-5 5v2a2 2 0 00-2 2v5a2 2 0 002 2h10a2 2 0 002-2v-5a2 2 0 00-2-2V7a5 5 0 00-5-5zm3 7V7a3 3 0 00-6 0v2h6z"/></svg>' ); // phpcs:ignore
							}
							?>
							<?php if ( 'email' === $key ) : ?>
								<a href="<?php echo esc_url( 'mailto:' . $value ); ?>"><?php echo esc_html( $value ); ?></a>
							<?php elseif ( 'phone' === $key ) : ?>
								<a href="<?php echo esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', $value ) ); ?>"><?php echo esc_html( $value ); ?></a>
							<?php else : ?>
								<span><?php echo esc_html( $value ); ?></span>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>

				<h3 class="esk-footer-head esk-footer-newsletter-head"><?php echo esc_html( (string) esk_site_ui( 'footer.newsletter_title', '' ) ); ?></h3>
				<p><?php echo esc_html( (string) esk_site_ui( 'footer.newsletter_intro', '' ) ); ?></p>
				<form class="esk-newsletter-form" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="post" novalidate>
					<label class="screen-reader-text" for="esk-newsletter-email"><?php echo esc_html( (string) esk_site_ui( 'footer.newsletter_email_label', '' ) ); ?></label>
					<input id="esk-newsletter-email" type="email" name="esk_newsletter" placeholder="<?php echo esc_attr( (string) esk_site_ui( 'footer.newsletter_placeholder', '' ) ); ?>" required>
					<button type="submit"><?php echo esc_html( (string) esk_site_ui( 'footer.newsletter_button', '' ) ); ?></button>
				</form>
				<?php
				$newsletter_msg = esk_get_flash( 'success' );
				if ( '' !== $newsletter_msg ) {
					echo '<p class="esk-newsletter-msg">' . esc_html( $newsletter_msg ) . '</p>';
				}
				$newsletter_err = esk_get_flash( 'error' );
				if ( '' !== $newsletter_err ) {
					echo '<p class="esk-newsletter-msg esk-newsletter-msg-error">' . esc_html( $newsletter_err ) . '</p>';
				}
				?>
			</div>
		</div>

		<div class="esk-footer-bottom">
			<p class="esk-copyright">
				&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( $school_name ); ?>.
				<?php echo esc_html( (string) esk_site_ui( 'footer.copyright_suffix', '' ) ); ?>
			</p>
			<div class="esk-footer-bottom-links">
				<a href="<?php echo esc_url( home_url( '/privacy/' ) ); ?>"><?php echo esc_html( (string) esk_site_ui( 'footer.link_privacy', '' ) ); ?></a>
				<a href="<?php echo esc_url( home_url( '/terms/' ) ); ?>"><?php echo esc_html( (string) esk_site_ui( 'footer.link_terms', '' ) ); ?></a>
				<a href="<?php echo esc_url( home_url( '/sitemap.xml' ) ); ?>"><?php echo esc_html( (string) esk_site_ui( 'footer.link_sitemap', '' ) ); ?></a>
			</div>
		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>