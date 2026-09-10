<?php
namespace WorkplaceForum;
final class Input
{
    public static function text(array $input, string $key, int $maximum): string
    {
        $value = $input[$key] ?? null;
        if (!is_string($value) || trim($value) === '' || mb_strlen(trim($value), 'UTF-8') > $maximum) {
            throw new \InvalidArgumentException(ucfirst($key) . ' is required and must be at most ' . $maximum . ' characters.');
        }
        return trim($value);
    }
    public static function id($value): int
    {
        $id = filter_var($value, FILTER_VALIDATE_INT);
        if ($id === false || $id < 1) throw new \InvalidArgumentException('Invalid record.');
        return $id;
    }
}
