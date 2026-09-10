<?php include '../header.php'; 

// if ($role != 5 || $role != 2) {

//     header("Location: /dashboard/404.php");

//     exit();

// }


?>

<?php
// Initialize variables
$error_message = "";
$success_message = "";
$category_name = ""; // Initialize category_name variable

// Handling form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize input
    if (!empty($_POST['category_name'])) {
        $category_name = htmlspecialchars($_POST['category_name']);
        

        // Prepare SQL statement
        $sql = "INSERT INTO sc_stock_categories (category_name) VALUES (?)";

        // Prepare and bind parameters
        $stmt = $con->prepare($sql);
        $stmt->bind_param("s", $category_name);

        // Execute the query
        if ($stmt->execute()) {
            $success_message = "Category added successfully.";
            echo '<script>$(document).ready(function() { $("#successModal").modal("show"); });</script>';
        } else {
            $error_message = "Error: " . $sql . "<br>" . $con->error;
        }

        // Close statement and connection
        $stmt->close();
        $con->close();
    } else {
        $error_message = "Category name is required.";
    }
}
?>

<div class="container mt-5">
    <h2>Add Category</h2>

    <!-- Display error or success messages -->
    <?php
    if (!empty($error_message)) {
        echo '<div class="alert alert-danger" role="alert">' . $error_message . '</div>';
    }
    ?>

    <!-- Category form -->
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="form-horizontal">
        <div class="form-group row">
            <label for="category_name" class="col-sm-2 col-form-label">Category Name:</label>
            <div class="col-sm-10">
                <input type="text" id="category_name" name="category_name" value="<?php echo htmlspecialchars($category_name); ?>" class="form-control" required oninput="capitalizeEachWord()">
            </div>
        </div>

        <div class="form-group row">
            <div class="col-sm-10 offset-sm-2">
                <input type="submit" value="Add Category" class="btn btn-primary">
            </div>
        </div>
    </form>
</div>

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1" role="dialog" aria-labelledby="successModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="successModalLabel">Category Added</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                Would you like to add stock for this category or add another category?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="window.location.href='sc_add_stock.php';">Add Stock</button>
                <button type="button" class="btn btn-danger" onclick="window.location.href='<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>';">Add Another Category</button>
            </div>
        </div>
    </div>
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
