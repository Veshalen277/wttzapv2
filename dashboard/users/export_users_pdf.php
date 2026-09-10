<?php
// Start output buffering
ob_start();

include '../config.php';
require('../fpdf/fpdf.php'); // Include FPDF library

class PDF extends FPDF
{
    function Header()
    {
        $this->SetFont('Arial', 'B', 10); // Reduced header font size
        $this->Cell(0, 8, 'All Users List', 0, 1, 'C');
        $this->Ln(8);
        $this->SetFont('Arial', 'B', 6); // Reduced header font size for table headers
        $this->Cell(8, 8, 'Sr.#', 1);
        $this->Cell(28, 8, 'Employee', 1);
        $this->Cell(28, 8, 'Designation', 1);
        $this->Cell(18, 8, 'Dept.', 1);
        $this->Cell(18, 8, 'Role', 1);
        $this->Cell(23, 8, 'Start Date', 1);
        $this->Cell(18, 8, 'ID No.', 1);
        $this->Cell(28, 8, 'Email', 1);
        $this->Cell(28, 8, 'Addr.', 1);
        $this->Cell(23, 8, 'Contact No.', 1);
        $this->Cell(28, 8, 'Next of Kin', 1);
        $this->Cell(28, 8, 'Next of Kin No.', 1);
        $this->Ln();
    }

    function Footer()
    {
        $this->SetY(-10);
        $this->SetFont('Arial', 'I', 6); // Reduced footer font size
        $this->Cell(0, 8, 'Page ' . $this->PageNo(), 0, 0, 'C');
    }
}

$pdf = new PDF('L'); // Use landscape orientation
$pdf->AddPage();
$pdf->SetFont('Arial', '', 6); // Reduced font size for table data

// Fetch all users
$sql = "SELECT * FROM users_tbl";
$query = mysqli_query($con, $sql);

$count = 1;
while ($row = mysqli_fetch_assoc($query)) {
    // Check if the current row exceeds the page height
    if ($pdf->GetY() >= 250) {
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 6);
        $pdf->Cell(8, 8, 'Sr.#', 1);
        $pdf->Cell(28, 8, 'Employee', 1);
        $pdf->Cell(28, 8, 'Designation', 1);
        $pdf->Cell(18, 8, 'Dept.', 1);
        $pdf->Cell(18, 8, 'Role', 1);
        $pdf->Cell(23, 8, 'Start Date', 1);
        $pdf->Cell(18, 8, 'ID No.', 1);
        $pdf->Cell(28, 8, 'Email', 1);
        $pdf->Cell(28, 8, 'Addr.', 1);
        $pdf->Cell(23, 8, 'Contact No.', 1);
        $pdf->Cell(28, 8, 'Next of Kin', 1);
        $pdf->Cell(28, 8, 'Next of Kin No.', 1);
        $pdf->Ln();
    }

    $pdf->Cell(8, 8, $count, 1);
    $pdf->Cell(28, 8, $row['fullname'], 1);
    $pdf->Cell(28, 8, $row['user_des'], 1);
    $pdf->Cell(18, 8, $row['user_scale'], 1);
    $pdf->Cell(18, 8, $row['user_role'], 1);
    $pdf->Cell(23, 8, $row['date_started'], 1);
    $pdf->Cell(18, 8, $row['id_number'], 1);
    $pdf->Cell(28, 8, $row['email'], 1);
    $pdf->Cell(28, 8, $row['address'], 1);
    $pdf->Cell(23, 8, $row['contact_number'], 1);
    $pdf->Cell(28, 8, $row['next_of_kin'], 1);
    $pdf->Cell(28, 8, $row['next_of_kin_number'], 1);
    $pdf->Ln();
    $count++;
}

// Clear the output buffer and send the PDF
ob_end_clean();
$pdf->Output('D', 'all_users.pdf');
?>
