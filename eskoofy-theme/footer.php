<?php
/**
 * Footer template — Eskoofy WordPress theme.
 *
 * @package Eskoofy
 */

declare(strict_types=1);
?>
</main>

<footer class="eskoofy-site-footer" role="contentinfo">
	<div class="eskoofy-container">
		<?php
		if ( has_nav_menu( 'footer' ) ) {
			wp_nav_menu(
				array(
					'theme_location' => 'footer',
					'container'      => false,
					'menu_class'     => 'eskoofy-menu eskoofy-footer-menu',
					'fallback_cb'    => false,
				)
			);
		}
		?>
		<p class="eskoofy-copyright">
			&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?>
			<?php bloginfo( 'name' ); ?>.
			<?php esc_html_e( 'All rights reserved.', 'eskoofy' ); ?>
		</p>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
