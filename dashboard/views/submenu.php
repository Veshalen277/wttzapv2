<?php foreach ($items as $item): ?>
    <?php if (isset($item['submenu'])): ?>
        <li class="portal-subgroup"><span><?= \Portal\Html::escape($item['title']) ?></span><ul>
            <?php \Portal\Html::view('submenu', ['items' => $item['submenu'], 'currentPage' => $currentPage]); ?>
        </ul></li>
    <?php else: $isActive = parse_url($item['link'], PHP_URL_PATH) === $currentPage; ?>
        <li><a class="portal-submenu-link<?= $isActive ? ' is-active' : '' ?>"
               href="<?= \Portal\Html::escape($item['link']) ?>"<?= $isActive ? ' aria-current="page"' : '' ?>><?= \Portal\Html::escape($item['title']) ?></a></li>
    <?php endif; ?>
<?php endforeach; ?>
