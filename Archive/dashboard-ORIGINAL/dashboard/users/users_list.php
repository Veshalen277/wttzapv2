<?php
include '../header.php';
if ($role != 2 && $role != 5 && $role != 7) {
    header("Location: ../404.php");
    exit();
}if ($role == 1) {
    header("location: ../emp_profile.php");
    exit;}$records_per_page = 70;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;$filter_sql = "SELECT COUNT(*) AS total FROM users_tbl";
$filter_conditions = [];
if (!empty($_POST['designation'])) {
    $filter_conditions[] = "fullname LIKE '%" . mysqli_real_escape_string($con, $_POST['designation']) . "%'";
}
if (!empty($_POST['department'])) {
    $filter_conditions[] = "user_scale LIKE '%" . mysqli_real_escape_string($con, $_POST['department']) . "%'";
}
if (!empty($_POST['email'])) {
    $filter_conditions[] = "email LIKE '%" . mysqli_real_escape_string($con, $_POST['email']) . "%'";
}
if (!empty($filter_conditions)) {
    $filter_sql .= " WHERE " . implode(' AND ', $filter_conditions);
}
$query_filter = mysqli_query($con, $filter_sql);
$row_filter = mysqli_fetch_assoc($query_filter);
$total_records = $row_filter['total'];
$sql = "SELECT * FROM users_tbl";
if (!empty($filter_conditions)) {
    $sql .= " WHERE " . implode(' AND ', $filter_conditions);
}
$sql .= " ORDER BY date_started DESC";$sql .= " LIMIT $records_per_page OFFSET $offset";$query = mysqli_query($con, $sql);
$rows = mysqli_num_rows($query);
if (isset($_SESSION['update'])) {
    $msg = $_SESSION['update'];
    echo '<div class="alert alert-success">' . $msg . '</div>';
    unset($_SESSION['update']);
}
?><div class="container-fluid">
    <div class="row">
        <div class="">
            <div class="container-fluid mt-2 table-responsive py-2 bg-white ">
                <h4 class="text-center mb-3">Employee List</h4>
                <a href="add_emp.php" class="btn btn-primary mb-3">Add New</a>
                <a href="export_users_csv.php" class="btn btn-secondary mb-3">Export Users to CSV</a>
                <a href="export_users_pdf.php" class="btn btn-secondary mb-3">Export Users to PDF</a>
                <?php
                if (isset($_SESSION['success'])) {
                    echo "<div class='alert alert-success'>" . $_SESSION['success'] . "</div>";
                    unset($_SESSION['success']);                }                if (isset($_SESSION['error'])) {
                    echo "<div class='alert alert-danger'>" . $_SESSION['error'] . "</div>";
                    unset($_SESSION['error']);                }
                ?>                <hr>                <form method="post">
                    <div class="row">
                                           <div class="col-md-3 mb-2">
                            <label for="designation" class="form-label">Search by User:</label>
                            <input type="text" class="form-control border border-danger" id="designation" name="designation" value="<?= isset($_POST['designation']) ? htmlspecialchars($_POST['designation']) : '' ?>">
                        </div>                                              <div class="col-md-3 mb-2">
                            <label for="department" class="form-label">Department:</label>
                            <input type="text" class="form-control" id="department" name="department" value="<?= isset($_POST['department']) ? htmlspecialchars($_POST['department']) : '' ?>">
                        </div>                                   <div class="col-md-3 mb-2">
                            <label for="email" class="form-label">Email:</label>
                            <input type="text" class="form-control" id="email" name="email" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                        </div>                        <div class="col-md-3 align-self-end mb-2">
                            <button type="submit" name="filter" class="btn btn-primary w-100">Apply Filters</button>
                        </div>
                    </div>
                </form>                <div class="table-responsive" id="user-list">
                    <table class="table table-hover table-bordered bg-white table-sm p-4">
                        <thead>
                            <tr>
                                <th>Sr.#</th>
                                <th>Employee</th>
                                <th>Designation</th>
                                <th>User Pic</th>
                                <th>Dept.</th>
                                <th>Role</th>
                                <th>Start Date</th>
                                <th>ID No.</th>
                                <th>Email</th>
                                <th>Addr.</th>
                                <th>Contact No.</th>
                                <th>Next of Kin</th>
                                <th>Next of Kin No.</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="user-list-body">
    <?php
    if ($rows > 0) {
        $count = $offset + 1;
        while ($result = mysqli_fetch_assoc($query)) {            $image_path = !empty($result['profile_photo']) ? '/dashboard/employee/' . $result['profile_photo'] : null;
            echo "<!-- Image Path: $image_path -->";
            ?>
            <tr>
                <td><?= $count; ?></td>
                <td class="text-white bg-success">
                    <a href="profile.php?id=<?= $result['id']; ?>" class="text-white">
                        <?= $result['fullname']; ?>
                    </a>
                </td>
                <td><?= $result['user_des']; ?></td>
                <td>
                    <?php if ($image_path && file_exists($_SERVER['DOCUMENT_ROOT'] . $image_path)) { ?>
                        <a href="profile.php?id=<?= $result['id']; ?>">
                            <img src="<?= $image_path; ?>" alt="User Photo" style="width: 50px; height: auto;">
                        </a>
                    <?php } else { ?>
                        <a href="profile.php?id=<?= $result['id']; ?>">
                            <img src="/dashboard/employee/uploads/placeholder.jpg" alt="Default Photo" style="width: 50px; height: auto;">
                        </a>
                    <?php } ?>
                </td>
                <td><?= $result['user_scale']; ?></td>
                <td><?= $result['user_role']; ?></td>
                <td><?= $result['date_started']; ?></td>
                <td><?= $result['id_number']; ?></td>
                <td><?= isset($result['email']) && strlen($result['email']) > 7 ? substr($result['email'], 0, 12) . '...' : $result['email']; ?></td>
                <td><?= isset($result['address']) && strlen($result['address']) > 7 ? substr($result['address'], 0, 12) . '...' : $result['address']; ?></td>
                <td><?= $result['contact_number']; ?></td>
                <td><?= $result['next_of_kin']; ?></td>
                <td><?= $result['next_of_kin_number']; ?></td>
                <td class="d-flex">
                    <a href="profile.php?id=<?= $result['id']; ?>" class="btn btn-sm btn-secondary text-dark mx-1" title="View">
                        <i class="bi bi-eye"></i>
                    </a>
                    <a href="edit_emp.php?id=<?= $result['id']; ?>" class="btn btn-sm btn-secondary text-white" title="Edit">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <a href="ass_task.php?id=<?= $result['id']; ?>" class="btn btn-sm btn-secondary mx-1 text-white" title="Assign">
                        <i class="bi bi-plus-circle"></i>
                    </a>
                    <a href="delete_user.php?id=<?= $result['id']; ?>" class="btn btn-sm btn-secondary mx-1 text-white" title="Delete" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
                        <i class="bi bi-trash"></i>
                    </a>
                </td>
            </tr>
            <?php
            $count++;
        }
    } else {
        echo "<tr><td colspan='14'>No Records found</td></tr>";
    }
    ?>
</tbody>                    </table>
                </div>
            </div>
        </div>
    </div>
</div><?php
include '../footer.php';
?>
