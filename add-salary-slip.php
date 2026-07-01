<?php
require 'db/config.php';
session_start();
error_reporting(E_ALL);

$requestId = $_GET['request_id'] ?? '';  // ✅ capture request ID
$selectedEmpId = $_GET['emp_id'] ?? '';

if (!isset($_SESSION["login_user"])) {
    header("location: index.php");
    exit;
}
$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "✅ POST request received.<br>";

    $user_id        = $_POST['user_id'];
    $slip_month     = $_POST['slip_month'];
    $month_days     = $_POST['month_days'];
    $present_days   = $_POST['present_days'];
    $net_pay        = $_POST['net_pay'];
    $net_pay_words  = $_POST['net_pay_words'];
    $status  = $_POST['status'];

 


    

    // Insert slip
    $insertSlip = "INSERT INTO salary_slips (user_id, slip_month, month_days, present_days, net_pay, net_pay_words, status) 
                   VALUES ('$user_id', '$slip_month', '$month_days', '$present_days', '$net_pay', '$net_pay_words' , '$status')";
    $slipResult = mysqli_query($db, $insertSlip);

    if ($slipResult) {
        echo "✅ Slip inserted successfully.<br>";

        $slip_id = mysqli_insert_id($db);

        // Insert earnings
        if (!empty($_POST['earnings_type'])) {
            echo "✅ Inserting earnings...<br>";
            for ($i = 0; $i < count($_POST['earnings_type']); $i++) {
                $type   = mysqli_real_escape_string($db, $_POST['earnings_type'][$i]);
                $scale  = mysqli_real_escape_string($db, $_POST['earnings_scale'][$i]);
                $amount = mysqli_real_escape_string($db, $_POST['earnings_amount'][$i]);

                mysqli_query($db, "INSERT INTO salary_earnings (slip_id, type, scale_rs, amount_rs) 
                                   VALUES ('$slip_id', '$type', '$scale', '$amount')");
            }
        }

        // Insert deductions
        if (!empty($_POST['deductions_type'])) {
            echo "✅ Inserting deductions...<br>";
            for ($i = 0; $i < count($_POST['deductions_type']); $i++) {
                $type   = mysqli_real_escape_string($db, $_POST['deductions_type'][$i]);
                $scale  = mysqli_real_escape_string($db, $_POST['deductions_scale'][$i]);
                $amount = mysqli_real_escape_string($db, $_POST['deductions_amount'][$i]);

                mysqli_query($db, "INSERT INTO salary_deductions (slip_id, type, scale_rs, amount_rs) 
                                   VALUES ('$slip_id', '$type', '$scale', '$amount')");
            }
        }

        echo "✅ Earnings & deductions inserted.<br>";

        $_SESSION['success'] = "Salary Slip added successfully.";

       if (!empty($_GET['request_id'])) {
    $reqId = intval($_GET['request_id']);

    $updateRequest = "UPDATE salary_slip_requests 
                      SET status = 'approved' 
                      WHERE request_id = '$reqId'";

    $result = mysqli_query($db, $updateRequest);
    if ($result && mysqli_affected_rows($db) > 0) {
        echo "✅ Request with ID $reqId marked approved.<br>";
    } else {
        echo "⚠️ No matching request found or already approved.<br>";
    }
}


        header("Location: add-salary-slip.php");
        exit;
    } else {
        echo "❌ Failed to insert salary slip: " . mysqli_error($db);
        $_SESSION['error'] = "Failed to add Salary Slip.";
        header("Location: add-salary-slip.php");
        exit;
    }
}
$query = "SELECT * FROM login_settings";
    $settingsResult = mysqli_query($db, $query);
    $settings = mysqli_fetch_assoc($settingsResult);

    $logoPath = $settings['backend_panel_logo'];
    $helpdeskNumber = $settings['helpdesk_no'];
    $favicon = $settings['favicon'];
    // echo $favicon;
    // exit;
?>





<!DOCTYPE html>
<html lang="en">

<head>
    <title> Add Salary Slip</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="icon" href="<?php echo $favicon; ?>" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/plugins/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .red-text {
            color: red;
        }
    </style>
    <style>
        .section-header {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 30px 0 15px;
            display: flex;
            align-items: center;
        }

        .section-header i {
            margin-right: 10px;
            color: #0d6efd;
        }

        .card {
            margin-bottom: 20px;
        }

        .table th,
        .table td {
            vertical-align: middle;
        }

        .remove-row {
            cursor: pointer;
            color: red;
            font-size: 1.2rem;
        }
    </style>
</head>

<body>
    <div class="loader-bg">
        <div class="loader-track">
            <div class="loader-fill"></div>
        </div>
    </div>

    <!-- Header -->
    <?php include("header.php"); ?>
    <!-- /Header -->

    <!-- Navbar -->
    <?php include("navbar.php"); ?>
    <!-- /Navbar -->

    <section class="pcoded-main-container">
        <div class="pcoded-content">
            <div class="page-header">
                <div class="page-block">
                    <div class="row align-items-center">
                        <div class="col-md-12">
                            <div class="page-header-title">
                                <h5 class="m-b-10"> Add Salary Slip</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header table-card-header">
                            <?php
                            if (isset($_SESSION['success'])) {
                                echo "<div class='alert alert-success alert-dismissible fade show' role='alert'>
            <strong><i class='feather icon-check'></i> Success!</strong> " . $_SESSION['success'] . "
            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'>X</button>
          </div>";
                                unset($_SESSION['success']);
                            }

                            if (isset($_SESSION['error'])) {
                                echo "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
            <strong><i class='feather icon-alert-circle'></i> Error!</strong> " . $_SESSION['error'] . "
            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'>X</button>
          </div>";
                                unset($_SESSION['error']);
                            }
                            ?>

                            <br />

                            <form method="POST" enctype="multipart/form-data" autocomplete="off">
                                <div class="container bg-white p-4 rounded shadow">

                                    <!-- Employee Selection -->
                                    <div class="section-header"><i class="bi bi-person-badge-fill"></i> Employee Information</div>
                                    <div class="card p-3">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Select Employee</label>
                                                <select name="user_id" id="employeeSelect" class="form-control" required>
                                                    <option value="">-- Select --</option>
                                                    <?php
                                                    $res = mysqli_query($db, "SELECT personal_id, name FROM personal_details");
                                                    while ($row = mysqli_fetch_assoc($res)) {
                                                        $selected = ($row['personal_id'] == $selectedEmpId) ? "selected" : "";
                                                        echo "<option value='{$row['personal_id']}' $selected>{$row['name']}</option>";
                                                    }
                                                    ?>
                                                </select>
<?php if ($selectedEmpId): ?>
    <input type="hidden" name="user_id" value="<?= htmlspecialchars($selectedEmpId) ?>">
<?php endif; ?>

                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Salary Month</label>
                                                <input type="month" name="slip_month" class="form-control" required>
                                            </div>
                                        </div>

                                        <div class="row mt-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Month Days</label>
                                                <input type="number" name="month_days" id="month_days" class="form-control" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Present Days</label>
                                                <input type="number" name="present_days" id="present_days" class="form-control" required>
                                            </div>
                                        </div>

                                        <div id="employeeInfo" class="border mt-3 p-3 bg-light rounded">
                                            <div class="row g-3">
                                                <div class="col-md-4"><strong>Father's Name:</strong> <span id="father_name">--</span></div>
                                                <div class="col-md-4"><strong>Designation:</strong> <span id="designation">--</span></div>
                                                <div class="col-md-4"><strong>DOJ:</strong> <span id="doj">--</span></div>
                                                <div class="col-md-4"><strong>Bank Name: </strong><span id="bank_name">--</span></div>
                                                <div class="col-md-4"><strong>Account No:</strong> <span id="account_no">--</span></div>
                                                <div class="col-md-4"><strong>DOB:</strong> <span id="dob">--</span></div>
                                                <div class="col-md-4"><strong>Employee Code:</strong> <span id="employee_code">--</span></div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Earnings -->
                                    <div class="section-header"><i class="bi bi-cash-coin"></i> Earnings</div>
                                    <div class="card p-3">
                                        <table class="table table-bordered align-middle" id="earningsTable">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Type</th>
                                                    <th>Scale (Rs.)</th>
                                                    <th>Amount (Rs.)</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td><input name="earnings_type[]" class="form-control" value="BASIC SALARY"></td>
                                                    <td><input name="earnings_scale[]" class="form-control" value="₹ 000,000/-"></td>
                                                    <td><input name="earnings_amount[]" class="form-control earning-amount" value="0"></td>
                                                    <td><span class="remove-row" onclick="removeRow(this)">×</span></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <button type="button" class="btn btn-outline-success btn-sm" onclick="addRow('earningsTable')">+ Add Earning</button>
                                    </div>

                                    <!-- Deductions -->
                                    <div class="section-header"><i class="bi bi-dash-circle"></i> Deductions</div>
                                    <div class="card p-3">
                                        <table class="table table-bordered align-middle" id="deductionsTable">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Type</th>
                                                    <th>Scale (Rs.)</th>
                                                    <th>Amount (Rs.)</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td><input name="deductions_type[]" class="form-control" value="PF"></td>
                                                    <td><input name="deductions_scale[]" class="form-control" value="NA"></td>
                                                    <td><input name="deductions_amount[]" class="form-control deduction-amount" value="0"></td>
                                                    <td><span class="remove-row" onclick="removeRow(this)">×</span></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <button type="button" class="btn btn-outline-success btn-sm" onclick="addRow('deductionsTable')">+ Add Deduction</button>
                                    </div>

                                    <!-- Net Pay -->
                                    <div class="section-header"><i class="bi bi-wallet2"></i> Net Pay</div>
                                    <div class="card p-3">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Net Pay</label>
                                                <input type="text" name="net_pay" id="net_pay" class="form-control" readonly>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Net Pay (in words)</label>
                                                <input type="text" name="net_pay_words" id="net_pay_words" class="form-control" readonly>
                                            </div>
                                               <!-- Status -->
                                    <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
                                        <div class="form-group">
                                            <label for="status" class="form-label">Status </label>
                                            <select name="status" class="form-control">
                                                <option value="" selected disabled>Choose</option>
                                                <option value="paid">Paid</option>
                                                <option value="unpaid">Unpaid</option>
                                            </select>
                                        </div>
                                    </div>
                                        </div>
                                        
                                    </div>
                                   

                                    <!-- Submit -->
                                    <div class="text-end mt-3">
                                        <button type="submit" class="btn btn-primary btn-lg">💾 Save Salary Slip</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="card-body">
                            <div class="dt-responsive table-responsive"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="assets/js/vendor-all.min.js"></script>
    <script src="assets/js/plugins/bootstrap.min.js"></script>
    <script src="assets/js/pcoded.min.js"></script>
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
    <script>
        $(document).ready(function() {
            $("#goldmessage").delay(5000).slideUp(300);
        });
    </script>
    <script>
        function addRow(tableId) {
            const table = document.getElementById(tableId).getElementsByTagName('tbody')[0];
            const newRow = table.rows[0].cloneNode(true);
            newRow.querySelectorAll('input').forEach(input => input.value = '');
            table.appendChild(newRow);
        }

        function removeRow(el) {
            const row = el.closest('tr');
            const tbody = row.parentElement;
            if (tbody.rows.length > 1) tbody.removeChild(row);
        }
        document.addEventListener("DOMContentLoaded", function() {
            const empSelect = document.getElementById("employeeSelect");

            // Reusable function for formatting date
            function formatDate(dateStr) {
                if (!dateStr) return '';
                const date = new Date(dateStr);
                if (isNaN(date)) return '';
                const day = String(date.getDate()).padStart(2, '0');
                const month = String(date.getMonth() + 1).padStart(2, '0'); // Month is 0-based
                const year = date.getFullYear();
                return `${day}-${month}-${year}`;
            }

            // Function to fetch and display employee info
            function loadEmployeeInfo(userId) {
                if (!userId) return;

                fetch("get-employee-info.php?user_id=" + userId)
                    .then(res => res.json())
                    .then(data => {
                        document.getElementById("father_name").innerText = data.father_name || '--';
                        document.getElementById("designation").innerText = data.designation || '--';
                        document.getElementById("doj").innerText = formatDate(data.doj) || '--';
                        document.getElementById("dob").innerText = formatDate(data.dob) || '--';
                        document.getElementById("account_no").innerText = data.account_no || '--';
                        document.getElementById("employee_code").innerText = data.employee_code || '--';
                        document.getElementById("bank_name").innerText = data.bank_name || '--';
                    });
            }

            // Trigger on change
            empSelect.addEventListener("change", function() {
                loadEmployeeInfo(this.value);
            });

            // Auto-trigger if value is pre-selected (e.g. via emp_id in URL)
            if (empSelect.value !== "") {
                loadEmployeeInfo(empSelect.value);
            }
        });
    </script>
    <script>
        function numberToWords(n) {
            const a = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten',
                'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
                'Seventeen', 'Eighteen', 'Nineteen'
            ];
            const b = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

            const numToWords = (num) => {
                if (num < 20) return a[num];
                if (num < 100) return b[Math.floor(num / 10)] + (num % 10 ? ' ' + a[num % 10] : '');
                if (num < 1000) return a[Math.floor(num / 100)] + ' Hundred' + (num % 100 ? ' and ' + numToWords(num % 100) : '');
                if (num < 100000) return numToWords(Math.floor(num / 1000)) + ' Thousand' + (num % 1000 ? ' ' + numToWords(num % 1000) : '');
                return num;
            };

            return n ? numToWords(n) + " Rupees Only" : '';
        }

        function calculateNetPay() {
            const monthDays = parseInt(document.getElementById("month_days").value) || 0;
            const presentDays = parseInt(document.getElementById("present_days").value) || 0;

            let totalEarnings = 0;
            let totalDeductions = 0;

            document.querySelectorAll(".earning-amount").forEach(input => {
                let val = parseFloat(input.value.replace(/,/g, '')) || 0;
                totalEarnings += val;
            });

            document.querySelectorAll(".deduction-amount").forEach(input => {
                let val = parseFloat(input.value.replace(/,/g, '')) || 0;
                totalDeductions += val;
            });

            // Prorated earnings calculation
            let proratedEarnings = totalEarnings;
            if (monthDays > 0 && presentDays > 0) {
                proratedEarnings = (totalEarnings / monthDays) * presentDays;
            }

            const netPay = Math.round(proratedEarnings - totalDeductions);

            document.getElementById("net_pay").value = netPay;
            document.getElementById("net_pay_words").value = numberToWords(netPay);
        }

        // Events to trigger net pay calculation
        document.getElementById("month_days").addEventListener("input", calculateNetPay);
        document.getElementById("present_days").addEventListener("input", calculateNetPay);

        // Attach event to dynamically added inputs too
        document.addEventListener('input', function(event) {
            if (event.target.matches('.earning-amount') || event.target.matches('.deduction-amount')) {
                calculateNetPay();
            }
        });
    </script>



</body>

</html>