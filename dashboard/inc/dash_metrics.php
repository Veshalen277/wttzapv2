<?php
/**
 * User-centric metrics module
 * Tables supported (current scope): users_tbl, user_logins, work_tbl
 * Engine: MariaDB/MySQL (information_schema available)
 */

function ds_table_exists(mysqli $con, string $table): bool {
    $sql = "SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1";
    $stmt = mysqli_prepare($con, $sql);
    if (!$stmt) return false;
    mysqli_stmt_bind_param($stmt, "s", $table);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    return (bool)($res && mysqli_fetch_row($res));
}

function ds_safe_limit(int $limit, int $min = 5, int $max = 100): int {
    $limit = (int)$limit;
    if ($limit < $min) return $min;
    if ($limit > $max) return $max;
    return $limit;
}

/**
 * 1) Recent logins (user interaction)
 * Returns: [ ['fullname'=>..., 'user_role'=>..., 'login_time'=>...], ... ]
 */
function get_recent_logins(mysqli $con, int $limit = 20): array {
    if (!ds_table_exists($con, 'user_logins') || !ds_table_exists($con, 'users_tbl')) return [];
    $limit = ds_safe_limit($limit, 5, 200);

    // LIMIT must be an integer literal; casted above.
    $sql = "SELECT u.fullname, u.user_role, l.login_time
            FROM user_logins l
            INNER JOIN users_tbl u ON u.id = l.user_id
            ORDER BY l.login_time DESC
            LIMIT $limit";

    $res = mysqli_query($con, $sql);
    if (!$res) return [];

    $out = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $out[] = $row;
    }
    return $out;
}

/**
 * 2) User KPI block (current user)
 * Focus: interaction counts + recency
 */
function get_user_kpis(mysqli $con, int $user_id, string $month_start, string $month_end): array {
    $user_id = (int)$user_id;
    $k = [
        'my_last_login' => null,
        'my_logins_mtd' => 0,
        'my_work_mtd' => 0,
        'my_work_7d' => 0,
        'my_last_work' => null,
    ];

    if ($user_id <= 0 || !ds_table_exists($con, 'users_tbl')) return $k;

    // Last login + logins MTD
    if (ds_table_exists($con, 'user_logins')) {
        $sql_last = "SELECT MAX(login_time) AS last_login FROM user_logins WHERE user_id = ?";
        $stmt = mysqli_prepare($con, $sql_last);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: [];
            $k['my_last_login'] = $row['last_login'] ?? null;
        }

        $sql_cnt = "SELECT COUNT(*) AS c FROM user_logins WHERE user_id = ? AND login_time BETWEEN ? AND ?";
        $stmt = mysqli_prepare($con, $sql_cnt);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "iss", $user_id, $month_start, $month_end);
            mysqli_stmt_execute($stmt);
            $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: [];
            $k['my_logins_mtd'] = (int)($row['c'] ?? 0);
        }
    }

    // work_tbl uses employee_id (NOT user_id)
    if (ds_table_exists($con, 'work_tbl')) {
        $sql_w_last = "SELECT MAX(work_date) AS last_work FROM work_tbl WHERE employee_id = ?";
        $stmt = mysqli_prepare($con, $sql_w_last);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: [];
            $k['my_last_work'] = $row['last_work'] ?? null;
        }

        $sql_w_mtd = "SELECT COUNT(*) AS c FROM work_tbl WHERE employee_id = ? AND work_date BETWEEN ? AND ?";
        $stmt = mysqli_prepare($con, $sql_w_mtd);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "iss", $user_id, $month_start, $month_end);
            mysqli_stmt_execute($stmt);
            $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: [];
            $k['my_work_mtd'] = (int)($row['c'] ?? 0);
        }

        $sql_w_7d = "SELECT COUNT(*) AS c FROM work_tbl WHERE employee_id = ? AND work_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        $stmt = mysqli_prepare($con, $sql_w_7d);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: [];
            $k['my_work_7d'] = (int)($row['c'] ?? 0);
        }
    }

    return $k;
}

/**
 * 3) Unified user activity feed
 * - LOGIN events from user_logins
 * - WORK events from work_tbl
 * Returns rows with: event_time, fullname, user_role, event_type, detail
 */
function get_user_activity_feed(mysqli $con, int $limit = 30): array {
    if (!ds_table_exists($con, 'users_tbl')) return [];
    $limit = ds_safe_limit($limit, 10, 200);

    $parts = [];

    if (ds_table_exists($con, 'user_logins')) {
        $parts[] = "(
            SELECT l.login_time AS event_time,
                   u.id AS user_id,
                   u.fullname,
                   u.user_role,
                   'LOGIN' AS event_type,
                   '' AS detail
            FROM user_logins l
            INNER JOIN users_tbl u ON u.id = l.user_id
        )";
    }

    if (ds_table_exists($con, 'work_tbl')) {
        // work_desc can be long; keep it short for feed.
        $parts[] = "(
            SELECT w.work_date AS event_time,
                   u.id AS user_id,
                   u.fullname,
                   u.user_role,
                   'WORK' AS event_type,
                   LEFT(COALESCE(w.work_desc,''), 120) AS detail
            FROM work_tbl w
            INNER JOIN users_tbl u ON u.id = w.employee_id
            WHERE w.work_date IS NOT NULL
        )";
    }

    if (!$parts) return [];

    $sql = "SELECT * FROM (" . implode(" UNION ALL ", $parts) . ") x
            ORDER BY x.event_time DESC
            LIMIT $limit";

    $res = mysqli_query($con, $sql);
    if (!$res) return [];

    $out = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $out[] = $row;
    }
    return $out;
}

/**
 * 4) Team rankings (user interaction focus)
 */
function get_team_rankings(mysqli $con, string $start, string $end, int $limit = 10): array {
    $limit = ds_safe_limit($limit, 5, 50);

    $out = [
        'top_logins' => [],
        'top_work' => [],
        'inactive_14d' => [],
    ];

    if (!ds_table_exists($con, 'users_tbl')) return $out;

    if (ds_table_exists($con, 'user_logins')) {
        $sql = "SELECT u.id, u.fullname, u.user_role, COUNT(*) AS cnt
                FROM user_logins l
                INNER JOIN users_tbl u ON u.id = l.user_id
                WHERE l.login_time BETWEEN ? AND ?
                GROUP BY u.id, u.fullname, u.user_role
                ORDER BY cnt DESC
                LIMIT $limit";
        $stmt = mysqli_prepare($con, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ss", $start, $end);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            while ($res && ($row = mysqli_fetch_assoc($res))) $out['top_logins'][] = $row;
        }

        // Inactive users: no login in last 14 days (but exist in users_tbl)
        $sql = "SELECT u.id, u.fullname, u.user_role
                FROM users_tbl u
                LEFT JOIN (
                    SELECT user_id, MAX(login_time) AS last_login
                    FROM user_logins
                    GROUP BY user_id
                ) l ON l.user_id = u.id
                WHERE (l.last_login IS NULL OR l.last_login < DATE_SUB(NOW(), INTERVAL 14 DAY))
                ORDER BY l.last_login IS NULL DESC, l.last_login ASC
                LIMIT $limit";
        $res = mysqli_query($con, $sql);
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) $out['inactive_14d'][] = $row;
        }
    }

    if (ds_table_exists($con, 'work_tbl')) {
        $sql = "SELECT u.id, u.fullname, u.user_role, COUNT(*) AS cnt
                FROM work_tbl w
                INNER JOIN users_tbl u ON u.id = w.employee_id
                WHERE w.work_date BETWEEN ? AND ?
                GROUP BY u.id, u.fullname, u.user_role
                ORDER BY cnt DESC
                LIMIT $limit";
        $stmt = mysqli_prepare($con, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ss", $start, $end);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            while ($res && ($row = mysqli_fetch_assoc($res))) $out['top_work'][] = $row;
        }
    }

    return $out;
}

/**
 * 5) Data quality overview (user-centric)
 * - missing emails, missing date_started, leave balances anomalies
 * Returns array suitable for render_data_quality_cards()
 */
function get_user_data_quality(mysqli $con, int $limit = 10): array {
    if (!ds_table_exists($con, 'users_tbl')) return [];

    $cards = [];

    // Total users
    $res = mysqli_query($con, "SELECT COUNT(*) AS c FROM users_tbl");
    $total = ($res && ($r = mysqli_fetch_assoc($res))) ? (int)$r['c'] : 0;
    $cards[] = ['label' => 'Users', 'value' => number_format($total), 'hint' => 'Total in users_tbl'];

    // Missing email
    $res = mysqli_query($con, "SELECT COUNT(*) AS c FROM users_tbl WHERE COALESCE(email,'') = ''");
    $c = ($res && ($r = mysqli_fetch_assoc($res))) ? (int)$r['c'] : 0;
    $cards[] = ['label' => 'Missing email', 'value' => number_format($c), 'hint' => 'Email required for comms'];

    // Duplicate emails (case-insensitive)
    $sql = "SELECT COUNT(*) AS c FROM (
                SELECT LOWER(email) e
                FROM users_tbl
                WHERE COALESCE(email,'') <> ''
                GROUP BY LOWER(email)
                HAVING COUNT(*) > 1
            ) x";
    $res = mysqli_query($con, $sql);
    $c = ($res && ($r = mysqli_fetch_assoc($res))) ? (int)$r['c'] : 0;
    $cards[] = ['label' => 'Duplicate emails', 'value' => number_format($c), 'hint' => 'Potential account conflicts'];

    // Missing date_started
    $res = mysqli_query($con, "SELECT COUNT(*) AS c FROM users_tbl WHERE date_started IS NULL");
    $c = ($res && ($r = mysqli_fetch_assoc($res))) ? (int)$r['c'] : 0;
    $cards[] = ['label' => 'Missing start date', 'value' => number_format($c), 'hint' => 'Affects tenure stats'];

    // Leave anomalies
    $res = mysqli_query($con, "SELECT COUNT(*) AS c FROM users_tbl WHERE leave_balances < 0");
    $c = ($res && ($r = mysqli_fetch_assoc($res))) ? (int)$r['c'] : 0;
    $cards[] = ['label' => 'Negative leave', 'value' => number_format($c), 'hint' => 'Investigate leave updates'];

    return array_slice($cards, 0, ds_safe_limit($limit, 4, 12));
}
