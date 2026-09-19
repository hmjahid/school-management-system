<?php
/**
 * 404 error page — Eskoofy WordPress theme.
 *
 * @package Eskoofy
 */

declare(strict_types=1);

get_header();

get_template_part(
	'template-parts/inner-hero',
	null,
	array(
		'title'    => (string) esk_site_ui( 'error404.title', __( 'Page not found', 'eskoofy' ) ),
		'subtitle' => (string) esk_site_ui( 'error404.text', __( 'The page you are looking for might have been moved or does not exist.', 'eskoofy' ) ),
	)
);
?>
<div class="esk-page-sections">
	<div class="esk-container" style="text-align:center; padding:3rem 0;">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="esk-btn">
			<?php echo esc_html( (string) esk_site_ui( 'error404.back_home', __( 'Back to home', 'eskoofy' ) ) ); ?>
		</a>
	</div>
</div>
<?php
get_footer();