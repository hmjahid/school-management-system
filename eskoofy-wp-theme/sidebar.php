<?php
/**
 * Sidebar template — Eskoofy WordPress theme.
 *
 * @package Eskoofy
 */

declare(strict_types=1);

if ( ! is_active_sidebar( 'sidebar-1' ) ) {
	return;
}
?>

<aside class="eskoofy-sidebar" role="complementary" aria-label="<?php esc_attr_e( 'Sidebar', 'eskoofy' ); ?>">
	<?php dynamic_sidebar( 'sidebar-1' ); ?>
</aside>
