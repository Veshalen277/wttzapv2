<?php
include 'header.php';
include 'yes_tfm.php';

// Fetch report data if an ID is provided
$report = null;
$submitter_name = '';

// Ensure report_id is set and valid
if (isset($_GET['id'])) {
    $report_id = intval($_GET['id']);
    
    // Prepare and execute the query to fetch the report and submitter's name
    $stmt = $con->prepare("
        SELECT r.*, u.fullname AS submitter_name
        FROM mauritius_reports r
        LEFT JOIN users_tbl u ON r.user_id = u.id
        WHERE r.id = ?");
    $stmt->bind_param("i", $report_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $report = $result->fetch_assoc();
    $stmt->close();

    // Retrieve the submitter's name
    if ($report) {
        $submitter_name = $report['submitter_name'];
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-6 col-sm-12 border rounded p-4 my-3">
            <form id="reportForm" action="process_tfm_report.php" method="POST">
                <h3 class="text-danger text-center">Report Submitted by: 
                    <span><?php echo htmlspecialchars($submitter_name); ?></span>
                </h3>

                <input type="hidden" id="report_id" name="report_id" value="<?php echo isset($report['id']) ? htmlspecialchars($report['id']) : ''; ?>">
                <input type="hidden" class="form-control" id="user_id" name="user_id" value="<?php echo htmlspecialchars($_SESSION['u_data'][5]); ?>" readonly>
                
                <div class="form-group">
                    <label for="report_date">Date:</label>
                    <input type="date" id="report_date" name="report_date" class="form-control" value="<?php echo isset($report['report_date']) ? htmlspecialchars($report['report_date']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="user_dept">Department:</label>
                    <input type="text" id="user_dept" name="user_dept" class="form-control" value="<?php echo htmlspecialchars($_SESSION['u_data'][2]); ?>" readonly>
                </div>

                <h4 class="text-info">Income:</h4>
                <div class="form-group">
                    <label for="income_cash">Income via Cash:</label>
                    <input type="number" id="income_cash" name="income_cash" class="form-control" step="0.01" value="<?php echo isset($report['income_cash']) ? htmlspecialchars($report['income_cash']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="income_card">Income via Card:</label>
                    <input type="number" id="income_card" name="income_card" class="form-control" step="0.01" value="<?php echo isset($report['income_card']) ? htmlspecialchars($report['income_card']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="blinq">Income via Blinq:</label>
                    <input type="number" id="blinq" name="blinq" class="form-control" step="0.01" value="<?php echo isset($report['blinq']) ? htmlspecialchars($report['blinq']) : ''; ?>" required>
                </div>
                
                <hr>
                
                <h4 class="text-info">Expenses:</h4>
                <div class="form-group">
                    <label for="expense_cash">Expenses:</label>
                    <input type="number" id="expense_cash" name="expense_cash" class="form-control" step="0.01" value="<?php echo isset($report['expense_cash']) ? htmlspecialchars($report['expense_cash']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="airtime">Airtime:</label>
                    <input type="number" id="airtime" name="airtime" class="form-control" step="5.00" value="<?php echo isset($report['airtime']) ? htmlspecialchars($report['airtime']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="notes">Notes:</label>
                    <textarea id="notes" name="notes" class="form-control" rows="4" required><?php echo isset($report['notes']) ? htmlspecialchars($report['notes']) : ''; ?></textarea>
                </div>
                
                <hr>
                <h4 class="text-info">Total Income and Expenses:</h4>
                <p><strong>Total Income:</strong> <span id="total_income">$0.00</span></p>
                <p><strong>Total Expenses:</strong> <span id="total_expenses">$0.00</span></p>
                <p><strong>Net Total:</strong> <span id="net_total">$0.00</span></p>

                <button type="button" class="btn btn-primary" onclick="showConfirmationModal()">Submit Report</button>
            </form>
        </div>
        <div class="col-md-6 col-sm-12 p-5">
            <img src="iceland-5543_512.gif" alt="Report Image" class="img-fluid">
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
                <p><strong>Total Income:</strong> <span id="modal_total_income">$0.00</span></p>
                <p><strong>Total Expenses:</strong> <span id="modal_total_expenses">$0.00</span></p>
                <p><strong>Net Total:</strong> <span id="modal_net_total">$0.00</span></p>
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
        const blinq = parseFloat(document.getElementById('blinq').value) || 0;
        const expenseCash = parseFloat(document.getElementById('expense_cash').value) || 0;
        const airtime = parseFloat(document.getElementById('airtime').value) || 0;

        const totalIncome = incomeCash + incomeCard + blinq;
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

    document.querySelectorAll('input').forEach(input => {
        input.addEventListener('input', calculateTotals);
    });

    calculateTotals();
</script>

<?php include 'footer.php'; ?>
