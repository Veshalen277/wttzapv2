<?php
namespace Portal;

final class Html
{
    public static function escape($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function asset(string $path): string
    {
        // Paths are developer-owned, relative to /dashboard. Never accept user input here.
        if (!preg_match('~^[a-zA-Z0-9_./-]+$~', $path) || strpos($path, '..') !== false || $path[0] === '/') {
            throw new \InvalidArgumentException('Expected a relative asset path.');
        }
        $file = dirname(__DIR__) . '/' . $path;
        return '/dashboard/' . $path . (is_file($file) ? '?v=' . filemtime($file) : '');
    }

    public static function view(string $name, array $variables = []): void
    {
        if (!preg_match('~^[a-zA-Z0-9_/-]+$~', $name)) throw new \InvalidArgumentException('Invalid view name.');
        $path = dirname(__DIR__) . '/views/' . $name . '.php';
        if (!is_file($path)) throw new \RuntimeException('View not found: ' . $name);
        // Scope templates so they cannot overwrite the calling page's variables.
        (static function (string $__path, array $__variables): void {
            extract($__variables, EXTR_SKIP);
            require $__path;
        })($path, $variables);
    }
}
