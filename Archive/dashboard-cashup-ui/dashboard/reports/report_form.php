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

<link rel="stylesheet" href="<?= \Portal\Html::escape(\Portal\Html::asset('reports/assets/cashup.css')) ?>">
<div class="cashup-page">
<header class="cashup-heading"><div><p class="cashup-eyebrow">YOUR WORKSPACE / CASHUPS</p><h1>Daily cashup</h1><p>Record today’s takings and expenses, then check the totals before submitting.</p></div></header>
<div class="container-fluid py-4">
    <div class="row">
        
        
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header text-dark">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0"><i class="fas fa-file-alt me-2"></i>Cashup details</h4>
                        <span class="badge bg-light text-dark fs-6"><?php echo $user[0]; ?></span>
                    </div>
                </div>
                
                <div class="card-body">
                    <?php if (isset($_GET['message'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo htmlspecialchars($_GET['message']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php elseif (isset($_GET['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php echo htmlspecialchars($_GET['error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form id="reportForm" action="process_report.php" method="POST" class="needs-validation" novalidate>
                        <input type="hidden" class="form-control" id="user_id" name="user_id" value="<?php echo $user[5]; ?>" readonly>
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="date" id="report_date" name="report_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                                    <label for="report_date">Report Date</label>
                                    <div class="invalid-feedback">Please select a date</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control bg-light" id="user_dept" name="user_dept" value="<?php echo $user[2]; ?>" readonly>
                                    <label for="user_dept">Department</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mb-4 border-primary">
                            <div class="card-header text-primary bg-opacity-10">
                                <h6 class="mb-0"><i class="fas fa-money-bill-wave me-2"></i>Money received</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <div class="form-floating">
                                            <input type="number" id="income_cash" name="income_cash" class="form-control" step="0.01" placeholder="0.00" required>
                                            <label for="income_cash">Cash Income (R)</label>
                                            <div class="invalid-feedback">Please enter cash income</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-floating">
                                            <input type="number" id="income_card" name="income_card" class="form-control" step="0.01" placeholder="0.00" required>
                                            <label for="income_card">Card Income (R)</label>
                                            <div class="invalid-feedback">Please enter card income</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-floating">
                                            <input type="number" id="income_other" name="income_other" class="form-control" step="0.01" placeholder="0.00" required>
                                            <label for="income_other">Sundry Income (R)</label>
                                            <div class="invalid-feedback">Please enter other income</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mb-4 border-danger">
                            <div class="card-header text-danger bg-opacity-10">
                                <h6 class="mb-0"><i class="fas fa-receipt me-2"></i>Money paid out</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="number" id="expense_cash" name="expense_cash" class="form-control" step="0.01" placeholder="0.00" required>
                                            <label for="expense_cash">Expenses (R)</label>
                                            <div class="invalid-feedback">Please enter expenses</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="number" id="airtime" name="airtime" class="form-control" step="5.00" placeholder="0.00" required>
                                            <label for="airtime">Airtime (R)</label>
                                            <div class="invalid-feedback">Please enter airtime amount</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-floating">
                                    <textarea id="notes" name="notes" class="form-control" style="height: 100px" required></textarea>
                                    <label for="notes">Notes/Comments</label>
                                    <div class="invalid-feedback">Please enter some notes</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mb-4 border-success cashup-summary">
                            <div class="card-header text-success bg-opacity-10">
                                <h6 class="mb-0"><i class="fas fa-calculator me-2"></i>Your totals</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="p-3 bg-light rounded text-center">
                                            <h6 class="text-muted">Total Income</h6>
                                            <h3 class="text-primary" id="total_income">R 0.00</h3>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="p-3 bg-light rounded text-center">
                                            <h6 class="text-muted">Total Expenses</h6>
                                            <h3 class="text-danger" id="total_expenses">R 0.00</h3>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="p-3 bg-light rounded text-center">
                                            <h6 class="text-muted">Net Total</h6>
                                            <h3 class="text-success" id="net_total">R 0.00</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="button" class="btn btn-primary px-4 py-2" onclick="showConfirmationModal()">
                                <i class="fas fa-paper-plane me-2"></i>Review cashup
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div id="confirmationModal" class="modal fade" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header text-dark">
                <h6 class="modal-title" id="confirmationModalLabel"><i class="fas fa-check-circle me-2"></i>Review your cashup</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="lead">Please verify the following totals before submitting:</p>
                
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <th class="bg-light">Total Income</th>
                                <td class="text-end" id="modal_total_income">R 0.00</td>
                            </tr>
                            <tr>
                                <th class="bg-light">Total Expenses</th>
                                <td class="text-end" id="modal_total_expenses">R 0.00</td>
                            </tr>
                            <tr class="table-success">
                                <th class="bg-light">Net Total</th>
                                <td class="text-end fw-bold" id="modal_net_total">R 0.00</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="alert alert-warning mt-3">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Once submitted, this report cannot be edited. Please ensure all information is correct.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-edit me-2"></i>Keep editing
                </button>
                <button type="button" class="btn btn-primary" id="confirmSubmitBtn">
                    <i class="fas fa-check me-2"></i>Submit cashup
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Enhanced form validation
    (function () {
        'use strict'
        
        var forms = document.querySelectorAll('.needs-validation')
        
        Array.prototype.slice.call(forms)
            .forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    
                    form.classList.add('was-validated')
                }, false)
            })
    })()
    
    function calculateTotals() {
        const incomeCash = parseFloat(document.getElementById('income_cash').value) || 0;
        const incomeCard = parseFloat(document.getElementById('income_card').value) || 0;
        const incomeOther = parseFloat(document.getElementById('income_other').value) || 0;
        const expenseCash = parseFloat(document.getElementById('expense_cash').value) || 0;
        const airtime = parseFloat(document.getElementById('airtime').value) || 0;

        const totalIncome = incomeCash + incomeCard + incomeOther;
        const totalExpenses = expenseCash + airtime;
        const netTotal = totalIncome - totalExpenses;

        document.getElementById('total_income').textContent = 'R ' + totalIncome.toFixed(2);
        document.getElementById('total_expenses').textContent = 'R ' + totalExpenses.toFixed(2);
        document.getElementById('net_total').textContent = 'R ' + netTotal.toFixed(2);
    }

    function showConfirmationModal() {
        // First validate the form
        const form = document.getElementById('reportForm');
        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            return;
        }
        
        // If valid, show the modal with totals
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

    // Add event listeners to all number inputs
    document.querySelectorAll('input[type="number"], textarea').forEach(input => {
        input.addEventListener('input', calculateTotals);
    });

    // Initialize totals on page load
    calculateTotals();
</script>

<details class="cashup-help"><summary>Need help completing your cashup?</summary><p>Enter the report date and check the department. Record money received and money paid out in the matching fields. Use notes to explain anything your reviewer should know.</p><p>The totals shown are calculated from your entries. Choose “Review cashup” to check them before sending. Keep your receipts and supporting records for your usual review process.</p><details><summary>More about the figures</summary><p>Total income combines the income fields. Total expenses and net total follow this form’s existing calculation rules. The manager form also includes staff-related fields and an optional sector breakdown; check the sector-total option before submitting.</p></details></details>
</div>
<?php include '../footer.php'; ?>