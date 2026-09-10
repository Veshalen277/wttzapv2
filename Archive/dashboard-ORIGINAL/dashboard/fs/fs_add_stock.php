<?php include 'header.php'; ?>

<div class="container mt-5">
    <h2 class="mb-4">Add New Stock Item</h2>

    <!-- Form to Add New Stock Items -->
    <form action="fs_process_stock.php" method="post" class="form-horizontal">
        <div class="form-group row">
            <label for="item_name" class="col-sm-2 col-form-label">Item Name:</label>
            <!-- <div class="col-sm-10">
                <input type="text" id="item_name" name="item_name" class="form-control" required>
            </div> -->
            <div class="col-sm-10">
    <input type="text" id="item_name" name="item_name" class="form-control" required oninput="capitalizeEachWord()">
</div>

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

        <div class="form-group row">
            <label for="category_id" class="col-sm-2 col-form-label">Category:</label>
            <div class="col-sm-10">
                <select id="category_id" name="category_id" class="form-control" required>
                    <!-- Populate this dropdown with categories dynamically from database -->
                    <?php
                    // Fetch categories from database and populate dropdown
                    $sql = "SELECT * FROM fs_stock_categories";
                    $result = mysqli_query($con, $sql);
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo "<option value='" . $row['category_id'] . "'>" . $row['category_name'] . "</option>";
                    }
                    ?>
                </select>
            </div>
        </div>

        <div class="form-group row">
            <div class="col-sm-10 offset-sm-2">
                <input type="submit" value="Add Item" class="btn btn-primary">
            </div>
        </div>
    </form>
<!-- 
    <h2 class="mt-5">Stock Items</h2> -->
    <!-- </?php
    $sql_categories = "SELECT * FROM fs_stock_categories";
    $result_categories = mysqli_query($con, $sql_categories);

    while ($category = mysqli_fetch_assoc($result_categories)) {
        echo "<h3 class='mt-4'>" . $category['category_name'] . "</h3>";

        $category_id = $category['category_id'];
        $sql_items = "SELECT * FROM fs_stock_items WHERE category_id = $category_id";
        $result_items = mysqli_query($con, $sql_items);

        echo "<table class='table table-bordered'>";
        echo "<thead class='thead-light'><tr><th>Item Name</th><th>Qty on Hand</th><th>Order Qty</th><th>Received Qty</th></tr></thead>";
        echo "<tbody>";

        while ($item = mysqli_fetch_assoc($result_items)) {
            echo "<tr>";
            echo "<td>" . $item['item_name'] . "</td>";
            echo "<td>" . $item['qty_on_hand'] . "</td>";
            echo "<td>" . $item['order_qty'] . "</td>";
            echo "<td>" . $item['received_qty'] . "</td>";
            echo "</tr>";
        }

        echo "</tbody>";
        echo "</table>";
    }
    ?> -->
</div>

<?php include 'footer.php'; ?>
