<?php
include 'header.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email']) && isset($_POST['group_id'])) {
    $email = mysqli_real_escape_string($con, htmlspecialchars($_POST['email']));
    $group_id = intval($_POST['group_id']);
    
    // Fetch user ID by email
    $user_result = mysqli_query($con, "SELECT id FROM users_tbl WHERE email = '$email'");
    if ($user_result && mysqli_num_rows($user_result) > 0) {
        $user = mysqli_fetch_assoc($user_result);
        $user_id = $user['id'];
        
        // Remove member from group
        $stmt = $con->prepare("DELETE FROM group_members WHERE group_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $group_id, $user_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }
        
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'error' => 'User not found.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request.']);
}
?>
