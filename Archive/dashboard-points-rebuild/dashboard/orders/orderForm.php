<?php
include '../header.php';
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/* -----------------------------
   Helpers
------------------------------*/
function sendEmail($to, $subject, $message) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = getenv('SMTP_HOST');
        $mail->SMTPAuth   = true;
        $mail->Username   = getenv('SMTP_USERNAME');
        $mail->Password   = getenv('SMTP_PASSWORD');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        $mail->setFrom(getenv('SMTP_FROM'), 'WTT Orders');
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Do not echo in production; log instead
        return false;
    }
}

function clean($v) {
    return htmlspecialchars(trim((string)$v), ENT_QUOTES, 'UTF-8');
}

/* -----------------------------
   Preload user context
------------------------------*/
$user_id = isset($_SESSION['u_data'][5]) ? (int)$_SESSION['u_data'][5] : 0;

$fullname = 'User';
$department = 'Unknown';

if ($user_id > 0) {
    $stmt = $con->prepare("SELECT fullname, user_scale FROM users_tbl WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($user_result) {
        $fullname = $user_result['fullname'] ?? 'User';
        $department = $user_result['user_scale'] ?? 'Unknown';
    }
}

/* -----------------------------
   Load product options
------------------------------*/
$productOptions = [];
$productResult = $con->query("SELECT item_id, item_name FROM stock_items ORDER BY item_name ASC");
if ($productResult) {
    while ($row = $productResult->fetch_assoc()) {
        $productOptions[] = $row;
    }
}

/* -----------------------------
   Form processing
------------------------------*/
$productErr = [];
$quantityErr = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!\Portal\Csrf::valid($_POST['_csrf'] ?? null)) { http_response_code(403); exit('Refresh and try again.'); }

    // IMPORTANT: Read posted fullname/department (even if readonly)
    $postedFullname   = isset($_POST['fullname']) ? trim($_POST['fullname']) : '';
    $postedDepartment = isset($_POST['department']) ? trim($_POST['department']) : '';

    // Your form displays "Hi, Name" — strip the prefix so DB stays clean
    $postedFullname = preg_replace('/^Hi,\s*/i', '', $postedFullname);

    // fallback to session values
    $fullname   = $postedFullname   !== '' ? $postedFullname   : $fullname;
    $department = $postedDepartment !== '' ? $postedDepartment : $department;

    $product_ids = isset($_POST['product']) ? (array)$_POST['product'] : [];
    $quantities  = isset($_POST['quantity']) ? (array)$_POST['quantity'] : [];

    $errors = false;

    // Validate matching sizes
    $count = max(count($product_ids), count($quantities));
    if ($count === 0) {
        $_SESSION['error'] = "Please add at least one product.";
        header("Location: orderForm.php");
        exit();
    }

    for ($i = 0; $i < $count; $i++) {
        $pid = isset($product_ids[$i]) ? (int)$product_ids[$i] : 0;
        $qty = isset($quantities[$i]) ? $quantities[$i] : '';

        if ($pid <= 0) {
            $productErr[$i] = "Product is required";
            $errors = true;
        }

        if ($qty === '' || !is_numeric($qty) || (int)$qty < 1) {
            $quantityErr[$i] = "Quantity must be a positive number";
            $errors = true;
        }
    }

    if (!$errors) {
        $date = date('Y-m-d H:i:s');

        // Create order header
        $stmt = $con->prepare("INSERT INTO orders (fullname, department, submitted_at, points_user_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $fullname, $department, $date, $user_id);
        $stmt->execute();
        $order_id = (int)$stmt->insert_id;
        $stmt->close();

        // Insert items
        $item_stmt = $con->prepare("INSERT INTO order_items (order_id, product, quantity) VALUES (?, ?, ?)");
        $product_stmt = $con->prepare("SELECT item_name FROM stock_items WHERE item_id = ? LIMIT 1");

        $orderedLinesHtml = "<ul style='margin:0;padding-left:18px;'>";
        foreach ($product_ids as $index => $product_id) {
            $product_id = (int)$product_id;
            $quantity = (int)$quantities[$index];

            $product_stmt->bind_param("i", $product_id);
            $product_stmt->execute();
            $product_result = $product_stmt->get_result()->fetch_assoc();
            $product_name = $product_result['item_name'] ?? 'Unknown item';

            $item_stmt->bind_param("isi", $order_id, $product_name, $quantity);
            $item_stmt->execute();

            $orderedLinesHtml .= "<li>" . clean($product_name) . " × " . (int)$quantity . "</li>";
        }
        $orderedLinesHtml .= "</ul>";

        $product_stmt->close();
        $item_stmt->close();

        $_SESSION['success'] = "Order submitted successfully.";

        // Email user
        $userEmail = isset($_SESSION['u_data'][1]) ? $_SESSION['u_data'][1] : '';
        if (!empty($userEmail)) {
            $subject = "Order Confirmation (Order #{$order_id})";
            $message = "
                Dear " . clean($fullname) . ",<br><br>
                Your order has been successfully submitted.<br><br>
                <strong>Order #{$order_id}</strong><br>
                Department: " . clean($department) . "<br>
                Submitted: " . clean($date) . "<br><br>
                Items:<br>
                {$orderedLinesHtml}
                <br>
                Thank you.
            ";
            sendEmail($userEmail, $subject, $message);
        }

        // Email admin
        $adminEmail = 'admin@timefliesza.co.za';
        $adminSubject = "New Order Submitted (Order #{$order_id})";
        $adminMessage = "
            A new order has been submitted.<br><br>
            <strong>Order #{$order_id}</strong><br>
            User: " . clean($fullname) . "<br>
            Department: " . clean($department) . "<br>
            Submitted At: " . clean($date) . "<br><br>
            Items:<br>
            {$orderedLinesHtml}
        ";
        sendEmail($adminEmail, $adminSubject, $adminMessage);

        header("Location: orderForm.php");
        exit();
    } else {
        $_SESSION['error'] = "Please fix the errors highlighted below.";
    }
}
?>

<style>
    .page-title { font-size: .95rem; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; }
    .card-title-sm { font-size: .8rem; font-weight: 800; text-transform: uppercase; }
    .hint { font-size: .75rem; color: #6c757d; }
    .product-row { border-left: 4px solid #dc3545; padding-left: 12px; }
    .remove-row { font-size: .75rem; }
</style>

<div class="container-fluid mt-3">
    <div class="row g-3">

        <!-- Sidebar -->
        <div class="col-12 col-md-3">
            <div class="bg-white border p-2 shadow-sm">
                <div class="page-title mb-2">Navigation</div>
                <?php include '../inc/sidebar.php';?>
            </div>
        </div>

        <!-- Main -->
        <div class="col-12 col-md-9">

            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="page-title">Order Form</div>
                <div class="hint">Logged in as: <strong><?= clean($fullname); ?></strong></div>
            </div>

            <!-- Alerts -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= clean($_SESSION['success']); unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= clean($_SESSION['error']); unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card border shadow-sm">
                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="card-title-sm text-danger mb-1">Place your order</div>
                            <div class="hint">Add one or more items, then submit. Your name and department are captured automatically.</div>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-danger">Orders</span>
                        </div>
                    </div>

                    <hr>

                    <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" class="needs-validation" novalidate>
                        <?= \Portal\Csrf::field() ?>

                        <div class="row g-3 mb-2">
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-bold text-uppercase">User</label>
                                <input type="text" name="fullname" class="form-control"
                                       value="<?= clean($fullname); ?>" readonly>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-bold text-uppercase">Department</label>
                                <input type="text" name="department" class="form-control"
                                       value="<?= clean($department); ?>" readonly>
                            </div>
                        </div>

                        <div class="bg-light border rounded p-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="card-title-sm mb-0">Order items</div>
                                <button type="button" id="add-product" class="btn btn-sm btn-outline-dark">
                                    + Add item
                                </button>
                            </div>

                            <div id="product-fields">
                                <!-- Row 0 -->
                                <div class="row g-2 align-items-end product-row mb-2 order-row">
                                    <div class="col-12 col-md-8">
                                        <label class="form-label small fw-bold">Product</label>
                                        <select name="product[]" class="form-select" required>
                                            <option value="">Select a product</option>
                                            <?php foreach ($productOptions as $option): ?>
                                                <option value="<?= (int)$option['item_id']; ?>">
                                                    <?= clean($option['item_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="invalid-feedback">Please select a product.</div>
                                        <?php if (!empty($productErr[0])): ?>
                                            <div class="text-danger small mt-1"><?= clean($productErr[0]); ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="col-8 col-md-3">
                                        <label class="form-label small fw-bold">Quantity</label>
                                        <input type="number" name="quantity[]" min="1" class="form-control" required>
                                        <div class="invalid-feedback">Please enter a valid quantity.</div>
                                        <?php if (!empty($quantityErr[0])): ?>
                                            <div class="text-danger small mt-1"><?= clean($quantityErr[0]); ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="col-4 col-md-1 text-end">
                                        <button type="button" class="btn btn-sm btn-outline-secondary remove-row" disabled>
                                            Remove
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid mt-3">
                            <button type="submit" class="btn btn-danger btn-lg">
                                Submit Order
                            </button>
                        </div>

                    </form>

                </div>
            </div>

        </div>
    </div>
</div>

<script>
(function () {
    // Bootstrap validation
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });

    const productFields = document.getElementById('product-fields');
    const addBtn = document.getElementById('add-product');

    addBtn.addEventListener('click', function() {
        const row = document.createElement('div');
        row.className = 'row g-2 align-items-end product-row mb-2 order-row';

        row.innerHTML = `
            <div class="col-12 col-md-8">
                <label class="form-label small fw-bold">Product</label>
                <select name="product[]" class="form-select" required>
                    <option value="">Select a product</option>
                    <?php foreach ($productOptions as $option): ?>
                        <option value="<?= (int)$option['item_id']; ?>"><?= clean($option['item_name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback">Please select a product.</div>
            </div>

            <div class="col-8 col-md-3">
                <label class="form-label small fw-bold">Quantity</label>
                <input type="number" name="quantity[]" min="1" class="form-control" required>
                <div class="invalid-feedback">Please enter a valid quantity.</div>
            </div>

            <div class="col-4 col-md-1 text-end">
                <button type="button" class="btn btn-sm btn-outline-secondary remove-row">
                    Remove
                </button>
            </div>
        `;

        productFields.appendChild(row);
        refreshRemoveButtons();
    });

    function refreshRemoveButtons() {
        const rows = productFields.querySelectorAll('.order-row');
        rows.forEach((r, idx) => {
            const btn = r.querySelector('.remove-row');
            if (!btn) return;

            if (rows.length === 1) {
                btn.disabled = true;
                return;
            }

            btn.disabled = false;
            btn.onclick = function() {
                r.remove();
                refreshRemoveButtons();
            };
        });
    }

    refreshRemoveButtons();
})();
</script>

<?php include '../footer.php'; ?>
