
<?php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('Access-Control-Allow-Origin: *'); // Optional, if cross-origin access is needed

// // Set the script to keep running
// ignore_user_abort(true);
// set_time_limit(0);

// // Include database connection
// include '../config.php';

// while (true) {
//     $result = $con->query("SELECT * FROM checklist_items ORDER BY created_at DESC");
//     $data = [];
//     while ($row = $result->fetch_assoc()) {
//         $data[] = $row;
//     }
//     echo "data: " . json_encode($data) . "\n\n";
//     flush();
//     sleep(10); // Send updates every 10 seconds
// }
include '../config.php';

$user_id = $_SESSION['u_data'][5]; // Assuming the user ID is stored in the session

while (true) {
    $stmt = $con->prepare("SELECT * FROM checklist_items WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    echo "data: " . json_encode($data) . "\n\n";
    flush();
    sleep(10); // Send updates every 10 seconds
}

$stmt->close();
$con->close();


?>