<?php
// Include necessary files
include '../header.php'; // Ensure this file initializes $con and handles session
include '../functions.php'; // Ensure this file contains required functions

// Fetch user performance based on the number of distinct work dates (reports submitted)
$query = "
    SELECT u.id, u.fullname, 
           COUNT(DISTINCT DATE(w.work_date)) AS report_count,  -- Count distinct dates
           COUNT(DISTINCT w.task) AS tasks_completed,
           MAX(u.task_datetime) AS last_task_date, 
           MAX(w.work_date) AS last_report_date
    FROM users_tbl u
    LEFT JOIN work_tbl w ON u.id = w.employee_id
    GROUP BY u.id
    ORDER BY report_count DESC
";

$result = mysqli_query($con, $query);
if (!$result) {
    die("Database query failed: " . mysqli_error($con));
}

// HTML for the league table
?>
<!--
<div class="container mt-2">
    <h2 class="text-center">User Performance League Table</h2>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Rank</th>
                <th>Full Name</th>
                <th>Reports Submitted</th>
                <th>Tasks Completed</th>
                <th>Last Task Date</th>
                <th>Last Report Date</th>
            </tr>
        </thead>
        <tbody>
            </?php
            $rank = 1;
            $totalUsers = mysqli_num_rows($result);
            $bottomCount = min(5, $totalUsers); // Highlight the bottom 5 users

            // Loop through the result to display the league table
            while ($row = mysqli_fetch_assoc($result)) {
                // Determine if this user is in the bottom 5
                $rowClass = '';
                if ($rank <= 10) {
                    $rowClass = 'table-success'; // Highlight for top 10
                } elseif ($rank > ($totalUsers - $bottomCount)) {
                    $rowClass = 'table-danger'; // Highlight for bottom 5
                }

                // Format last task and report dates
                $lastTaskDate = !empty($row['last_task_date']) ? date('Y-m-d', strtotime($row['last_task_date'])) : 'N/A';
                $lastReportDate = !empty($row['last_report_date']) ? date('Y-m-d', strtotime($row['last_report_date'])) : 'N/A';

                // Output the table row
                echo "<tr class='$rowClass'>
                        <td>{$rank}</td>
                        <td>" . htmlspecialchars($row['fullname']) . "</td>
                        <td>" . htmlspecialchars($row['report_count']) . "</td>
                        <td class='bg-secondary text-white'>" . htmlspecialchars($row['tasks_completed']) . "</td>
                        <td class='table-success'>$lastTaskDate</td>
                        <td class='table-warning'>$lastReportDate</td>
                      </tr>";
                $rank++;
            }

            // Add a section for the bottom 5 "Losers" if necessary
            if ($totalUsers > 0 && $rank > $totalUsers - $bottomCount) {
                echo "<tr><td colspan='6' class='text-center table-danger'><strong>Losers</strong></td></tr>";
            }
            ?>
        </tbody>
    </table> 
</div> -->




<div class="container mt-2">
  <h2 class="text-center">User Performance League Table</h2>
  <div class="table-responsive">  <table class="table table-bordered">
      <thead>
        <tr>
          <th>Rank</th>
          <th title="Full Name">Full Name</th>
          <th>Reports</th>  <th class="d-none d-sm-table-cell">Tasks</th>  <th title="Last Task Date">Last Task</th>  <th title="Last Report Date">Last Report</th>  </tr>
      </thead>
      <tbody>
        <?php
        $rank = 1;
        $totalUsers = mysqli_num_rows($result);
        $bottomCount = min(5, $totalUsers);

        // Loop through the result to display the league table
        while ($row = mysqli_fetch_assoc($result)) {
          // Determine if this user is in the bottom 5
          $rowClass = '';
          if ($rank <= 10) {
            $rowClass = 'table-success'; // Highlight for top 10
          } elseif ($rank > ($totalUsers - $bottomCount)) {
            $rowClass = 'table-danger'; // Highlight for bottom 5
          }

          // Format last task and report dates
          $lastTaskDate = !empty($row['last_task_date']) ? date('Y-m-d', strtotime($row['last_task_date'])) : 'N/A';
          $lastReportDate = !empty($row['last_report_date']) ? date('Y-m-d', strtotime($row['last_report_date'])) : 'N/A';

          // Output the table row
          echo "<tr class='$rowClass'>
                  <td>{$rank}</td>
                  <td>" . htmlspecialchars($row['fullname']) . "</td>
                  <td>" . htmlspecialchars($row['report_count']) . "</td>
                  <td class='d-none d-sm-table-cell'>" . htmlspecialchars($row['tasks_completed']) . "</td>
                  <td class='table-success'>$lastTaskDate</td>
                  <td class='table-warning'>$lastReportDate</td>
                </tr>";
          $rank++;
        }

        // Add a section for the bottom 5 "Losers" if necessary
        // if ($totalUsers > 0 && $rank > $totalUsers - $bottomCount) {
        //   echo "<tr><td colspan='6' class='text-center table-danger'><strong>Losers</strong></td></tr>";
        // }
        ?>
      </tbody>
    </table>
  </div>
</div>








<?php
include '../footer.php'; // Include footer if necessary
?>  
