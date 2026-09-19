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

<footer class="no-print border-t border-slate-200 bg-slate-900 text-slate-300">
	<div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
		<div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-4 lg:gap-8">
			<?php $footer_logo = esk_school( 'footer_logo_url' ) ?: esk_school( 'logo_url' ); ?>
			<div>
				<div class="flex items-center gap-3">
					<?php if ( $footer_logo ) : ?>
						<img src="<?php echo esc_url( $footer_logo ); ?>" alt="<?php echo esc_attr( $school_name ); ?>" class="h-10 w-10 rounded-lg object-contain ring-1 ring-white/10">
					<?php else : ?>
						<span class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-600 text-lg font-bold text-white"><?php echo esc_html( mb_substr( $school_name, 0, 1 ) ); ?></span>
					<?php endif; ?>
					<div>
						<p class="text-base font-bold text-white"><?php echo esc_html( $school_name ); ?></p>
						<p class="text-xs text-slate-400"><?php echo esc_html( (string) esk_site_ui( 'footer.tagline', __( 'Excellence in Education', 'eskoofy' ) ) ); ?></p>
					</div>
				</div>
				<p class="mt-4 text-sm leading-relaxed text-slate-400"><?php echo esc_html( $about_text ); ?></p>
				<?php if ( ! empty( $socials ) ) : ?>
					<div class="mt-5 flex gap-3">
						<?php foreach ( $socials as $social ) : ?>
							<a href="<?php echo esc_url( $social['url'] ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( $social['label'] ); ?>" class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-800 text-slate-300 ring-1 ring-slate-700 transition hover:bg-brand-600 hover:text-white">
								<?php echo $esc( $social['svg'] ); // phpcs:ignore ?>
							</a>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>

			<div>
				<h3 class="text-sm font-semibold uppercase tracking-wider text-white"><?php echo esc_html( (string) esk_site_ui( 'footer.quick_links_title', '' ) ); ?></h3>
				<ul class="mt-4 space-y-2.5">
					<?php foreach ( $quick_links as $key => $url ) : $label = esk_site_ui( 'footer.' . $key, '' ); if ( $label ) : ?>
						<li><a href="<?php echo esc_url( $url ); ?>" class="text-sm text-slate-400 transition-colors hover:text-white"><?php echo esc_html( (string) $label ); ?></a></li>
					<?php endif; endforeach; ?>
				</ul>
			</div>

			<div>
				<h3 class="text-sm font-semibold uppercase tracking-wider text-white"><?php echo esc_html( (string) esk_site_ui( 'footer.important_title', '' ) ); ?></h3>
				<ul class="mt-4 space-y-2.5">
					<?php if ( ! empty( $ministry_links ) ) : foreach ( $ministry_links as $entry ) : $parts = explode( '|', (string) $entry ); if ( count( $parts ) < 2 || ! $parts[0] ) { continue; } ?>
						<li><a href="<?php echo esc_url( $parts[1] ); ?>" target="_blank" rel="noopener noreferrer" class="text-sm text-slate-400 transition-colors hover:text-white"><?php echo esc_html( $parts[0] ); ?></a></li>
					<?php endforeach; else : ?>
						<li><a href="https://www.moedu.gov.bd" target="_blank" rel="noopener noreferrer" class="text-sm text-slate-400 transition-colors hover:text-white"><?php echo esc_html( (string) esk_site_ui( 'footer.link_ministry_education_ministry', '' ) ); ?></a></li>
						<li><a href="<?php echo esc_url( home_url( '/transport/' ) ); ?>" class="text-sm text-slate-400 transition-colors hover:text-white"><?php echo esc_html( (string) esk_site_ui( 'nav.transport', '' ) ); ?></a></li>
					<?php endif; ?>
				</ul>
			</div>

			<div>
				<h3 class="text-sm font-semibold uppercase tracking-wider text-white"><?php echo esc_html( (string) esk_site_ui( 'footer.contact_title', '' ) ); ?></h3>
				<ul class="mt-4 space-y-3">
					<?php foreach ( $contact_rows as $key => $value ) : ?>
						<li class="flex items-center gap-2">
							<?php if ( 'phone' === $key ) : ?>
								<a href="<?php echo esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', $value ) ); ?>" class="text-sm text-slate-400 hover:text-white"><?php echo esc_html( $value ); ?></a>
							<?php elseif ( 'email' === $key ) : ?>
								<a href="<?php echo esc_url( 'mailto:' . $value ); ?>" class="text-sm text-slate-400 hover:text-white"><?php echo esc_html( $value ); ?></a>
							<?php else : ?>
								<span class="text-sm text-slate-400"><?php echo esc_html( $value ); ?></span>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>

				<div class="mt-6">
					<h4 class="text-sm font-semibold text-white"><?php echo esc_html( (string) esk_site_ui( 'footer.newsletter_title', '' ) ); ?></h4>
					<form action="<?php echo esc_url( home_url( '/' ) ); ?>" method="post" class="mt-2 flex gap-2" novalidate>
						<label class="screen-reader-text" for="esk-newsletter-email"><?php echo esc_html( (string) esk_site_ui( 'footer.newsletter_email_label', '' ) ); ?></label>
						<input id="esk-newsletter-email" type="email" name="esk_newsletter" required placeholder="<?php echo esc_attr( (string) esk_site_ui( 'footer.newsletter_placeholder', '' ) ); ?>" aria-label="<?php echo esc_attr( (string) esk_site_ui( 'footer.newsletter_email_label', '' ) ); ?>" class="min-w-0 flex-1 rounded-lg border border-slate-600 bg-slate-800 px-3 py-2 text-sm text-white placeholder:text-slate-500 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
						<button type="submit" class="rounded-lg bg-brand-600 px-3 py-2 text-sm font-semibold text-white hover:bg-brand-700" aria-label="<?php echo esc_attr( (string) esk_site_ui( 'footer.newsletter_button', '' ) ); ?>">
							<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
						</button>
					</form>
				</div>
			</div>
		</div>
	</div>

	<div class="border-t border-slate-800">
		<div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-4 py-4 sm:flex-row sm:px-6 lg:px-8">
			<p class="text-xs text-slate-500">&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( $school_name ); ?>. <?php echo esc_html( (string) esk_site_ui( 'footer.copyright_suffix', '' ) ); ?></p>
			<div class="flex gap-4">
				<a href="<?php echo esc_url( home_url( '/privacy/' ) ); ?>" class="text-xs text-slate-500 transition-colors hover:text-slate-300"><?php echo esc_html( (string) esk_site_ui( 'footer.link_privacy', '' ) ); ?></a>
				<a href="<?php echo esc_url( home_url( '/terms/' ) ); ?>" class="text-xs text-slate-500 transition-colors hover:text-slate-300"><?php echo esc_html( (string) esk_site_ui( 'footer.link_terms', '' ) ); ?></a>
				<a href="<?php echo esc_url( home_url( '/sitemap.xml' ) ); ?>" class="text-xs text-slate-500 transition-colors hover:text-slate-300"><?php echo esc_html( (string) esk_site_ui( 'footer.link_sitemap', '' ) ); ?></a>
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