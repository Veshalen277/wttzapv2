<?php
namespace Portal\Http;

final class Response
{
    public static function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        header('X-Content-Type-Options: nosniff');
        echo json_encode($payload, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    public static function redirect(string $path, int $status = 303): void
    {
        if (!preg_match('~^/(?!/)~', $path) || preg_match('/[\x00-\x20]/', $path) || strpos($path, '\\') !== false) {
            throw new \InvalidArgumentException('Redirect must be a local absolute path.');
        }
        header('Location: ' . $path, true, $status);
        exit;
    }
}
