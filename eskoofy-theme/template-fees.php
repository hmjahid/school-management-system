<?php
/**
 * Template Name: Fees Payment
 *
 * @package Eskoofy
 */

declare(strict_types=1);

get_header();
?>
<div class="eskoofy-container">
	<?php echo do_shortcode( '[eskoofy_fees_payment]' ); ?>
</div>
<?php
get_footer();
