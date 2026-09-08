<?php

declare(strict_types=1);

/**
 * Eskoofy is not meant to be used without a page builder or the bundled
 * templates. We intentionally keep this placeholder minimal so the theme
 * activates and is translatable, without pretending to be a full site.
 */

if (! function_exists('eskoofy_theme_setup')) {
    function eskoofy_theme_setup(): void
    {
        load_theme_textdomain('eskoofy', get_template_directory().'/languages');
    }
}
add_action('after_setup_theme', 'eskoofy_theme_setup');