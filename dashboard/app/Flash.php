<?php
namespace Portal;

final class Flash
{
    public static function set(string $message, string $type = 'success'): void
    {
        Auth::start();
        $_SESSION['msg'] = $message;
        $_SESSION['msg_type'] = $type;
    }

    public static function take(): ?array
    {
        Auth::start();
        if (!isset($_SESSION['msg'])) return null;
        $message = ['text' => (string) $_SESSION['msg'], 'type' => ($_SESSION['msg_type'] ?? '') === 'error' ? 'danger' : 'success'];
        unset($_SESSION['msg'], $_SESSION['msg_type']);
        return $message;
    }
}
