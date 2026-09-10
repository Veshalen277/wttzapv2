<?php
include '../header.php';
// Check if category_id is set
if (!isset($_GET['category_id']) || !is_numeric($_GET['category_id'])) {
    echo '<div class="alert alert-danger">Invalid category ID.</div>';
    include 'footer.php';
    exit();
}

$category_id = intval($_GET['category_id']);

// Fetch category name
$sql = "SELECT category_name FROM sc_stock_categories WHERE category_id = ?";
$stmt = $con->prepare($sql);
$stmt->bind_param("i", $category_id);
$stmt->execute();
$stmt->bind_result($category_name);
$stmt->fetch();
$stmt->close();

if (!$category_name) {
    echo '<div class="alert alert-danger">Category not found.</div>';
    include 'footer.php';
    exit();
}

// Fetch items for the selected category
$sql = "SELECT * FROM sc_stock_items WHERE category_id = ?";
$stmt = $con->prepare($sql);
$stmt->bind_param("i", $category_id);
$stmt->execute();
$result = $stmt->get_result();

?>

<div class="container-fluid mt-5">
    <h2>Items in Category: <?php echo htmlspecialchars($category_name); ?></h2>
    <a href="sc_add_stock.php?category_id=<?php echo $category_id; ?>" class="btn btn-sm btn-primary my-2">Add New Item</a>

    <!-- Display items -->
    <?php
    if ($result->num_rows > 0) {
        echo '<div class="container-fluid emp_profile">';
        echo '<table class="table table-bordered">';
        echo '<tr><th>ID</th><th>Item Name</th><th>Quantity on Hand</th><th>Actions</th></tr>';
        while ($row = $result->fetch_assoc()) {
            echo '<tr>';
            echo '<td>' . $row['item_id'] . '</td>';
            echo '<td>' . htmlspecialchars($row['item_name']) . '</td>';
            echo '<td>' . htmlspecialchars($row['qty_on_hand']) . '</td>';
            echo '<td class="action-buttons">';
            echo '<a href="sc_edit_stock.php?id=' . $row['item_id'] . '" class="btn btn-warning btn-sm edit-btn"><i class="bi bi-pencil"></i> <span class="d-none d-md-inline">Edit</span></a> ';
            echo '<button class="btn btn-danger btn-sm delete-btn" onclick="showDeleteItemModal(' . $row['item_id'] . ')"><i class="bi bi-trash"></i> <span class="d-none d-md-inline">Delete</span></button>';
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';
        echo '</div>';
    } else {
        echo '<div class="alert alert-info">No items found for this category.</div>';
    }
    $stmt->close();
    ?>
</div>

<!-- Modal for Item Deletion Confirmation -->
<div id="deleteItemModal" class="modal fade" tabindex="-1" aria-labelledby="deleteItemModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteItemModalLabel">Confirm Item Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this item? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteItemBtn">Yes I'm Sure!</button>
            </div>
        </div>
    </div>
</div>

<script>
    let deleteItemId;

    function showDeleteItemModal(itemId) {
        deleteItemId = itemId;
        const deleteItemModal = new bootstrap.Modal(document.getElementById('deleteItemModal'));
        deleteItemModal.show();
    }

    document.getElementById('confirmDeleteItemBtn').addEventListener('click', function () {
        if (deleteItemId) {
            window.location.href = 'sc_delete_stock.php?id=' + deleteItemId;
        }
    });
</script>

<style>
    /* Hide text and show icons on mobile devices */
    @media (max-width: 767.98px) {
        .action-buttons .btn {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .action-buttons .btn span {
            display: none;
        }
        .action-buttons .btn i {
            font-size: 1.2rem;
        }
    }
</style>

<?php include '../footer.php'; ?>
