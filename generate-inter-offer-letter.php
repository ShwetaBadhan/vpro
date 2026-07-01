<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Cache-Control: no-cache, no-store, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

set_time_limit(60);
ini_set('memory_limit', '256M');

// Load Composer Autoloader
require_once __DIR__ . '/vendor/autoload.php';

// ✅ CHECK IF TCPDF IS LOADED
if (!class_exists('TCPDF')) {
    die("<h1>❌ Error: TCPDF Library Not Found!</h1><p>Please run <code>composer require tecnickcom/tcpdf setasign/fpdi-tcpdf</code> on your server.</p>");
}

require_once __DIR__ . '/db/config.php';

use setasign\Fpdi\TcpdfFpdi;

class OfferLetterPDF extends TcpdfFpdi {
    protected $tplIdx = null;
    public function setLetterheadTemplate($tplIdx) { $this->tplIdx = $tplIdx; }
    public function Header() {
        if ($this->tplIdx) {
            $this->useTemplate($this->tplIdx, 0, 0, 210, 297, true);
        }
    }
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage(), 0, false, 'C');
    }
}

function cleanDatabaseHtml($html) {
    $html = stripslashes($html);
    $html = str_replace(["\\'", "\\\\'", "\\\\\\'"], "'", $html);
    $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $html = preg_replace('/\s*style\s*=\s*"[^"]*"/i', '', $html);
    $html = preg_replace('/<p\s+[^>]*>/', '<p>', $html);
    $html = preg_replace('/<span\s+[^>]*>/', '<span>', $html);
    $html = preg_replace('/<ul\s+[^>]*>/', '<ul>', $html);
    $html = preg_replace('/<li\s+[^>]*>/', '<li>', $html);
    $html = preg_replace('/\\\\+\'/', "'", $html);
    $html = preg_replace('/<p>\s*<\/p>/i', '', $html);
    $html = preg_replace('/<p>\s*&nbsp;\s*<\/p>/i', '', $html);
    $html = preg_replace('/\s+/', ' ', $html);
    return $html;
}

try {
    if (!isset($_GET['id'])) die("Missing ID");
    $decoded_id = base64_decode($_GET['id']);
    
    if (!isset($db)) die("DB connection failed");

    $query = "SELECT ol.*, p.name AS position_name, o.name AS reporting_manager
              FROM offer_letter ol
              LEFT JOIN position p ON ol.position = p.position_id
              LEFT JOIN personal_details o ON ol.reporting_manager = o.personal_id
              WHERE ol.offer_id = ?";
              
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "i", $decoded_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (!$row) die("Offer letter not found");

    $candidate_name    = $row['candidate_name'] ?? 'Candidate';
    $position_name     = $row['position_name'] ?? 'Position';
    $doj               = date('d-m-Y', strtotime($row['doj'] ?? 'now'));
    $salary            = trim($row['salary'] ?? '0');
    $employee_type     = $row['employee_type'] ?? 'Work From Home';
    $reporting_manager = $row['reporting_manager'] ?? 'Manager';
    $reference_no      = $row['reference_no'] ?? 'REF-001';
    $offer_date        = date('d-m-Y', strtotime($row['offer_date'] ?? 'now'));
    $letter_type       = $row['letter_type'] ?? 'intern';
    $duration          = $row['duration'] ?? 'Six Months';  

    $introPara = "<p>We are pleased to inform you that you have been appointed to the position of <b>{$position_name}</b> at Vibrantick Infotech Solutions. We are delighted to extend this offer to you based on the terms and conditions outlined below:</p>";

    $dynamicPoints = "
        <p><b>1. Designation:</b> {$position_name}</p>
        <p><b>2. Date of Joining:</b> {$doj}</p>
        <p><b>3. Place of posting and assignment:</b> You will be working as <b>{$position_name}</b> for Vibrantick Infotech Solutions. Your employment type will be <b>{$employee_type}</b>.</p>
    ";

    if (!empty($salary) && $salary != '0' && $salary != '0.00') {
        $dynamicPoints .= "<p><b>4. Compensation:</b> Rs. {$salary}</p>";
    }

    $db_content = '';
    $temp_stmt = mysqli_prepare($db, "SELECT description FROM offer_letter_content WHERE letter_type = ? LIMIT 1");
    mysqli_stmt_bind_param($temp_stmt, "s", $letter_type);
    mysqli_stmt_execute($temp_stmt);
    $temp_result = mysqli_stmt_get_result($temp_stmt);
    $temp_row = mysqli_fetch_assoc($temp_result);
    mysqli_stmt_close($temp_stmt);
    
    if ($temp_row) {
        $db_content = $temp_row['description'];
    }

    $cleanedDbContent = cleanDatabaseHtml($db_content);

    $search_placeholders = [
        '[designation]', '[doj]', '[reporting_manager]', '[candidate_name]', 
        '[employee_type]', '[salary]', '[duration]', 'Work From Home'
    ];
    
    $replace_with_data = [
        $position_name, $doj, $reporting_manager, $candidate_name, 
        $employee_type, $salary, $duration, $employee_type
    ];

    $cleanedDbContent = str_replace($search_placeholders, $replace_with_data, $cleanedDbContent);

    $finalHtml = $introPara . $dynamicPoints . $cleanedDbContent;

    // Generate PDF
    $pdf = new OfferLetterPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->setPrintHeader(true);
    $pdf->setPrintFooter(true);
    
    // ✅ INCREASED bottom margin to prevent overlap
    $pdf->SetMargins(20, 40, 20);
    $pdf->SetAutoPageBreak(true, 45); // ✅ CHANGED: 30 se 45 kiya (footer ke liye zyada space)
    
    $pdf->SetFont('helvetica', '', 10);

   // Fetch letterhead path from database
$letterheadPath = __DIR__ . '/pdf/Letterhead.pdf'; // Default fallback
$lh_query = mysqli_query($db, "SELECT letterhead_pdf FROM login_settings LIMIT 1");
if ($lh_query && $lh_row = mysqli_fetch_assoc($lh_query)) {
    if (!empty($lh_row['letterhead_pdf']) && file_exists(__DIR__ . '/' . $lh_row['letterhead_pdf'])) {
        $letterheadPath = __DIR__ . '/' . $lh_row['letterhead_pdf'];
    }
}

// Use the dynamic path
if (file_exists($letterheadPath)) {
    $pdf->setSourceFile($letterheadPath);
    $tplIdx = $pdf->importPage(1);
    $pdf->setLetterheadTemplate($tplIdx);
}

    $pdf->AddPage();

    // Header: Ref No & Date
    $pdf->SetXY(20, 48);
    $pdf->Write(0, "Ref No. - " . $reference_no);
    $pdf->SetXY(155, 48);
    $pdf->Write(0, "Date - " . $offer_date);

    // Salutation
    $pdf->SetXY(20, 60);
    $pdf->Write(0, "Dear ");
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Write(0, $candidate_name);
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Write(0, ",");
    $pdf->Ln(8);

    // Render the Combined HTML
    $pdf->SetXY(20, 70);
    $pdf->writeHTML($finalHtml, true, false, true, false, 'J');

    // ✅ ADD SPACING before signature
    $pdf->Ln(5); // Add 5mm spacing

    // Signature
    $sigPath = __DIR__ . '/sign/default-sign.png';
    if (!empty($row['sign']) && file_exists(__DIR__ . '/sign/' . basename($row['sign']))) {
        $sigPath = __DIR__ . '/sign/' . basename($row['sign']);
    }

    $sigY = $pdf->GetY() + 10;
    
    // ✅ CHECK if signature will overlap with footer
    $pageHeight = $pdf->GetPageHeight();
    $footerMargin = 45; // Same as AutoPageBreak
    
    if ($sigY > ($pageHeight - $footerMargin - 10)) {
        // Signature will overlap, so add new page
        $pdf->AddPage();
        $sigY = 60; // Start from top on new page
    }
    
    if (file_exists($sigPath)) {
        $pdf->Image($sigPath, 20, $sigY, 60, 0, 'PNG', '', 'T', true, 300);
    }

    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetXY(25, $sigY + 30);
    $pdf->Write(0, "Vibrantick Infotech Solutions");

    // Output PDF
    while (ob_get_level()) ob_end_clean();
    $unique_filename = "Offer_" . preg_replace('/[^A-Za-z0-9]/', '', $candidate_name) . "_" . time() . ".pdf";
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $unique_filename . '"');
    $pdf->Output($unique_filename, 'I');
    exit;

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>