<?php
include "../header.php"; // Include your header and database connection

// Fetch users for the dropdown
$users_result = mysqli_query($con, "SELECT id, fullname FROM users_tbl");
$users = [];
while ($row = mysqli_fetch_assoc($users_result)) {
    $users[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = $_POST['message'];
    $user_id = $_POST['user_id']; // Get the user ID from the form

    // Validate input
    if (!empty($message) && !empty($user_id)) {
        $query = "INSERT INTO notifications (message, user_id) VALUES (?, ?)";
        $stmt = mysqli_prepare($con, $query);
        mysqli_stmt_bind_param($stmt, "si", $message, $user_id);
        
        if (mysqli_stmt_execute($stmt)) {
            echo "<p>Notification added successfully!</p>";
        } else {
            echo "<p>Error: " . mysqli_error($con) . "</p>";
        }
    } else {
        echo "<p>Please enter a notification message and select a user.</p>";
    }
}
?>

<h2>Add Notification</h2>
<form method="POST" action="">
    <textarea name="message" rows="4" cols="50" placeholder="Enter notification message" required></textarea>
    <br>
    <label for="user_id">Select User:</label>
    <select name="user_id" required>
        <option value="">--Select User--</option>
        <?php foreach ($users as $user): ?>
            <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['username']); ?></option>
        <?php endforeach; ?>
    </select>
    <br>
    <button type="submit">Add Notification</button>
</form>

<h2>Notifications</h2>
<table border="1">
    <tr>
        <th>ID</th>
        <th>Message</th>
        <th>Created By</th>
        <th>Created At</th>
    </tr>
    <?php
    // Fetch notifications to display in a table
    $result = mysqli_query($con, "SELECT n.*, u.username FROM notifications n JOIN users_tbl u ON n.user_id = u.id ORDER BY n.created_at DESC");
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['message']) . "</td>";
        echo "<td>" . htmlspecialchars($row['username']) . "</td>"; // Display created by (username)
        echo "<td>" . $row['created_at'] . "</td>";
        echo "</tr>";
    }
    ?>
</table>

<?php include "../footer.php"; // Include your footer ?>
