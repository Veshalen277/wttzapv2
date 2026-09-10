<?php
// Include necessary files
include '../header.php'; // Ensure this file initializes $con and handles session
include '../functions.php'; // Ensure this file contains required functions

// Fetch user performance based on the reports
$query = "
    SELECT r.user_id, u.fullname,
           SUM(r.total_income) AS total_income,
           SUM(r.total_expenses) AS total_expenses,
           SUM(r.net_total) AS net_total,
           COUNT(r.id) AS report_count,
           MAX(r.report_date) AS last_report_date
    FROM reports r
    LEFT JOIN users_tbl u ON r.user_id = u.id
    WHERE r.status = 'active'
    GROUP BY r.user_id
    ORDER BY net_total DESC
";

$result = mysqli_query($con, $query);
if (!$result) {
    die("Database query failed: " . mysqli_error($con));
}

// HTML for the reports table
?>
<div class="container mt-2">
    <h2 class="text-center">User Reports Performance Table</h2>
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Rank</th>
                    <th title="Full Name">Full Name</th>
                    <th>Total Income</th>
                    <th>Total Expenses</th>
                    <th>Net Total</th>
                    <th title="Cash Reports Submitted">Reports Submitted</th>
                    <th>Last Report Date</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $rank = 1;
                $totalUsers = mysqli_num_rows($result);
                $bottomCount = min(3, $totalUsers); // To avoid exceeding actual user count

                while ($row = mysqli_fetch_assoc($result)) {
                    // Determine if this user is in the top 10 or bottom 3
                    $rowClass = '';
                    if ($rank <= 10) {
                        $rowClass = 'table-success'; // Highlight for top 10
                    } elseif ($rank > ($totalUsers - $bottomCount)) {
                        $rowClass = 'table-danger text-white'; // Highlight for bottom 3
                    }

                    // Add star for the top user
                    $star = ($rank == 1) ? '⭐' : ''; // You can use HTML entity for a star: &#9733;

echo "<tr class='$rowClass'>
    <td>{$star} {$rank}</td>
    <td>" . htmlspecialchars($row['fullname'] ?? 'Unknown User') . "</td>
    <td class='text-right'>" . htmlspecialchars(number_format((float)($row['total_income'] ?? 0), 2)) . "</td>
    <td class='text-right'>" . htmlspecialchars(number_format((float)($row['total_expenses'] ?? 0), 2)) . "</td>
    <td class='text-right'>" . htmlspecialchars(number_format((float)($row['net_total'] ?? 0), 2)) . "</td>
    <td>" . htmlspecialchars((string)($row['report_count'] ?? 0)) . "</td>
    <td>" . htmlspecialchars(!empty($row['last_report_date']) ? date('Y-m-d', strtotime($row['last_report_date'])) : 'N/A') . "</td>
</tr>";
                    $rank++;
                }
                ?>
            </tbody>
        </table>
    </div>
</div>


<?php
include '../footer.php'; // Include footer if necessary
?>
