<?php
include '../header.php';
include '../config.php';

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

// Pagination setup
$results_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;
$offset = ($current_page - 1) * $results_per_page;

// Get total reports
$count_query = "SELECT COUNT(*) AS total FROM manager_report_tbl WHERE visibility_level >= ?";
$count_stmt = $con->prepare($count_query);
$count_stmt->bind_param("i", $user_role);
$count_stmt->execute();
$total_rows = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $results_per_page);

// Get reports
$query = "SELECT m.*, u.fullname AS manager_name 
          FROM manager_report_tbl m 
          LEFT JOIN users_tbl u ON m.manager_id = u.id 
          WHERE m.visibility_level >= ? 
          ORDER BY m.report_date DESC
          LIMIT ?, ?";
$stmt = $con->prepare($query);
$stmt->bind_param("iii", $user_role, $offset, $results_per_page);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="container-fluid mt-4">
  <div class="row">
    <div class="col-md-8 col-sm-12">
      <h4 class="mb-4">Manager Reports</h4>
      <div class="table-responsive bg-white p-3 border">
        <table class="table table-striped">
          <thead class="table-light">
            <tr>
              <th>Date</th>
              <th>Title</th>
              <th>Department</th>
              <th>Manager</th>
              <th>Report</th>
              <th>Attachment</th>
            </tr>
          </thead>
          <tbody>
            <?php
            if ($result->num_rows === 0) {
              echo "<tr><td colspan='6' class='text-center text-muted'>No reports found</td></tr>";
            }

            while ($row = $result->fetch_assoc()) {
              echo "<tr>";
              echo "<td>" . date("Y-m-d H:i", strtotime($row['report_date'])) . "</td>";
              echo "<td>" . htmlspecialchars($row['report_title']) . "</td>";
              echo "<td>" . htmlspecialchars($row['department']) . "</td>";
              echo "<td>" . (!empty($row['manager_name']) ? htmlspecialchars($row['manager_name']) : 'System Generated') . "</td>";
              echo "<td><button class='btn btn-sm btn-outline-secondary' data-bs-toggle='collapse' data-bs-target='#report_" . $row['id'] . "'>View</button></td>";
              echo "<td>";
              if (!empty($row['attachment_path'])) {
                $file_name = basename($row['attachment_path']);
                echo "<a href='../" . $row['attachment_path'] . "' target='_blank' title='$file_name'>Download</a>";
              } else {
                echo "-";
              }
              echo "</td>";
              echo "</tr>";

              echo "<tr class='collapse' id='report_" . $row['id'] . "'>";
              echo "<td colspan='6'>";
              echo "<div class='p-3 bg-light mb-3'>";
              echo "<strong>Report Body:</strong><br>";
              echo nl2br(htmlspecialchars($row['report_body']));
              echo "</div>";

              // Fetch tasks assigned TO this manager
              $manager_id = $row['manager_id'];
              $task_query = mysqli_query($con, "
                SELECT a.task_description, a.created_at, u.fullname AS assigned_by
                FROM adv_user_tasks_tbl a
                LEFT JOIN users_tbl u ON a.assigned_by = u.id
                WHERE a.user_id = $manager_id
                ORDER BY a.created_at DESC
              ");

              if (mysqli_num_rows($task_query) > 0) {
                echo "<div class='mb-2'><strong>Assigned Tasks:</strong>";
                echo "<ul class='list-group'>";
                while ($task = mysqli_fetch_assoc($task_query)) {
                  echo "<li class='list-group-item small'>";
                  echo "<strong>Task:</strong> " . htmlspecialchars($task['task_description'] ?? '') . "<br>";
                  echo "<small class='text-muted'>Assigned by: " . htmlspecialchars($task['assigned_by'] ?? 'Unknown') . " on " . date("Y-m-d H:i", strtotime($task['created_at'])) . "</small>";
                  echo "</li>";
                }
                echo "</ul></div>";
              } else {
                echo "<div class='text-muted small'>No tasks assigned to this manager yet.</div>";
              }

              echo "</td></tr>";
            }
            ?>
          </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation">
          <ul class="pagination justify-content-center">
            <?php if ($current_page > 1): ?>
              <li class="page-item">
                <a class="page-link" href="?page=<?= $current_page - 1 ?>" aria-label="Previous">
                  <span aria-hidden="true">&laquo;</span>
                </a>
              </li>
            <?php endif;

            $start_page = max(1, $current_page - 2);
            $end_page = min($total_pages, $current_page + 2);

            for ($i = $start_page; $i <= $end_page; $i++): ?>
              <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
              </li>
            <?php endfor; ?>

            <?php if ($current_page < $total_pages): ?>
              <li class="page-item">
                <a class="page-link" href="?page=<?= $current_page + 1 ?>" aria-label="Next">
                  <span aria-hidden="true">&raquo;</span>
                </a>
              </li>
            <?php endif; ?>
          </ul>
        </nav>
        <?php endif; ?>
      </div>
    </div>

    <!-- Task Assignment Form -->
    <div class="col-md-4 col-sm-12">
      <?php
      $user_query = mysqli_query($con, "SELECT id, fullname, user_role FROM users_tbl WHERE user_role >= 2");
      ?>
      <div class="card shadow p-3 mt-3">
        <h5>Assign a Task to a User</h5>
        <form action="save_user_task.php" method="POST">
          <div class="mb-2">
            <label>Select User</label>
            <select name="user_id" class="form-select" required>
              <option disabled selected>Select user</option>
              <?php while ($user = mysqli_fetch_assoc($user_query)): ?>
                <option value="<?= $user['id'] ?>">
                  <?= htmlspecialchars($user['fullname']) ?> (Role <?= $user['user_role'] ?>)
                </option>
              <?php endwhile; ?>
            </select>
          </div>

          <div class="mb-2">
            <label>Task Description</label>
            <textarea name="task_description" class="form-control" rows="4" required></textarea>
          </div>

          <input type="hidden" name="assigned_by" value="<?= $_SESSION['u_data'][0] ?>">

          <button type="submit" class="btn btn-success mt-2">Assign Task</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php include '../footer.php'; ?>
