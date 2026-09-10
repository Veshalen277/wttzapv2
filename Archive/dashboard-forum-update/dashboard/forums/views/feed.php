<?php if (!empty($forumMemberHint)): ?><div class="alert alert-info">Open a private discussion you own, then use “Members” to add or remove people. Moderators can manage any discussion.</div><?php endif; ?>
<form method="get" class="forum-filters" action="/dashboard/forums/view_topics.php">
    <label for="forum-search" class="visually-hidden">Search discussions</label>
    <input id="forum-search" class="form-control" type="search" name="q" maxlength="180" value="<?= \Portal\Html::escape($search) ?>" placeholder="Search titles and discussion text">
    <label for="forum-filter" class="visually-hidden">Show discussions</label>
    <select id="forum-filter" class="form-select" name="filter">
        <?php foreach (['all'=>'All discussions','mine'=>'My discussions','archived'=>'Archived'] as $value=>$label): ?><option value="<?= $value ?>" <?= $filter===$value?'selected':'' ?>><?= $label ?></option><?php endforeach; ?>
    </select>
    <button class="btn btn-primary" type="submit">Search</button>
</form>
<p class="forum-count"><?= (int)$feed['total'] ?> discussions</p>
<?php if (!$feed['items']): ?>
    <section class="card forum-empty"><h2>No discussions found</h2><p>Try another search, or start a discussion with your colleagues.</p></section>
<?php endif; ?>
<?php foreach ($feed['items'] as $discussion): ?>
    <article class="card forum-card">
        <div class="forum-meta"><span class="forum-avatar" aria-hidden="true"><?= \Portal\Html::escape(mb_strtoupper(mb_substr($discussion['author_name'],0,1))) ?></span><div><strong><?= \Portal\Html::escape($discussion['author_name']) ?></strong><small>Started <?= \Portal\Html::escape($discussion['created_at']) ?></small></div></div>
        <h2><a href="/dashboard/forums/thread.php?id=<?= (int)$discussion['id'] ?>"><?= \Portal\Html::escape($discussion['title']) ?></a></h2>
        <p class="forum-preview"><?= \Portal\Html::escape(mb_substr($discussion['body'],0,240)) ?><?= mb_strlen($discussion['body'])>240?'…':'' ?></p>
        <div class="forum-card-footer"><span><?= (int)$discussion['reply_count'] ?> replies · Updated <?= \Portal\Html::escape($discussion['updated_at']) ?></span><span class="forum-label"><?= $discussion['visibility']==='private'?'Private':'Open' ?><?= $discussion['is_locked']?' · Locked':'' ?><?= $discussion['is_archived']?' · Archived':'' ?></span></div>
    </article>
<?php endforeach; ?>
<?php if ($feed['pages']>1): ?><nav class="forum-pagination" aria-label="Discussion pages">
    <?php if ($feed['page']>1): ?><a href="?<?= \Portal\Html::escape(http_build_query(['q'=>$search,'filter'=>$filter,'page'=>$feed['page']-1])) ?>">Previous</a><?php endif; ?>
    <span>Page <?= $feed['page'] ?> of <?= $feed['pages'] ?></span>
    <?php if ($feed['page']<$feed['pages']): ?><a href="?<?= \Portal\Html::escape(http_build_query(['q'=>$search,'filter'=>$filter,'page'=>$feed['page']+1])) ?>">Next</a><?php endif; ?>
</nav><?php endif; ?>
