<?php
namespace Portal;

final class Modules
{
    public static function definition(string $slug): ?array
    {
        if (!preg_match('/^[a-z][a-z0-9-]*$/', $slug)) return null;
        $file = dirname(__DIR__) . '/modules/' . $slug . '/module.php';
        if (!is_file($file)) return null;
        $module = require $file;
        if (!is_array($module) || empty($module['enabled'])) return null;
        if (!is_string($module['title'] ?? null) || !is_array($module['roles'] ?? null) || !is_array($module['pages'] ?? null)) {
            throw new \RuntimeException('Invalid module definition: ' . $slug);
        }
        foreach ($module['roles'] as $role) {
            if (!is_int($role) || $role < 0) throw new \RuntimeException('Module roles must be integers: ' . $slug);
        }
        foreach ($module['pages'] as $page) {
            if (!is_array($page) || !is_string($page['title'] ?? null) || !preg_match('/^[a-z][a-z0-9_-]*\.php$/', $page['file'] ?? '')) {
                throw new \RuntimeException('Invalid module page: ' . $slug);
            }
            if (!is_file(dirname($file) . '/' . $page['file'])) throw new \RuntimeException('Missing module page: ' . $slug);
        }
        return $module;
    }

    public static function menu(int $role): array
    {
        $items = [];
        foreach (glob(dirname(__DIR__) . '/modules/*/module.php') ?: [] as $path) {
            $slug = basename(dirname($path));
            $module = self::definition($slug);
            if (!$module || !in_array($role, $module['roles'], true) || !$module['pages']) continue;
            $pages = array_map(static function (array $page) use ($slug): array {
                return ['title' => $page['title'], 'link' => '/dashboard/modules/' . $slug . '/' . $page['file']];
            }, $module['pages']);
            $items[] = ['title' => $module['title'], 'link' => '#', 'submenu' => $pages];
        }
        return $items;
    }

    public static function guard(string $slug): User
    {
        $module = self::definition($slug);
        if (!$module) { http_response_code(404); exit('Page not found.'); }
        return Auth::requireRoles($module['roles']);
    }
}
