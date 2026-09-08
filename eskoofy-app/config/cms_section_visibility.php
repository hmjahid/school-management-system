<?php

/**
 * Per-page section visibility keys for the public website.
 *
 * Each CMS page edit screen renders a "Section visibility" accordion with a
 * show/hide checkbox per key. Values are persisted in
 * `WebsiteSetting.section_visibility` (a flat [key => bool] map already
 * consumed by the public site views via `$siteSettings->section_visibility`).
 *
 * Key prefixes intentionally match what the site views read
 * (e.g. `contact_form`, `adm_hero`, `faculty_grid`).
 */

return [
    'home' => [
        'hero' => 'Hero banner',
        'features' => 'Features',
        'stats' => 'Stats counter',
        'principal' => "Principal's message",
        'teachers' => 'Teachers section',
        'committee_members' => 'Managing Committee',
        'testimonials' => 'Testimonials',
        'remarkable_students' => 'Remarkable students',
        'slider' => 'Photo slider',
        'events' => 'Upcoming events',
        'news' => 'Latest news',
        'highlights' => 'Highlights',
        'cta' => 'CTA banner',
        'partners' => 'Partners strip',
        'admissions_bar' => 'Admissions top bar',
        'urgent_notices' => 'Urgent notices (hero)',
    ],
    'admissions' => [
        'adm_hero' => 'Hero banner',
        'adm_process' => 'Admission process',
        'adm_fee' => 'Fee structure',
        'adm_prospectus' => 'Download prospectus',
        'adm_faq' => 'FAQs',
        'adm_cta' => 'CTA banner',
        'adm_scholarship' => 'Scholarship form',
    ],
    'contact' => [
        'contact_hero' => 'Hero banner',
        'contact_cards' => 'Contact cards',
        'contact_form' => 'Contact form',
        'contact_hours' => 'Opening hours',
        'contact_emergency' => 'Emergency contacts',
        'contact_map' => 'Map',
        'contact_faq' => 'FAQs',
    ],
    'faculty' => [
        'faculty_hero' => 'Hero banner',
        'faculty_search' => 'Search & filter',
        'faculty_grid' => 'Faculty grid',
    ],
    'gallery' => [
        'gallery_hero' => 'Hero banner',
        'gallery_tabs' => 'Category tabs',
        'gallery_grid' => 'Gallery grid',
    ],
    'news' => [
        'news_hero' => 'Hero banner',
        'news_featured' => 'Featured article',
        'news_grid' => 'News grid',
    ],
    'payments' => [
        'payments_hero' => 'Hero banner',
        'payments_fee' => 'Fee table',
        'payments_gateways' => 'Payment gateways',
    ],
    'about' => [
        'page_hero' => 'Hero banner',
        'page_content' => 'Page content',
    ],
    'events' => [
        'events_hero' => 'Hero banner',
        'events_filters' => 'Filters & view toggle',
        'events_upcoming' => 'Upcoming events',
        'events_past' => 'Past events',
    ],
    'notices' => [
        'notices_hero' => 'Hero banner',
        'notices_list' => 'Notices list',
    ],
    'results' => [
        'results_hero' => 'Hero banner',
        'results_form' => 'Search form',
    ],
    'routines' => [
        'routines_hero' => 'Hero banner',
        'routines_filter' => 'Filter form',
        'routines_grid' => 'Routine grid',
    ],
    'transport' => [
        'transport_hero' => 'Hero banner',
        'transport_routes' => 'Route cards',
        'transport_fleet' => 'Fleet section',
        'transport_map' => 'Route map',
    ],
];
