<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$slug = $argv[1] ?? '';
if (!preg_match('/^[a-z][a-z0-9-]*$/', $slug)) {
    fwrite(STDERR, "Usage: php bin/create-module.php equipment\nUse lowercase letters, digits and hyphens.\n");
    exit(1);
}
$root = dirname(__DIR__);
$destination = $root . '/modules/' . $slug;
if (file_exists($destination)) { fwrite(STDERR, "Module already exists; nothing changed.\n"); exit(1); }
if (!is_dir($root . '/modules') && !mkdir($root . '/modules', 0755)) exit(1);
if (!mkdir($destination, 0755)) exit(1);
$title = ucwords(str_replace('-', ' ', $slug));
foreach (['module.php', 'index.php'] as $file) {
    $source = file_get_contents($root . '/scaffolds/module/' . $file);
    if ($source === false || file_put_contents($destination . '/' . $file, str_replace('__MODULE_TITLE__', $title, $source)) === false) {
        fwrite(STDERR, "Could not finish scaffolding. Inspect {$destination} before retrying.\n"); exit(1);
    }
}
echo "Created modules/{$slug}. Edit module.php roles/pages, build the page, then set enabled to true.\n";
