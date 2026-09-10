<div class="alert alert-<?= \Portal\Html::escape($message['type']) ?>" role="status">
    <?= nl2br(\Portal\Html::escape($message['text'])) ?>
</div>
