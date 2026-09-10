<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/app/autoload.php';
\Portal\Auth::start();
$checks = 0;
$check = static function (bool $condition, string $message) use (&$checks): void {
    if (!$condition) throw new \RuntimeException($message);
    $checks++;
};
$check(\Portal\User::fromSession([]) === null, 'Anonymous session must not authenticate.');
$user = \Portal\User::fromSession(['u_data' => ['Vesh', '', 'WTT', 'Task', '0', '34']]);
$check($user !== null && $user->role === 0 && $user->id === 34, 'Role zero/string IDs must map correctly.');
$check($user->hasRole([0]) && !$user->hasRole([7]), 'Roles must restrict endpoints.');
$check(\Portal\User::fromSession(['u_data' => ['', '', '', '', 7, -1]]) === null, 'Reject invalid identity.');
$menu = new \Portal\Menu([
    ['title' => 'One', 'submenu' => [['title' => 'First', 'link' => '/dashboard/one/index.php']]],
    ['title' => 'Two', 'submenu' => [['title' => 'Second', 'link' => '/dashboard/two/index.php']]],
], '/dashboard/two/index.php?page=2');
$check($menu->activeSection === 1, 'Match full paths and ignore query strings.');
$check($menu->firstLink($menu->items[1]) === '/dashboard/two/index.php', 'Resolve section landing link.');
$escaped = \Portal\Html::escape('<script>"&');
$check(strpos($escaped, '<script>') === false && strpos($escaped, '&quot;') !== false, 'Escape untrusted text.');
$token = \Portal\Csrf::token();
$check(\Portal\Csrf::valid($token), 'Accept this session token.');
$check(!\Portal\Csrf::valid('wrong') && !\Portal\Csrf::valid([$token]), 'Reject invalid CSRF values.');
\Portal\Flash::set("Quote '\n<script>alert(1)</script>", 'error');
$message = \Portal\Flash::take();
$check($message['type'] === 'danger' && \Portal\Flash::take() === null, 'Consume flash once.');
ob_start();
\Portal\Html::view('flash', ['message' => $message]);
$html = ob_get_clean();
$check(strpos($html, '<script>') === false, 'Flash rendering must not inject scripts.');
$check(\Portal\Modules::definition('../config') === null, 'Reject module path traversal.');
try { \Portal\Html::asset('../config.php'); $check(false, 'Reject asset traversal.'); }
catch (\InvalidArgumentException $error) { $check(true, 'Asset traversal rejected.'); }
echo "PASS {$checks} core checks (no database required).\n";
