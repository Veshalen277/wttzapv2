<?php
include '../header.php'; // Ensure session management and connection are handled here

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_group'])) {
    $group_name = mysqli_real_escape_string($con, htmlspecialchars($_POST['group_name']));
    $created_by = $_SESSION['u_data'][5];

    $sql = "INSERT INTO groups (group_name, created_by) VALUES ('$group_name', $created_by)";
    if (mysqli_query($con, $sql)) {
        $_SESSION['msg'] = "Group created successfully.";
        $_SESSION['msg_type'] = "success";
        header("Location: group_management.php");
        exit();
    } else {
        $_SESSION['msg'] = "Error creating group: " . mysqli_error($con);
        $_SESSION['msg_type'] = "error";
    }
}
?>




<div class="container-fluid mt-3 p-5 bg-white border">
    <div class="row">
        <div class="col-md-3 col-sm-12">
            <?php include '../inc/sidebar.php';?>
        </div>
        <div class="col-md-9 col-sm-12">
<div class="container mt-4">
    <h2>Create Group</h2>

    <?php
    if (isset($_SESSION['msg'])) {
        echo "<div class='alert alert-{$_SESSION['msg_type']}'>".$_SESSION['msg']."</div>";
        unset($_SESSION['msg']);
    }
    ?>

    <form method="post" action="">
        <div class="form-group">
            <label for="group_name">Group Name:</label>
            <input type="text" class="form-control" id="group_name" name="group_name" required>
        </div>
        <button type="submit" name="create_group" class="btn btn-primary">Create Group</button>
    </form>
</div>
        </div>
    </div>
</div>








<?php include '../footer.php';?>
