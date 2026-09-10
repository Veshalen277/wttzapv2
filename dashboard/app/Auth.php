<?php
namespace Portal;

final class Auth
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
    }

    public static function requireUser(bool $json = false): User
    {
        self::start();
        $user = User::fromSession($_SESSION);
        if ($user) return $user;
        if ($json) Http\Response::json(['error' => 'Sign in to continue.'], 401);
        header('Location: /index.php');
        exit;
    }

    public static function requireRoles(array $roles, bool $json = false): User
    {
        $user = self::requireUser($json);
        if ($user->hasRole($roles)) return $user;
        if ($json) Http\Response::json(['error' => 'Access denied.'], 403);
        http_response_code(403);
        exit('Access denied.');
    }
}
