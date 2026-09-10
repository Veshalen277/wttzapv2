<?php
// Start or resume session
include '../header.php';
if ($role != 7 && $role != 5 && $role !=0) {
    header("Location: /dashboard/404.php");
    exit();
}
// Check if user is logged in
if (!isset($_SESSION['u_data'])) {
    // Redirect to login page if not logged in
    header("Location: login.php");
    exit();
}

// Pagination configuration
$items_per_page = 20;
$current_page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$start_from = ($current_page - 1) * $items_per_page;

// Fetch distinct order_id values
$order_sql = "SELECT DISTINCT order_id FROM order_items";
$order_query = mysqli_query($con, $order_sql);
$orders = mysqli_fetch_all($order_query, MYSQLI_ASSOC);

// Handle bulk item deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_items'])) {
    if (isset($_POST['item_ids']) && is_array($_POST['item_ids'])) {
        $item_ids = array_map('intval', $_POST['item_ids']); // Sanitize the IDs as integers

        if (empty($item_ids)) {
            $_SESSION['msg'] = "No items selected for deletion.";
            $_SESSION['msg_type'] = "error";
        } else {
            $item_ids_str = implode(',', $item_ids); // Create a comma-separated string of IDs for SQL

            // Perform the deletion query
            $sql_delete = "DELETE FROM order_items WHERE id IN ($item_ids_str)";
            $delete_query = mysqli_query($con, $sql_delete);

            if ($delete_query) {
                $_SESSION['msg'] = "Selected items deleted successfully.";
                $_SESSION['msg_type'] = "success";
                header("Location: orderBoard.php?page=$current_page");
                exit();
            } else {
                $_SESSION['msg'] = "Error deleting items: " . mysqli_error($con);
                $_SESSION['msg_type'] = "error";
            }
        }
    } else {
        $_SESSION['msg'] = "No items selected for deletion.";
        $_SESSION['msg_type'] = "error";
    }
}

// Base SQL query
$sql = "SELECT id, product, quantity, order_id
        FROM order_items";

// Array to store conditions
$conditions = array();

// Filter by product
if (!empty($_GET['product'])) {
    $product = mysqli_real_escape_string($con, $_GET['product']);
    $conditions[] = "product LIKE '%$product%'";
}

// Filter by order_id
if (!empty($_GET['order_id'])) {
    $order_id = mysqli_real_escape_string($con, $_GET['order_id']);
    $conditions[] = "order_id = '$order_id'";
}

// Add WHERE clause if conditions exist
if (!empty($conditions)) {
    $sql .= " WHERE " . implode(' AND ', $conditions);
}

// Add ORDER BY and LIMIT clauses
$sql .= " ORDER BY order_id DESC
         LIMIT $start_from, $items_per_page";

$result = mysqli_query($con, $sql);

// Count total items (for pagination)
$sql_count = "SELECT COUNT(*) AS total_items FROM order_items";
$count_result = mysqli_query($con, $sql_count);
$total_items = mysqli_fetch_assoc($count_result)['total_items'];
$total_pages = ceil($total_items / $items_per_page);

?>

<style>
    /* Optional custom styles for form inputs and buttons */
.form-control, .btn {
    border-radius: 5px;
    transition: box-shadow 0.3s ease-in-out;
}

.form-control:focus, .btn:hover {
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

.table th, .table td {
    vertical-align: middle;
}

</style>
<div class="container-fluid p-4">
    <!-- Filter Form -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h4 class="card-title">Apply Filters</h4>
            <form method="get" action="orderBoard.php">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="product" class="form-label">Product:</label>
                        <input type="text" class="form-control" id="product" name="product"
                               value="<?= isset($_GET['product']) ? htmlspecialchars($_GET['product']) : ''; ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="order_id" class="form-label">Order ID:</label>
                        <select class="form-control" id="order_id" name="order_id">
                            <option value="">Order ID</option>
                            <?php foreach ($orders as $order): ?>
                                <option value="<?= $order['order_id'] ?>"
                                    <?= isset($_GET['order_id']) && $_GET['order_id'] == $order['order_id'] ? 'selected' : '' ?>>
                                    <?= $order['order_id'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary mt-3">Apply Filters</button>
            </form>
        </div>
    </div>
    <!-- End of Filter Form -->

    <!-- Order Items Table -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h4 class="card-title">Order Items</h4>
            <form method="post" action="orderBoard.php?page=<?= $current_page ?>">
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead class="table-dark text-dark">
                                <tr>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Order ID</th>
                                    <th>Delete</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td><?= $row['product'] ?></td>
                                        <td><?= $row['quantity'] ?></td>
                                        <td><?= $row['order_id'] ?></td>
                                        <td><input type="checkbox" name="item_ids[]" value="<?= $row['id'] ?>"></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <button type="submit" name="delete_items" class="btn btn-danger"
                            onclick="return confirm('Are you sure you want to delete selected items?')">
                        Delete Selected
                    </button>
                <?php else: ?>
                    <p class="text-muted">No items found.</p>
                <?php endif; ?>
            </form>
        </div>
    </div>
    <!-- End of Order Items Table -->

    <!-- Pagination Links -->
    <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center mt-4">
                <?php if ($current_page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="orderBoard.php?page=<?= $current_page - 1 ?>&product=<?= urlencode($_GET['product']) ?>&order_id=<?= urlencode($_GET['order_id']) ?>">Previous</a>
                    </li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                        <a class="page-link" href="orderBoard.php?page=<?= $i ?>&product=<?= urlencode($_GET['product']) ?>&order_id=<?= urlencode($_GET['order_id']) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($current_page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="orderBoard.php?page=<?= $current_page + 1 ?>&product=<?= urlencode($_GET['product']) ?>&order_id=<?= urlencode($_GET['order_id']) ?>">Next</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<?php
// Include footer
include '../footer.php';
?>
