<?php
include '../header.php';


if (!isset($_SESSION['u_data'])) {
  header("Location: ../login.php");
  exit();
}

$user = $_SESSION['u_data'];
$user_role = $user[4];

// Only allow access to role 8 or 7
if (!in_array($user_role, [8, 7])) {
  header("Location: ../admin/admin_profile.php");
  exit();
}


$task_result = mysqli_query($con, "
  SELECT ut.id, ut.task_description, ut.created_at, u.fullname
  FROM adv_user_tasks_tbl ut
  JOIN users_tbl u ON ut.user_id = u.id
  ORDER BY ut.created_at DESC
");
?>

<div class="card mt-4 p-3 shadow">
  <h6>Assigned Tasks</h6>
  <table class="table table-bordered table-sm">
    <thead>
      <tr>
        <th>User</th>
        <th>Task</th>
        <th>Date Assigned</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($task = mysqli_fetch_assoc($task_result)): ?>
        <tr>
          <td><?= htmlspecialchars($task['fullname']) ?></td>
          <td><?= htmlspecialchars($task['task_description']) ?></td>
          <td><?= htmlspecialchars($task['created_at']) ?></td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>
<?php include '../footer.php';?>