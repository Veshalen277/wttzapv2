<?php
// message_queries.php
// Output: $result, $page, $total_pages

$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

$start = ($page - 1) * $limit;

$sql = "SELECT messages.id, users_tbl.fullname, users_tbl.user_scale, messages.message_text, messages.posted_at
        FROM messages
        INNER JOIN users_tbl ON messages.user_id = users_tbl.id
        ORDER BY messages.posted_at DESC
        LIMIT $start, $limit";
$result = mysqli_query($con, $sql);

$sql_count = "SELECT COUNT(id) AS total FROM messages";
$result_count = mysqli_query($con, $sql_count);
$row_count = $result_count ? mysqli_fetch_assoc($result_count) : ['total' => 0];

$total = (int)($row_count['total'] ?? 0);
$total_pages = (int)ceil($total / $limit);
if ($total_pages < 1) $total_pages = 1;

// Clamp page if user enters too high
if ($page > $total_pages) {
  $page = $total_pages;
}