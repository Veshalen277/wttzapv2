<?php
include '../header.php';

$success_message = isset($_GET['success_message']) ? urldecode($_GET['success_message']) : '';
$error_message = isset($_GET['error_message']) ? urldecode($_GET['error_message']) : '';

// Pagination settings
$limit = 12; // Number of categories per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Fetch total number of categories
$total_categories_sql = "SELECT COUNT(*) AS total FROM sc_stock_categories";
$total_categories_result = $con->query($total_categories_sql);
$total_categories_row = $total_categories_result->fetch_assoc();
$total_categories = $total_categories_row['total'];
$total_pages = ceil($total_categories / $limit);

// Fetch categories with item count and batch details
$sql = "
    SELECT 
        c.category_id, 
        c.category_name, 
        COUNT(s.item_id) AS item_count
    FROM 
        sc_stock_categories c
    LEFT JOIN 
        sc_stock_items s ON c.category_id = s.category_id
    GROUP BY 
        c.category_id
    LIMIT $limit OFFSET $offset
";
$result = $con->query($sql);
?>

<div class="container-fluid">
    <h2>Inventory</h2>

    <a href="sc_add_category.php" class="btn btn-sm btn-primary my-2">Add New Category</a>
    <a href="export_categories.php" class="btn btn-danger my-2">Export to CSV</a> <!-- Export to CSV Button -->

    <!-- Display Success/Error Modal -->
    <?php if ($success_message || $error_message): ?>
        <div id="messageModal" class="modal fade" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="messageModalLabel"><?php echo $success_message ? 'Success' : 'Error'; ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <?php echo $success_message ? htmlspecialchars($success_message) : htmlspecialchars($error_message); ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (<?php echo json_encode($success_message || $error_message); ?>) {
                    const messageModal = new bootstrap.Modal(document.getElementById('messageModal'));
                    messageModal.show();
                }
            });
        </script>
    <?php endif; ?>

    <!-- Fetch and display categories -->
    <?php
    if ($result->num_rows > 0) {
        echo '<div class="row">';
        while ($row = $result->fetch_assoc()) {
            echo '<div class="col-md-4 mb-3">'; // Adjusted to create 3 columns per row
            echo '<div class="card h-100">';
            echo '<div class="card-body">';
            echo '<h5 class="card-title">' . htmlspecialchars($row['category_name']) . '</h5>';
            echo '<p class="card-text">ID: ' . $row['category_id'] . '</p>';
            echo '<p class="card-text">Items in Category: ' . $row['item_count'] . '</p>';
            echo '</div>';
            echo '<div class="card-footer">';
            echo '<a href="sc_edit_category.php?id=' . $row['category_id'] . '" class="btn btn-warning btn-sm"><i class="bi bi-pencil"></i></a> ';
            echo '<a href="sc_edit_items_by_category.php?category_id=' . $row['category_id'] . '" class="btn btn-info btn-sm"><i class="bi bi-eye"></i></a> ';
            echo '<a class="btn btn-danger btn-sm" onclick="showDeleteModal(' . $row['category_id'] . ')"><i class="bi bi-trash3-fill"></i></a>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
    } else {
        echo '<div class="alert alert-info">No categories found.</div>';
    }
    ?>

    <!-- Pagination Links -->
    <nav aria-label="Page navigation">
        <ul class="pagination justify-content-center">
            <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?>" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?>" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
</div>

<!-- Modal for Delete Confirmation -->
<div id="deleteModal" class="modal fade" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this category? This action cannot be undone, and all associated stock items will also be permanently removed.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Yes I'm Sure!</button>
            </div>
        </div>
    </div>
</div>

<script>
    let deleteCategoryId;

    function showDeleteModal(categoryId) {
        deleteCategoryId = categoryId;
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        deleteModal.show();
    }

    document.getElementById('confirmDeleteBtn').addEventListener('click', function () {
        window.location.href = 'sc_delete_category.php?id=' + deleteCategoryId;
    });
</script>

<?php include '../footer.php'; ?>
