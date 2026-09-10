<?php
namespace Portal\Repositories;

final class WorkDraft
{
    private \mysqli $db;

    public function __construct(\mysqli $db) { $this->db = $db; }

    public function find(int $userId): ?array
    {
        // Use only the column confirmed in the supplied schema. No timestamp migration needed.
        $statement = $this->db->prepare('SELECT work_draft FROM users_tbl WHERE id = ? LIMIT 1');
        if (!$statement) throw new \RuntimeException('Could not prepare draft read.');
        try {
            $statement->bind_param('i', $userId);
            if (!$statement->execute()) throw new \RuntimeException('Could not read draft.');
            $statement->bind_result($text);
            return $statement->fetch() ? ['text' => (string) ($text ?? '')] : null;
        } finally { $statement->close(); }
    }

    public function save(int $userId, ?string $text): bool
    {
        $statement = $this->db->prepare('UPDATE users_tbl SET work_draft = ? WHERE id = ?');
        if (!$statement) throw new \RuntimeException('Could not prepare draft save.');
        try {
            $statement->bind_param('si', $text, $userId);
            if (!$statement->execute()) throw new \RuntimeException('Could not save draft.');
            $changed = $statement->affected_rows > 0;
        } finally { $statement->close(); }
        return $changed || $this->find($userId) !== null;
    }
}
