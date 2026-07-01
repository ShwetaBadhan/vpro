<?php
require_once __DIR__ . '/vendor/autoload.php';
include("db/config.php");

use Mpdf\Mpdf;

ob_start(); // Start capturing output

error_reporting(E_ALL);
ini_set('display_errors', 1);

$slipId = base64_decode($_GET['id']);
if (!$slipId) die("Invalid Slip ID");

$query = "
SELECT 
    ss.*, 
    pd.name, pd.father_name, pd.dob,
    cd.employee_code, cd.doj,
    pos.name AS designation,
    bd.account_no,
    bd.bank_name
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

$monthText = date("F Y", strtotime($slip['slip_month']));
$dob = date("d-M-Y", strtotime($slip['dob']));
$doj = date("d-M-Y", strtotime($slip['doj']));
?>
<!DOCTYPE html>
<html>

<head>
    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"> -->

    <style>
        table {
    border-collapse: collapse;
    width: 100%;
    table-layout: fixed;   /* 🔥 MOST IMPORTANT */
    font-family: Times;
    font-size: 12px;
}


        th,
        td {
            border: 1px solid #000;
            padding: 6px;
            vertical-align: top;
            text-align: left;
        }

        .no-border {
            border: none;
        }

        .bold {
            font-weight: bold;
        }

        .footer-note {
            font-weight: bold;
        }

        .salary-slip-header {
            text-align: center;
        }

        .center-img {
            display: block;
            margin: 0 auto 10px auto;
            width: 70px;
        }
    </style>
</head>

<body>
    <div style="text-align: right;">

</div>
    <div class="container salary-slip-header">
            <!-- Status Stamp Image at Footer -->

        <div class="row">
            <div class="col-lg-12">
                <img src="assets/images/test.jpg" alt="img" class="center-img">
                <h5>VIBRANTICK INFOTECH SOLUTIONS</h5>
                <h5>Office No. 36, Second Floor, D-185, Phase 8B,</h5>
                <h5>Industrial Area, Sector 74, Sahibzada Ajit Singh Nagar (Mohali), Punjab-160055</h5>
                <h5>Salary Slip for Month: <?= $monthText ?></h5>
            </div>
        </div>
        
    </div>





    <table class="bold">
        <tr>
            <td>Month Days</td>
            <td>:</td>
            <td><?= $slip['month_days'] ?></td>
            <td>Present Days</td>
            <td>:</td>
            <td><?= $slip['present_days'] ?></td>
        </tr>
        <tr>
            <td>Employee Name</td>
            <td>:</td>
            <td><?= $slip['name'] ?></td>
            <td>Code</td>
            <td>:</td>
            <td><?= $slip['employee_code'] ?></td>
        </tr>
        <tr>
            <td>Father’s Name</td>
            <td>:</td>
            <td><?= $slip['father_name'] ?></td>
            <td>DOJ</td>
            <td>:</td>
            <td><?= $doj ?></td>
        </tr>
        <tr>
            <td>Bank A/c No</td>
            <td>:</td>
            <td><?= $slip['account_no'] ?></td>
            <td>Designation</td>
            <td>:</td>
<td style="
    width: 180px;
    word-wrap: break-word;
    overflow-wrap: break-word;
    white-space: normal;
">
    <?= $slip['designation'] ?>
</td>


        </tr>
        <tr>
            <td>Bank Name</td>
            <td>:</td>
            <td><?= $slip['bank_name'] ?></td>

            <td>DOB</td>
            <td>:</td>
            <td><?= $dob ?></td>
        </tr>
    </table>

    <br>

    <table class="bold">
        <tr>
            <th>Earnings</th>
            <th>Scale</th>
            <th>Amount</th>
            <th>Deductions</th>
            <th>Scale</th>
            <th>Amount</th>

        </tr>

        <?php
        $earning_rows = [];
        while ($e = mysqli_fetch_assoc($earnings)) {
            $earning_rows[] = $e;
        }

        $deduction_rows = [];
        while ($d = mysqli_fetch_assoc($deductions)) {
            $deduction_rows[] = $d;
        }

        $maxRows = max(count($earning_rows), count($deduction_rows));
        $groupSize = 1;

        for ($i = 0; $i < $maxRows; $i += $groupSize) {
            for ($j = 0; $j < $groupSize; $j++) {
                echo "<tr>";

                // Rowspan only for first row in group
                if ($j == 0) {
                    echo "<td rowspan='$groupSize'>" . ($earning_rows[$i]['type'] ?? '') . "</td>";
                }

                // Earnings scale/amount
                echo "<td>" . ($earning_rows[$i + $j]['scale_rs'] ?? '') . "</td>";
                echo "<td>" . ($earning_rows[$i + $j]['amount_rs'] ?? '') . "</td>";

                // Deductions
                echo "<td>" . ($deduction_rows[$i + $j]['type'] ?? '') . "</td>";
                echo "<td>" . ($deduction_rows[$i + $j]['scale_rs'] ?? '') . "</td>";
                echo "<td>" . ($deduction_rows[$i + $j]['amount_rs'] ?? '') . "</td>";

                echo "</tr>";
            }
        }


        ?>
    </table>



    <table class="bold">
        <tr>
            <td width="20%">Net Pay</td>
            <td width="5%">:</td>
            <td>₹<?= $slip['net_pay'] ?></td>
        </tr>
        <tr>
            <td>In Words</td>
            <td>:</td>
            <td><?= $slip['net_pay_words'] ?></td>
        </tr>
    </table>



    <table>
        <tr>
            <td class="footer-note">This is a computer-generated sheet and does not require a signature.</td>
        </tr>
    </table>

    <script src="assets/js/vendor-all.min.js"></script>
    <script src="assets/js/plugins/bootstrap.min.js"></script>
    <script src="assets/js/pcoded.min.js"></script>
    <!--<script src="assets/js/menu-setting.min.js"></script>-->

    <script src="assets/js/plugins/jquery.dataTables.min.js"></script>
    <script src="assets/js/plugins/dataTables.bootstrap4.min.js"></script>
    <script src="assets/js/plugins/buttons.colVis.min.js"></script>
    <script src="assets/js/plugins/buttons.print.min.js"></script>
    <script src="assets/js/plugins/pdfmake.min.js"></script>
    <script src="assets/js/plugins/jszip.min.js"></script>
    <script src="assets/js/plugins/dataTables.buttons.min.js"></script>
    <script src="assets/js/plugins/buttons.html5.min.js"></script>
    <script src="assets/js/plugins/buttons.bootstrap4.min.js"></script>
    <script src="assets/js/pages/data-export-custom.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <!-- Bootstrap JS (Popper included) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
<?php
$html = ob_get_clean();

// ✅ Fix image paths for mPDF (convert relative to absolute)
$basePath = __DIR__ . '/';
$html = str_replace('src="assets/', 'src="' . $basePath . 'assets/', $html);

// Generate PDF
$mpdf = new Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'margin_left' => 10,
    'margin_right' => 10,
    'margin_top' => 15,
    'margin_bottom' => 15,
]);
$mpdf->WriteHTML($html);
$mpdf->Output("SalarySlip.pdf", "I");
?>