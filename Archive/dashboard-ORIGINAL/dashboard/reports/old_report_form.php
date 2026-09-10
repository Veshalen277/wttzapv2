<?php 
include '../header.php'; 

$user = $_SESSION['u_data']; 
if (!isset($user)) {
    header("Location: ../dashboard/404.php");
    exit;
}

$user_role = $user[4];
$user_scale = $user[2];
?>

<div class="container mt-3 p-5 bg-white">
    <?php if (isset($_GET['message'])): ?>
        <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
            <?php echo htmlspecialchars($_GET['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php elseif (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
            <?php echo htmlspecialchars($_GET['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="report-form">
        <div class="row">
            <div class="col-md-3 col-sm-12">
                <?php include '../inc/sidebar.php';?>
            </div>

            <div class="col-md-9 col-sm-12 rounded">
                <form id="reportForm" action="process_report.php" method="POST">
                    <h3 class="text-danger text-center" style="text-decoration:underline;"><?php echo $user[0]; ?>'s Report</h3>

                    <div class="mb-4">
                        <label for="report_date" class="form-label">Date:</label>
                        <input type="date" id="report_date" name="report_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <input type="hidden" class="form-control" id="user_id" name="user_id" value="<?php echo $user[5]; ?>" readonly> 

                    <div class="mb-4">
                        <h4>Department:</h4>
                        <input type="text" class="form-control" id="user_dept" name="user_dept" value="<?php echo $user[2]; ?>" readonly> 
                    </div>

                    <div class="mb-4">
                        <h4>Income:</h4>

                        <div class="mb-3">
                            <label for="income_cash" class="form-label">Income via Cash:</label>
                            <input type="number" id="income_cash" name="income_cash" class="form-control" step="0.01" required>
                        </div>

                        <div class="mb-3">
                            <label for="income_card" class="form-label">Income via Card:</label>
                            <input type="number" id="income_card" name="income_card" class="form-control" step="0.01" required>
                        </div>

                        <div class="mb-3">
                            <label for="income_other" class="form-label">Sundry income:</label>
                            <input type="number" id="income_other" name="income_other" class="form-control" step="0.01" required>
                        </div>
                    </div>

                    <hr>

                    <div class="mb-4">
                        <h4>Expenses:</h4>

                        <div class="mb-3">
                            <label for="expense_cash" class="form-label">Expenses:</label>
                            <input type="number" id="expense_cash" name="expense_cash" class="form-control" step="0.01" required>
                        </div>

                        <div class="mb-3">
                            <label for="airtime" class="form-label">Airtime:</label>
                            <input type="number" id="airtime" name="airtime" class="form-control" step="5.00" required>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes:</label>
                            <textarea id="notes" name="notes" class="form-control" rows="4" required></textarea>
                        </div>
                    </div>

                    <hr>

                    <div class="mb-4">
                        <h4>Total Income and Expenses:</h4>
                        <p><strong>Total Income:</strong> <span id="total_income">R 0.00</span></p>
                        <p><strong>Total Expenses:</strong> <span id="total_expenses">R 0.00</span></p>
                        <p><strong>Net Total:</strong> <span id="net_total">R 0.00</span></p>
                    </div>

                    <div class="d-flex justify-content-center">
                        <button type="button" class="btn btn-block btn-primary" onclick="showConfirmationModal()">Submit Report</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div id="confirmationModal" class="modal fade" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmationModalLabel">Confirm Your Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Please review your totals before submitting:</p>
                <p><strong>Total Income:</strong> <span id="modal_total_income">R 0.00</span></p>
                <p><strong>Total Expenses:</strong> <span id="modal_total_expenses">R 0.00</span></p>
                <p><strong>Net Total:</strong> <span id="modal_net_total">R 0.00</span></p>
                <hr>
                <p>Are you sure you want to submit this report?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Recheck</button>
                <button type="button" class="btn btn-primary" id="confirmSubmitBtn">Submit</button>
            </div>
        </div>
    </div>
</div>

<script>
    function calculateTotals() {
        const incomeCash = parseFloat(document.getElementById('income_cash').value) || 0;
        const incomeCard = parseFloat(document.getElementById('income_card').value) || 0;
        const incomeOther = parseFloat(document.getElementById('income_other').value) || 0;
        const expenseCash = parseFloat(document.getElementById('expense_cash').value) || 0;
        const airtime = parseFloat(document.getElementById('airtime').value) || 0;

        const totalIncome = incomeCash + incomeCard + incomeOther;
        const totalExpenses = expenseCash + airtime;
        const netTotal = totalIncome - totalExpenses;

        document.getElementById('total_income').textContent = totalIncome.toFixed(2);
        document.getElementById('total_expenses').textContent = totalExpenses.toFixed(2);
        document.getElementById('net_total').textContent = netTotal.toFixed(2);
    }

    function showConfirmationModal() {
        const totalIncome = document.getElementById('total_income').textContent;
        const totalExpenses = document.getElementById('total_expenses').textContent;
        const netTotal = document.getElementById('net_total').textContent;

        document.getElementById('modal_total_income').textContent = totalIncome;
        document.getElementById('modal_total_expenses').textContent = totalExpenses;
        document.getElementById('modal_net_total').textContent = netTotal;

        const confirmationModal = new bootstrap.Modal(document.getElementById('confirmationModal'));
        confirmationModal.show();
    }

    document.getElementById('confirmSubmitBtn').addEventListener('click', function () {
        document.getElementById('reportForm').submit();
    });

    document.querySelectorAll('input[type="number"]').forEach(input => {
        input.addEventListener('input', calculateTotals);
    });

    calculateTotals();
</script>

<?php include '../footer.php'; ?>
