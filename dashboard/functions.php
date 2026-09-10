<?php

// Escape for HTML output (use when echoing user content into pages)
function e(?string $s): string {
    return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function addEmployee(mysqli $con, array $data) {
    $fullname           = (string)($data['user_name'] ?? '');
    $user_des           = (string)($data['user_des'] ?? '');
    $user_res           = (string)($data['user_res'] ?? '');
    $user_scale         = (string)($data['user_scale'] ?? '');
    $user_id            = (string)($data['user_id'] ?? '');
    $plain_pass         = (string)($data['user_pass'] ?? '');
    $role               = (string)($data['user_role'] ?? '');
    $date_started       = (string)($data['date_started'] ?? '');
    $id_number          = (string)($data['id_number'] ?? '');
    $address            = (string)($data['address'] ?? '');
    $contact_number     = (string)($data['contact_number'] ?? '');
    $email              = (string)($data['email'] ?? '');
    $next_of_kin        = (string)($data['next_of_kin'] ?? '');
    $next_of_kin_number = (string)($data['next_of_kin_number'] ?? '');
    $job_functions      = (string)($data['job_functions'] ?? '');

    // Validate password length (validate BEFORE hashing)
    if (mb_strlen($plain_pass) < 4) {
        return "Password length must be at least 4 characters.";
    }

    // NOTE: keeping your SHA1 to avoid breaking logins immediately.
    // Recommended upgrade: password_hash() (I can refactor login too).
    $pass = sha1($plain_pass);

    // Check if user_id exists
    $stmt = $con->prepare("SELECT 1 FROM users_tbl WHERE user_id = ? LIMIT 1");
    if (!$stmt) return "DB prepare failed: " . $con->error;
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->close();
        return "User ID already exists.";
    }
    $stmt->close();

    // Insert employee
    $sql = "INSERT INTO users_tbl
        (fullname,user_des,user_res,user_scale,user_id,user_pass,user_role,date_started,id_number,email,address,contact_number,next_of_kin,next_of_kin_number,job_functions)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

    $stmt = $con->prepare($sql);
    if (!$stmt) return "DB prepare failed: " . $con->error;

    $stmt->bind_param(
        "sssssssssssssss",
        $fullname,
        $user_des,
        $user_res,
        $user_scale,
        $user_id,
        $pass,
        $role,
        $date_started,
        $id_number,
        $email,
        $address,
        $contact_number,
        $next_of_kin,
        $next_of_kin_number,
        $job_functions
    );

    $ok = $stmt->execute();
    if (!$ok) {
        $err = $stmt->error;
        $stmt->close();
        return "Insert failed: " . $err;
    }
    $stmt->close();
    return true;
}

function countTotalRecords(mysqli $con): int {
    $stmt = $con->prepare("SELECT COUNT(*) AS total FROM work_tbl");
    if (!$stmt) return 0;
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)($res['total'] ?? 0);
}

function deleteRecord(int $delete_id, mysqli $con): bool {
    $stmt = $con->prepare("DELETE FROM work_tbl WHERE id = ?");
    if (!$stmt) return false;
    $stmt->bind_param("i", $delete_id);
    $ok = $stmt->execute();
    $stmt->close();
    return (bool)$ok;
}

function get_current_password(mysqli $con, int $id): ?string {
    $stmt = $con->prepare("SELECT user_pass FROM users_tbl WHERE id = ? LIMIT 1");
    if (!$stmt) return null;
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row['user_pass'] ?? null;
}

function update_user_details(mysqli $con, array $data): bool {
    $id                = (int)($data['id'] ?? 0);
    $name              = (string)($data['user_name'] ?? '');
    $designation       = (string)($data['user_des'] ?? '');
    $responsibilities  = (string)($data['user_res'] ?? '');
    $job_functions     = (string)($data['job_functions'] ?? '');
    $date_started      = (string)($data['date_started'] ?? '');
    $id_number         = (string)($data['id_number'] ?? '');
    $email             = (string)($data['email'] ?? '');
    $user_scale        = (string)($data['user_scale'] ?? '');
    $user_id           = (string)($data['user_id'] ?? '');
    $address           = (string)($data['address'] ?? '');
    $contact_number    = (string)($data['contact_number'] ?? '');
    $next_of_kin       = (string)($data['next_of_kin'] ?? '');
    $next_of_kin_number= (string)($data['next_of_kin_number'] ?? '');

    $sql = "UPDATE users_tbl SET
        fullname = ?,
        user_des = ?,
        user_res = ?,
        job_functions = ?,
        date_started = ?,
        id_number = ?,
        email = ?,
        user_scale = ?,
        user_id = ?,
        address = ?,
        contact_number = ?,
        next_of_kin = ?,
        next_of_kin_number = ?
        WHERE id = ?";

    $stmt = $con->prepare($sql);
    if (!$stmt) return false;

    $stmt->bind_param(
        "sssssssssssssi",
        $name,
        $designation,
        $responsibilities,
        $job_functions,
        $date_started,
        $id_number,
        $email,
        $user_scale,
        $user_id,
        $address,
        $contact_number,
        $next_of_kin,
        $next_of_kin_number,
        $id
    );

    $ok = $stmt->execute();
    $stmt->close();
    return (bool)$ok;
}

function get_distinct_user_scales(mysqli $con): array {
    $stmt = $con->prepare("SELECT DISTINCT user_scale FROM users_tbl");
    if (!$stmt) return [];
    $stmt->execute();
    $result = $stmt->get_result();
    $out = [];
    while ($row = $result->fetch_assoc()) $out[] = $row;
    $stmt->close();
    return $out;
}

function get_user_by_id(mysqli $con, int $id): ?array {
    $stmt = $con->prepare("SELECT * FROM users_tbl WHERE id = ? LIMIT 1");
    if (!$stmt) return null;
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

/**
 * SAFER getCount: you cannot bind table names in prepared statements,
 * so you MUST whitelist allowed table names.
 */
function getCount(mysqli $con, string $table): int {
    $allowed = [
        'leave_applications',
        'reports',
        'orders',
        'users_tbl',
        'work_tbl',
        'suggestions',
    ];
    if (!in_array($table, $allowed, true)) {
        return 0;
    }

    $sql = "SELECT COUNT(*) AS count FROM `$table`";
    $stmt = $con->prepare($sql);
    if (!$stmt) return 0;
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)($row['count'] ?? 0);
}

function getWorkStatistics(mysqli $con): array {
    $stmt = $con->prepare("SELECT COUNT(*) AS total_tasks, COUNT(DISTINCT employee_id) AS total_employees FROM work_tbl");
    if (!$stmt) return ['total_tasks' => 0, 'total_employees' => 0];
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: ['total_tasks' => 0, 'total_employees' => 0];
}

?>