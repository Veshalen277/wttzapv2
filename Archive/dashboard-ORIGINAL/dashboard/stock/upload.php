<?php
// Include the header file for database conection and authentication
include '../header.php'; // Ensure this path is correct

// Handle file upload and processing
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['csvfile'])) {
    // Check for upload errors
    if ($_FILES['csvfile']['error'] !== UPLOAD_ERR_OK) {
        die("File upload error: " . $_FILES['csvfile']['error']);
    }

    // Open the uploaded CSV file
    if (($handle = fopen($_FILES['csvfile']['tmp_name'], 'r')) !== FALSE) {
        // Skip the header row if present
        $header = fgetcsv($handle);

        // Prepare SQL statement with ON DUPLICATE KEY UPDATE to handle both inserts and updates
        $sql = "INSERT INTO sc_stock_items 
                    (item_id, item_name, category_id, qty_on_hand, order_qty, received_qty, cost_price, retail_price) 
                VALUES 
                    (?, ?, ?, ?, ?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                    item_name = VALUES(item_name),
                    category_id = VALUES(category_id),
                    qty_on_hand = VALUES(qty_on_hand),
                    order_qty = VALUES(order_qty),
                    received_qty = VALUES(received_qty),
                    cost_price = VALUES(cost_price),
                    retail_price = VALUES(retail_price)";

        // Prepare the statement
        $stmt = $con->prepare($sql);

        // Check if the statement preparation was successful
        if (!$stmt) {
            die("Statement preparation failed: " . $con->error);
        }

        // Bind parameters
        $stmt->bind_param("isiiiiid", $item_id, $item_name, $category_id, $qty_on_hand, $order_qty, $received_qty, $cost_price, $retail_price);

        // Read the CSV file and process each row
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            // Assuming CSV columns: item_id, item_name, category_id, qty_on_hand, order_qty, received_qty, cost_price, retail_price
            $item_id = (int)$data[0];
            $item_name = $con->real_escape_string($data[1]);
            $category_id = (int)$data[2];
            $qty_on_hand = (int)$data[3];
            $order_qty = (int)$data[4];
            $received_qty = (int)$data[5];
            $cost_price = (float)$data[6];
            $retail_price = (float)$data[7];

            // Execute the prepared statement
            if (!$stmt->execute()) {
                echo "Error processing row: " . $stmt->error . "<br>";
            }
        }

        fclose($handle);
        $stmt->close();
        echo "CSV file processed successfully.";
    } else {
        die("Unable to open the file.");
    }

    // Close the database conection (if not handled in header.php)
    $con->close();
}
?>



<div class="container mt-5">
    <h1 class="mb-4 text-center">Upload CSV File</h1>
    
    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
        <div class="mb-4">
            <label for="csvfile" class="form-label">Select CSV File:</label>
            <input type="file" name="csvfile" id="csvfile" class="form-control" accept=".csv" required>
            <div class="invalid-feedback">Please upload a valid CSV file.</div>
        </div>
        
        <div class="mb-4">
            <input type="submit" value="Upload" class="btn btn-primary btn-lg w-100">
        </div>
    </form>
</div>

<!-- Styles for better visuals -->
<style>
    .form-label {
        font-weight: bold;
    }

    .btn-lg {
        font-size: 1.25rem;
        padding: 0.5rem 1rem;
    }

    .needs-validation .form-control:invalid {
        border-color: #dc3545;
    }

    .needs-validation .form-control:valid {
        border-color: #198754;
    }

    .invalid-feedback {
        display: none;
    }

    .form-control:invalid:focus ~ .invalid-feedback {
        display: block;
    }
</style>

<script>
    (function () {
        'use strict';
        window.addEventListener('load', function () {
            const forms = document.getElementsByClassName('needs-validation');
            for (let i = 0; i < forms.length; i++) {
                forms[i].addEventListener('submit', function (event) {
                    if (!this.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    this.classList.add('was-validated');
                }, false);
            }
        }, false);
    })();
</script>

    <?php include '../footer.php';?>

