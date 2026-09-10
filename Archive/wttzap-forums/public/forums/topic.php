<?php
require __DIR__ . '/../../app/bootstrap.php';
$user = Auth::requireLogin();
$activeNav = 'forums';
$businessId = Auth::activeBusinessId();

$topicId = (int) ($_GET['id'] ?? 0);
$topic = Database::one(
    'SELECT t.*, u.full_name AS author_name, c.name AS category_name, c.id AS category_id
     FROM forum_topics t JOIN users u ON u.id = t.user_id JOIN forum_categories c ON c.id = t.category_id
     WHERE t.id = ? AND t.business_id = ?',
    [$topicId, $businessId]
);
if (!$topic) { http_response_code(404); die('Topic not found.'); }

$isModerator = Auth::isManagerOrAbove();
$isAuthor = $topic['user_id'] == $user['id'];
$error = null;

// -- moderation & reply actions --
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'reply' && (!$topic['is_locked'] || $isModerator)) {
        $body = trim($_POST['body'] ?? '');
        if ($body !== '') {
            Database::run('INSERT INTO forum_replies (topic_id, user_id, body) VALUES (?,?,?)', [$topicId, $user['id'], $body]);
            Database::run(
                'UPDATE forum_topics SET reply_count = reply_count + 1, last_reply_at = NOW(), last_reply_by = ? WHERE id = ?',
                [$user['id'], $topicId]
            );
            redirect('forums/topic.php?id=' . $topicId . '#latest');
        }
    } elseif ($action === 'pin' && $isModerator) {
        Database::run('UPDATE forum_topics SET is_pinned = NOT is_pinned WHERE id = ?', [$topicId]);
        redirect('forums/topic.php?id=' . $topicId);
    } elseif ($action === 'lock' && $isModerator) {
        Database::run('UPDATE forum_topics SET is_locked = NOT is_locked WHERE id = ?', [$topicId]);
        redirect('forums/topic.php?id=' . $topicId);
    } elseif ($action === 'delete_topic' && ($isModerator || $isAuthor)) {
        Database::run('DELETE FROM forum_topics WHERE id = ?', [$topicId]);
        flash('success', 'Topic deleted.');
        redirect('forums/category.php?id=' . $topic['category_id']);
    } elseif ($action === 'delete_reply') {
        $replyId = (int) ($_POST['reply_id'] ?? 0);
        $reply = Database::one('SELECT * FROM forum_replies WHERE id = ? AND topic_id = ?', [$replyId, $topicId]);
        if ($reply && ($isModerator || $reply['user_id'] == $user['id'])) {
            Database::run('DELETE FROM forum_replies WHERE id = ?', [$replyId]);
            Database::run('UPDATE forum_topics SET reply_count = GREATEST(reply_count - 1, 0) WHERE id = ?', [$topicId]);
        }
        redirect('forums/topic.php?id=' . $topicId);
    }
}

// count a view (simple — no per-user dedupe)
Database::run('UPDATE forum_topics SET views = views + 1 WHERE id = ?', [$topicId]);

$replies = Database::all(
    'SELECT r.*, u.full_name AS author_name FROM forum_replies r JOIN users u ON u.id = r.user_id
     WHERE r.topic_id = ? ORDER BY r.created_at',
    [$topicId]
);

// reactions: counts + whether the current user reacted, for the topic and every reply in one go
function reaction_info(string $type, int $id, int $userId): array {
    $count = Database::one('SELECT COUNT(*) c FROM forum_reactions WHERE target_type = ? AND target_id = ?', [$type, $id])['c'];
    $mine = Database::one('SELECT id FROM forum_reactions WHERE target_type = ? AND target_id = ? AND user_id = ?', [$type, $id, $userId]);
    return ['count' => (int) $count, 'mine' => (bool) $mine];
}
$topicReaction = reaction_info('topic', $topicId, $user['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($topic['title']) ?> · Forums · WttZap</title>
    <link rel="stylesheet" href="../assets/css/app.css">
    <link rel="stylesheet" href="../assets/css/forums.css">
</head>
<body>
<div class="app-shell">
    <?php require __DIR__ . '/../../app/partials/nav.php'; ?>

    <main class="main">
        <div class="topbar">
            <div>
                <div class="forum-breadcrumb">
                    <a href="index.php">Forums</a> / <a href="category.php?id=<?= (int) $topic['category_id'] ?>"><?= e($topic['category_name']) ?></a>
                </div>
                <h1><?= e($topic['title']) ?></h1>
            </div>
            <?php require __DIR__ . '/../../app/partials/user_menu.php'; ?>
        </div>

        <div style="display:flex; gap:8px; margin-bottom:16px;">
            <?php if ($topic['is_pinned']): ?><span class="badge badge-pending">Pinned</span><?php endif; ?>
            <?php if ($topic['is_locked']): ?><span class="badge badge-inactive">Locked</span><?php endif; ?>

            <?php if ($isModerator): ?>
                <form method="post" style="display:inline;">
                    <?= csrf_field() ?><input type="hidden" name="action" value="pin">
                    <button class="btn btn-outline btn-sm" type="submit"><?= $topic['is_pinned'] ? 'Unpin' : 'Pin' ?></button>
                </form>
                <form method="post" style="display:inline;">
                    <?= csrf_field() ?><input type="hidden" name="action" value="lock">
                    <button class="btn btn-outline btn-sm" type="submit"><?= $topic['is_locked'] ? 'Unlock' : 'Lock' ?></button>
                </form>
            <?php endif; ?>
            <?php if ($isModerator || $isAuthor): ?>
                <form method="post" style="display:inline;" onsubmit="return confirm('Delete this whole topic and all its replies?');">
                    <?= csrf_field() ?><input type="hidden" name="action" value="delete_topic">
                    <button class="btn btn-danger btn-sm" type="submit">Delete topic</button>
                </form>
            <?php endif; ?>
        </div>

        <div class="card forum-post" id="topic-<?= $topicId ?>">
            <div class="forum-post-body"><?= nl2br(e($topic['body'])) ?></div>
            <div class="forum-post-footer">
                <span class="forum-meta">by <strong><?= e($topic['author_name']) ?></strong> · <?= format_date($topic['created_at']) ?> · <span class="num"><?= (int) $topic['views'] ?></span> views</span>
                <form method="post" action="react.php" style="display:inline;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="target_type" value="topic">
                    <input type="hidden" name="target_id" value="<?= $topicId ?>">
                    <input type="hidden" name="topic_id" value="<?= $topicId ?>">
                    <button class="btn btn-sm <?= $topicReaction['mine'] ? 'btn-accent' : 'btn-outline' ?>" type="submit">
                        👍 <span class="num"><?= $topicReaction['count'] ?></span>
                    </button>
                </form>
            </div>
        </div>

        <h2><span class="num"><?= count($replies) ?></span> repl<?= count($replies) === 1 ? 'y' : 'ies' ?></h2>

        <?php foreach ($replies as $r): $rr = reaction_info('reply', $r['id'], $user['id']); ?>
            <div class="card forum-post" id="reply-<?= (int) $r['id'] ?>">
                <div class="forum-post-body"><?= nl2br(e($r['body'])) ?></div>
                <div class="forum-post-footer">
                    <span class="forum-meta">by <strong><?= e($r['author_name']) ?></strong> · <?= time_ago($r['created_at']) ?></span>
                    <div style="display:flex; gap:8px;">
                        <form method="post" action="react.php" style="display:inline;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="target_type" value="reply">
                            <input type="hidden" name="target_id" value="<?= (int) $r['id'] ?>">
                            <input type="hidden" name="topic_id" value="<?= $topicId ?>">
                            <button class="btn btn-sm <?= $rr['mine'] ? 'btn-accent' : 'btn-outline' ?>" type="submit">
                                👍 <span class="num"><?= $rr['count'] ?></span>
                            </button>
                        </form>
                        <?php if ($isModerator || $r['user_id'] == $user['id']): ?>
                            <form method="post" style="display:inline;" onsubmit="return confirm('Delete this reply?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete_reply">
                                <input type="hidden" name="reply_id" value="<?= (int) $r['id'] ?>">
                                <button class="btn btn-outline btn-sm" type="submit">Delete</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <a id="latest"></a>
        <?php if ($topic['is_locked'] && !$isModerator): ?>
            <div class="alert alert-error">This topic is locked — only managers and admins can reply.</div>
        <?php else: ?>
            <div class="card">
                <h3>Reply</h3>
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="reply">
                    <textarea name="body" required rows="4" placeholder="Write a reply…"></textarea>
                    <button class="btn btn-accent" type="submit" style="margin-top:14px;">Post reply</button>
                </form>
            </div>
        <?php endif; ?>
    </main>
</div>
</body>
</html>
