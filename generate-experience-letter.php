<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once 'db/config.php';

use Mpdf\Mpdf;

if (isset($_GET['id'])) {
    $encoded_id = $_GET['id'];
    $decoded_id = base64_decode($encoded_id);
    
    // ✅ SQL Injection fix
    $decoded_id = mysqli_real_escape_string($db, $decoded_id);

    $query = "
    SELECT 
        el.experience_id,
        el.employee_id,
        el.designation,
        el.status,
        el.date_to,
        el.date_from,
        el.reference_no,
        el.created_at,
        el.sign,
        position.name AS position_name,
        personal_details.name AS candidate_name
    FROM experience_letter AS el
    LEFT JOIN position ON position.position_id = el.designation
    LEFT JOIN personal_details ON personal_details.personal_id = el.employee_id
    WHERE el.experience_id = '$decoded_id'
    ";

    $result = mysqli_query($db, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);

        $reference = $row['reference_no'];
        $issue_date = date('F d, Y', strtotime($row['created_at']));
        $candidate_name = $row['candidate_name'];
        $position = $row['position_name'];
        $date_To = date('d F, Y', strtotime($row['date_to']));
        $date_From = date('d F, Y', strtotime($row['date_from']));
        $sign = $row['sign'];
        
        // ✅ Fix image path
        if (!empty($sign) && !file_exists($sign)) {
            $sign = 'sign/default-sign.png';
        }
        
        $html = ' 
        <html>
        <head>
            <style>
                body { 
                    font-family: times; 
                    font-size: 12pt;
                }
                .header {
                    margin-top: 80px;
                    margin-left: 20px;
                    margin-right: 20px;
                }
                .center { 
                    text-align: center; 
                    margin-top: 40px; 
                }
                .bold-underline { 
                    font-weight: bold; 
                    text-decoration: underline; 
                }
                .content { 
                    text-align: justify; 
                    margin: 30px 40px 0 40px;
                    line-height: 1.6;
                }
                .signature {
                    margin-top: 50px;
                    margin-left: 40px;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <table width="100%">
                    <tr>
                        <td style="text-align: left;">Ref No. - ' . $reference . '</td>
                        <td style="text-align: right;">Date - ' . $issue_date . '</td>
                    </tr>
                </table>
            </div>

            <div class="center">
                <span class="bold-underline">TO WHOM IT MAY CONCERN</span>
            </div>

            <div class="content">
                <p>
                    This is to certify that <strong>' . $candidate_name . '</strong> was employed with Vibrantick Infotech Solutions as a <strong>' . $position . '</strong> from <strong>' . $date_From . '</strong> to <strong>' . $date_To . '</strong>.
                </p>
                <p>
                    During their tenure with us, <strong>' . $candidate_name . '</strong> demonstrated excellent professionalism, dedication, and a positive attitude toward their work. They maintained good relationships with colleagues and contributed significantly to the team\'s objectives.
                </p>
                <p>We wish them the best in their future endeavors.</p>
            </div>

            <div class="signature">
                <img src="' . $sign . '" alt="Signature" style="width:200px; height:auto;"><br>
                <strong>Authorized Signatory</strong><br>
                Vibrantick Infotech Solutions
            </div>
        </body>
        </html>';

        try {
            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'default_font_size' => 12,
                'default_font' => 'times',
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 50,
                'margin_bottom' => 20
            ]);

            // ✅ DYNAMIC LETTERHEAD - Fetch from database
            $letterheadPath = 'pdf/Letterhead.pdf'; // Default fallback
            $lh_query = mysqli_query($db, "SELECT letterhead_pdf FROM login_settings LIMIT 1");
            if ($lh_query && $lh_row = mysqli_fetch_assoc($lh_query)) {
                if (!empty($lh_row['letterhead_pdf']) && file_exists($lh_row['letterhead_pdf'])) {
                    $letterheadPath = $lh_row['letterhead_pdf'];
                }
            }
        
            // ✅ CORRECT ORDER: Import template FIRST
            $pagecount = $mpdf->SetSourceFile($letterheadPath);
            $tplId = $mpdf->ImportPage(1);
            
            // ✅ Add page with template
            $mpdf->AddPage();
            $mpdf->UseTemplate($tplId);

            // ✅ THEN write HTML content
            $mpdf->WriteHTML($html);
            
            // ✅ Output with unique filename
            $filename = 'Experience_Letter_' . preg_replace('/[^A-Za-z0-9]/', '_', $candidate_name) . '_' . time() . '.pdf';
            $mpdf->Output($filename, 'I');
            
        } catch (Exception $e) {
            echo "PDF Generation Error: " . $e->getMessage();
        }
    } else {
        echo "Invalid request or experience letter not found.";
    }
} else {
    echo "Missing ID parameter.";
}
?>