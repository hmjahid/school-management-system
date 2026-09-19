<?php
/**
 * Custom search form — Eskoofy WordPress theme.
 *
 * @package Eskoofy
 */

declare(strict_types=1);
?>
<form role="search" method="get" class="eskoofy-search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="eskoofy-search-label" for="eskoofy-search-input">
		<?php esc_html_e( 'Search', 'eskoofy' ); ?>
	</label>
	<input
		type="search"
		id="eskoofy-search-input"
		class="eskoofy-search-input"
		placeholder="<?php esc_attr_e( 'Search&hellip;', 'eskoofy' ); ?>"
		value="<?php echo esc_attr( get_search_query() ); ?>"
		name="s"
	/>
	<button type="submit" class="eskoofy-search-submit">
		<?php esc_html_e( 'Search', 'eskoofy' ); ?>
	</button>
</form>
