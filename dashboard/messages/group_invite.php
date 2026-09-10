<?php
include '../header.php'; // Ensure session management and connection are handled here

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['invite_users'])) {
    $group_id = intval($_POST['group_id']);
    $user_ids = $_POST['user_ids']; // Array of user IDs

    foreach ($user_ids as $user_id) {
        $user_id = intval($user_id);
        $sql = "INSERT INTO group_members (group_id, user_id) VALUES ($group_id, $user_id)";
        mysqli_query($con, $sql);
    }

    $_SESSION['msg'] = "Users invited successfully.";
    $_SESSION['msg_type'] = "success";
    header("Location: group_management.php");
    exit();
}

$groups_result = mysqli_query($con, "SELECT * FROM groups");
$users_result = mysqli_query($con, "SELECT id, fullname FROM users_tbl");
?>


<div class="container mt-4">
    <h2>Invite Users to Group</h2>

    <?php
    if (isset($_SESSION['msg'])) {
        echo "<div class='alert alert-{$_SESSION['msg_type']}'>".$_SESSION['msg']."</div>";
        unset($_SESSION['msg']);
    }
    ?>

    <form method="post" action="">
        <div class="form-group">
            <label for="group_id">Select Group:</label>
            <select class="form-control" id="group_id" name="group_id" required>
                <?php while ($group = mysqli_fetch_assoc($groups_result)): ?>
                    <option value="<?php echo $group['id']; ?>"><?php echo htmlspecialchars($group['group_name']); ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="user_ids">Select Users:</label>
            <select multiple class="form-control" id="user_ids" name="user_ids[]" required>
                <?php while ($user = mysqli_fetch_assoc($users_result)): ?>
                    <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['fullname']); ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <button type="submit" name="invite_users" class="btn btn-primary">Invite Users</button>
    </form>
</div>
<?php include '../footer.php'?>