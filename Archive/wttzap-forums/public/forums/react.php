<?php
require __DIR__ . '/../../app/bootstrap.php';
$user = Auth::requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('forums/index.php'); }
csrf_verify();

$targetType = $_POST['target_type'] ?? '';
$targetId = (int) ($_POST['target_id'] ?? 0);
$topicId = (int) ($_POST['topic_id'] ?? 0); // where to redirect back to

if (!in_array($targetType, ['topic', 'reply'], true) || !$targetId || !$topicId) {
    redirect('forums/index.php');
}

$existing = Database::one(
    'SELECT id FROM forum_reactions WHERE target_type = ? AND target_id = ? AND user_id = ?',
    [$targetType, $targetId, $user['id']]
);

if ($existing) {
    Database::run('DELETE FROM forum_reactions WHERE id = ?', [$existing['id']]);
} else {
    Database::run(
        'INSERT INTO forum_reactions (target_type, target_id, user_id, reaction_type) VALUES (?,?,?,"like")',
        [$targetType, $targetId, $user['id']]
    );
}

redirect('forums/topic.php?id=' . $topicId . '#' . $targetType . '-' . $targetId);
