<?php
include 'header.php'; // Ensure this file has your $con connection object

// Extract user role and scale from the session data

$user_role = $user[4]; // Assuming user role is stored in $user[4]

$user_scale = $user[2]; // Assuming user scale is stored in $user[2]



// Define allowed roles

$allowed_roles = ['5', '7']; // Roles that should have access



// // Check access based on user role

// if (in_array($user_role, $allowed_roles)) {

//     // If user role is 0 or 2, they are allowed to access the page

//     // No further checks are needed

// } else if ($user_role === '1' && $user_scale !== 'TFM') {

//     // If user role is 1, check if the user scale is TFM

//     // If both conditions are met, allow access

// } else {

//     // If none of the conditions are met, deny access

//     header("Location: /dashboard/404.php");

//     exit;

// }



?>



<style>

    /* Adjust the width of the table and table cells */

    #reportsTable {

        table-layout: fixed; /* Use fixed table layout */

        width: 100%; /* Set table width to 100% */

    }



    #reportsTable th, #reportsTable td {

        overflow: hidden; /* Hide overflow */

        white-space: nowrap; /* Prevent text wrapping */

        text-overflow: ellipsis; /* Show ellipsis for overflowing text */

        padding: 5px; /* Reduce padding for smaller cells */

    }



    /* Optional: Set specific column widths */

    #reportsTable th:nth-child(1),

    #reportsTable td:nth-child(1) { width: 50px; } /* ID */

    

    #reportsTable th:nth-child(2),

    #reportsTable td:nth-child(2) { width: 100px; } /* Report Date */

    

    #reportsTable th:nth-child(3),

    #reportsTable td:nth-child(3) { width: 70px; } /* User ID */

    

    #reportsTable th:nth-child(4),

    #reportsTable td:nth-child(4) { width: 100px; } /* User Name */

    

    #reportsTable th:nth-child(5),

    #reportsTable td:nth-child(5) { width: 100px; } /* User Department */

    

    #reportsTable th:nth-child(6),

    #reportsTable td:nth-child(6) { width: 80px; } /* Income Cash */

    

    #reportsTable th:nth-child(7),

    #reportsTable td:nth-child(7) { width: 80px; } /* Income Card */

    

    #reportsTable th:nth-child(8),

    #reportsTable td:nth-child(8) { width: 80px; } /* Income Other */

    

    #reportsTable th:nth-child(9),

    #reportsTable td:nth-child(9) { width: 80px; } /* Total Income */

    

    #reportsTable th:nth-child(10),

    #reportsTable td:nth-child(10) { width: 80px; } /* Expense Cash */

    

    #reportsTable th:nth-child(11),

    #reportsTable td:nth-child(11) { width: 80px; } /* Expense Card */

    

    #reportsTable th:nth-child(12),

    #reportsTable td:nth-child(12) { width: 70px; } /* Airtime */

    

    #reportsTable th:nth-child(13),

    #reportsTable td:nth-child(13) { width: 80px; } /* Total Expenses */

    

    #reportsTable th:nth-child(14),

    #reportsTable td:nth-child(14) { width: 70px; } /* Net Total */

    

    #reportsTable th:nth-child(15),

    #reportsTable td:nth-child(15) { width: 100px; } /* Action */

</style>



<?php 

// Function to fetch distinct user scales

function fetchUserScales($con) {

    $scale_sql = "SELECT DISTINCT user_scale FROM users_tbl";

    $scale_query = mysqli_query($con, $scale_sql);

    return mysqli_fetch_all($scale_query, MYSQLI_ASSOC);

}



// Function to handle archive logic

function handleArchive($con) {

    if (isset($_POST['archive_btn'])) {

        $archive_id = $_POST['archive_id'];

        $archive_sql = "UPDATE reports SET status = 'archived' WHERE id = ?";

        

        $stmt = $con->prepare($archive_sql);

        $stmt->bind_param("i", $archive_id);

        

        if ($stmt->execute()) {

            echo '<div class="alert alert-success mt-3" role="alert">Report archived successfully</div>';

        } else {

            echo '<div class="alert alert-danger mt-3" role="alert">Error archiving report: ' . $con->error . '</div>';

        }



        $stmt->close();

    }

}



// Function to filter reports

function filterReports($con) {

    $sql = "SELECT r.*, u.fullname, u.user_scale

            FROM reports r

            LEFT JOIN users_tbl u ON r.user_id = u.id

            WHERE r.status != 'archived'"; // Only fetch non-archived reports



    if (isset($_POST['filter'])) {

        $filter_conditions = [];



        if (!empty($_POST['start_date']) && !empty($_POST['end_date'])) {

            $start_date = $_POST['start_date'];

            $end_date = $_POST['end_date'];

            $filter_conditions[] = "r.report_date BETWEEN '$start_date' AND '$end_date'";

        }



        if (!empty($_POST['user_scale'])) {

            $user_scale = $_POST['user_scale'];

            $filter_conditions[] = "u.user_scale = '$user_scale'";

        }



        if (!empty($filter_conditions)) {

            $sql .= " AND " . implode(" AND ", $filter_conditions);

        }

    }



    $result = $con->query($sql);

    return $result;

}



// Function to display reports and calculate totals

// function displayReports($result) {

//     $totals = [

//         'income_cash' => 0,

//         'income_card' => 0,

//         'income_other' => 0,

//         'total_income' => 0,

//         'expense_cash' => 0,

//         'expense_card' => 0,

//         'airtime' => 0,

//         'total_expenses' => 0,

//         'net_total' => 0

//     ];



//     if ($result->num_rows > 0) {

//         echo "<table id='reportsTable' class='table table-hover table-bordered bg-white table-sm p-4'>

//                 <thead>

//                     <tr>

//                         <th>ID</th>

//                         <th>Report Date</th>

//                         <th>User ID</th>

//                         <th>User Name</th>

//                         <th>User Department</th>

//                         <th>Income Cash</th>

//                         <th>Income Card</th>

//                         <th>Income Other</th>

//                         <th>Total Income</th>

//                         <th>Expense Cash</th>

//                         <th>Expense Card</th>

//                         <th>Airtime</th>

//                         <th>Total Expenses</th>

//                         <th>Net Total</th>

//                         <th>Action</th>

//                     </tr>

//                 </thead>

//                 <tbody>";



//         while ($row = $result->fetch_assoc()) {

//             // Accumulate totals

//             $totals['income_cash'] += $row['income_cash'];

//             $totals['income_card'] += $row['income_card'];

//             $totals['income_other'] += $row['income_other'];

//             $totals['total_income'] += $row['total_income'];

//             $totals['expense_cash'] += $row['expense_cash'];

//             $totals['expense_card'] += $row['expense_card'];

//             $totals['airtime'] += $row['airtime'];

//             $totals['total_expenses'] += $row['total_expenses'];

//             $totals['net_total'] += $row['net_total'];



//             echo "<tr>

//                     <td>{$row['id']}</td>

//                     <td>{$row['report_date']}</td>

//                     <td>{$row['user_id']}</td>

//                     <td>{$row['fullname']}</td>

//                     <td>{$row['user_scale']}</td>

//                     <td>{$row['income_cash']}</td>

//                     <td>{$row['income_card']}</td>

//                     <td>{$row['income_other']}</td>

//                     <td>{$row['total_income']}</td>

//                     <td>{$row['expense_cash']}</td>

//                     <td>{$row['expense_card']}</td>

//                     <td>{$row['airtime']}</td>

//                     <td>{$row['total_expenses']}</td>

//                     <td>{$row['net_total']}</td>

//                     <td class='action'>

//                         <form method='post' class='d-inline'>

//                             <input type='hidden' name='archive_id' value='{$row['id']}'>

//                             <button type='submit' name='archive_btn' class='btn btn-warning btn-sm'>Archive</button>

//                         </form>

//                     </td>

//                 </tr>";

//         }



//         // Display totals row

//         echo "<tr>

//                 <td colspan='5' class='text-center font-weight-bold'>Totals:</td>

//                 <td>{$totals['income_cash']}</td>

//                 <td>{$totals['income_card']}</td>

//                 <td>{$totals['income_other']}</td>

//                 <td>{$totals['total_income']}</td>

//                 <td>{$totals['expense_cash']}</td>

//                 <td>{$totals['expense_card']}</td>

//                 <td>{$totals['airtime']}</td>

//                 <td>{$totals['total_expenses']}</td>

//                 <td>{$totals['net_total']}</td>

//                 <td></td> <!-- Empty cell for action -->

//               </tr>";



//         echo "</tbody></table>";

//     } else {

//         echo "<p class='mt-3'>0 results</p>";

//     }

// }

//V2

// Function to display reports and calculate totals

function displayReports($result) {

    $totals = [

        'income_cash' => 0,

        'income_card' => 0,

        'income_other' => 0,

        'total_income' => 0,

        'expense_cash' => 0,

        'expense_card' => 0,

        'airtime' => 0,

        'total_expenses' => 0,

        'net_total' => 0 // Initialize net_total

    ];



    if ($result->num_rows > 0) {

        echo "<table id='reportsTable' class='table table-hover table-bordered bg-white table-sm p-4'>

                <thead>

                    <tr>

                        <th>ID</th>

                        <th>Report Date</th>

                        <th>User ID</th>

                        <th>User Name</th>

                        <th>User Department</th>

                        <th>Income Cash</th>

                        <th>Income Card</th>

                        <th>Income Other</th>

                        <th>Total Income</th>

                        <th>Expense Cash</th>

                        <th>Expense Card</th>

                        <th>Airtime</th>

                        <th>Total Expenses</th>

                        <th>Net Total</th>

                        <th>Action</th>

                    </tr>

                </thead>

                <tbody>";



        while ($row = $result->fetch_assoc()) {

            // Calculate net total for this row

            $row['net_total'] = $row['total_income'] - $row['total_expenses'];



            // Accumulate totals

            $totals['income_cash'] += $row['income_cash'];

            $totals['income_card'] += $row['income_card'];

            $totals['income_other'] += $row['income_other'];

            $totals['total_income'] += $row['total_income'];

            $totals['expense_cash'] += $row['expense_cash'];

            $totals['expense_card'] += $row['expense_card'];

            $totals['airtime'] += $row['airtime'];

            $totals['total_expenses'] += $row['total_expenses'];

            $totals['net_total'] += $row['net_total'];



            echo "<tr>

                    <td>{$row['id']}</td>

                    <td>{$row['report_date']}</td>

                    <td>{$row['user_id']}</td>

                    <td>{$row['fullname']}</td>

                    <td>{$row['user_scale']}</td>

                    <td>{$row['income_cash']}</td>

                    <td>{$row['income_card']}</td>

                    <td>{$row['income_other']}</td>

                    <td>{$row['total_income']}</td>

                    <td>{$row['expense_cash']}</td>

                    <td>{$row['expense_card']}</td>

                    <td>{$row['airtime']}</td>

                    <td>{$row['total_expenses']}</td>

                    <td>{$row['net_total']}</td> <!-- Display the calculated net total for the row -->

                    <td class='action'>

                        <form method='post' class='d-inline'>

                            <input type='hidden' name='archive_id' value='{$row['id']}'>

                            <button type='submit' name='archive_btn' class='btn btn-warning btn-sm'>Archive</button>

                        </form>

                    </td>

                </tr>";

        }



        // Display totals row

        echo "<tr>

                <td colspan='5' class='text-center font-weight-bold'>Totals:</td>

                <td>{$totals['income_cash']}</td>

                <td>{$totals['income_card']}</td>

                <td>{$totals['income_other']}</td>

                <td>{$totals['total_income']}</td>

                <td>{$totals['expense_cash']}</td>

                <td>{$totals['expense_card']}</td>

                <td>{$totals['airtime']}</td>

                <td>{$totals['total_expenses']}</td>

                <td class='bg-danger text-white'>{$totals['net_total']}</td> <!-- Display the accumulated net total -->

                <td></td> <!-- Empty cell for action -->

              </tr>";



        echo "</tbody></table>";

    } else {

        echo "<p class='mt-3'>0 results</p>";

    }

}





// Fetch distinct user scales

$scales = fetchUserScales($con);



// Handle archive logic

handleArchive($con);



// Filter reports and get result

$result = filterReports($con);



// Display reports

?>



<div class="container-fluid mt-4 table-responsive px-5 py-2 bg-white">

    <h4 class="text-center mb-3">Income Reports</h4>



    <!-- Print All Button -->

    <div class="mb-3 text-center">

        <button onclick="printTable()" class="btn btn-secondary">Print All Reports</button>

    </div>



    <form method="post">

        <div class="row mb-3">

            <!-- Filter by date range -->

            <div class="col-md-3">

                <label for="start_date" class="form-label">Start Date:</label>

                <input type="date" class="form-control" id="start_date" name="start_date">

            </div>



            <div class="col-md-3">

                <label for="end_date" class="form-label">End Date:</label>

                <input type="date" class="form-control" id="end_date" name="end_date">

            </div>



            <!-- Filter by user scale -->

            <div class="col-md-3">

                <label for="user_scale" class="form-label">Departments:</label>

                <select class="form-control" id="user_scale" name="user_scale">

                    <option value="">All Dept</option>

                    <?php foreach ($scales as $scale): ?>

                        <option value="<?= $scale['user_scale'] ?>" <?= isset($_POST['user_scale']) && $_POST['user_scale'] == $scale['user_scale'] ? 'selected' : '' ?>>

                            <?= $scale['user_scale'] ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>



            <div class="col-md-3 align-self-end">

                <button type="submit" name="filter" class="btn btn-primary">Apply Filters</button>

            </div>

        </div>

    </form>



    <?php 

    displayReports($result);

    ?>



</div>



<?php include 'footer.php'; ?>

<?php ob_end_flush(); ?>



<script>

function printTable() {

    var table = document.getElementById('reportsTable').outerHTML;

    var printWindow = window.open('', '_blank');

    printWindow.document.open();

    printWindow.document.write('<html><head><title>Print Report</title>');

    printWindow.document.write('<link rel="stylesheet" type="text/css" href="../assets/style.css">'); // Link your CSS file here

    printWindow.document.write('</head><body>');

    printWindow.document.write('<h2>Income Reports</h2>'); // Title of the printed page

    printWindow.document.write(table); // Content to print

    printWindow.document.write('</body></html>');

    printWindow.document.close();

    printWindow.print();

}

</script>



<style>

    @media print {

        .action {

            display: none; /* Hide the Archive button when printing */

        }

    }

</style>

