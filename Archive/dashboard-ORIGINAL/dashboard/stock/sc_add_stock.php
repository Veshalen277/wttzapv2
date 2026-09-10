<?php
include '../header.php'; 


// Retrieve category ID from URL parameter or POST data if available
$selected_category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : (isset($_POST['category_id']) ? intval($_POST['category_id']) : 0);

// Retrieve message and message_type from URL parameters
$message = isset($_GET['message']) ? htmlspecialchars($_GET['message']) : '';
$message_type = isset($_GET['message_type']) ? htmlspecialchars($_GET['message_type']) : '';

// Debugging: Output the message and message type
echo "<!-- Debug: Message = $message, Message Type = $message_type -->";
?>

<div class="container mt-5">
    <h2 class="mb-4">Add New Stock Item</h2>

    <!-- Form to Add New Stock Items -->
    <form action="sc_process_stock.php" method="post" class="form-horizontal">
        <div class="mb-3 row">
            <label for="item_name" class="col-sm-2 col-form-label">Item Name:</label>
            <div class="col-sm-10">
                <input type="text" id="item_name" name="item_name" class="form-control" required oninput="capitalizeEachWord()">
            </div>
        </div>

        <div class="mb-3 row">
            <label for="category_id" class="col-sm-2 col-form-label">Category:</label>
            <div class="col-sm-10">
                <select id="category_id" name="category_id" class="form-select" required>
                    <?php
                    // Fetch categories from the database and populate dropdown
                    $sql = "SELECT * FROM sc_stock_categories";
                    $result = mysqli_query($con, $sql);
                    while ($row = mysqli_fetch_assoc($result)) {
                        // Set 'selected' attribute if this category matches the selected category ID
                        $selected = ($row['category_id'] == $selected_category_id) ? 'selected' : '';
                        echo "<option value='" . htmlspecialchars($row['category_id']) . "' $selected>" . htmlspecialchars($row['category_name']) . "</option>";
                    }
                    ?>
                </select>
            </div>
        </div>

        <div class="mb-3 row">
            <label for="cost_price" class="col-sm-2 col-form-label">Cost Price:</label>
            <div class="col-sm-10">
                <input type="number" id="cost_price" name="cost_price" class="form-control" step="0.01" min="0">
            </div>
        </div>

        <div class="mb-3 row">
            <label for="retail_price" class="col-sm-2 col-form-label">Retail Price:</label>
            <div class="col-sm-10">
                <input type="number" id="retail_price" name="retail_price" class="form-control" step="0.01" min="0">
            </div>
        </div>

        <div class="mb-3 row">
            <label for="qty_on_hand" class="col-sm-2 col-form-label">Quantity on Hand:</label>
            <div class="col-sm-10">
                <input type="number" id="qty_on_hand" name="qty_on_hand" class="form-control" min="0">
            </div>
        </div>

        <div class="mb-3 row">
            <label for="order_qty" class="col-sm-2 col-form-label">Order Quantity (Optional):</label>
            <div class="col-sm-10">
                <input type="number" id="order_qty" name="order_qty" class="form-control" min="0" placeholder="Leave blank if not ordered">
            </div>
        </div>

        <div class="mb-3 row">
            <label for="received_qty" class="col-sm-2 col-form-label">Received Quantity (Optional):</label>
            <div class="col-sm-10">
                <input type="number" id="received_qty" name="received_qty" class="form-control" min="0" placeholder="Leave blank if not received">
            </div>
        </div>

        <div class="mb-3 row">
            <label for="description" class="col-sm-2 col-form-label">Description:</label>
            <div class="col-sm-10">
                <textarea id="description" name="description" class="form-control" rows="4" placeholder="Product descriptions need to be as specific as possible"></textarea>
            </div>
        </div>

        <div class="mb-3 row">
            <label for="supplier" class="col-sm-2 col-form-label">Supplier:</label>
            <div class="col-sm-10">
                <input type="text" id="supplier" name="supplier" class="form-control" placeholder="Enter supplier's name">
            </div>
        </div>

        <div class="mb-3 row">
            <div class="col-sm-10 offset-sm-2">
                <input type="submit" value="Add Item" class="btn btn-primary">
            </div>
        </div>
    </form>

    <!-- Modal for success/error messages -->
    <div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="messageModalLabel">Notification</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php echo $message; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="window.location.href='sc_add_stock.php'">Add Another Item</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/5.3.0/js/bootstrap.min.js"></script>

    <script>
        // Show the modal if there's a message
        <?php if ($message): ?>
            document.addEventListener('DOMContentLoaded', function () {
                var messageModal = new bootstrap.Modal(document.getElementById('messageModal'));
                messageModal.show();
            });
        <?php endif; ?>
    </script>
</div>

<?php include '../footer.php'; ?>




<!-- </?php
include 'header.php'; 
include 'config.php';

// Retrieve category ID from URL parameter or POST data if available
$selected_category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : (isset($_POST['category_id']) ? intval($_POST['category_id']) : 0);

// Debugging: Output the selected category ID
echo " Debug: Selected Category ID is $selected_category_id ";

// Retrieve message and message_type from URL parameters
$message = isset($_GET['message']) ? $_GET['message'] : '';
$message_type = isset($_GET['message_type']) ? $_GET['message_type'] : '';
?>

<div class="container mt-5">
    <h2 class="mb-4">Add New Stock Item</h2>

  Form to Add New Stock Items 
    <form action="sc_process_stock.php" method="post" class="form-horizontal">
        <div class="form-group row">
            <label for="item_name" class="col-sm-2 col-form-label">Item Name:</label>
            <div class="col-sm-10">
                <input type="text" id="item_name" name="item_name" class="form-control" required oninput="capitalizeEachWord()">
            </div>
        </div>

        <div class="form-group row">
            <label for="category_id" class="col-sm-2 col-form-label">Category:</label>
            <div class="col-sm-10">
                <select id="category_id" name="category_id" class="form-control" required>
                    </?php
                    // Fetch categories from the database and populate dropdown
                    $sql = "SELECT * FROM sc_stock_categories";
                    $result = mysqli_query($con, $sql);
                    while ($row = mysqli_fetch_assoc($result)) {
                        // Set 'selected' attribute if this category matches the selected category ID
                        $selected = ($row['category_id'] == $selected_category_id) ? 'selected' : '';
                        echo "<option value='" . htmlspecialchars($row['category_id']) . "' $selected>" . htmlspecialchars($row['category_name']) . "</option>";
                    }
                    ?>
                </select>
            </div>
        </div>

        <div class="form-group row">
            <label for="cost_price" class="col-sm-2 col-form-label">Cost Price:</label>
            <div class="col-sm-10">
                <input type="number" id="cost_price" name="cost_price" class="form-control" step="0.01" min="0">
            </div>
        </div>

        <div class="form-group row">
            <label for="retail_price" class="col-sm-2 col-form-label">Retail Price:</label>
            <div class="col-sm-10">
                <input type="number" id="retail_price" name="retail_price" class="form-control" step="0.01" min="0">
            </div>
        </div>

        <div class="form-group row">
            <label for="qty_on_hand" class="col-sm-2 col-form-label">Quantity on Hand:</label>
            <div class="col-sm-10">
                <input type="number" id="qty_on_hand" name="qty_on_hand" class="form-control" min="0">
            </div>
        </div>

        <div class="form-group row">
            <label for="order_qty" class="col-sm-2 col-form-label">Order Quantity (Optional):</label>
            <div class="col-sm-10">
                <input type="number" id="order_qty" name="order_qty" class="form-control" min="0" placeholder="Leave blank if not ordered">
            </div>
        </div>

        <div class="form-group row">
            <label for="received_qty" class="col-sm-2 col-form-label">Received Quantity (Optional):</label>
            <div class="col-sm-10">
                <input type="number" id="received_qty" name="received_qty" class="form-control" min="0" placeholder="Leave blank if not received">
            </div>
        </div>

        <div class="form-group row">
            <label for="description" class="col-sm-2 col-form-label">Description:</label>
            <div class="col-sm-10">
                <textarea id="description" name="description" class="form-control" rows="4" placeholder="Product descriptions need to be as specific as possible"></textarea>
            </div>
        </div>

        <div class="form-group row">
            <label for="supplier" class="col-sm-2 col-form-label">Supplier:</label>
            <div class="col-sm-10">
                <input type="text" id="supplier" name="supplier" class="form-control" placeholder="Enter supplier's name">
            </div>
        </div>

        <div class="form-group row">
            <div class="col-sm-10 offset-sm-2">
                <input type="submit" value="Add Item" class="btn btn-primary">
            </div>
        </div>
    </form>

   Modal for success/error messages 
    <div class="modal fade" id="messageModal" tabindex="-1" role="dialog" aria-labelledby="messageModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="messageModalLabel">Notification</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    </?php echo htmlspecialchars($message); ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="window.location.href='sc_add_stock.php'">Add Another Item</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Show the modal if there's a message
        </?php if ($message): ?>
            $(document).ready(function() {
                $('#messageModal').modal('show');
            });
        </?php endif; ?>
    </script>
</div>

</?php include 'footer.php'; ?> -->
