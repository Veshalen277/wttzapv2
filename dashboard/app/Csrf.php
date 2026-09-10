<?php
namespace Portal;

final class Csrf
{
    public static function token(): string
    {
        Auth::start();
        if (!isset($_SESSION['_portal_csrf'])) $_SESSION['_portal_csrf'] = bin2hex(random_bytes(32));
        return $_SESSION['_portal_csrf'];
    }

    public static function valid($token): bool
    {
        Auth::start();
        $stored = $_SESSION['_portal_csrf'] ?? null;
        return is_string($stored) && is_string($token) && $stored !== '' && hash_equals($stored, $token);
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . Html::escape(self::token()) . '">';
    }
}
