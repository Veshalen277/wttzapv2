<?php
require_once __DIR__ . '/app/autoload.php';
$portalNavigationUser = \Portal\User::fromSession($_SESSION ?? []);
if (!$portalNavigationUser) return;
if (!defined('PORTAL_CONTEXT_NAV')) define('PORTAL_CONTEXT_NAV', true);
require_once __DIR__ . '/menu_config.php';
$portalMenu = new \Portal\Menu(
    array_merge(getMenuItems($portalNavigationUser->role), \Portal\Modules::menu($portalNavigationUser->role)),
    $portalNavigationPath ?? ($_SERVER['REQUEST_URI'] ?? '')
);
\Portal\Html::view('navigation', [
    'navigation' => $portalMenu,
    'menuItems' => $portalMenu->items,
    'activeSection' => $portalMenu->activeSection,
    'fullName' => \Portal\Html::escape($portalNavigationUser->name),
]);
