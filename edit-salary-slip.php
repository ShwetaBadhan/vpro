<?php
session_start();
require_once 'db/config.php';

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $encodedRoleId = $_GET['id'];
    $roleId = base64_decode($encodedRoleId);

    // Fetch salary slip
    $slipRes = mysqli_query($db, "SELECT * FROM salary_slips WHERE slip_id = '$roleId'");
    $slip = mysqli_fetch_assoc($slipRes);
    if (!$slip) die("Salary slip not found.");

    // Fetch earnings
    $earnings = [];
    $eRes = mysqli_query($db, "SELECT * FROM salary_earnings WHERE slip_id = '$roleId'");
    while ($row = mysqli_fetch_assoc($eRes)) {
        $earnings[] = $row;
    }

    // Fetch deductions
    $deductions = [];
    $dRes = mysqli_query($db, "SELECT * FROM salary_deductions WHERE slip_id = '$roleId'");
    while ($row = mysqli_fetch_assoc($dRes)) {
        $deductions[] = $row;
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $slip_month = $_POST['slip_month'];
        $month_days = $_POST['month_days'];
        $present_days = $_POST['present_days'];
        $net_pay = $_POST['net_pay'];
        $net_pay_words = $_POST['net_pay_words'];
        $status = $_POST['status'];

        // Update main salary slip
        $stmt = $db->prepare("UPDATE salary_slips SET slip_month=?, month_days=?, present_days=?, net_pay=?, net_pay_words=?, status=? WHERE slip_id=?");
        $stmt->bind_param("siisssi", $slip_month, $month_days, $present_days, $net_pay, $net_pay_words,$status, $roleId);
        $success = $stmt->execute();

        if ($success) {
            // Delete old earnings and deductions
            mysqli_query($db, "DELETE FROM salary_earnings WHERE slip_id = '$roleId'");
            mysqli_query($db, "DELETE FROM salary_deductions WHERE slip_id = '$roleId'");

            // Insert updated earnings
            if (!empty($_POST['earnings_type'])) {
                $typeArr = $_POST['earnings_type'];
                $scaleArr = $_POST['earnings_scale'];
                $amountArr = $_POST['earnings_amount'];

                foreach ($typeArr as $i => $type) {
                    $type = mysqli_real_escape_string($db, $type);
                    $scale = mysqli_real_escape_string($db, $scaleArr[$i]);
                    $amount = mysqli_real_escape_string($db, $amountArr[$i]);
                    mysqli_query($db, "INSERT INTO salary_earnings (slip_id, type, scale_rs, amount_rs) VALUES ('$roleId', '$type', '$scale', '$amount')");
                }
            }

            // Insert updated deductions
            if (!empty($_POST['deductions_type'])) {
                $typeArr = $_POST['deductions_type'];
                $scaleArr = $_POST['deductions_scale'];
                $amountArr = $_POST['deductions_amount'];

                foreach ($typeArr as $i => $type) {
                    $type = mysqli_real_escape_string($db, $type);
                    $scale = mysqli_real_escape_string($db, $scaleArr[$i]);
                    $amount = mysqli_real_escape_string($db, $amountArr[$i]);
                    mysqli_query($db, "INSERT INTO salary_deductions (slip_id, type, scale_rs, amount_rs) VALUES ('$roleId', '$type', '$scale', '$amount')");
                }
            }

            // Set success message and redirect
            $_SESSION['success'] = "Salary Slip updated successfully.";
            header("Location: edit-salary-slip.php?id=" . $_GET['id']);
            exit;
        } else {
            $_SESSION['error'] = "Failed to update Salary Slip.";
            header("Location: edit-salary-slip.php?id=" . $_GET['id']);
            exit;
        }
    }
} else {
    die("Invalid ID");
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
    <title><?php echo isset($_GET['id']) ? 'Update' : 'Add'; ?> </title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="" />
    <meta name="keywords" content="">
    <meta name="author" content="" />
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

<body class="">
    <div class="loader-bg">
        <div class="loader-track">
            <div class="loader-fill"></div>
        </div>
    </div>
    <!-- Header -->
    <?php include("header.php"); ?>
    <!-- /Header -->
    <!-- navbar -->
    <?php include("navbar.php"); ?>
    <!-- /navbar -->
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
                                    <div class="section-header"><i class="bi bi-person-badge-fill"></i> Employee Info</div>
                                    <div class="card p-3">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label>Select Employee</label>
                                                <select name="user_id" class="form-control" disabled>
                                                    <?php
                                                    $res = mysqli_query($db, "SELECT personal_id, name FROM personal_details");
                                                    while ($row = mysqli_fetch_assoc($res)) {
                                                        $selected = ($row['personal_id'] == $slip['user_id']) ? "selected" : "";
                                                        echo "<option value='{$row['personal_id']}' $selected>{$row['name']}</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label>Salary Month</label>
                                                <input type="month" name="slip_month" class="form-control" value="<?= $slip['slip_month'] ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label>Month Days</label>
                                                <input type="number" name="month_days" class="form-control" id="month_days" value="<?= $slip['month_days'] ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label>Present Days</label>
                                                <input type="number" name="present_days" class="form-control" id="present_days" value="<?= $slip['present_days'] ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="section-header mt-4"><i class="bi bi-cash-coin"></i> Earnings</div>
                                    <div class="card p-3">
                                        <table class="table" id="earningsTable">
                                            <thead>
                                                <tr>
                                                    <th>Type</th>
                                                    <th>Scale</th>
                                                    <th>Amount</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($earnings as $e): ?>
                                                    <tr>
                                                        <td><input name="earnings_type[]" class="form-control" value="<?php echo $e['type'] ?>"></td>
                                                        <td><input name="earnings_scale[]" class="form-control" value="<?php echo $e['scale_rs'] ?>"></td>
                                                        <td><input name="earnings_amount[]" class="form-control earning-amount" value="<?php echo $e['amount_rs'] ?>"></td>
                                                        <td><span class="remove-row" onclick="removeRow(this)">×</span></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                        <button type="button" class="btn btn-sm btn-outline-success" onclick="addRow('earningsTable')">+ Add</button>
                                    </div>

                                    <div class="section-header mt-4"><i class="bi bi-dash-circle"></i> Deductions</div>
                                    <div class="card p-3">
                                        <table class="table" id="deductionsTable">
                                            <thead>
                                                <tr>
                                                    <th>Type</th>
                                                    <th>Scale</th>
                                                    <th>Amount</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($deductions as $d): ?>
                                                    <tr>
                                                        <td><input name="deductions_type[]" class="form-control" value="<?php echo $d['type'] ?>"></td>
                                                        <td><input name="deductions_scale[]" class="form-control" value="<?php echo $d['scale_rs'] ?>"></td>
                                                        <td><input name="deductions_amount[]" class="form-control deduction-amount" value="<?php echo $d['amount_rs'] ?>"></td>
                                                        <td><span class="remove-row" onclick="removeRow(this)">×</span></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                        <button type="button" class="btn btn-sm btn-outline-success" onclick="addRow('deductionsTable')">+ Add</button>
                                    </div>

                                    <div class="section-header mt-4"><i class="bi bi-wallet2"></i> Net Pay</div>
                                    <div class="card p-3">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label>Net Pay</label>
                                                <input type="text" name="net_pay" id="net_pay" class="form-control" value="<?= $slip['net_pay'] ?>" readonly>
                                            </div>
                                            <div class="col-md-6">
                                                <label>Net Pay (Words)</label>
                                                <input type="text" name="net_pay_words" id="net_pay_words" class="form-control" value="<?= $slip['net_pay_words'] ?>" readonly>
                                            </div>
                                            <div class="col-xl-6 col-lg-6 col-md-4 col-sm-12 col-12 mt-3">
    <div class="form-group">
        Status <span class="red-text">*</span>
        <select name="status" id="status" class="form-control" required>
            <option value="" disabled>Choose</option>
            <option value="paid" <?php echo (isset($slip['status']) && $slip['status'] === 'paid') ? 'selected' : ''; ?>>
                Paid
            </option>
            <option value="unpaid" <?php echo (isset($slip['status']) && $slip['status'] === 'unpaid') ? 'selected' : ''; ?>>
                Unpaid
            </option>
        </select>
    </div>
</div>
                                      
                                        </div>
                                    </div>

                                    <div class="text-end mt-3">
                                        <button type="submit" class="btn btn-primary btn-lg">💾 Update Salary Slip</button>
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
        // Function to show the preview button when a file is selected
        function showPreviewButton() {
            var previewBtn = document.getElementById('previewBtn');
            previewBtn.style.display = 'block';
        }
    </script>

    <script>
        // Function to show the preview modal
        function showPreviewModal() {
            var modal = document.getElementById('imagePreviewModal');
            var modalImg = document.getElementById('previewImage');
            var files = document.getElementsByName('uploaded_file')[0].files;

            // Check if any file is selected
            if (files.length > 0) {
                var file = files[0];
                var reader = new FileReader();

                reader.onload = function(e) {
                    modalImg.src = e.target.result;
                    $(modal).modal('show'); // Show the modal
                }

                reader.readAsDataURL(file);
            }
        }
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
        document.getElementById("employeeSelect").addEventListener("change", function() {
            const userId = this.value;
            if (userId === "") return;

            fetch("get-employee-info.php?user_id=" + userId) // ✅ fixed URL
                .then(res => res.json())
                .then(data => {
                    document.getElementById("father_name").innerText = data.father_name || '--';
                    document.getElementById("designation").innerText = data.designation || '--';
                    document.getElementById("doj").innerText = formatDate(data.doj) || '--';

                    function formatDate(dateStr) {
                        if (!dateStr) return '';
                        const date = new Date(dateStr);
                        if (isNaN(date)) return '';
                        const day = String(date.getDate()).padStart(2, '0');
                        const month = String(date.getMonth() + 1).padStart(2, '0'); // Month is 0-based
                        const year = date.getFullYear();
                        return `${day}-${month}-${year}`;
                    }
                    document.getElementById("dob").innerText = formatDate(data.dob) || '--';

                    function formatDate(dateStr) {
                        if (!dateStr) return '';
                        const date = new Date(dateStr);
                        if (isNaN(date)) return '';
                        const day = String(date.getDate()).padStart(2, '0');
                        const month = String(date.getMonth() + 1).padStart(2, '0'); // Month is 0-based
                        const year = date.getFullYear();
                        return `${day}-${month}-${year}`;
                    }

                    document.getElementById("account_no").innerText = data.account_no || '--';
                    document.getElementById("employee_code").innerText = data.employee_code || '--';

                })

        });
    </script>
    <!-- Your full HTML form here (including inputs with ids: month_days, present_days, net_pay, net_pay_words) -->

<!-- Place this script at the bottom of the page -->
<script>
document.addEventListener("DOMContentLoaded", function () {

    function numberToWords(n) {
        const a = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten',
            'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
            'Seventeen', 'Eighteen', 'Nineteen'];
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
        const monthDaysEl = document.getElementById("month_days");
        const presentDaysEl = document.getElementById("present_days");

        if (!monthDaysEl || !presentDaysEl) return;

        const monthDays = parseInt(monthDaysEl.value) || 0;
        const presentDays = parseInt(presentDaysEl.value) || 0;

        let totalEarnings = 0;
        let totalDeductions = 0;

        document.querySelectorAll(".earning-amount").forEach(input => {
            let val = parseFloat(input.value.replace(/[^0-9.]/g, '')) || 0;
            totalEarnings += val;
        });

        document.querySelectorAll(".deduction-amount").forEach(input => {
            let val = parseFloat(input.value.replace(/[^0-9.]/g, '')) || 0;
            totalDeductions += val;
        });

        let proratedEarnings = totalEarnings;
        if (monthDays > 0 && presentDays > 0) {
            proratedEarnings = (totalEarnings / monthDays) * presentDays;
        }

        const netPay = Math.round(proratedEarnings - totalDeductions);

        document.getElementById("net_pay").value = netPay;
        document.getElementById("net_pay_words").value = numberToWords(netPay);
    }

    // Attach listeners
    const monthDaysInput = document.getElementById("month_days");
    const presentDaysInput = document.getElementById("present_days");

    if (monthDaysInput && presentDaysInput) {
        monthDaysInput.addEventListener("input", calculateNetPay);
        presentDaysInput.addEventListener("input", calculateNetPay);
    }

    document.addEventListener("input", function (event) {
        if (event.target.matches(".earning-amount") || event.target.matches(".deduction-amount")) {
            calculateNetPay();
        }
    });

    // Calculate initially if pre-filled
    calculateNetPay();
});
</script>

</body>

</html>
