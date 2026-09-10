<?php
return [
    'title' => '__MODULE_TITLE__',
    'enabled' => false, // Build first, then enable intentionally.
    'roles' => [7], // Integer role IDs. Checked on the endpoint as well as in the menu.
    'pages' => [
        ['title' => 'Overview', 'file' => 'index.php'],
    ],
];
