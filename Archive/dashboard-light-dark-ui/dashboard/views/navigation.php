<link rel="stylesheet" href="<?= \Portal\Html::escape(\Portal\Html::asset('css/navigation.css')) ?>">
<a class="portal-skip" href="#portal-content">Skip to content</a>
<header class="portal-header">
    <div class="portal-topbar">
        <button class="portal-sidebar-toggle" type="button" aria-label="Toggle section sidebar" aria-controls="portal-sidebar" aria-expanded="true"><i class="bi bi-list" aria-hidden="true"></i></button>
        <a class="portal-brand" href="/dashboard/employee/emp_profile.php">
            <img src="/dashboard/images/FrontWebPageWheel.png" alt="" width="36" height="36">
            <span>WTT<span class="portal-brand-accent">Zap</span><small>EMPLOYEE PORTAL</small></span>
        </a>
        <div class="portal-search">
            <label class="portal-search-label" for="portal-menu-search"><i class="bi bi-search" aria-hidden="true"></i><span class="visually-hidden">Find a page</span></label>
            <input id="portal-menu-search" type="search" placeholder="Search your workplace pages…" autocomplete="off" aria-controls="portal-search-results" aria-expanded="false">
            <div id="portal-search-results" class="portal-search-results" hidden></div>
            <span class="visually-hidden" id="portal-search-status" role="status"></span>
        </div>
        <div class="portal-account">
            <button class="portal-theme-toggle" id="theme-toggle" type="button" aria-label="Dark mode" aria-pressed="false" title="Toggle light and dark appearance">
                <i class="bi bi-moon-stars" aria-hidden="true"></i><span>Dark mode</span>
            </button>
            <a class="portal-icon-link" href="/dashboard/employee/emp_profile.php" aria-label="My work report" title="My work report"><i class="bi bi-journal-check" aria-hidden="true"></i></a>
            <details class="portal-profile">
                <summary aria-label="Account menu"><i class="bi bi-person" aria-hidden="true"></i><span class="portal-account-name"><?= $fullName ?></span><i class="bi bi-chevron-down" aria-hidden="true"></i></summary>
                <div class="portal-profile-menu">
                    <div class="portal-profile-heading"><strong><?= $fullName ?></strong><small>Employee account</small></div>
                    <a href="/dashboard/employee/my_profile.php"><i class="bi bi-person-circle" aria-hidden="true"></i> My profile</a>
                    <a href="/dashboard/employee/emp_profile.php"><i class="bi bi-journal-text" aria-hidden="true"></i> My work report</a>
                    <a href="/dashboard/logout.php" class="portal-logout"><i class="bi bi-box-arrow-right" aria-hidden="true"></i> Log out</a>
                </div>
            </details>
        </div>
    </div>
    <nav class="portal-sections" aria-label="Main sections">
        <?php foreach ($menuItems as $index => $item): ?>
            <a href="<?= \Portal\Html::escape($navigation->firstLink($item)) ?>"
               class="portal-section<?= $index === $activeSection ? ' is-selected' : '' ?>"
               data-section="<?= $index ?>"
               <?= isset($item['submenu']) ? 'data-has-submenu="true"' : '' ?>
               aria-controls="portal-panel-<?= $index ?>"
               aria-expanded="<?= $index === $activeSection ? 'true' : 'false' ?>"><?= \Portal\Html::escape($item['title']) ?></a>
        <?php endforeach; ?>
    </nav>
</header>
<button class="portal-backdrop" type="button" aria-label="Close section sidebar" tabindex="-1" hidden></button>
<aside class="portal-sidebar" id="portal-sidebar" aria-label="Section navigation">
    <div class="portal-sidebar-caption">WORKSPACE NAVIGATION</div>
    <?php foreach ($menuItems as $index => $item): ?>
        <section id="portal-panel-<?= $index ?>" class="portal-panel" <?= $index !== $activeSection ? 'hidden' : '' ?>>
            <h2><?= \Portal\Html::escape($item['title']) ?></h2>
            <p class="portal-section-hint">Pages in this section</p>
            <nav aria-label="<?= \Portal\Html::escape($item['title']) ?> submenu">
                <ul><?php \Portal\Html::view('submenu', ['items' => $item['submenu'] ?? [$item], 'currentPage' => $navigation->currentPath]); ?></ul>
            </nav>
        </section>
    <?php endforeach; ?>
    <div class="portal-sidebar-bottom"><strong>WTTZap</strong><span>Employee Portal</span></div>
</aside>
<script src="<?= \Portal\Html::escape(\Portal\Html::asset('js/navigation.js')) ?>" defer></script>
