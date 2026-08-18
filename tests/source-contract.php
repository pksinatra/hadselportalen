<?php

declare(strict_types=1);

$root = dirname(__DIR__) . '/wp-content/plugins/lokalportalen-core';
$required = array(
    'lokalportalen-core.php',
    'includes/class-lp-post-types.php',
    'includes/class-lp-meta-boxes.php',
    'includes/class-lp-importer.php',
    'includes/class-lp-admin.php',
    'includes/class-lp-shortcodes.php',
    'assets/frontend.css',
);

$errors = array();
foreach ($required as $file) {
    if (!is_file($root . '/' . $file)) {
        $errors[] = 'Mangler fil: ' . $file;
    }
}

$source = implode("\n", array_map(static fn(string $file): string => (string) file_get_contents($root . '/' . $file), array_filter($required, static fn(string $file): bool => is_file($root . '/' . $file))));
foreach (array('lp_source', 'lp_place', 'lp_current', 'lp_event', 'lp_business', 'lp_experience', 'lp_organization', 'lp_import_log', '_lp_external_id', '_lp_source_url', '_lp_source_type', 'dx_culture', '_lp_max_age_days', '_lp_include_keywords', '_lp_venue', 'lp_hourly_import', 'lokalportalen-core', 'lokalportalen_finn') as $needle) {
    if (!str_contains($source, $needle)) {
        $errors[] = 'Mangler kontrakt: ' . $needle;
    }
}

if ($errors) {
    fwrite(STDERR, implode("\n", $errors) . "\n");
    exit(1);
}

echo "Source contract OK\n";
