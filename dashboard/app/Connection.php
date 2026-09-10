<?php
namespace Portal;

final class Connection
{
    private static ?\mysqli $connection = null;

    public static function get(): \mysqli
    {
        if (self::$connection) return self::$connection;
        if (($GLOBALS['con'] ?? null) instanceof \mysqli) {
            return self::$connection = $GLOBALS['con'];
        }
        // Keep the deployed connection and SMTP settings as the authoritative config.
        $connection = (static function () {
            require dirname(__DIR__) . '/config.php';
            return $con ?? $conn ?? $db ?? null;
        })();
        if (!$connection instanceof \mysqli) throw new \RuntimeException('Database connection is unavailable.');
        self::$connection = $connection;
        return $connection;
    }
}
