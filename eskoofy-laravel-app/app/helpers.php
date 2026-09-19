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

if (! function_exists('dashboard_help_section_for_route')) {
    /**
     * Map the current dashboard route to the most relevant help.sections key.
     */
    function dashboard_help_section_for_route(?string $routeName): string
    {
        $map = [
            'attendance' => ['dashboard.attendance', 'dashboard.staff-attendance'],
            'managing_students' => ['dashboard.students', 'dashboard.bulk', 'dashboard.teachers'],
            'exams_results' => ['dashboard.exams', 'dashboard.results', 'dashboard.admit', 'dashboard.seat', 'dashboard.assignments'],
            'fees_payments' => ['dashboard.fees', 'dashboard.payments', 'dashboard.expenses', 'dashboard.budget', 'dashboard.bank', 'dashboard.reconciliation'],
            'cms_management' => ['dashboard.cms', 'dashboard.pages', 'dashboard.website', 'dashboard.gallery', 'dashboard.announcements', 'dashboard.events', 'dashboard.documents', 'dashboard.communications', 'dashboard.messages'],
            'public_website' => ['dashboard.help', 'dashboard.backups', 'dashboard.activity', 'dashboard.settings'],
        ];

        $needle = $routeName ?? '';
        foreach ($map as $section => $prefixes) {
            foreach ($prefixes as $prefix) {
                if ($needle === $prefix || str_starts_with($needle, $prefix.'.')) {
                    return $section;
                }
            }
        }

        if ($needle === 'dashboard') {
            return 'getting_started';
        }

        return 'getting_started';
    }
}
