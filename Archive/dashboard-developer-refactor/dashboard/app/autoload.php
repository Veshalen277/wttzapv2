<?php
// Small, isolated loader: existing Composer dependencies and lockfile are untouched.
spl_autoload_register(static function (string $class): void {
    $prefix = 'Portal\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) return;
    $relative = substr($class, strlen($prefix));
    if (!preg_match('/^[A-Za-z0-9_\\\\]+$/', $relative)) return;
    $path = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) require $path;
});
