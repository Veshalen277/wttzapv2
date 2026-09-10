<?php include '../header.php'; ?>



<?php

// Initialize variables

$error_message = "";

$success_message = "";



// Fetch items for dropdown

$sql = "SELECT item_id, item_name FROM sc_stock_items";

$items_result = $con->query($sql);



$item_options = [];

if ($items_result->num_rows > 0) {

    while ($item = $items_result->fetch_assoc()) {

        $item_options[] = [

            'id' => $item['item_id'],

            'name' => htmlspecialchars($item['item_name'])

        ];

    }

}



// Handling form submission

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $items = isset($_POST['items']) ? $_POST['items'] : [];



    foreach ($items as $item) {

        $item_id = isset($item['item_id']) ? intval($item['item_id']) : 0;

        $quantity_sold = isset($item['quantity_sold']) ? intval($item['quantity_sold']) : 0;



        if ($item_id > 0 && $quantity_sold > 0) {

            // Check if the item exists and has sufficient quantity

            $check_sql = "SELECT qty_on_hand FROM sc_stock_items WHERE item_id = ?";

            $stmt = $con->prepare($check_sql);

            $stmt->bind_param("i", $item_id);

            $stmt->execute();

            $stmt->bind_result($qty_on_hand);

            $stmt->fetch();

            $stmt->close();



            if ($qty_on_hand >= $quantity_sold) {

                // Update the stock quantity

                $update_sql = "UPDATE sc_stock_items SET qty_on_hand = qty_on_hand - ? WHERE item_id = ?";

                $stmt = $con->prepare($update_sql);

                $stmt->bind_param("ii", $quantity_sold, $item_id);



                if ($stmt->execute()) {

                    $success_message = "Stock updated successfully. You're on a roll!";

                } else {

                    $error_message = "Error updating stock: " . $con->error;

                }



                $stmt->close();

            } else {

                $error_message = "Not enough stock available for item ID: $item_id.";

            }

        } else {

            $error_message = "Invalid item ID or quantity for item ID: $item_id.";

        }

    }

}



$con->close();

?>



<div class="container mt-5">
    <h2 class="mb-4 text-center">Update Stock</h2>

    <!-- Display Success/Error Modal -->
    <?php if ($success_message || $error_message): ?>
    <div id="messageModal" class="modal fade" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="messageModalLabel"><?php echo $success_message ? 'Success!' : 'Oops!'; ?></h5>
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

    <!-- Stock Update Form -->
    <form id="stockUpdateForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="needs-validation" novalidate>
        <div id="itemsContainer">
            <div class="item-row mb-4">
                <div class="row g-3 align-items-center">
                    <label class="col-sm-3 col-form-label">Select Item:</label>
                    <div class="col-sm-4">
                        <select name="items[0][item_id]" class="form-select" required>
                            <option value="">Select an item</option>
                            <?php foreach ($item_options as $option): ?>
                                <option value="<?php echo $option['id']; ?>"><?php echo $option['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Please select an item.</div>
                    </div>
                    <label class="col-sm-3 col-form-label">Quantity Sold:</label>
                    <div class="col-sm-2">
                        <input type="number" name="items[0][quantity_sold]" class="form-control" min="1" required>
                        <div class="invalid-feedback">Please enter a quantity.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mb-4">
            <div class="d-flex justify-content-between">
                <button type="button" id="addItem" class="btn btn-secondary">Add Another Item</button>
                <button type="button" id="removeItem" class="btn btn-danger">Remove Last Item</button>
            </div>
        </div>

        <div class="mb-3">
            <input type="submit" value="Update Stock" class="btn btn-primary btn-lg w-100">
        </div>
    </form>
</div>

<!-- Styles for better visuals -->
<style>
    .modal-content {
        border-radius: 8px;
    }

    .btn-lg {
        font-size: 1.25rem;
        padding: 0.5rem 1rem;
    }

    .needs-validation .form-select:invalid,
    .needs-validation .form-control:invalid {
        border-color: #dc3545;
    }

    .needs-validation .form-select:valid,
    .needs-validation .form-control:valid {
        border-color: #198754;
    }

    .invalid-feedback {
        display: none;
    }

    .form-select:invalid:focus ~ .invalid-feedback,
    .form-control:invalid:focus ~ .invalid-feedback {
        display: block;
    }
</style>




<script>

    // Define the itemOptions variable globally

    const itemOptions = <?php echo json_encode($item_options); ?>;



    let itemIndex = 1;



    function createItemDropdown(index) {

        const select = document.createElement('select');

        select.name = `items[${index}][item_id]`;

        select.className = 'form-control';

        select.required = true;

        select.innerHTML = '<option value="">Select an item</option>';

        

        itemOptions.forEach(item => {

            const option = document.createElement('option');

            option.value = item.id;

            option.textContent = item.name;

            select.appendChild(option);

        });

        

        return select;

    }



    document.getElementById('addItem').addEventListener('click', function () {

        const container = document.getElementById('itemsContainer');

        const newItemRow = document.createElement('div');

        newItemRow.classList.add('item-row');

        newItemRow.innerHTML = `

            <div class="form-group row">

                <label class="col-sm-2 col-form-label">Select Item:</label>

                <div class="col-sm-4"></div>

                <label class="col-sm-2 col-form-label">Quantity Sold:</label>

                <div class="col-sm-4">

                    <input type="number" name="items[${itemIndex}][quantity_sold]" class="form-control" min="1" required>

                </div>

            </div>

        `;



        // Add item dropdown

        const select = createItemDropdown(itemIndex);

        newItemRow.querySelector('.col-sm-4').appendChild(select);



        container.appendChild(newItemRow);

        itemIndex++;

    });



    document.getElementById('removeItem').addEventListener('click', function () {

        const container = document.getElementById('itemsContainer');

        const rows = container.getElementsByClassName('item-row');

        if (rows.length > 1) {

            container.removeChild(rows[rows.length - 1]);

            itemIndex--;

        }

    });

</script>



<?php include '../footer.php'; ?>

