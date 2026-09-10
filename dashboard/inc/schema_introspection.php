<?php

function get_schema_summary(mysqli $con, int $limit = 20): array {
    $limit = max(10, min((int)$limit, 200));

    $sql = "SELECT table_name,
                   COALESCE(table_rows,0) AS rows_est,
                   COALESCE(update_time,'') AS update_time
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
            ORDER BY table_rows DESC
            LIMIT $limit";

    $res = mysqli_query($con, $sql);
    if (!$res) return [];

    $out = [];
    while ($row = mysqli_fetch_assoc($res)) $out[] = $row;
    return $out;
}

function get_foreign_keys(mysqli $con): array {
    $sql = "SELECT constraint_name,
                   table_name AS child_table,
                   column_name AS child_column,
                   referenced_table_name AS parent_table,
                   referenced_column_name AS parent_column
            FROM information_schema.key_column_usage
            WHERE table_schema = DATABASE()
              AND referenced_table_name IS NOT NULL
            ORDER BY table_name, constraint_name";

    $res = mysqli_query($con, $sql);
    if (!$res) return [];

    $out = [];
    while ($row = mysqli_fetch_assoc($res)) $out[] = $row;
    return $out;
}

/**
 * Computes orphan counts per FK constraint.
 * NOTE: Only works if FK constraints actually exist in the live DB.
 */
function get_fk_orphan_summary(mysqli $con, int $limit = 12): array {
    $limit = max(5, min((int)$limit, 50));

    $fks = get_foreign_keys($con);
    if (!$fks) return [];

    $results = [];

    foreach ($fks as $fk) {
        $child_table = $fk['child_table'];
        $child_col   = $fk['child_column'];
        $parent_table = $fk['parent_table'];
        $parent_col   = $fk['parent_column'];

        // Basic identifier validation (defense-in-depth)
        foreach ([$child_table,$child_col,$parent_table,$parent_col] as $ident) {
            if (!preg_match('/^[A-Za-z0-9_]+$/', $ident)) {
                continue 2;
            }
        }

        $sql = "SELECT COUNT(*) AS c
                FROM `$child_table` c
                LEFT JOIN `$parent_table` p
                  ON p.`$parent_col` = c.`$child_col`
                WHERE c.`$child_col` IS NOT NULL
                  AND p.`$parent_col` IS NULL";

        $res = mysqli_query($con, $sql);
        $count = 0;
        if ($res && ($row = mysqli_fetch_assoc($res))) $count = (int)$row['c'];

        $results[] = [
            'constraint' => $fk['constraint_name'],
            'child_table' => $child_table,
            'orphans' => $count,
        ];
    }

    usort($results, fn($a,$b) => ($b['orphans'] <=> $a['orphans']));
    return array_slice($results, 0, $limit);
}
