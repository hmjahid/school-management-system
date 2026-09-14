<?php
/**
 * Renders sections from esk_website_contents for a page.
 *
 * Expected variables: $page (string), $fallback_option (string, optional).
 *
 * @package Eskoofy
 */

declare(strict_types=1);

$args            = is_array( $args ) ? $args : array();
$page            = $args['page'] ?? '';
$fallback_option = $args['fallback_option'] ?? '';
?>
<div class="esk-page-sections">
	<div class="esk-container">
		<?php esk_render_page_content( $page, $fallback_option ); ?>
	</div>
</div>