<?php
if (!isset($_SESSION['u_data'])) {
    return;
}
if (!defined('PORTAL_CONTEXT_NAV')) define('PORTAL_CONTEXT_NAV', true);
require_once __DIR__ . '/menu_config.php';
$data = $_SESSION['u_data'];
$role = (int) ($data[4] ?? -1);
$menuItems = getMenuItems($role);
$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
$fullName = $escape($data[0] ?? 'Employee');
$currentPage = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$containsPage = static function ($items, $path) use (&$containsPage) {
    foreach ($items as $item) {
        if (isset($item['submenu']) && $containsPage($item['submenu'], $path)) return true;
        if (($item['link'] ?? '#') !== '#' && parse_url($item['link'], PHP_URL_PATH) === $path) return true;
    }
    return false;
};
$firstLink = static function ($item) use (&$firstLink) {
    if (!empty($item['submenu'])) return $firstLink($item['submenu'][0]);
    return $item['link'] ?? '#';
};
$activeSection = 0;
foreach ($menuItems as $index => $item) {
    if ($containsPage([$item], $currentPage)) {
        $activeSection = $index;
        break;
    }
}
$renderLinks = static function ($items) use (&$renderLinks, $escape, $currentPage) {
    foreach ($items as $item) {
        if (isset($item['submenu'])) {
            echo '<li class="portal-subgroup"><span>' . $escape($item['title']) . '</span><ul>';
            $renderLinks($item['submenu']);
            echo '</ul></li>';
        } else {
            $active = parse_url($item['link'], PHP_URL_PATH) === $currentPage;
            echo '<li><a class="portal-submenu-link' . ($active ? ' is-active' : '') . '" href="' . $escape($item['link']) . '"' . ($active ? ' aria-current="page"' : '') . '>' . $escape($item['title']) . '</a></li>';
        }
    }
};
?>
<link rel="stylesheet" href="/dashboard/css/navigation.css">
<a class="portal-skip" href="#portal-content">Skip to content</a>
<header class="portal-header">
    <div class="portal-topbar">
        <a class="portal-brand" href="/dashboard/employee/emp_profile.php">
            <img src="/dashboard/images/FrontWebPageWheel.png" alt="" width="36" height="36">
            <span>WTT<span class="portal-brand-accent">Zap</span><small>EMPLOYEE PORTAL</small></span>
        </a>
        <span class="portal-workspace-label">Your workplace, connected.</span>
        <div class="portal-account">
            <a href="/dashboard/employee/my_profile.php"><?= $fullName ?></a>
            <a href="/dashboard/logout.php" class="portal-logout">Log out</a>
        </div>
    </div>
    <nav class="portal-sections" aria-label="Main sections">
        <?php foreach ($menuItems as $index => $item): ?>
            <a href="<?= $escape($firstLink($item)) ?>"
               class="portal-section<?= $index === $activeSection ? ' is-selected' : '' ?>"
               data-section="<?= $index ?>"
               <?= isset($item['submenu']) ? 'data-has-submenu="true"' : '' ?>
               aria-controls="portal-panel-<?= $index ?>"
               aria-expanded="<?= $index === $activeSection ? 'true' : 'false' ?>"><?= $escape($item['title']) ?></a>
        <?php endforeach; ?>
    </nav>
</header>
<button class="portal-sidebar-toggle" type="button" aria-controls="portal-sidebar" aria-expanded="false">Section menu</button>
<aside class="portal-sidebar" id="portal-sidebar" aria-label="Section navigation">
    <div class="portal-sidebar-caption">WORKSPACE</div>
    <?php foreach ($menuItems as $index => $item): ?>
        <section id="portal-panel-<?= $index ?>" class="portal-panel" <?= $index !== $activeSection ? 'hidden' : '' ?>>
            <h2><?= $escape($item['title']) ?></h2>
            <p class="portal-section-hint">Explore this section</p>
            <nav aria-label="<?= $escape($item['title']) ?> submenu">
                <ul><?php $renderLinks($item['submenu'] ?? [$item]); ?></ul>
            </nav>
        </section>
    <?php endforeach; ?>
    <div class="portal-sidebar-bottom"><span class="portal-status-dot"></span> WTTZap Employee Portal</div>
</aside>
<script src="/dashboard/js/navigation.js" defer></script>
