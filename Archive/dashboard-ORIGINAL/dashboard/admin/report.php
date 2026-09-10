<?php
include '../header.php'; // Assuming header.php includes database connection and any necessary initializations
require_once '../library/tcpdf.php'; // Adjust the path if necessary

if (isset($_POST['report_btn'])) {
    $work_date = $_POST['work_date'];
    $formatted_date = date('Y-m-d', strtotime($work_date)); // Format input date to match database datetime format

    // Prepared statement to prevent SQL injection
    $sql = "SELECT * FROM work_tbl
            LEFT JOIN users_tbl ON work_tbl.employee_id = users_tbl.id
            WHERE DATE(work_tbl.work_date) = ?";

    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "s", $formatted_date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        $html = '<table border="0.5" width="100%" cellpadding="2" cellspacing="0">
                    <tr>
                        <th>No.</th>
                        <th>Emp.</th>
                        <th>Des.</th>
                        <th>Dept</th>
                        <th>Date</th>
                        <th colspan="3">Desc</th>
                        <th colspan="3">Task</th>
                    </tr>';

        $count = 0;
        while ($row = mysqli_fetch_assoc($result)) {
            $html .= '<tr>
                        <td>' . ++$count . '</td>
                        <td>' . $row['fullname'] . '</td>
                        <td>' . $row['user_des'] . '</td>
                        <td>' . $row['user_scale'] . '</td>
                        <td>' . date('d-m-Y', strtotime($row['work_date'])) . '</td> <!-- Display date portion -->
                        <td colspan="3"><small>' . $row['work_desc'] . '</small></td>
                        <td colspan="3"><small>' . $row['task'] . '</small></td>
                    </tr>';
        }

        $html .= '</table>';

        // Create new PDF document in landscape mode
        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false); // 'L' for landscape
        $pdf->AddPage();
        $pdf->writeHTML($html, true, false, true, false, '');
        ob_end_clean(); // Clean any output buffers
        $pdf->Output('work_report.pdf', 'D'); // Output PDF directly to the browser
        exit();
    } else {
        $_SESSION['error'] = "<small class='text-danger'>No work found on selected date, kindly try again</small>";
        header("location: admin_profile.php");
        exit();
    }

    mysqli_stmt_close($stmt);
    mysqli_close($con);
}
?>

<div class="container mt-5 py-2">
    <!-- Your HTML content goes here -->
</div>
<?php include '../footer.php'; ?>
