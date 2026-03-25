<?php

use Illuminate\Support\Arr;

if (! function_exists('site_ui')) {
    /**
     * Public site copy from merged config + CMS (site-ui). Keys use dot notation, e.g. nav.home
     */
    function site_ui(string $key, mixed $default = null): mixed
    {
        return Arr::get(\App\Support\SiteFrontend::merged(), $key, $default);
    }
}
