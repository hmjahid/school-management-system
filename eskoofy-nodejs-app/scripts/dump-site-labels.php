<?php
declare(strict_types=1);

/**
 * Dump `eskoofy-laravel-app/lang/{en,bn}/site_frontend.php` as JSON so the Node
 * product can regenerate `lib/site-labels.ts` without hand-copying 1400 lines.
 *
 * Usage (from eskoofy-nodejs-app/):
 *   php scripts/dump-site-labels.php > /tmp/labels.json
 *   node scripts/build-site-labels.mjs /tmp/labels.json > lib/site-labels.ts
 *
 * The two files are the contract: eskoofy-laravel-app is the reference app, and
 * the Node clone must offer exactly the same Global Labels editor tree.
 */

$appRoot = dirname(__DIR__, 2) . '/eskoofy-laravel-app';

if (! is_dir($appRoot)) {
    fwrite(STDERR, "Cannot find eskoofy-laravel-app at {$appRoot}\n");
    exit(1);
}

$out = [];

foreach (['en', 'bn'] as $locale) {
    $file = $appRoot . '/lang/' . $locale . '/site_frontend.php';

    if (! is_file($file)) {
        fwrite(STDERR, "Missing lang file: {$file}\n");
        exit(1);
    }

    $data = require $file;

    if (! is_array($data)) {
        fwrite(STDERR, "lang/{$locale}/site_frontend.php did not return an array\n");
        exit(1);
    }

    $out[$locale] = $data;
}

echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);