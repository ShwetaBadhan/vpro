<?php
session_start();
error_reporting(E_ALL);
require_once 'db/config.php';

$employeeId = $_SESSION['employee_id'] ?? null;
$adminId = $_SESSION['login_user_id'] ?? null;

if (!$employeeId || !$adminId) {
    $_SESSION['error'] = "You must be logged in as employee.";
    header("Location: index.php");
    exit();
}

// Fetch role
$roleName = '';
$roleQuery = "SELECT r.role_name FROM admin a JOIN roles r ON a.admin_role = r.role_id WHERE a._id = ?";
$stmt = $db->prepare($roleQuery);
$stmt->bind_param("i", $adminId);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $roleName = strtolower($row['role_name']);
} else {
    $_SESSION['error'] = "Role not found.";
    header("Location: index.php");
    exit();
}

if ($roleName !== 'employee') {
    $_SESSION['error'] = "Unauthorized access.";
    header("Location: dashboard.php");
    exit();
}


// ✅ Insert salary slip request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $monthFrom = $_POST['month_from'] ?? '';
    $monthTo = $_POST['month_to'] ?? '';

    $monthFromFormatted = !empty($monthFrom) ? date('Y-m-01', strtotime($monthFrom)) : null;
    $monthToFormatted = !empty($monthTo) ? date('Y-m-01', strtotime($monthTo)) : null;

    if ($monthToFormatted) {
        // ✅ month_to provided
        $stmt = $db->prepare("INSERT INTO salary_slip_requests (employee_id, month_from, month_to) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $employeeId, $monthFromFormatted, $monthToFormatted);
    } else {
        // ✅ month_to is NULL
        $stmt = $db->prepare("INSERT INTO salary_slip_requests (employee_id, month_from, month_to) VALUES (?, ?, NULL)");
        $stmt->bind_param("is", $employeeId, $monthFromFormatted); // only 2 params
    }

    if ($stmt->execute()) {
        $_SESSION['success'] = "Salary slip request submitted successfully!";
    } else {
        $_SESSION['error'] = "Failed to submit request: " . $stmt->error;
    }

    $stmt->close();
    header("Location: request-slip.php");
    exit();
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
    <title>Request Salary Slip</title>
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


            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header table-card-header">
                            <?php if (isset($_SESSION['success'])): ?>
                                <div class="alert alert-success"><?php echo $_SESSION['success'];
                                                                    unset($_SESSION['success']); ?></div>
                            <?php endif; ?>

                            <?php if (isset($_SESSION['error'])): ?>
                                <div class="alert alert-danger"><?php echo $_SESSION['error'];
                                                                unset($_SESSION['error']); ?></div>
                            <?php endif; ?>
                            <br />

                            <div class="container mt-5 mb-5">

                                <h4 class="mb-4 text-primary">Request Salary Slip</h4>

                                <form action="" method="POST">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="month_from" class="form-label">Month From</label>
                                            <input type="month" name="month_from" class="form-control" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="month_to" class="form-label">Month To</label>
                                            <input type="month" name="month_to" class="form-control">
                                        </div>

                                    </div>

                                    <button type="submit" class="btn btn-primary">
                                        <i class="feather icon-send"></i> Submit Request
                                    </button>
                                </form>


                            </div>
                            <?php
                            $query = "SELECT * FROM salary_slip_requests WHERE employee_id = ?";
                            $stmt = $db->prepare($query);
                            $stmt->bind_param("i", $employeeId);
                            $stmt->execute();
                            $result = $stmt->get_result();

                            echo "<table class='table table-bordered'>";
                            echo "<thead>
        <tr>
            <th>Month</th>
            <th>Status</th>
            <th>Requested On</th>
            <th>Action</th>
        </tr>
      </thead>
      <tbody>";

                            while ($row = $result->fetch_assoc()) {
                                $employee_id = $row['employee_id'];
                                $request_id = $row['request_id'];
                                $status = strtolower($row['status']);
                                $created_at = $row['created_at'];

                                $month_from = $row['month_from'];
                                $month_to = $row['month_to'];

                                // ✅ Always set start
                                if (!empty($month_from)) {
                                    $start = new DateTime($month_from);
                                } else {
                                    continue; // Skip this request if no month_from
                                }

                                // ✅ Set end based on month_to, or just use start again (for single month)
                                if (!empty($month_to)) {
                                    $end = new DateTime($month_to);
                                } else {
                                    $end = clone $start;
                                }

                                // ✅ Loop through all months between start and end
                                while ($start <= $end) {
                                    $month = $start->format('Y-m');
                                    $displayMonth = $start->format('F Y');

                                    $sql = "SELECT slip_id FROM salary_slips WHERE user_id = $employee_id AND LEFT(slip_month, 7) = '$month'";
                                    $slipCheck = mysqli_query($db, $sql);
                                    $slipData = mysqli_fetch_assoc($slipCheck);

                                    echo "<tr>
                <td>$displayMonth</td>
                <td>" . ucfirst($status) . "</td>
                <td>" . date('d-m-Y', strtotime($created_at)) . "</td>";

                                    if ($status === 'approved') {
                                        if ($slipData) {
                                            $encodedId = base64_encode($slipData['slip_id']);
                                            echo "<td>
                        <a href='generate-salary-slip.php?id={$encodedId}&request_id={$request_id}' class='btn btn-sm btn-success' target='_blank'>
                            Download Slip
                        </a>
                      </td>";
                                        } else {
                                            echo "<td><span style='color:red;'>❌ Slip not found</span></td>";
                                        }
                                    } else {
                                        echo "<td>--</td>";
                                    }

                                    echo "</tr>";

                                    $start->modify('+1 month');
                                }
                            }

                            echo "</tbody></table>";




                            ?>
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
        function previewTemplateImage(event) {
            const input = event.target;
            const preview = document.getElementById('templatePreview');

            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                };
                reader.readAsDataURL(input.files[0]);
            } else {
                // Reset to default if no file selected
                preview.src = 'assets/images/template/template.jpeg';
            }
        }
    </script>

</body>

</html>