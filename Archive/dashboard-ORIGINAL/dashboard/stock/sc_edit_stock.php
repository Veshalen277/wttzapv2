<?php
include '../header.php';


// Retrieve stock item details
$item_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($item_id <= 0) {
    echo "<p>Invalid stock item ID.</p>";
    exit;
}

// Fetch the item details from the database
$sql = "SELECT * FROM sc_stock_items WHERE item_id = $item_id";
$result = mysqli_query($con, $sql);

// Check if the query was successful
if (!$result) {
    echo "<p>Error executing query: " . mysqli_error($con) . "</p>";
    exit;
}

if (mysqli_num_rows($result) == 0) {
    echo "<p>Stock item not found.</p>";
    exit;
}

$item = mysqli_fetch_assoc($result);

// Ensure all fields are not null before applying htmlspecialchars
$item_name = htmlspecialchars($item['item_name'] ?? '');
$category_id = htmlspecialchars($item['category_id'] ?? '');
$cost_price = htmlspecialchars($item['cost_price'] ?? '');
$retail_price = htmlspecialchars($item['retail_price'] ?? '');
$qty_on_hand = htmlspecialchars($item['qty_on_hand'] ?? '');
$order_qty = htmlspecialchars($item['order_qty'] ?? '');
$received_qty = htmlspecialchars($item['received_qty'] ?? '');
$description = htmlspecialchars($item['description'] ?? '');
$supplier = htmlspecialchars($item['supplier'] ?? '');
?>

<div class="container mt-5">
    <h2 class="mb-4">Edit Stock Item</h2>

    <!-- Form to Edit Stock Items -->
    <form action="sc_process_edit_stock.php" method="post" class="form-horizontal">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($item['item_id']); ?>">

        <div class="form-group row">
            <label for="item_name" class="col-sm-2 col-form-label">Item Name:</label>
            <div class="col-sm-10">
                <input type="text" id="item_name" name="item_name" class="form-control" required value="<?php echo $item_name; ?>" oninput="capitalizeEachWord()">
            </div>
        </div>

        <div class="form-group row">
            <label for="category_id" class="col-sm-2 col-form-label">Category:</label>
            <div class="col-sm-10">
                <select id="category_id" name="category_id" class="form-control" required>
                    <?php
                    // Fetch categories from the database and populate the dropdown
                    $sql_categories = "SELECT * FROM sc_stock_categories";
                    $result_categories = mysqli_query($con, $sql_categories);

                    if (!$result_categories) {
                        echo "<p>Error executing query: " . mysqli_error($con) . "</p>";
                        exit;
                    }

                    while ($row = mysqli_fetch_assoc($result_categories)) {
                        $selected = ($row['category_id'] == $item['category_id']) ? 'selected' : '';
                        echo "<option value='" . htmlspecialchars($row['category_id']) . "' $selected>" . htmlspecialchars($row['category_name']) . "</option>";
                    }
                    ?>
                </select>
            </div>
        </div>

        <div class="form-group row">
            <label for="cost_price" class="col-sm-2 col-form-label">Cost Price:</label>
            <div class="col-sm-10">
                <input type="number" id="cost_price" name="cost_price" class="form-control" step="0.01" min="0" value="<?php echo $cost_price; ?>">
            </div>
        </div>

        <div class="form-group row">
            <label for="retail_price" class="col-sm-2 col-form-label">Retail Price:</label>
            <div class="col-sm-10">
                <input type="number" id="retail_price" name="retail_price" class="form-control" step="0.01" min="0" value="<?php echo $retail_price; ?>">
            </div>
        </div>

        <div class="form-group row">
            <label for="qty_on_hand" class="col-sm-2 col-form-label">Quantity on Hand:</label>
            <div class="col-sm-10">
                <input type="number" id="qty_on_hand" name="qty_on_hand" class="form-control" min="0" value="<?php echo $qty_on_hand; ?>">
            </div>
        </div>

        <div class="form-group row">
            <label for="order_qty" class="col-sm-2 col-form-label">Order Quantity (Optional):</label>
            <div class="col-sm-10">
                <input type="number" id="order_qty" name="order_qty" class="form-control" min="0" placeholder="Leave blank if not ordered" value="<?php echo $order_qty; ?>">
            </div>
        </div>

        <div class="form-group row">
            <label for="received_qty" class="col-sm-2 col-form-label">Received Quantity (Optional):</label>
            <div class="col-sm-10">
                <input type="number" id="received_qty" name="received_qty" class="form-control" min="0" placeholder="Leave blank if not received" value="<?php echo $received_qty; ?>">
            </div>
        </div>

        <div class="form-group row">
            <label for="description" class="col-sm-2 col-form-label">Description:</label>
            <div class="col-sm-10">
                <textarea id="description" name="description" class="form-control" rows="4" placeholder="Provide a brief description"><?php echo $description; ?></textarea>
            </div>
        </div>

        <div class="form-group row">
            <label for="supplier" class="col-sm-2 col-form-label">Supplier:</label>
            <div class="col-sm-10">
                <input type="text" id="supplier" name="supplier" class="form-control" placeholder="Enter supplier's name" value="<?php echo $supplier; ?>">
            </div>
        </div>

        <div class="form-group row">
            <div class="col-sm-10 offset-sm-2">
                <input type="submit" value="Update Item" class="btn btn-primary">
            </div>
        </div>
    </form>

    <script>
        function capitalizeEachWord() {
            let input = document.getElementById("item_name");
            let words = input.value.split(' ');

            for (let i = 0; i < words.length; i++) {
                if (words[i] !== '') {
                    words[i] = words[i].charAt(0).toUpperCase() + words[i].slice(1).toLowerCase();
                }
            }

            input.value = words.join(' ');
        }
    </script>
</div>

<?php include '../footer.php'; ?>
