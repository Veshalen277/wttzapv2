<?php
require __DIR__.'/../app/autoload.php';
\Portal\Auth::requireUser();
header('Location: /dashboard/messages/message_board.php#compose');exit;
