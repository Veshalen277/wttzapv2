<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['login_csrf_token'])) {
    $_SESSION['login_csrf_token'] = bin2hex(random_bytes(32));
}
// Keep the existing authentication contract and redirect behaviour.
require __DIR__ . '/dashboard/config.php';
require __DIR__ . '/gist.php';
$loginError = isset($_SESSION['error']) ? (string)$_SESSION['error'] : '';
unset($_SESSION['error']);
$loginUser = is_string($_POST['user_id'] ?? null) ? $_POST['user_id'] : '';
$escapeLogin = static function ($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
?>
<!doctype html>
<html lang="en" data-theme-user="guest">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <title>Sign in · WTTZap</title>
  <script src="/assets/login-appearance.js?v=<?= (int)filemtime(__DIR__.'/assets/login-appearance.js') ?>"></script>
  <link rel="stylesheet" href="/assets/wtt-login.css?v=<?= (int)filemtime(__DIR__.'/assets/wtt-login.css') ?>">
  <script src="/assets/wtt-login.js?v=<?= (int)filemtime(__DIR__.'/assets/wtt-login.js') ?>" defer></script>
</head>
<body>
  <div class="stadium-backdrop" aria-hidden="true"></div>
  <header class="login-topbar">
    <a class="login-brand" href="/" aria-label="WTTZap home"><span class="brand-symbol" aria-hidden="true"></span>WTT<span>Zap</span></a>
    <label class="appearance-control"><span>Appearance</span><select id="theme-select" aria-label="Appearance"><option value="system">System</option><option value="light">Light</option><option value="dark">Dark</option><option value="morning">Morning</option><option value="afternoon">Afternoon</option></select></label>
  </header>
  <main class="login-main">
    <section class="login-card" aria-labelledby="login-title">
      <div class="login-card-brand"><span class="brand-symbol" aria-hidden="true"></span><div><strong>WTTZap</strong><span>EMPLOYEE WORKSPACE</span></div></div>
      <div class="login-card-body">
        <p class="login-eyebrow">YOUR WORK. YOUR PROGRESS.</p>
        <h1 id="login-title">Welcome back.</h1>
        <p class="login-intro">Sign in to your workplace.</p>
        <?php if ($loginError !== ''): ?><div class="login-error" role="alert"><?= $escapeLogin($loginError) ?></div><?php endif; ?>
        <form method="post" id="login-form">
          <input type="hidden" name="csrf_token" value="<?= $escapeLogin($_SESSION['login_csrf_token']) ?>">
          <label for="login-user">User ID</label>
          <input id="login-user" name="user_id" type="text" autocomplete="username" autocapitalize="none" spellcheck="false" value="<?= $escapeLogin($loginUser) ?>" required>
          <div class="password-label"><label for="login-password">Password</label><a href="/forgot_password.php">Forgot password?</a></div>
          <div class="password-control"><input id="login-password" name="user_pass" type="password" autocomplete="current-password" required><button id="show-password" type="button" aria-controls="login-password" aria-label="Show password" aria-pressed="false" hidden>Show</button></div>
          <button class="login-submit" type="submit" name="login_btn">Sign in <span aria-hidden="true">→</span></button>
        </form>
      </div>
      <div class="login-card-footer">Work reports <span aria-hidden="true">·</span> Team discussions <span aria-hidden="true">·</span> Your progress</div>
    </section>
    <p class="login-caption">One place to contribute, connect, and grow.</p>
  </main>
  <footer class="login-footer"><span>WTTZap Employee Portal</span><span>Made for the work you do.</span></footer>
</body>
</html>
<?php if (ob_get_level() > 0) ob_end_flush(); ?>
