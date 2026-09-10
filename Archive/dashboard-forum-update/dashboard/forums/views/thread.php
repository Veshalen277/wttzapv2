<article class="card forum-card">
    <div class="forum-meta"><span class="forum-avatar" aria-hidden="true"><?= \Portal\Html::escape(mb_strtoupper(mb_substr($topic['author_name'],0,1))) ?></span><div><strong><?= \Portal\Html::escape($topic['author_name']) ?></strong><small><?= \Portal\Html::escape($topic['created_at']) ?> · <?= $topic['visibility']==='private'?'Private discussion':'Open discussion' ?></small></div></div>
    <div class="forum-body"><?= \Portal\Html::escape($topic['body']) ?></div>
    <?php if ($topic['is_locked'] || $topic['is_archived']): ?><p class="forum-label"><?= $topic['is_archived']?'Archived':'Locked' ?> — replies are closed.</p><?php endif; ?>
</article>
<?php if ($canManage): ?>
<details class="card forum-editor"><summary>Manage discussion</summary>
    <form method="post" class="forum-editor-fields">
        <?= \Portal\Csrf::field() ?><input type="hidden" name="action" value="edit">
        <label for="edit-title">Title</label><input class="form-control" id="edit-title" name="title" maxlength="180" required value="<?= \Portal\Html::escape(($_POST['action']??'')==='edit' && is_string($_POST['title']??null)?$_POST['title']:$topic['title']) ?>">
        <label for="edit-body">Discussion text</label><textarea id="edit-body" class="form-control" name="body" rows="6" maxlength="10000" required><?= \Portal\Html::escape(($_POST['action']??'')==='edit' && is_string($_POST['body']??null)?$_POST['body']:$topic['body']) ?></textarea>
        <div><button class="btn btn-primary" type="submit">Save changes</button></div>
    </form>
    <form method="post" class="forum-actions"><?= \Portal\Csrf::field() ?><button class="btn btn-outline-secondary" name="action" value="<?= $topic['is_locked']?'unlock':'lock' ?>"><?= $topic['is_locked']?'Unlock':'Lock' ?> replies</button><button class="btn btn-outline-secondary" name="action" value="<?= $topic['is_archived']?'restore':'archive' ?>"><?= $topic['is_archived']?'Restore discussion':'Archive discussion' ?></button></form>
</details>
<?php if ($topic['visibility']==='private'): ?>
<details class="card forum-editor" id="members"><summary>Members (<?= count($members) ?>)</summary>
    <p class="text-muted">The owner and moderators always have access.</p>
    <?php foreach ($members as $member): ?><form method="post" class="forum-member"><?= \Portal\Csrf::field() ?><input type="hidden" name="user_id" value="<?= (int)$member['user_id'] ?>"><span><?= \Portal\Html::escape($member['fullname']) ?></span><button class="btn btn-outline-secondary" name="action" value="remove_member">Remove member</button></form><?php endforeach; ?>
    <form method="post" class="forum-editor-fields"><?= \Portal\Csrf::field() ?><label for="member-user">Add a person with forum access</label><select class="form-select" name="user_id" id="member-user" required><option value="">Choose a person</option><?php foreach ($candidates as $candidate): ?><option value="<?= (int)$candidate['id'] ?>"><?= \Portal\Html::escape($candidate['fullname']) ?></option><?php endforeach; ?></select><div><button class="btn btn-primary" name="action" value="add_member">Add member</button></div></form>
</details>
<?php endif; endif; ?>
<h2 class="forum-reply-title">Replies (<?= (int)$replies['total'] ?>)</h2>
<?php if (!$replies['items']): ?><p class="text-muted">No replies yet.</p><?php endif; ?>
<?php foreach ($replies['items'] as $reply): ?>
<article class="card forum-card">
    <div class="forum-meta"><strong><?= \Portal\Html::escape($reply['author_name']) ?></strong><small><?= \Portal\Html::escape($reply['created_at']) ?></small></div>
    <div class="forum-body"><?= \Portal\Html::escape($reply['body']) ?></div>
    <?php if ($forumModerator || (int)$reply['author_id']===$forumUser->id): ?><details class="forum-remove"><summary>Remove reply</summary><p>This hides the reply from the discussion.</p><form method="post"><?= \Portal\Csrf::field() ?><input type="hidden" name="post_id" value="<?= (int)$reply['id'] ?>"><button class="btn btn-danger" name="action" value="remove_reply">Confirm removal</button></form></details><?php endif; ?>
</article>
<?php endforeach; ?>
<?php if ($replies['pages']>1): ?><nav class="forum-pagination" aria-label="Reply pages"><?php if ($replies['page']>1): ?><a href="?id=<?= $topicId ?>&amp;page=<?= $replies['page']-1 ?>">Previous</a><?php endif; ?><span>Page <?= $replies['page'] ?> of <?= $replies['pages'] ?></span><?php if ($replies['page']<$replies['pages']): ?><a href="?id=<?= $topicId ?>&amp;page=<?= $replies['page']+1 ?>">Next</a><?php endif; ?></nav><?php endif; ?>
<?php if ($canReply): ?><form method="post" class="card forum-editor">
    <?= \Portal\Csrf::field() ?><input type="hidden" name="action" value="reply"><label for="reply-body">Your reply</label><textarea class="form-control" id="reply-body" name="body" rows="5" maxlength="10000" required><?= \Portal\Html::escape(($_POST['action']??'')==='reply' && is_string($_POST['body']??null)?$_POST['body']:'') ?></textarea><div><button class="btn btn-primary" type="submit">Post reply</button></div>
</form><?php endif; ?>
