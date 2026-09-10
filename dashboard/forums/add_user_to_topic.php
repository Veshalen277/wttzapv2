<?php
if (isset($_GET['topic_id']) || isset($_GET['id'])) {
    $forumView='thread';
} else {
    $forumView='feed';
    $forumMemberHint=true;
}
require __DIR__.'/controller.php';
