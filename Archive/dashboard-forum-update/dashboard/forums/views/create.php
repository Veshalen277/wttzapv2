<form method="post" class="card forum-editor">
    <?= \Portal\Csrf::field() ?><input type="hidden" name="action" value="create">
    <label for="forum-title">Discussion title</label>
    <input class="form-control" id="forum-title" name="title" maxlength="180" required value="<?= \Portal\Html::escape(is_string($_POST['title']??null)?$_POST['title']:'') ?>">
    <label for="forum-body">Start the conversation</label>
    <textarea class="form-control" id="forum-body" name="body" rows="9" maxlength="10000" required><?= \Portal\Html::escape(is_string($_POST['body']??null)?$_POST['body']:'') ?></textarea>
    <label for="forum-visibility">Who can read and reply?</label>
    <select class="form-select" id="forum-visibility" name="visibility"><option value="open">Everyone with forum access</option><option value="private" <?= ($_POST['visibility']??'')==='private'?'selected':'' ?>>Only added members, you and moderators</option></select>
    <p class="text-muted">You can add members after creating a private discussion. Visibility stays fixed after creation.</p>
    <div><button class="btn btn-primary" type="submit">Create discussion</button></div>
</form>
