<?php include 'header.php'; ?>


<div class="container p-5">
    
<h2 class="mt-4 mb-3">Stock Report</h2>

<!-- Form to Update Stock Items -->
<form action="fs_process_stock_report.php" method="post" class="form-horizontal">
    <div class="form-group row">
        <label for="item_id" class="col-sm-2 col-form-label">Item:</label>
        <div class="col-sm-10">
            <select id="item_id" name="item_id" class="form-control" required>
                <!-- Populate this dropdown with items dynamically from database -->
                <?php
                // Fetch items from database and populate dropdown
                $sql = "SELECT * FROM fs_stock_items";
                $result = mysqli_query($con, $sql);
                while ($row = mysqli_fetch_assoc($result)) {
                    echo "<option value='" . $row['item_id'] . "'>" . $row['item_name'] . "</option>";
                }
                ?>
            </select>
        </div>
    </div>

    <div class="form-group row">
        <label for="qty_on_hand" class="col-sm-2 col-form-label">Qty on Hand:</label>
        <div class="col-sm-10">
            <input type="number" id="qty_on_hand" name="qty_on_hand" class="form-control" required>
        </div>
    </div>

    <div class="form-group row">
        <label for="order_qty" class="col-sm-2 col-form-label">Order Qty:</label>
        <div class="col-sm-10">
            <input type="number" id="order_qty" name="order_qty" class="form-control" required>
        </div>
    </div>

    <div class="form-group row">
        <label for="received_qty" class="col-sm-2 col-form-label">Received Qty:</label>
        <div class="col-sm-10">
            <input type="number" id="received_qty" name="received_qty" class="form-control" required>
        </div>
    </div>

    <div class="form-group row">
        <div class="col-sm-10 offset-sm-2">
            <input type="submit" value="Update Stock" class="btn btn-primary">
        </div>
    </div>
</form>
</div>

<?php include 'footer.php'; ?>
