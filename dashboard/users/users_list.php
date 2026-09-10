<?php
include '../header.php';

if ($role != 2 && $role != 5 && $role != 7) {
    header("Location: ../404.php");
    exit();
}

function safe($value): string
{
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

function shortText($value, int $length = 18): string
{
    $value = (string)($value ?? '');

    if (mb_strlen($value) > $length) {
        return safe(mb_substr($value, 0, $length) . '...');
    }

    return safe($value);
}

/* =========================================================
   FILTERS
========================================================= */

$searchUser = trim((string)($_GET['designation'] ?? ''));
$department = trim((string)($_GET['department'] ?? ''));
$email      = trim((string)($_GET['email'] ?? ''));

$records_per_page = 70;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $records_per_page;

/*
 * is_active = 1 is always applied.
 * Archived employees stay in the database but do not appear here.
 */
$whereConditions = [
    "is_active = 1"
];

$params = [];
$types = '';

if ($searchUser !== '') {
    $whereConditions[] = "fullname LIKE ?";
    $params[] = '%' . $searchUser . '%';
    $types .= 's';
}

if ($department !== '') {
    $whereConditions[] = "user_scale LIKE ?";
    $params[] = '%' . $department . '%';
    $types .= 's';
}

if ($email !== '') {
    $whereConditions[] = "email LIKE ?";
    $params[] = '%' . $email . '%';
    $types .= 's';
}

$whereSql = " WHERE " . implode(" AND ", $whereConditions);

/* =========================================================
   COUNT ACTIVE EMPLOYEES
========================================================= */

$countSql = "
    SELECT COUNT(*) AS total
    FROM users_tbl
    {$whereSql}
";

$countStmt = $con->prepare($countSql);

if (!$countStmt) {
    die("Count query preparation failed: " . safe($con->error));
}

if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}

$countStmt->execute();
$countResult = $countStmt->get_result();
$countRow = $countResult->fetch_assoc();
$total_records = (int)($countRow['total'] ?? 0);
$countStmt->close();

$total_pages = max(
    1,
    (int)ceil($total_records / $records_per_page)
);

if ($page > $total_pages) {
    $page = $total_pages;
    $offset = ($page - 1) * $records_per_page;
}

/* =========================================================
   FETCH ACTIVE EMPLOYEES
========================================================= */

$sql = "
    SELECT
        id,
        fullname,
        user_des,
        profile_photo,
        user_scale,
        user_role,
        date_started,
        id_number,
        email,
        address,
        contact_number,
        next_of_kin,
        next_of_kin_number
    FROM users_tbl
    {$whereSql}
    ORDER BY date_started DESC, fullname ASC
    LIMIT ? OFFSET ?
";

$queryStmt = $con->prepare($sql);

if (!$queryStmt) {
    die("Employee query preparation failed: " . safe($con->error));
}

$queryParams = $params;
$queryTypes = $types . 'ii';

$queryParams[] = $records_per_page;
$queryParams[] = $offset;

$queryStmt->bind_param($queryTypes, ...$queryParams);
$queryStmt->execute();

$query = $queryStmt->get_result();
$rows = $query->num_rows;

/* =========================================================
   QUERY STRING FOR PAGINATION
========================================================= */

$queryString = http_build_query([
    'designation' => $searchUser,
    'department'  => $department,
    'email'       => $email
]);
?>

<style>
.office-page {
    background: #f3f5f7;
    padding: 22px;
    min-height: calc(100vh - 80px);
}

.office-panel {
    background: #ffffff;
    border: 1px solid #d9dde3;
    padding: 22px;
    margin-bottom: 18px;
}

.office-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    margin-bottom: 18px;
}

.office-title {
    margin: 0;
    color: #202124;
    font-size: 24px;
    font-weight: 700;
}

.office-subtitle {
    margin: 4px 0 0;
    color: #666666;
    font-size: 14px;
}

.office-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.office-filter {
    background: #f8f9fb;
    border: 1px solid #d9dde3;
    padding: 16px;
    margin-bottom: 18px;
}

.office-filter label {
    color: #444444;
    font-size: 13px;
    font-weight: 700;
}

.office-page .form-control,
.office-page .form-select,
.office-page .btn,
.office-page .alert,
.office-page .page-link {
    border-radius: 0 !important;
    box-shadow: none !important;
}

.office-page .form-control,
.office-page .form-select {
    border: 1px solid #bfc5cc;
}

.office-page .form-control:focus,
.office-page .form-select:focus {
    border-color: #0f6cbd;
    box-shadow: inset 0 0 0 1px #0f6cbd !important;
}

.btn-office {
    border: 1px solid #bfc5cc;
    background: #ffffff;
    color: #242424;
    padding: 7px 14px;
    font-size: 13px;
    text-decoration: none;
}

.btn-office:hover {
    background: #f3f5f7;
    border-color: #8f969d;
    color: #242424;
}

.btn-office-primary {
    border: 1px solid #0f6cbd;
    background: #0f6cbd;
    color: #ffffff;
    padding: 7px 14px;
    font-size: 13px;
    text-decoration: none;
}

.btn-office-primary:hover {
    background: #115ea3;
    border-color: #115ea3;
    color: #ffffff;
}

.btn-office-danger {
    border: 1px solid #b42318;
    background: #ffffff;
    color: #b42318;
    padding: 4px 8px;
    font-size: 12px;
    text-decoration: none;
}

.btn-office-danger:hover {
    background: #b42318;
    color: #ffffff;
}

.btn-office-icon {
    border: 1px solid #bfc5cc;
    background: #ffffff;
    color: #242424;
    padding: 4px 8px;
    font-size: 12px;
    text-decoration: none;
}

.btn-office-icon:hover {
    background: #f3f5f7;
    border-color: #8f969d;
    color: #242424;
}

.btn-office-edit {
    border-color: #0f6cbd;
    color: #0f6cbd;
}

.btn-office-edit:hover {
    background: #0f6cbd;
    color: #ffffff;
}

.btn-office-assign {
    border-color: #107c10;
    color: #107c10;
}

.btn-office-assign:hover {
    background: #107c10;
    color: #ffffff;
}

.office-table {
    margin-bottom: 0;
    font-size: 13px;
}

.office-table thead th {
    background: #f1f3f5;
    border-bottom: 2px solid #c7ccd1;
    color: #333333;
    font-weight: 700;
    white-space: nowrap;
}

.office-table td {
    vertical-align: middle;
    white-space: nowrap;
}

.office-table tbody tr:hover {
    background: #f8fafc;
}

.employee-name {
    color: #0f6cbd;
    font-weight: 700;
    text-decoration: none;
}

.employee-name:hover {
    color: #115ea3;
    text-decoration: underline;
}

.employee-photo {
    width: 42px;
    height: 42px;
    border: 1px solid #d9dde3;
    background: #ffffff;
    object-fit: cover;
}

.role-badge {
    display: inline-block;
    border: 1px solid #d9dde3;
    background: #eef2f6;
    padding: 3px 8px;
    font-size: 12px;
    font-weight: 700;
}

.action-buttons {
    display: flex;
    gap: 5px;
}

.office-alert {
    margin-bottom: 16px;
}

.employee-count {
    margin-bottom: 10px;
    color: #666666;
    font-size: 13px;
}

.no-records {
    padding: 28px;
    color: #666666;
    text-align: center;
}

.pagination .page-link {
    border-color: #d0d7de;
    color: #0f6cbd;
}

.pagination .page-link:hover {
    background: #f3f5f7;
    color: #115ea3;
}

.pagination .page-item.active .page-link {
    border-color: #0f6cbd;
    background: #0f6cbd;
    color: #ffffff;
}

@media (max-width: 768px) {
    .office-page {
        padding: 12px;
    }

    .office-panel {
        padding: 14px;
    }

    .office-header {
        flex-direction: column;
        align-items: stretch;
    }

    .office-actions {
        flex-direction: column;
    }

    .office-actions a {
        width: 100%;
        text-align: center;
    }
}
</style>

<div class="office-page">

    <div class="office-panel">

        <div class="office-header">
            <div>
                <h4 class="office-title">Employee List</h4>

                <p class="office-subtitle">
                    Manage active employees, profiles, departments and access records.
                </p>
            </div>

            <div class="office-actions">
                <a href="add_emp.php" class="btn-office-primary">
                    Add New
                </a>

                <a href="export_users_csv.php" class="btn-office">
                    Export CSV
                </a>

                <a href="export_users_pdf.php" class="btn-office">
                    Export PDF
                </a>
            </div>
        </div>

        <?php if (isset($_SESSION['update'])): ?>
            <div class="alert alert-success office-alert">
                <?= safe($_SESSION['update']); ?>
            </div>

            <?php unset($_SESSION['update']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success office-alert">
                <?= safe($_SESSION['success']); ?>
            </div>

            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger office-alert">
                <?= safe($_SESSION['error']); ?>
            </div>

            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <form method="get" class="office-filter">
            <div class="row g-2">

                <div class="col-md-3">
                    <label for="designation" class="form-label">
                        Search User
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        id="designation"
                        name="designation"
                        value="<?= safe($searchUser); ?>"
                        placeholder="Employee name">
                </div>

                <div class="col-md-3">
                    <label for="department" class="form-label">
                        Department
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        id="department"
                        name="department"
                        value="<?= safe($department); ?>"
                        placeholder="Department">
                </div>

                <div class="col-md-3">
                    <label for="email" class="form-label">
                        Email
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        id="email"
                        name="email"
                        value="<?= safe($email); ?>"
                        placeholder="Email address">
                </div>

                <div class="col-md-3 d-flex align-items-end">
                    <button
                        type="submit"
                        class="btn-office-primary w-100">
                        Apply Filters
                    </button>
                </div>

                <?php if ($searchUser !== '' || $department !== '' || $email !== ''): ?>
                    <div class="col-12 mt-2">
                        <a href="<?= safe(basename($_SERVER['PHP_SELF'])); ?>"
                           class="btn-office">
                            Clear Filters
                        </a>
                    </div>
                <?php endif; ?>

            </div>
        </form>

        <div class="employee-count">
            Showing <?= number_format($rows); ?> of
            <?= number_format($total_records); ?> active employee records
        </div>

        <div class="table-responsive">
            <table class="table table-hover table-bordered office-table">

                <thead>
                    <tr>
                        <th>Sr.#</th>
                        <th>Employee</th>
                        <th>Designation</th>
                        <th>Photo</th>
                        <th>Dept.</th>
                        <th>Role</th>
                        <th>Start Date</th>
                        <th>ID No.</th>
                        <th>Email</th>
                        <th>Address</th>
                        <th>Contact</th>
                        <th>Next of Kin</th>
                        <th>Kin Contact</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($rows > 0): ?>

                        <?php
                        $count = $offset + 1;

                        while ($result = $query->fetch_assoc()):

                            $employeeId = (int)($result['id'] ?? 0);

                            $photo = !empty($result['profile_photo'])
                                ? '/dashboard/employee/' . ltrim(
                                    (string)$result['profile_photo'],
                                    '/'
                                )
                                : '/dashboard/employee/uploads/placeholder.jpg';

                            $photoFilesystemPath =
                                rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $photo;

                            if (
                                !is_file($photoFilesystemPath) ||
                                !is_readable($photoFilesystemPath)
                            ) {
                                $photo =
                                    '/dashboard/employee/uploads/placeholder.jpg';
                            }
                        ?>

                            <tr>
                                <td><?= $count; ?></td>

                                <td>
                                    <a
                                        href="profile.php?id=<?= $employeeId; ?>"
                                        class="employee-name">
                                        <?= safe(
                                            $result['fullname']
                                            ?? 'Unknown User'
                                        ); ?>
                                    </a>
                                </td>

                                <td>
                                    <?= safe($result['user_des'] ?? ''); ?>
                                </td>

                                <td>
                                    <a href="profile.php?id=<?= $employeeId; ?>">
                                        <img
                                            src="<?= safe($photo); ?>"
                                            alt="Employee photo"
                                            class="employee-photo">
                                    </a>
                                </td>

                                <td>
                                    <?= safe($result['user_scale'] ?? ''); ?>
                                </td>

                                <td>
                                    <span class="role-badge">
                                        <?= safe($result['user_role'] ?? ''); ?>
                                    </span>
                                </td>

                                <td>
                                    <?= safe($result['date_started'] ?? ''); ?>
                                </td>

                                <td>
                                    <?= safe($result['id_number'] ?? ''); ?>
                                </td>

                                <td
                                    title="<?= safe($result['email'] ?? ''); ?>">
                                    <?= shortText(
                                        $result['email'] ?? '',
                                        18
                                    ); ?>
                                </td>

                                <td
                                    title="<?= safe($result['address'] ?? ''); ?>">
                                    <?= shortText(
                                        $result['address'] ?? '',
                                        18
                                    ); ?>
                                </td>

                                <td>
                                    <?= safe(
                                        $result['contact_number'] ?? ''
                                    ); ?>
                                </td>

                                <td>
                                    <?= safe(
                                        $result['next_of_kin'] ?? ''
                                    ); ?>
                                </td>

                                <td>
                                    <?= safe(
                                        $result['next_of_kin_number'] ?? ''
                                    ); ?>
                                </td>

                                <td>
                                    <div class="action-buttons">

                                        <a
                                            href="profile.php?id=<?= $employeeId; ?>"
                                            class="btn-office-icon"
                                            title="View employee">
                                            <i class="bi bi-eye"></i>
                                        </a>

                                        <a
                                            href="edit_emp.php?id=<?= $employeeId; ?>"
                                            class="btn-office-icon btn-office-edit"
                                            title="Edit employee">
                                            <i class="bi bi-pencil"></i>
                                        </a>

                                        <a
                                            href="ass_task.php?id=<?= $employeeId; ?>"
                                            class="btn-office-icon btn-office-assign"
                                            title="Assign task">
                                            <i class="bi bi-plus-circle"></i>
                                        </a>

                                        <a
                                            href="delete_user.php?id=<?= $employeeId; ?>"
                                            class="btn-office-danger"
                                            title="Archive Employee"
                                            onclick="return confirm(
                                                'Archive this employee? The employee will be removed from active lists but their historical reports will remain.'
                                            );">
                                            <i class="bi bi-archive"></i>
                                        </a>

                                    </div>
                                </td>
                            </tr>

                        <?php
                            $count++;
                        endwhile;
                        ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="14" class="no-records">
                                No active employee records were found.
                            </td>
                        </tr>

                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>

            <nav class="mt-4" aria-label="Employee list pagination">
                <ul class="pagination justify-content-center flex-wrap">

                    <li class="page-item <?= $page <= 1 ? 'disabled' : ''; ?>">
                        <a
                            class="page-link"
                            href="?page=<?= max(1, $page - 1); ?>&<?= safe($queryString); ?>">
                            Previous
                        </a>
                    </li>

                    <?php
                    $startPage = max(1, $page - 3);
                    $endPage = min($total_pages, $page + 3);
                    ?>

                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <li class="page-item <?= $page === $i ? 'active' : ''; ?>">
                            <a
                                class="page-link"
                                href="?page=<?= $i; ?>&<?= safe($queryString); ?>">
                                <?= $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : ''; ?>">
                        <a
                            class="page-link"
                            href="?page=<?= min($total_pages, $page + 1); ?>&<?= safe($queryString); ?>">
                            Next
                        </a>
                    </li>

                </ul>
            </nav>

        <?php endif; ?>

    </div>
</div>

<?php
$queryStmt->close();
include '../footer.php';
?>