<?php
include '../header.php';
require '../vendor/autoload.php'; // Autoload PHPMailer using Composer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Initialize variables for form data and errors
$fullname = $department = '';
$productErr = $quantityErr = [];
$products = [];

// Function to send email
function sendEmail($to, $subject, $message) {
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = getenv('SMTP_HOST');
        $mail->SMTPAuth   = true;
        $mail->Username   = getenv('SMTP_USERNAME');
        $mail->Password   = getenv('SMTP_PASSWORD');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        // Recipients
        $mail->setFrom(getenv('SMTP_FROM'), 'Mailer');
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;

        $mail->send();
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}

// Function to sanitize form data
function test_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_ids = $_POST['product'];
    $quantities = $_POST['quantity'];

    $errors = false;
    foreach ($product_ids as $index => $product_id) {
        if (empty($product_id)) {
            $productErr[$index] = "Product is required";
            $errors = true;
        }
        if (empty($quantities[$index]) || !is_numeric($quantities[$index]) || $quantities[$index] < 1) {
            $quantityErr[$index] = "Quantity is required and must be a positive number";
            $errors = true;
        }
    }

    // If no errors, process the order
    if (!$errors) {
        $date = date('Y-m-d H:i:s');

        // Insert order into 'orders' table first
        $stmt = $con->prepare("INSERT INTO orders (fullname, department, submitted_at) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $fullname, $department, $date);
        $stmt->execute();
        $order_id = $stmt->insert_id;
        $stmt->close();

        // Insert each product into 'order_items'
        foreach ($product_ids as $index => $product_id) {
            $quantity = $quantities[$index];

            // Fetch the product name based on the product ID
            $product_stmt = $con->prepare("SELECT item_name FROM stock_items WHERE item_id = ?");
            $product_stmt->bind_param("i", $product_id);
            $product_stmt->execute();
            $product_result = $product_stmt->get_result();
            $product_name = $product_result->fetch_assoc()['item_name'];
            $product_stmt->close();

            // Insert into order_items table
            $stmt = $con->prepare("INSERT INTO order_items (order_id, product, quantity) VALUES (?, ?, ?)");
            $stmt->bind_param("isi", $order_id, $product_name, $quantity);
            $stmt->execute();
        }

        $_SESSION['success'] = "Order submitted successfully!";

        // Send email notification to the user
        $userEmail = isset($_SESSION['u_data'][1]) ? $_SESSION['u_data'][1] : '';
        if ($userEmail) {
            $subject = "Order Confirmation";
            $message = "Dear $fullname,<br><br>Your order has been successfully submitted.<br><br>Thank you!";
            sendEmail($userEmail, $subject, $message);
        }

        // Send email notification to the admin
        $adminEmail = 'admin@timefliesza.co.za';
        $adminSubject = "New Order Submitted";
        $adminMessage = "An order has been submitted.<br><br>User: $fullname<br>Department: $department<br>Submitted At: $date";
        sendEmail($adminEmail, $adminSubject, $adminMessage);

        // Redirect to avoid form resubmission on refresh
        header("Location: orderForm.php");
        exit();
    } else {
        $_SESSION['error'] = "Please fix the errors in your form.";
    }
}

// Fetch user's full name and department
$user_id = isset($_SESSION['u_data']) ? $_SESSION['u_data'][5] : 0;

if ($user_id) {
    $stmt = $con->prepare("SELECT fullname, user_scale FROM users_tbl WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_result = $stmt->get_result()->fetch_assoc();
    $fullname = $user_result ? $user_result['fullname'] : 'User';
    $department = $user_result ? $user_result['user_scale'] : 'Unknown';
    $stmt->close();
} else {
    $fullname = 'User';
    $department = 'Unknown';
}

// Fetch all products from the stock_items table
$productQuery = "SELECT item_id, item_name FROM stock_items";
$productResult = $con->query($productQuery);
$productOptions = [];
while ($row = $productResult->fetch_assoc()) {
    $productOptions[] = $row;
}
?>








<div class="container mt-3 p-2 bg-white border ">
<div class="row">
    <div class="col-md-3 col-sm-12">
        <?php include '../inc/sidebar.php';?>
    </div>
    <div class="col-md-9 col-sm-12">
<!-- Order Form -->
<div class="container">
    <!-- Display success or error messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Order Form -->
    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" class="needs-validation" novalidate>
        <div class="row">
            <h5 class="text-center mb-4">Place Your Order</h5>

            <!-- Fullname (readonly) -->
            <div class="mb-3 col-12">
                <label for="fullname" class="form-label">User:</label>
                <input type="text" id="fullname" name="fullname" class="form-control" value="Hi, <?php echo htmlspecialchars($fullname); ?>" readonly>
            </div>

            <!-- Department (readonly) -->
            <div class="mb-3 col-12">
                <label for="department" class="form-label">Department:</label>
                <input type="text" id="department" name="department" class="form-control" value="<?php echo htmlspecialchars($department); ?>" readonly>
            </div>

            <!-- Dynamic Product Fields (Dropdown) -->
            <div id="product-fields">
                <div class="product-item mb-3">
                    <label for="product[]" class="form-label">Product:</label>
                    <select name="product[]" class="form-control" required>
                        <option value="">Select a product</option>
                        <?php foreach ($productOptions as $option): ?>
                            <option value="<?php echo $option['item_id']; ?>"><?php echo $option['item_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback">Please select a product.</div>
                    <span class="text-danger"><?php echo isset($productErr[0]) ? $productErr[0] : ''; ?></span>
                </div>
                <div class="product-item mb-3">
                    <label for="quantity[]" class="form-label">Quantity:</label>
                    <input type="number" name="quantity[]" min="1" class="form-control" required>
                    <div class="invalid-feedback">Please enter a valid quantity.</div>
                    <span class="text-danger"><?php echo isset($quantityErr[0]) ? $quantityErr[0] : ''; ?></span>
                </div>
            </div>

            <!-- Add more product button -->
            <div class="mb-3 text-center">
                <button type="button" id="add-product" class="btn btn-outline-secondary">+ Add another product</button>
            </div>

            <!-- Submit Button -->
            <div class="mb-3 text-center">
                <input type="submit" class="btn btn-primary w-100" value="Place Order" name="submit_order">
            </div>
        </div>
    </form>
</div>

    </div>
</div>
</div>


































<script>
    // JavaScript to dynamically add more product input fields
    document.getElementById('add-product').addEventListener('click', function() {
        var productFields = document.getElementById('product-fields');

        // Product dropdown field
        var newProductField = document.createElement('div');
        newProductField.classList.add('product-item', 'mb-3');
        newProductField.innerHTML = `
            <label for="product[]" class="form-label">Product:</label>
            <select name="product[]" class="form-control" required>
                <option value="">Select a product</option>
                <?php foreach ($productOptions as $option): ?>
                    <option value="<?php echo $option['item_id']; ?>"><?php echo $option['item_name']; ?></option>
                <?php endforeach; ?>
            </select>
            <div class="invalid-feedback">Please select a product.</div>
            <span class="text-danger"></span>
        `;

        // Quantity field
        var newQuantityField = document.createElement('div');
        newQuantityField.classList.add('product-item', 'mb-3');
        newQuantityField.innerHTML = `
            <label for="quantity[]" class="form-label">Quantity:</label>
            <input type="number" name="quantity[]" min="1" class="form-control" required>
            <div class="invalid-feedback">Please enter a valid quantity.</div>
            <span class="text-danger"></span>
        `;

        // Append to form
        productFields.appendChild(newProductField);
        productFields.appendChild(newQuantityField);
    });
</script>

<?php include '../footer.php'; ?>
