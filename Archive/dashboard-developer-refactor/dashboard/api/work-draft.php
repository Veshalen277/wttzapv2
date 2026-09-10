<?php
// Independent JSON endpoint: no header/footer rendering and no client-supplied user ID.
require_once dirname(__DIR__) . '/app/autoload.php';
use Portal\Auth;
use Portal\Connection;
use Portal\Csrf;
use Portal\Http\Response;
use Portal\Repositories\WorkDraft;

ini_set('display_errors', '0');
$user = Auth::requireUser(true);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if (!in_array($method, ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    Response::json(['error' => 'Method not allowed.'], 405);
}

$action = null;
$text = null;
if ($method === 'POST') {
    if (!Csrf::valid($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) Response::json(['error' => 'Refresh the page and try again.'], 403);
    if (strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0])) !== 'application/json') Response::json(['error' => 'Send application/json.'], 415);
    // Bound reads even when Content-Length is omitted.
    $stream = fopen('php://input', 'rb');
    $raw = stream_get_contents($stream, 131073);
    fclose($stream);
    if ($raw === false || strlen($raw) > 131072) Response::json(['error' => 'Request is too large.'], 413);
    try { $payload = json_decode($raw, true, 32, JSON_THROW_ON_ERROR); }
    catch (\JsonException $error) { Response::json(['error' => 'Invalid JSON.'], 400); }
    if (!is_array($payload)) Response::json(['error' => 'Expected a JSON object.'], 400);
    $action = $payload['action'] ?? 'save';
    if (!in_array($action, ['save', 'clear'], true)) Response::json(['error' => 'Unknown action.'], 422);
    if ($action === 'save') {
        $text = $payload['text'] ?? null;
        if (!is_string($text) || strlen($text) > 100000) Response::json(['error' => 'Draft must be text of at most 100000 bytes.'], 422);
    }
}

try {
    $repository = new WorkDraft(Connection::get());
    if ($method === 'GET') {
        $draft = $repository->find($user->id);
        if ($draft === null) Response::json(['error' => 'User not found.'], 404);
        Response::json(['draft' => $draft, 'csrfToken' => Csrf::token()]);
    }
    if (!$repository->save($user->id, $text)) Response::json(['error' => 'User not found.'], 404);
    Response::json(['saved' => true, 'cleared' => $action === 'clear']);
} catch (\Throwable $error) {
    error_log('Work draft API: ' . $error->getMessage());
    Response::json(['error' => 'Draft could not be processed. Please try again.'], 500);
}
