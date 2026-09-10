<?php
if (!function_exists('stats_h')) {
    function stats_h($value): string {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('stats_table_exists')) {
    function stats_table_exists(mysqli $con, string $table): bool {
        static $cache = [];
        if (isset($cache[$table])) {
            return $cache[$table];
        }

        $safe = mysqli_real_escape_string($con, $table);
        $res = mysqli_query($con, "SHOW TABLES LIKE '{$safe}'");
        $cache[$table] = ($res && mysqli_num_rows($res) > 0);
        return $cache[$table];
    }
}

if (!function_exists('stats_get_columns')) {
    function stats_get_columns(mysqli $con, string $table): array {
        static $cache = [];
        if (isset($cache[$table])) {
            return $cache[$table];
        }

        $cols = [];
        $safeTable = '`' . str_replace('`', '``', $table) . '`';
        $res = mysqli_query($con, "SHOW COLUMNS FROM {$safeTable}");
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $cols[] = $row['Field'];
            }
        }
        $cache[$table] = $cols;
        return $cols;
    }
}

if (!function_exists('stats_has_column')) {
    function stats_has_column(mysqli $con, string $table, string $column): bool {
        return in_array($column, stats_get_columns($con, $table), true);
    }
}

if (!function_exists('stats_qv')) {
    function stats_qv(mysqli $con, string $sql, $default = 0) {
        $res = mysqli_query($con, $sql);
        if ($res && $row = mysqli_fetch_row($res)) {
            return $row[0] ?? $default;
        }
        return $default;
    }
}

if (!function_exists('stats_fetch_all')) {
    function stats_fetch_all(mysqli_result $res): array {
        $rows = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $rows[] = $row;
        }
        return $rows;
    }
}

if (!function_exists('stats_date_range')) {
    function stats_date_range(array $input): array {
        $range = $input['range'] ?? 'month';
        $today = date('Y-m-d');

        switch ($range) {
            case 'today':
                $from = $today;
                $to = $today;
                break;

            case 'week':
                $from = date('Y-m-d', strtotime('monday this week'));
                $to = $today;
                break;

            case 'year':
                $from = date('Y-01-01');
                $to = $today;
                break;

            case 'custom':
                $from = !empty($input['from']) ? $input['from'] : date('Y-m-01');
                $to   = !empty($input['to']) ? $input['to'] : $today;
                break;

            case 'month':
            default:
                $from = date('Y-m-01');
                $to = $today;
                $range = 'month';
                break;
        }

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to, $range];
    }
}

if (!function_exists('stats_date_clause')) {
    function stats_date_clause(mysqli $con, string $table, string $column, string $from, string $to): string {
        if (!stats_table_exists($con, $table) || !stats_has_column($con, $table, $column)) {
            return '';
        }
        $fromSafe = mysqli_real_escape_string($con, $from);
        $toSafe = mysqli_real_escape_string($con, $to);
        return " AND DATE(`{$column}`) BETWEEN '{$fromSafe}' AND '{$toSafe}' ";
    }
}

if (!function_exists('stats_count')) {
    function stats_count(mysqli $con, string $table, string $userCol, int $userId, ?string $dateCol = null, ?string $from = null, ?string $to = null): int {
        if (!stats_table_exists($con, $table) || !stats_has_column($con, $table, $userCol)) {
            return 0;
        }

        $sql = "SELECT COUNT(*) FROM `{$table}` WHERE `{$userCol}` = {$userId}";
        if ($dateCol && $from && $to) {
            $sql .= stats_date_clause($con, $table, $dateCol, $from, $to);
        }

        return (int) stats_qv($con, $sql, 0);
    }
}

if (!function_exists('stats_sum')) {
    function stats_sum(mysqli $con, string $table, string $sumCol, string $userCol, int $userId, ?string $dateCol = null, ?string $from = null, ?string $to = null): float {
        if (
            !stats_table_exists($con, $table) ||
            !stats_has_column($con, $table, $sumCol) ||
            !stats_has_column($con, $table, $userCol)
        ) {
            return 0;
        }

        $sql = "SELECT COALESCE(SUM(`{$sumCol}`),0) FROM `{$table}` WHERE `{$userCol}` = {$userId}";
        if ($dateCol && $from && $to) {
            $sql .= stats_date_clause($con, $table, $dateCol, $from, $to);
        }

        return (float) stats_qv($con, $sql, 0);
    }
}

if (!function_exists('stats_min')) {
    function stats_min(mysqli $con, string $table, string $col, string $userCol, int $userId, ?string $dateCol = null, ?string $from = null, ?string $to = null): string {
        if (
            !stats_table_exists($con, $table) ||
            !stats_has_column($con, $table, $col) ||
            !stats_has_column($con, $table, $userCol)
        ) {
            return '';
        }

        $sql = "SELECT MIN(`{$col}`) FROM `{$table}` WHERE `{$userCol}` = {$userId}";
        if ($dateCol && $from && $to) {
            $sql .= stats_date_clause($con, $table, $dateCol, $from, $to);
        }

        return (string) stats_qv($con, $sql, '');
    }
}

if (!function_exists('stats_max')) {
    function stats_max(mysqli $con, string $table, string $col, string $userCol, int $userId, ?string $dateCol = null, ?string $from = null, ?string $to = null): string {
        if (
            !stats_table_exists($con, $table) ||
            !stats_has_column($con, $table, $col) ||
            !stats_has_column($con, $table, $userCol)
        ) {
            return '';
        }

        $sql = "SELECT MAX(`{$col}`) FROM `{$table}` WHERE `{$userCol}` = {$userId}";
        if ($dateCol && $from && $to) {
            $sql .= stats_date_clause($con, $table, $dateCol, $from, $to);
        }

        return (string) stats_qv($con, $sql, '');
    }
}

if (!function_exists('stats_collect_dates_for_user')) {
    function stats_collect_dates_for_user(mysqli $con, int $userId, string $from, string $to): array {
        $dates = [];

        $sources = [
            ['table' => 'reports', 'user_col' => 'user_id', 'date_col' => 'report_date'],
            ['table' => 'mauritius_reports', 'user_col' => 'user_id', 'date_col' => 'report_date'],
            ['table' => 'work_tbl', 'user_col' => 'employee_id', 'date_col' => stats_has_column($con, 'work_tbl', 'work_date') ? 'work_date' : (stats_has_column($con, 'work_tbl', 'created_at') ? 'created_at' : '')],
        ];

        foreach ($sources as $src) {
            if (empty($src['date_col'])) {
                continue;
            }
            if (!stats_table_exists($con, $src['table']) || !stats_has_column($con, $src['table'], $src['user_col']) || !stats_has_column($con, $src['table'], $src['date_col'])) {
                continue;
            }

            $fromSafe = mysqli_real_escape_string($con, $from);
            $toSafe = mysqli_real_escape_string($con, $to);

            $sql = "
                SELECT DISTINCT DATE(`{$src['date_col']}`) AS d
                FROM `{$src['table']}`
                WHERE `{$src['user_col']}` = {$userId}
                  AND DATE(`{$src['date_col']}`) BETWEEN '{$fromSafe}' AND '{$toSafe}'
            ";
            $res = mysqli_query($con, $sql);
            if ($res) {
                while ($row = mysqli_fetch_assoc($res)) {
                    if (!empty($row['d'])) {
                        $dates[] = $row['d'];
                    }
                }
            }
        }

        $dates = array_values(array_unique(array_filter($dates)));
        sort($dates);
        return $dates;
    }
}

if (!function_exists('stats_calculate_streak')) {
    function stats_calculate_streak(array $dates): array {
        $dates = array_values(array_unique(array_filter($dates)));
        sort($dates);

        if (!$dates) {
            return [
                'distinct_days'  => 0,
                'longest_streak' => 0,
                'current_streak' => 0,
                'first_date'     => '',
                'last_date'      => '',
                'gaps'           => 0,
            ];
        }

        $longest = 1;
        $current = 1;
        $gaps = 0;

        for ($i = 1; $i < count($dates); $i++) {
            $prev = new DateTimeImmutable($dates[$i - 1]);
            $curr = new DateTimeImmutable($dates[$i]);
            $diff = (int) $prev->diff($curr)->format('%a');

            if ($diff === 1) {
                $current++;
            } else {
                if ($diff > 1) {
                    $gaps += ($diff - 1);
                }
                $current = 1;
            }

            if ($current > $longest) {
                $longest = $current;
            }
        }

        $currentStreak = 1;
        for ($i = count($dates) - 1; $i > 0; $i--) {
            $prev = new DateTimeImmutable($dates[$i - 1]);
            $curr = new DateTimeImmutable($dates[$i]);
            $diff = (int) $prev->diff($curr)->format('%a');
            if ($diff === 1) {
                $currentStreak++;
            } else {
                break;
            }
        }

        return [
            'distinct_days'  => count($dates),
            'longest_streak' => $longest,
            'current_streak' => $currentStreak,
            'first_date'     => $dates[0] ?? '',
            'last_date'      => end($dates) ?: '',
            'gaps'           => $gaps,
        ];
    }
}

if (!function_exists('stats_domain_field')) {
    function stats_domain_field(mysqli $con): string {
        if (stats_has_column($con, 'users_tbl', 'user_scale')) {
            return 'user_scale';
        }
        if (stats_has_column($con, 'users_tbl', 'department')) {
            return 'department';
        }
        return 'user_scale';
    }
}

if (!function_exists('stats_build_user_stats')) {
    function stats_build_user_stats(mysqli $con, array $user, string $from, string $to): array {
        $uid = (int)($user['id'] ?? 0);

        $loginDateCol = stats_has_column($con, 'user_logins', 'login_time') ? 'login_time' : (stats_has_column($con, 'user_logins', 'created_at') ? 'created_at' : null);
        $workDateCol = stats_has_column($con, 'work_tbl', 'work_date') ? 'work_date' : (stats_has_column($con, 'work_tbl', 'created_at') ? 'created_at' : null);

        $logins_count = $loginDateCol ? stats_count($con, 'user_logins', 'user_id', $uid, $loginDateCol, $from, $to) : 0;
        $first_login = $loginDateCol ? stats_min($con, 'user_logins', $loginDateCol, 'user_id', $uid, $loginDateCol, $from, $to) : '';
        $last_login  = $loginDateCol ? stats_max($con, 'user_logins', $loginDateCol, 'user_id', $uid, $loginDateCol, $from, $to) : '';

        $reports_count = stats_count($con, 'reports', 'user_id', $uid, 'report_date', $from, $to);
        $mauritius_reports_count = stats_count($con, 'mauritius_reports', 'user_id', $uid, 'report_date', $from, $to);
        $work_count = $workDateCol ? stats_count($con, 'work_tbl', 'employee_id', $uid, $workDateCol, $from, $to) : stats_count($con, 'work_tbl', 'employee_id', $uid);

        $leave_count = stats_count($con, 'leave_applications', 'user_id', $uid, 'start_date', $from, $to);
        $approved_leave = (int) stats_qv($con, "SELECT COUNT(*) FROM `leave_applications` WHERE `user_id` = {$uid} AND `status` = 'Approved' " . stats_date_clause($con, 'leave_applications', 'start_date', $from, $to), 0);
        $pending_leave = (int) stats_qv($con, "SELECT COUNT(*) FROM `leave_applications` WHERE `user_id` = {$uid} AND `status` = 'Pending' " . stats_date_clause($con, 'leave_applications', 'start_date', $from, $to), 0);
        $disapproved_leave = (int) stats_qv($con, "SELECT COUNT(*) FROM `leave_applications` WHERE `user_id` = {$uid} AND `status` = 'Disapproved' " . stats_date_clause($con, 'leave_applications', 'start_date', $from, $to), 0);

        $reports_income = stats_sum($con, 'reports', 'total_income', 'user_id', $uid, 'report_date', $from, $to);
        $reports_expenses = stats_sum($con, 'reports', 'total_expenses', 'user_id', $uid, 'report_date', $from, $to);
        $reports_net = stats_sum($con, 'reports', 'net_total', 'user_id', $uid, 'report_date', $from, $to);

        $mru_income = stats_sum($con, 'mauritius_reports', 'total_income', 'user_id', $uid, 'report_date', $from, $to);
        $mru_expenses = stats_sum($con, 'mauritius_reports', 'total_expenses', 'user_id', $uid, 'report_date', $from, $to);
        $mru_net = stats_sum($con, 'mauritius_reports', 'net_total', 'user_id', $uid, 'report_date', $from, $to);

        $manager_reports = stats_count($con, 'manager_report_tbl', 'manager_id', $uid, 'report_date', $from, $to);
        $adv_tasks = stats_count($con, 'adv_user_tasks_tbl', 'user_id', $uid, stats_has_column($con, 'adv_user_tasks_tbl', 'created_at') ? 'created_at' : null, $from, $to);
        $checklist_items = stats_count($con, 'checklist_items', 'user_id', $uid, stats_has_column($con, 'checklist_items', 'created_at') ? 'created_at' : null, $from, $to);
        $checklist_done = (int) stats_qv($con, "SELECT COUNT(*) FROM `checklist_items` WHERE `user_id` = {$uid} AND `checked` = 1", 0);

        $ack_count = stats_count($con, 'acknowledgments', 'user_id', $uid, stats_has_column($con, 'acknowledgments', 'ack_date') ? 'ack_date' : null, $from, $to);
        $bulletin_reads = stats_count($con, 'bulletin_reads', 'user_id', $uid, stats_has_column($con, 'bulletin_reads', 'read_at') ? 'read_at' : null, $from, $to);
        $notifications = stats_count($con, 'notifications', 'user_id', $uid, stats_has_column($con, 'notifications', 'created_at') ? 'created_at' : null, $from, $to);
        $unread_notifications = (int) stats_qv($con, "SELECT COUNT(*) FROM `notifications` WHERE `user_id` = {$uid} AND `is_read` = 0", 0);
        $policies = stats_count($con, 'policies', 'user_id', $uid, stats_has_column($con, 'policies', 'upload_date') ? 'upload_date' : null, $from, $to);

        $messages_posted = stats_count($con, 'messages', 'user_id', $uid, stats_has_column($con, 'messages', 'posted_at') ? 'posted_at' : null, $from, $to);
        $replies_posted = stats_count($con, 'replies', 'user_id', $uid, stats_has_column($con, 'replies', 'replied_at') ? 'replied_at' : null, $from, $to);
        $posts_created = stats_count($con, 'posts', 'user_id', $uid, stats_has_column($con, 'posts', 'created_at') ? 'created_at' : null, $from, $to);
        $post_reactions = stats_count($con, 'post_reactions', 'user_id', $uid, stats_has_column($con, 'post_reactions', 'created_at') ? 'created_at' : null, $from, $to);
        $item_reactions = stats_count($con, 'item_reactions', 'user_id', $uid, stats_has_column($con, 'item_reactions', 'created_at') ? 'created_at' : null, $from, $to);

        $projects_assigned = stats_count($con, 'project_users_tbl', 'user_id', $uid, stats_has_column($con, 'project_users_tbl', 'date_assigned') ? 'date_assigned' : null, $from, $to);
        $projects_assigned_by = stats_count($con, 'project_users_tbl', 'assigned_by', $uid, stats_has_column($con, 'project_users_tbl', 'date_assigned') ? 'date_assigned' : null, $from, $to);
        $projects_created = stats_count($con, 'projects_tbl', 'created_by', $uid, stats_has_column($con, 'projects_tbl', 'created_at') ? 'created_at' : null, $from, $to);

        $group_memberships = stats_count($con, 'group_members', 'user_id', $uid);
        $groups_created = stats_count($con, 'groups', 'created_by', $uid, stats_has_column($con, 'groups', 'created_at') ? 'created_at' : null, $from, $to);
        $group_messages = stats_count($con, 'group_messages', 'sender_id', $uid, stats_has_column($con, 'group_messages', 'sent_at') ? 'sent_at' : null, $from, $to);

        $pm_sent = stats_count($con, 'private_messages', 'sender_id', $uid, stats_has_column($con, 'private_messages', 'sent_at') ? 'sent_at' : null, $from, $to);
        $pm_received = stats_count($con, 'private_messages', 'receiver_id', $uid, stats_has_column($con, 'private_messages', 'sent_at') ? 'sent_at' : null, $from, $to);

        $password_resets = stats_count($con, 'password_resets', 'user_id', $uid, stats_has_column($con, 'password_resets', 'created_at') ? 'created_at' : null, $from, $to);

        $dates = stats_collect_dates_for_user($con, $uid, $from, $to);
        $streak = stats_calculate_streak($dates);

        $overall_score =
            ($work_count * 2) +
            ($reports_count * 3) +
            ($mauritius_reports_count * 3) +
            ($adv_tasks * 1) +
            ($checklist_done * 1) +
            ($manager_reports * 2) +
            ($streak['distinct_days'] * 2) +
            ($streak['longest_streak'] * 3) +
            ($messages_posted * 1) +
            ($replies_posted * 1);

        $image_path = !empty($user['profile_photo']) ? '/dashboard/employee/' . $user['profile_photo'] : '';

        return [
            'user' => $user,
            'image_path' => $image_path,
            'domain' => $user[stats_domain_field($con)] ?? '',

            'logins_count' => $logins_count,
            'first_login' => $first_login,
            'last_login' => $last_login,

            'reports_count' => $reports_count,
            'mauritius_reports_count' => $mauritius_reports_count,
            'work_count' => $work_count,

            'leave_count' => $leave_count,
            'approved_leave' => $approved_leave,
            'pending_leave' => $pending_leave,
            'disapproved_leave' => $disapproved_leave,

            'reports_income' => $reports_income,
            'reports_expenses' => $reports_expenses,
            'reports_net' => $reports_net,

            'mru_income' => $mru_income,
            'mru_expenses' => $mru_expenses,
            'mru_net' => $mru_net,

            'combined_cashups' => $reports_count + $mauritius_reports_count,
            'combined_income' => $reports_income + $mru_income,
            'combined_expenses' => $reports_expenses + $mru_expenses,
            'combined_net' => $reports_net + $mru_net,

            'manager_reports' => $manager_reports,
            'adv_tasks' => $adv_tasks,
            'checklist_items' => $checklist_items,
            'checklist_done' => $checklist_done,

            'ack_count' => $ack_count,
            'bulletin_reads' => $bulletin_reads,
            'notifications' => $notifications,
            'unread_notifications' => $unread_notifications,
            'policies' => $policies,

            'messages_posted' => $messages_posted,
            'replies_posted' => $replies_posted,
            'posts_created' => $posts_created,
            'post_reactions' => $post_reactions,
            'item_reactions' => $item_reactions,

            'projects_assigned' => $projects_assigned,
            'projects_assigned_by' => $projects_assigned_by,
            'projects_created' => $projects_created,

            'group_memberships' => $group_memberships,
            'groups_created' => $groups_created,
            'group_messages' => $group_messages,

            'pm_sent' => $pm_sent,
            'pm_received' => $pm_received,
            'password_resets' => $password_resets,

            'distinct_days' => $streak['distinct_days'],
            'longest_streak' => $streak['longest_streak'],
            'current_streak' => $streak['current_streak'],
            'first_report_date' => $streak['first_date'],
            'last_report_date' => $streak['last_date'],
            'report_gaps' => $streak['gaps'],

            'overall_score' => $overall_score,
        ];
    }
}

if (!function_exists('stats_get_users')) {
    function stats_get_users(mysqli $con, array $filters = [], ?int $limit = null, int $offset = 0): array {
        $where = [];

        if (!empty($filters['search_user'])) {
            $safe = mysqli_real_escape_string($con, $filters['search_user']);
            $where[] = "(fullname LIKE '%{$safe}%' OR id LIKE '%{$safe}%')";
        }
        if (!empty($filters['department'])) {
            $safe = mysqli_real_escape_string($con, $filters['department']);
            $domainField = stats_domain_field($con);
            $where[] = "`{$domainField}` LIKE '%{$safe}%'";
        }
        if (!empty($filters['email'])) {
            $safe = mysqli_real_escape_string($con, $filters['email']);
            $where[] = "email LIKE '%{$safe}%'";
        }
        if (!empty($filters['user_id'])) {
            $uid = (int)$filters['user_id'];
            $where[] = "id = {$uid}";
        }

        $whereSql = $where ? (' WHERE ' . implode(' AND ', $where)) : '';
        $sql = "SELECT * FROM `users_tbl` {$whereSql} ORDER BY `date_started` DESC";

        if ($limit !== null) {
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
        }

        $res = mysqli_query($con, $sql);
        return $res ? stats_fetch_all($res) : [];
    }
}

if (!function_exists('stats_count_users')) {
    function stats_count_users(mysqli $con, array $filters = []): int {
        $where = [];

        if (!empty($filters['search_user'])) {
            $safe = mysqli_real_escape_string($con, $filters['search_user']);
            $where[] = "(fullname LIKE '%{$safe}%' OR id LIKE '%{$safe}%')";
        }
        if (!empty($filters['department'])) {
            $safe = mysqli_real_escape_string($con, $filters['department']);
            $domainField = stats_domain_field($con);
            $where[] = "`{$domainField}` LIKE '%{$safe}%'";
        }
        if (!empty($filters['email'])) {
            $safe = mysqli_real_escape_string($con, $filters['email']);
            $where[] = "email LIKE '%{$safe}%'";
        }
        if (!empty($filters['user_id'])) {
            $uid = (int)$filters['user_id'];
            $where[] = "id = {$uid}";
        }

        $whereSql = $where ? (' WHERE ' . implode(' AND ', $where)) : '';
        return (int) stats_qv($con, "SELECT COUNT(*) FROM `users_tbl` {$whereSql}", 0);
    }
}

if (!function_exists('stats_build_ranking')) {
    function stats_build_ranking(mysqli $con, string $from, string $to): array {
        $users = stats_get_users($con);
        $ranked = [];

        foreach ($users as $user) {
            $stats = stats_build_user_stats($con, $user, $from, $to);
            $domain = trim((string)($stats['domain'] ?? ''));
            if ($domain === '') {
                $domain = 'Unassigned';
            }
            $ranked[$domain][] = $stats;
        }

        foreach ($ranked as $domain => $items) {
            usort($items, function ($a, $b) {
                return $b['overall_score'] <=> $a['overall_score'];
            });

            foreach ($items as $i => $item) {
                $items[$i]['rank_in_domain'] = $i + 1;
            }

            $ranked[$domain] = $items;
        }

        ksort($ranked);
        return $ranked;
    }
}

if (!function_exists('stats_find_user_rank')) {
    function stats_find_user_rank(array $rankedDomains, int $userId): array {
        foreach ($rankedDomains as $domain => $items) {
            foreach ($items as $item) {
                if ((int)$item['user']['id'] === $userId) {
                    return [
                        'domain' => $domain,
                        'rank_in_domain' => $item['rank_in_domain'] ?? null,
                        'overall_score' => $item['overall_score'] ?? 0,
                    ];
                }
            }
        }

        return [
            'domain' => '',
            'rank_in_domain' => null,
            'overall_score' => 0,
        ];
    }
}