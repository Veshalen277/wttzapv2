<?php
namespace WorkplaceForum;
final class Policy
{
    public static function canView(array $topic, int $userId, bool $moderator, bool $member): bool
    {
        return $moderator || (int)$topic['author_id'] === $userId || $topic['visibility'] === 'open' || $member;
    }
    public static function canManage(array $topic, int $userId, bool $moderator): bool
    {
        return $moderator || (int)$topic['author_id'] === $userId;
    }
    public static function canReply(array $topic, bool $visible): bool
    {
        return $visible && !(bool)$topic['is_locked'] && !(bool)$topic['is_archived'];
    }
}
