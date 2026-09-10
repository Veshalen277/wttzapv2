<?php
include 'header.php';?>

<form action="add_user_to_topic_process.php" method="POST">
    <label for="user_id">Select User to Add:</label>
    <select name="user_id" id="user_id">
        <?php
        // Fetch users from the database
        $query = "SELECT id, full_name FROM users_tbl";
        $stmt = $pdo->query($query);
        while ($row = $stmt->fetch()) {
            echo "<option value='{$row['id']}'>{$row['full_name']}</option>";
        }
        ?>
    </select>
    <input type="hidden" name="topic_id" value="<?= $_GET['topic_id']; ?>"> <!-- Assuming topic ID is passed via URL -->
    <button type="submit">Add User</button>
</form>



<?php

include 'footer.php';
?>
