<?php
/**
 * Template Name: Results Page
 *
 * @package Eskoofy
 */

declare(strict_types=1);

get_header();
?>
<div class="eskoofy-container">
	<?php echo do_shortcode( '[eskoofy_results_lookup]' ); ?>
</div>
<?php
get_footer();
