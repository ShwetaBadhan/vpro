<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/vendor/autoload.php';

use setasign\Fpdf\Fpdf;
use setasign\Fpdi\Fpdi;

require_once __DIR__ . '/db/config.php';

// Helper function to get centered X position
function getCenteredX($pdf, $text, $centerReferenceX = 105, $fontFamily = 'Times', $fontSize = 26) {
    $pdf->SetFont($fontFamily, '', $fontSize);
    $textWidth = $pdf->GetStringWidth($text);
    return $centerReferenceX - ($textWidth / 2);
}

if (isset($_GET['id'])) {
    $encoded_id = $_GET['id'];
    $decoded_id = base64_decode($encoded_id);

    $query = "SELECT * FROM internship_certificate WHERE internship_id = ?";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "i", $decoded_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        $reference = $row['certificate_id'];
        $issue_date = date('F d, Y', strtotime($row['created_at']));
        $candidate_name = $row['student_name'];
        $training = $row['training_type'];
        $designation = $row['designation'];
        
        // ✅ STATUS FETCH
        $status = $row['status'] ?? 'Currently Pursuing';
        
        // ✅ DYNAMIC STATUS TEXT
        if ($status == 'Successfully Completed') {
            $status_text = 'HAS SUCCESSFULLY COMPLETED';
        } else {
            $status_text = 'IS CURRENTLY PURSUING';
        }
        
        // ✅ DYNAMIC DATE HANDLING
        $date_From = date('d F, Y', strtotime($row['date_from']));
        
        // Check if date_to is empty, null, or '0000-00-00'
        if (empty($row['date_to']) || $row['date_to'] == '0000-00-00' || $row['date_to'] == '0000-00-00 00:00:00') {
            $date_To = 'Present';
        } else {
            $date_To = date('d F, Y', strtotime($row['date_to']));
        }

        // ✅ FETCH CERTIFICATE TEMPLATE PATH FROM DATABASE (DYNAMIC)
        $certificatePath = __DIR__ . '/pdf/internship-certificate.pdf'; // Default fallback
        $cert_query = mysqli_query($db, "SELECT certificate_pdf FROM login_settings LIMIT 1");
        
        if ($cert_query && $cert_row = mysqli_fetch_assoc($cert_query)) {
            if (!empty($cert_row['certificate_pdf']) && file_exists(__DIR__ . '/' . $cert_row['certificate_pdf'])) {
                $certificatePath = __DIR__ . '/' . $cert_row['certificate_pdf'];
            }
        }

        // ✅ CHECK IF CERTIFICATE TEMPLATE EXISTS
        if (!file_exists($certificatePath)) {
            die("<h1>❌ Error: Certificate Template Not Found!</h1>
                 <p>Please upload a certificate template from System Settings → Global Certificate Template.</p>
                 <p>Expected path: <code>" . htmlspecialchars($certificatePath) . "</code></p>");
        }

        $pdf = new Fpdi();

        // ✅ IMPORT BACKGROUND PDF (DYNAMIC PATH)
        $pageCount = $pdf->setSourceFile($certificatePath);
        $templateId = $pdf->importPage(1);
        $templateSize = $pdf->getTemplateSize($templateId);

        $pdf->AddPage($templateSize['orientation'], [$templateSize['width'], $templateSize['height']]);
        $pdf->useTemplate($templateId);

        // ─────────────────────────────────────
        // Candidate Name (Centered Dynamically)
        // ─────────────────────────────────────
        $pdf->SetFont('Times', '', 26);
        $pdf->SetTextColor(27,72,105);
        $nameX = getCenteredX($pdf, $candidate_name, 105, 'Times', 26);
        $pdf->Text($nameX, 115, $candidate_name);

        // ─────────────────────────────────────
        // ✅ STATUS TEXT (Centered Dynamically)
        // ─────────────────────────────────────
        $pdf->SetFont('Times', 'I', 18); // Italic for status
        $pdf->SetTextColor(0, 0, 0); // Gray color
        $statusX = getCenteredX($pdf, $status_text, 105, 'Times', 18);
        $pdf->Text($statusX, 128, $status_text);

        // ─────────────────────────────────────
        // Training Type (Centered Dynamically)
        // ─────────────────────────────────────
        $pdf->SetFont('Times', '', 24);
        $pdf->SetTextColor(27,72,105);
        $trainingX = getCenteredX($pdf, $training, 105, 'Times', 24);
        $pdf->Text($trainingX, 145, $training);

        // ─────────────────────────────────────
        // Designation (Centered + Blue Color)
        // ─────────────────────────────────────
        $pdf->SetTextColor(27,72,105);
        $pdf->SetFont('Times', '', 28);
        $desigX = getCenteredX($pdf, $designation, 105, 'Times', 28);
        $pdf->Text($desigX, 165, $designation);

        // ─────────────────────────────────────
        // Dates (Fixed positions)
        // ─────────────────────────────────────
        $pdf->SetFont('Times', '', 16);
        $pdf->SetTextColor(27,72,105);
        $pdf->Text(68, 182, $date_From);
        $pdf->Text(128, 182, $date_To);

        // ─────────────────────────────────────
        // Certificate ID
        // ─────────────────────────────────────
        $pdf->SetFont('Times', '', 12);
        $pdf->SetTextColor(27,72,105);
        $pdf->Text(107, 213, $reference);

        // ─────────────────────────────────────
        // Issue Date
        // ─────────────────────────────────────
        $pdf->Text(20, 242, $issue_date);

        // Output
        $filename = 'Internship_Certificate_' . preg_replace('/[^A-Za-z0-9]/', '_', $candidate_name) . '_' . time() . '.pdf';
        
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        $pdf->Output($filename, 'I');
        exit;
    } else {
        echo 'Invalid request.';
    }
} else {
    echo 'No ID provided.';
}
?>