<?php
namespace Portal;
final class Passwords
{
    public static function validate(string $password): void
    {
        // Preserve exact bytes: do not trim, strip symbols, or coerce to numbers.
        if (strlen($password) < 8 || strlen($password) > 72 || strpos($password, "\0") !== false) {
            throw new \InvalidArgumentException('Use 8–72 bytes, including letters, numbers, spaces or symbols.');
        }
    }
    public static function verify(string $password, string $stored): bool
    {
        if (preg_match('/^[a-f0-9]{40}$/i', $stored)) return hash_equals(strtolower($stored), sha1($password));
        return password_verify($password, $stored);
    }
    public static function replacement(string $password, string $stored): string
    {
        self::validate($password);
        // Retain legacy compatibility until the external sign-in handler is upgraded.
        if (preg_match('/^[a-f0-9]{40}$/i', $stored)) return sha1($password);
        return password_hash($password, PASSWORD_DEFAULT);
    }
}
