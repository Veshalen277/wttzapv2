<?php

// /reports/special_daily_report.php

include '../header.php';
require '../vendor/autoload.php'; // Autoload PHPMailer using Composer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


$user = $_SESSION['u_data'] ?? null;

if (!$user) {

  header("Location: ../dashboard/404.php");

  exit;

}



$user_role  = (int)($user[4] ?? 0);

$user_scale = $user[2] ?? '';

//$isSpecial  = ($user_role === 7); 



$isSpecial = in_array($user_role, [2, 7], true);

if (!$isSpecial) {

  header("Location: ../dashboard/404.php");

  exit;

}

?>



<div class="container-fluid py-4">

  <div class="row">

    <div class="col-md-3 col-lg-2">

      <?php include '../inc/sidebar.php'; ?>

    </div>



    <div class="col-md-9 col-lg-10">

      <div class="card shadow-sm">

        <div class="card-header text-dark">

          <div class="d-flex justify-content-between align-items-center">

            <h4 class="mb-0"><i class="fas fa-file-alt me-2"></i>Daily Financial Report (Special)</h4>

            <span class="badge bg-light text-dark fs-6"><?php echo htmlspecialchars((string)($user[0] ?? '')); ?></span>

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



          <form id="reportForm" action="process_special_report.php" method="POST" class="needs-validation" novalidate>

            <input type="hidden" id="user_id" name="user_id" value="<?php echo (int)($user[5] ?? 0); ?>">



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

                  <input type="text" class="form-control bg-light" id="user_dept" name="user_dept" value="<?php echo htmlspecialchars((string)($user[2] ?? '')); ?>" readonly>

                  <label for="user_dept">Department</label>

                </div>

              </div>

            </div>



            <!-- Income Details -->

            <div class="card mb-4 border-primary">

              <div class="card-header text-primary bg-opacity-10">

                <h6 class="mb-0"><i class="fas fa-money-bill-wave me-2"></i>Income Details</h6>

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

                      <div class="invalid-feedback">Please enter sundry income</div>

                    </div>

                  </div>

                </div>

              </div>

            </div>



            <!-- Cash & Card Sectors -->

            <div class="card mb-4 border-info">

              <div class="card-header text-info bg-opacity-10 d-flex justify-content-between align-items-center">

                <h6 class="mb-0"><i class="fas fa-layer-group me-2"></i>Cash & Card Sectors</h6>

                <button type="button" class="btn btn-sm btn-outline-info" id="addSectorRowBtn">

                  <i class="fas fa-plus me-1"></i>Add Sector

                </button>

              </div>

              <div class="card-body">

                <div class="table-responsive">

                  <table class="table align-middle" id="sectorTable">

                    <thead>

                      <tr>

                        <th style="min-width:220px;">Sector Name</th>

                        <th style="min-width:160px;" class="text-end">Cash (R)</th>

                        <th style="min-width:160px;" class="text-end">Card (R)</th>

                        <th style="width:80px;"></th>

                      </tr>

                    </thead>

                    <tbody id="sectorTbody"></tbody>

                    <tfoot>

                      <tr class="table-light">

                        <th class="text-end">Totals:</th>

                        <th class="text-end" id="sectorCashTotal">R 0.00</th>

                        <th class="text-end" id="sectorCardTotal">R 0.00</th>

                        <th></th>

                      </tr>

                    </tfoot>

                  </table>

                </div>



                <div class="form-text">

                  Add sector breakdown lines for cash and card

                </div>



                <div class="form-check mt-2">

            <input class="form-check-input" type="checkbox" value="1" id="useSectorTotals" name="use_sector_totals">

                  <label class="form-check-label" for="useSectorTotals">

                    Use sector totals to fill Income Cash and Income Card

                  </label>

                </div>

              </div>

            </div>



            <!-- Expense Details -->

            <div class="card mb-4 border-danger">

              <div class="card-header text-danger bg-opacity-10">

                <h6 class="mb-0"><i class="fas fa-receipt me-2"></i>Expense Details</h6>

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



                <div class="row g-3 mb-3">

                  <div class="col-md-6">

                    <div class="form-floating">

                      <input type="number" id="staff_advances" name="staff_advances" class="form-control" step="0.01" placeholder="0.00" required>

                      <label for="staff_advances">Staff Advances (R)</label>

                      <div class="invalid-feedback">Please enter staff advances</div>

                    </div>

                  </div>

                  <div class="col-md-6">

                    <div class="form-floating">

                      <input type="number" id="staff_purchases" name="staff_purchases" class="form-control" step="0.01" placeholder="0.00" required>

                      <label for="staff_purchases">Staff Purchases (R)</label>

                      <div class="invalid-feedback">Please enter staff purchases</div>

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



            <!-- Summary -->

            <div class="card mb-4 border-success">

              <div class="card-header text-success bg-opacity-10">

                <h6 class="mb-0"><i class="fas fa-calculator me-2"></i>Financial Summary</h6>

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

                <i class="fas fa-paper-plane me-2"></i>Submit Report

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

        <h6 class="modal-title" id="confirmationModalLabel"><i class="fas fa-check-circle me-2"></i>Confirm Submission</h6>

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

          <i class="fas fa-edit me-2"></i>Recheck Details

        </button>

        <button type="button" class="btn btn-primary" id="confirmSubmitBtn">

          <i class="fas fa-check me-2"></i>Confirm & Submit

        </button>

      </div>

    </div>

  </div>

</div>



<script>

  // Bootstrap validation

  (function () {

    'use strict'

    var forms = document.querySelectorAll('.needs-validation')

    Array.prototype.slice.call(forms).forEach(function (form) {

      form.addEventListener('submit', function (event) {

        if (!form.checkValidity()) {

          event.preventDefault()

          event.stopPropagation()

        }

        form.classList.add('was-validated')

      }, false)

    })

  })()



  function money(n){ return 'R ' + (Number(n||0)).toFixed(2); }



  function getSectorSums(){

    let cash = 0, card = 0;

    document.querySelectorAll('.sector-row').forEach(row => {

      const c = parseFloat(row.querySelector('.sector-cash')?.value) || 0;

      const k = parseFloat(row.querySelector('.sector-card')?.value) || 0;

      cash += c; card += k;

    });

    return {cash, card};

  }



  function calculateTotals() {

    const incomeCashEl = document.getElementById('income_cash');

    const incomeCardEl = document.getElementById('income_card');



    const incomeOther = parseFloat(document.getElementById('income_other')?.value) || 0;



    const expenseCash = parseFloat(document.getElementById('expense_cash')?.value) || 0;

    const airtime     = parseFloat(document.getElementById('airtime')?.value) || 0;



    const staffAdv    = parseFloat(document.getElementById('staff_advances')?.value) || 0;

    const staffPur    = parseFloat(document.getElementById('staff_purchases')?.value) || 0;



    // Sector totals

    const sums = getSectorSums();

    document.getElementById('sectorCashTotal').textContent = money(sums.cash);

    document.getElementById('sectorCardTotal').textContent = money(sums.card);



    const useSector = document.getElementById('useSectorTotals')?.checked;

    // if (useSector) {

    //   incomeCashEl.value = sums.cash.toFixed(2);

    //   incomeCardEl.value = sums.card.toFixed(2);

    //   incomeCashEl.readOnly = true;

    //   incomeCardEl.readOnly = true;

    //   incomeCashEl.classList.add('bg-light');

    //   incomeCardEl.classList.add('bg-light');

    // } else {

    //   incomeCashEl.readOnly = false;

    //   incomeCardEl.readOnly = false;

    // }

if (useSector) {

  incomeCashEl.value = sums.cash.toFixed(2);

  incomeCardEl.value = sums.card.toFixed(2);

} 

// no readonly at all

    const incomeCash = parseFloat(incomeCashEl?.value) || 0;

    const incomeCard = parseFloat(incomeCardEl?.value) || 0;



    const totalIncome   = incomeCash + incomeCard + incomeOther;

    const totalExpenses = expenseCash + airtime + staffAdv + staffPur;

    const netTotal      = totalIncome - totalExpenses;



    document.getElementById('total_income').textContent = money(totalIncome);

    document.getElementById('total_expenses').textContent = money(totalExpenses);

    document.getElementById('net_total').textContent = money(netTotal);

  }



  function addSectorRow(sectorName = '', cash = '', card = ''){

    const tbody = document.getElementById('sectorTbody');

    if (!tbody) return;



    const tr = document.createElement('tr');

    tr.className = 'sector-row';

    tr.innerHTML = `

      <td>

        <input type="text" name="sector_name[]" class="form-control sector-name" value="${sectorName}" required>

      </td>

      <td>

        <input type="number" name="sector_cash[]" class="form-control text-end sector-cash" step="0.01" value="${cash}">

      </td>

      <td>

        <input type="number" name="sector_card[]" class="form-control text-end sector-card" step="0.01" value="${card}">

      </td>

      <td class="text-end">

        <button type="button" class="btn btn-sm btn-outline-danger removeSectorBtn">

          <i class="fas fa-trash"></i>

        </button>

      </td>

    `;



    tr.querySelectorAll('input').forEach(inp => inp.addEventListener('input', calculateTotals));

    tr.querySelector('.removeSectorBtn').addEventListener('click', () => {

      tr.remove();

      calculateTotals();

    });



    tbody.appendChild(tr);

    calculateTotals();

  }



  function showConfirmationModal() {

    const form = document.getElementById('reportForm');

    if (!form.checkValidity()) {

      form.classList.add('was-validated');

      return;

    }



    // ensure totals are current

    calculateTotals();



    document.getElementById('modal_total_income').textContent   = document.getElementById('total_income').textContent;

    document.getElementById('modal_total_expenses').textContent = document.getElementById('total_expenses').textContent;

    document.getElementById('modal_net_total').textContent      = document.getElementById('net_total').textContent;



    const confirmationModal = new bootstrap.Modal(document.getElementById('confirmationModal'));

    confirmationModal.show();

  }



  document.getElementById('confirmSubmitBtn').addEventListener('click', function () {

    document.getElementById('reportForm').submit();

  });



  document.addEventListener('DOMContentLoaded', () => {

    document.querySelectorAll('input[type="number"], textarea').forEach(input => {

      input.addEventListener('input', calculateTotals);

    });



    const addBtn = document.getElementById('addSectorRowBtn');

    if (addBtn) {

      addBtn.addEventListener('click', () => addSectorRow());

      addSectorRow(); // start with one row

    }



    const useSector = document.getElementById('useSectorTotals');

    if (useSector) useSector.addEventListener('change', calculateTotals);



    calculateTotals();

  });

</script>



<?php include '../footer.php'; ?>