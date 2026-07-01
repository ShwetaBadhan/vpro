<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/vendor/autoload.php';
include("db/config.php");

use setasign\Fpdf\Fpdf;
use setasign\Fpdi\Fpdi;

// Get slip ID
$slipId = base64_decode($_GET['id']);
if (!$slipId) die("Invalid Slip ID");

// Fetch slip and related data
$query = "
SELECT 
    ss.*, 
    pd.name, pd.father_name, pd.dob,
    cd.employee_code, cd.doj,
    pos.name AS designation,
    bd.account_no
FROM 
    salary_slips ss
LEFT JOIN personal_details pd ON ss.user_id = pd.personal_id
LEFT JOIN company_details cd ON ss.user_id = cd.user_id
LEFT JOIN position pos ON cd.designation = pos.position_id
LEFT JOIN bank_details bd ON ss.user_id = bd.user_id
WHERE 
    ss.slip_id = '$slipId'

";
$result = mysqli_query($db, $query);
$slip = mysqli_fetch_assoc($result);
if (!$slip) die("Slip not found");

$earnings = mysqli_query($db, "SELECT * FROM salary_earnings WHERE slip_id = '$slipId'");
$deductions = mysqli_query($db, "SELECT * FROM salary_deductions WHERE slip_id = '$slipId'");

// Create PDF
$pdf = new FPDI();
$pdf->AddPage();
$pdf->setSourceFile('pdf/salaryslip.pdf');
$template = $pdf->importPage(1);
$pdf->useTemplate($template);

$rupee = chr(8377);
$pdf->SetFont('Times', '', 10);

// ------------------------------
// Employee Info Block
// ------------------------------
// Format month and year
$monthText = date("F Y", strtotime($slip['slip_month'])); // e.g., July 2025

// Set font to bold and size 10
$pdf->SetFont('Times', 'B', 12);

// Set position and write
$pdf->SetXY(112, 59);
$pdf->Write(0, $monthText);

$pdf->SetFont('Times', 'B', 12);
$pdf->SetXY(58, 71); $pdf->Write(0, $slip['month_days']);

$pdf->SetFont('Times', 'B', 12);
$pdf->SetXY(138, 71); $pdf->Write(0, $slip['present_days']);

// $pdf->SetFont('Times', 'B', 12);
$pdf->SetXY(58, 80); $pdf->Write(0, $slip['name']);

$pdf->SetXY(138, 80); $pdf->Write(0, $slip['employee_code']);

$pdf->SetXY(58, 88); $pdf->Write(0, "Mr. " . $slip['father_name']);
$pdf->SetXY(138, 88); $pdf->Write(0, date("d-F-Y", strtotime($slip['doj'])));

$pdf->SetXY(58, 97); $pdf->Write(0, $slip['account_no']);
$pdf->SetXY(138, 97); $pdf->Write(0, $slip['designation']);

// $pdf->SetXY(40, 63); $pdf->Write(0, $slip['bank_name']);
$pdf->SetXY(138, 106); $pdf->Write(0, date("d-M-Y", strtotime($slip['dob'])));


$pdf->SetFont('Times', 'B', 12);
$lineHeight = 8;
$rowCount = max(mysqli_num_rows($earnings), mysqli_num_rows($deductions));
mysqli_data_seek($earnings, 0);
mysqli_data_seek($deductions, 0);

// Rows
$startY = 135;  // Starting Y-position
$lineHeight = 7; // Space between lines (adjust as needed)

for ($i = 0; $i < $rowCount; $i++) {
    $e = mysqli_fetch_assoc($earnings);
    $d = mysqli_fetch_assoc($deductions);

    $currentY = $startY + ($i * $lineHeight);

    // Earnings
    if ($e) {
        $pdf->SetXY(17, $currentY);   $pdf->Write(0, $e['type']);
        $pdf->SetXY(58, $currentY);  $pdf->Write(0, $e['scale_rs']);
        $pdf->SetXY(82, $currentY);  $pdf->Write(0, $e['amount_rs']);
    }

    // Deductions (optional: if you want to show in parallel)
    if ($d) {
        $pdf->SetXY(110, $currentY); $pdf->Write(0, $d['type']);
        $pdf->SetXY(145, $currentY); $pdf->Write(0, $d['scale_rs']);
        $pdf->SetXY(172, $currentY); $pdf->Write(0, $d['scale_rs']);

        $pdf->SetXY(145, 188); $pdf->Write(0, $d['scale_rs']);
        $pdf->SetXY(172, 188); $pdf->Write(0, $d['scale_rs']);

    }
}

$pdf->SetXY(58, 188); $pdf->Write(0, $slip['net_pay']);
$pdf->SetXY(82, 188); $pdf->Write(0, $slip['net_pay']);
$pdf->SetXY(50, 197); $pdf->Write(0, $slip['net_pay']);
$pdf->SetXY(50, 205); $pdf->Write(0, $slip['net_pay_words']);



// Output PDF
// ------------------------------
ob_end_clean();
$pdf->Output('I', 'Salary-Slip.pdf');
