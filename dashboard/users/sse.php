<?php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('Access-Control-Allow-Origin: *'); // Optional, if cross-origin access is needed

// Start the session to access session data
session_start();

// Include database connection
include '../config.php';

// Check if user is logged in
if (!isset($_SESSION['u_data'])) {
    // If not logged in, send empty data and exit
    echo "data: " . json_encode([]) . "\n\n";
    flush();
    exit();
}

$user_id = $_SESSION['u_data'][5]; // Assuming the user ID is stored in the session

// Set infinite loop but with proper error handling
while (true) {
    // Check connection still alive
    if (connection_aborted()) {
        break;
    }
    
    $stmt = $con->prepare("SELECT * FROM checklist_items WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    $stmt->close();

    echo "data: " . json_encode($data) . "\n\n";
    
    // Flush the output buffer
    ob_flush();
    flush();
    
    // Sleep for 10 seconds
    sleep(10);
    
    // Re-establish connection if needed (optional)
    if (!$con->ping()) {
        $con->close();
        include '../config.php';
    }
}

$con->close();
?>