<?php
include '../header.php'; // session_start() should already be in here
include '../functions.php';
require '../vendor/autoload.php';
require '../config.php'; // DB connection

// Fetch projects
$projects_sql = "
    SELECT
        p.project_id,
        p.project_name,
        p.project_description,
        p.status,
        p.created_at,
        u.fullname
    FROM projects_tbl p
    LEFT JOIN users_tbl u ON p.created_by = u.id
    ORDER BY p.created_at DESC
";
$projects_result = mysqli_query($con, $projects_sql) or die("SQL Error: " . mysqli_error($con));
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">📋 Project Dashboard</h2>
        <a href="add_project.php" class="btn btn-primary">
            ➕ Add New Project
        </a>
    </div>
    <div class="table-responsive shadow-sm rounded">
        <table class="table table-hover align-middle table-bordered">
            <thead class="table-light text-center">
                <tr>
                    <th>#</th>
                    <th>Project</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Created By</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($projects_result)): ?>
                    <tr>
                        <td class="text-center"><?= $row['project_id'] ?></td>
                        <td><strong><?= htmlspecialchars($row['project_name']) ?></strong></td>
                      <td> 
    <?= htmlspecialchars(mb_strimwidth($row['project_description'], 0, 30, '...')) ?>
</td>
                        <td class="text-center">
                            <?php
                                $status = htmlspecialchars($row['status']);
                                $badge = match ($status) {
                                    'pending'   => 'warning',
                                    'active'    => 'danger',
                                    'completed' => 'success',
                                    default     => 'secondary',
                                };
                            ?>
                            <span class="badge bg-<?= $badge ?>"><?= $status ?></span>
                        </td>
                        <td><?= htmlspecialchars($row['fullname'] ?: 'N/A') ?></td>
                        <td><?= date('Y-m-d H:i', strtotime($row['created_at'])) ?></td>
                        <td class="text-center">
                            <div class="d-flex gap-2 justify-content-center flex-wrap">
<a href="project_messages.php?project_id=<?= $row['project_id'] ?>" class="btn btn-secondary btn-sm">Read more</a>
                                <form action="delete_project.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this project?');">
                                    <input type="hidden" name="project_id" value="<?= $row['project_id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        🗑️ Delete
                                    </button>
                                    <a href="edit_project.php?project_id=<?= $row['project_id'] ?>" class="btn btn-info btn-sm text-dark">✏️ Edit</a>

                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include '../footer.php'; ?>
