<?php

function render_data_quality_cards(array $items): void {
    echo '<div class="row g-2">';
    foreach ($items as $it) {
        $label = htmlspecialchars($it['label'] ?? '');
        $value = htmlspecialchars((string)($it['value'] ?? ''));
        $hint  = htmlspecialchars($it['hint'] ?? '');

        echo '<div class="col-6">';
        echo '  <div class="border rounded p-2 bg-white">';
        echo '      <div class="small text-muted fw-bold text-uppercase">'.$label.'</div>';
        echo '      <div class="h6 mb-0 fw-bold">'.$value.'</div>';
        echo '      <div class="text-muted" style="font-size:0.7rem;">'.$hint.'</div>';
        echo '  </div>';
        echo '</div>';
    }
    echo '</div>';
}

function render_ranking_table(array $rows, string $title, string $count_label = 'Count'): void {
    echo '<div class="bg-white border shadow-sm rounded mb-3">';
    echo '  <div class="p-3 border-bottom bg-light">';
    echo '    <h6 class="mb-0 fw-bold small text-uppercase">'.htmlspecialchars($title).'</h6>';
    echo '  </div>';
    echo '  <div class="table-responsive">';
    echo '    <table class="table table-sm table-hover mb-0" style="font-size:0.75rem;">';
    echo '      <thead class="table-light"><tr><th class="ps-3">User</th><th>Role</th><th class="text-end pe-3">'.htmlspecialchars($count_label).'</th></tr></thead>';
    echo '      <tbody>';

    if (!$rows) {
        echo '<tr><td class="ps-3 text-muted" colspan="3">No data</td></tr>';
    } else {
        foreach ($rows as $r) {
            echo '<tr>';
            echo '  <td class="ps-3 fw-bold">'.htmlspecialchars($r['fullname'] ?? '').'</td>';
            echo '  <td class="text-muted">'.htmlspecialchars((string)($r['user_role'] ?? '')).'</td>';
            echo '  <td class="text-end pe-3 fw-bold">'.number_format((int)($r['cnt'] ?? 0)).'</td>';
            echo '</tr>';
        }
    }

    echo '      </tbody>';
    echo '    </table>';
    echo '  </div>';
    echo '</div>';
}
