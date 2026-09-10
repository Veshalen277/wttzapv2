<?php include '../header.php'; ?>

<div class="container mt-5">
    <h2 class="mb-4">Add New Stock Item</h2>

    <!-- Form to Add New Stock Items -->
    <form action="process_stock.php" method="post" class="form-horizontal">
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
                    $sql = "SELECT * FROM stock_categories";
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

</div>

<?php include '../footer.php'; ?>
