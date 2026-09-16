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

$socials = esk_social_profiles();

$social_svgs = esk_social_icons();

$quick_links = array(
	'link_about_school' => home_url( '/about/' ),
	'link_academics'    => home_url( '/academics/' ),
	'link_admissions'   => home_url( '/admission/' ),
	'link_faculty'      => home_url( '/faculty/' ),
	'link_committee'    => home_url( '/committee/' ),
	'link_news'         => home_url( '/news/' ),
	'link_gallery'      => home_url( '/gallery/' ),
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

<footer id="colophon" class="esk-footer no-print" role="contentinfo">
	<div class="esk-container">
		<div class="esk-footer-grid">
			<div class="esk-footer-col esk-footer-about">
				<h3 class="esk-footer-head"><?php echo esc_html( (string) esk_site_ui( 'footer.about_title', '' ) ); ?></h3>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="esk-brand">
					<?php
					$footer_logo = esk_school( 'footer_logo_url' ) ?: esk_school( 'logo_url' );
					if ( $footer_logo ) :
						?>
						<img class="esk-brand-logo-img" src="<?php echo esc_url( $footer_logo ); ?>" alt="<?php echo esc_attr( $school_name ); ?>">
					<?php else : ?>
						<span class="esk-brand-logo" aria-hidden="true"><?php echo esc_html( esk_initials( $school_name ) ); ?></span>
					<?php endif; ?>
					<span class="esk-brand-name"><?php echo esc_html( $school_name ); ?></span>
				</a>
				<p><?php echo esc_html( $about_text ); ?></p>
				<?php if ( ! empty( $socials ) ) : ?>
					<div class="esk-footer-social">
						<?php foreach ( $socials as $social ) : ?>
							<a href="<?php echo esc_url( $social['url'] ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( $social['label'] ); ?>">
								<?php echo $esc( $social['svg'] ); // phpcs:ignore ?>
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
				<?php if ( ! empty( $ministry_links ) ) : ?>
					<?php foreach ( $ministry_links as $entry ) : ?>
						<?php
						$parts = explode( '|', (string) $entry );
						if ( count( $parts ) < 2 || ! $parts[0] ) {
							continue;
						}
						?>
						<li><a href="<?php echo esc_url( $parts[1] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $parts[0] ); ?></a></li>
					<?php endforeach; ?>
				<?php else : ?>
					<li><a href="https://www.moedu.gov.bd" target="_blank" rel="noopener noreferrer"><?php echo esc_html( (string) esk_site_ui( 'footer.link_ministry_education_ministry', '' ) ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/transport/' ) ); ?>"><?php echo esc_html( (string) esk_site_ui( 'nav.transport', '' ) ); ?></a></li>
				<?php endif; ?>
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

<?php
$esk_flash_success = esk_get_flash( 'success' );
$esk_flash_error   = esk_get_flash( 'error' );
$esk_flash_info    = esk_get_flash( 'notice' );
?>
<?php if ( '' !== $esk_flash_success || '' !== $esk_flash_error || '' !== $esk_flash_info ) : ?>
	<div data-esk-flash-toast data-message="<?php echo esc_attr( '' !== $esk_flash_success ? $esk_flash_success : ( '' !== $esk_flash_error ? $esk_flash_error : $esk_flash_info ) ); ?>" data-type="<?php echo esc_attr( '' !== $esk_flash_success ? 'success' : ( '' !== $esk_flash_error ? 'error' : 'info' ) ); ?>"></div>
<?php endif; ?>

<div class="esk-toast-root" id="esk-toast-root" aria-live="polite" aria-atomic="true"></div>

<div class="esk-confirm-modal" id="esk-confirm-modal" aria-hidden="true">
	<div class="esk-confirm-backdrop" data-confirm-backdrop></div>
	<div class="esk-confirm-panel" role="alertdialog" aria-labelledby="esk-confirm-title" aria-describedby="esk-confirm-message">
		<h3 id="esk-confirm-title" data-confirm-title><?php esc_html_e( 'Are you sure?', 'eskoofy' ); ?></h3>
		<p id="esk-confirm-message" data-confirm-message><?php esc_html_e( 'This action cannot be undone.', 'eskoofy' ); ?></p>
		<div class="esk-confirm-actions">
			<button type="button" class="esk-btn esk-btn-plain" data-confirm-cancel><?php esc_html_e( 'Cancel', 'eskoofy' ); ?></button>
			<button type="button" class="esk-btn esk-btn-accent" data-confirm-ok><?php esc_html_e( 'Confirm', 'eskoofy' ); ?></button>
		</div>
	</div>
</div>

<?php if ( is_user_logged_in() ) : ?>
	<script>
		window.eskAdmin = window.eskAdmin || <?php echo wp_json_encode(
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'esk_ajax_nonce' ),
				'restNonce' => wp_create_nonce( 'wp_rest' ),
			)
		); ?>;
	</script>
<?php endif; ?>

<?php wp_footer(); ?>
</body>
</html>