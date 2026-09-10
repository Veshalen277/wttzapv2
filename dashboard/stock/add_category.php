<?php
include '../header.php';
include '../functions.php';

// Initialize variables
$error_message = "";
$success_message = "";
$category_name = "";

// Handling form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize input
    if (!empty($_POST['category_name'])) {
        $category_name = htmlspecialchars($_POST['category_name']);

        // Add category using the function
        $result = addCategory($con, $category_name);

        if ($result) {
            $success_message = "Category added successfully.";
            $category_name = ""; // Clear the input field after successful submission
        } else {
            $error_message = "Error adding category.";
        }
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
    if (!empty($success_message)) {
        echo '<div class="alert alert-success" role="alert">' . $success_message . '</div>';
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
