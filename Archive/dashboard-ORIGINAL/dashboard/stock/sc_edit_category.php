<?php include '../header.php'; ?>

<?php
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $category_name = "";
    
    // Fetch the category details
    $sql = "SELECT * FROM sc_stock_categories WHERE category_id = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $category = $result->fetch_assoc();
        $category_name = $category['category_name'];
    } else {
        $error_message = "Category not found.";
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $category_name = htmlspecialchars($_POST['category_name']);
        
        // Update the category in the database
        $sql = "UPDATE sc_stock_categories SET category_name = ? WHERE category_id = ?";
        $stmt = $con->prepare($sql);
        $stmt->bind_param("si", $category_name, $id);

        if ($stmt->execute()) {
            $success_message = "Category updated successfully.";
            header("Location: sc_manage_categories.php");
            exit();
        } else {
            $error_message = "Error updating category: " . $con->error;
        }
    }
}
?>

<div class="container mt-5">
    <h2>Edit Category</h2>

    <!-- Display error or success messages -->
    <?php
    if (!empty($error_message)) {
        echo '<div class="alert alert-danger" role="alert">' . $error_message . '</div>';
    }
    if (!empty($success_message)) {
        echo '<div class="alert alert-success" role="alert">' . $success_message . '</div>';
    }
    ?>

    <!-- Edit category form -->
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . $id; ?>" method="post" class="form-horizontal">
        <div class="form-group row">
            <label for="category_name" class="col-sm-2 col-form-label">Category Name:</label>
            <div class="col-sm-10">
                <input type="text" id="category_name" name="category_name" value="<?php echo htmlspecialchars($category_name); ?>" class="form-control" required oninput="capitalizeEachWord()">
            </div>
        </div>

        <div class="form-group row">
            <div class="col-sm-10 offset-sm-2">
                <input type="submit" value="Update Category" class="btn btn-primary">
            </div>
        </div>
    </form>
</div>

<script>
    function capitalizeEachWord() {
        let input = document.getElementById("category_name");
        let words = input.value.split(' ');

        for (let i = 0; i < words.length; i++) {
            if (words[i] !== '') {
                words[i] = words[i].charAt(0).toUpperCase() + words[i].slice(1).toLowerCase();
            }
        }

        input.value = words.join(' ');
    }
</script>

<?php include '../footer.php'; ?>
