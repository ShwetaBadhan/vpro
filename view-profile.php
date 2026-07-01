<?php
session_start();
error_reporting(E_ALL);
require_once 'db/config.php';
// echo '<pre>';
// print_r($_SESSION);
// exit;
$employeeId = $_SESSION['employee_id'] ?? null;
$adminId = $_SESSION['login_user_id'] ?? null;
// echo $employeeId;
// exit;
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
    // echo $roleName;
    // exit;
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

// Fetch employee data
$query = "SELECT 
    p.personal_id, p.name, p.father_name, p.dob, p.mobile, p.email, p.address,
    p.state, p.city, p.status AS personal_status, p.blood_group, p.adhar_no, p.pan_no,
    b.ifsc_code, b.account_no, b.bank_name,
    c.doj, c.email AS company_email, c.laptop_mobile, c.employee_status, c.id_card,
    c.device_details, c.last_working_day, c.verified_by, c.verification_date,
    c.employee_code, c.work_assigned,
    position.name AS designation,
    GROUP_CONCAT(clients.name SEPARATOR ', ') AS assigned_clients,
    state.state_name, city.city_name
FROM personal_details p
LEFT JOIN bank_details b ON b.user_id = p.personal_id
LEFT JOIN company_details c ON c.user_id = p.personal_id
LEFT JOIN state ON state.state_id = p.state
LEFT JOIN city ON city.city_id = p.city
LEFT JOIN position ON position.position_id = c.designation
LEFT JOIN clients ON FIND_IN_SET(clients.client_id, c.assigned_client)
WHERE p.personal_id = ?
GROUP BY p.personal_id";

$stmt = $db->prepare($query);
$stmt->bind_param("i", $employeeId);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $emp = $result->fetch_assoc();
} else {
    echo "<div class='alert alert-danger'>Profile not found.</div>";
    exit();
}

// Set form defaults
$names = $emp['name'] ?? '';
$father_name = $emp['father_name'] ?? '';
$phone = $emp['mobile'] ?? '';
$gmail = $emp['email'] ?? '';
$address = $emp['address'] ?? '';
$state = $emp['state'] ?? '';
$city = $emp['city'] ?? '';
$blood = $emp['blood_group'] ?? '';
$dob = $emp['dob'] ?? '';
$adhar = $emp['adhar_no'] ?? '';
$pan = $emp['pan_no'] ?? '';
$ifsc = $emp['ifsc_code'] ?? '';
$account = $emp['account_no'] ?? '';
$bankname = $emp['bank_name'] ?? '';

// Update Personal Details
if (isset($_POST['personal_submit'])) {
    $updatedName = mysqli_real_escape_string($db, $_POST['name']);
    $updateFather = mysqli_real_escape_string($db, $_POST['father_name']);
    $updatedPhone = mysqli_real_escape_string($db, $_POST['mobile']);
    $updatedGmail = mysqli_real_escape_string($db, $_POST['email']);
    $updatedAddress = mysqli_real_escape_string($db, $_POST['address']);
    $updatedState = intval($_POST['state'] ?? 0);
    $updatedCity = intval($_POST['city'] ?? 0);
    $updatedBlood = mysqli_real_escape_string($db, $_POST['blood_group']);
    $updateddob = mysqli_real_escape_string($db, $_POST['dob']);
    $updatedAdhar = mysqli_real_escape_string($db, $_POST['adhar_no']);
    $updatedPan = mysqli_real_escape_string($db, $_POST['pan_no']);

    $checkQuery = "SELECT * FROM personal_details WHERE personal_id = $employeeId";
    $checkResult = mysqli_query($db, $checkQuery);

    if (mysqli_num_rows($checkResult) > 0) {
        $updateQuery = "UPDATE personal_details SET 
            name = '$updatedName',
            mobile = '$updatedPhone',
            father_name = '$updateFather',
            email = '$updatedGmail',
            address = '$updatedAddress',
            state = '$updatedState',
            city = '$updatedCity',
            blood_group = '$updatedBlood',
            dob = '$updateddob',
            adhar_no = '$updatedAdhar',
            pan_no = '$updatedPan'
            WHERE personal_id = $employeeId";
    } else {
        $updateQuery = "INSERT INTO personal_details (
            personal_id, name, mobile, father_name, email, address, state, city, blood_group, dob, adhar_no, pan_no
        ) VALUES (
            $employeeId, '$updatedName', '$updatedPhone', '$updateFather', '$updatedGmail', '$updatedAddress',
            '$updatedState', '$updatedCity', '$updatedBlood', '$updateddob', '$updatedAdhar', '$updatedPan')";
    }

    if ($db->query($updateQuery) === TRUE) {
        $_SESSION['success'] = "Personal details updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating personal information: " . $db->error;
    }
    header("Location: view-profile.php");
    exit();
}

// Update Bank Details
if (isset($_POST['bank_submit'])) {
    $updateIFSC = mysqli_real_escape_string($db, $_POST['ifsc_code']);
    $updatedAccount = mysqli_real_escape_string($db, $_POST['account_no']);
    $updatedBank = mysqli_real_escape_string($db, $_POST['bank_name']);

    $checkQuery = "SELECT * FROM bank_details WHERE user_id = $employeeId";
    $checkResult = mysqli_query($db, $checkQuery);

    if (mysqli_num_rows($checkResult) > 0) {
        $updateQuery = "UPDATE bank_details SET 
            ifsc_code = '$updateIFSC',
            account_no = '$updatedAccount',
            bank_name = '$updatedBank'
            WHERE user_id = $employeeId";
    } else {
        $updateQuery = "INSERT INTO bank_details (user_id, ifsc_code, account_no, bank_name) 
                        VALUES ($employeeId, '$updateIFSC', '$updatedAccount', '$updatedBank')";
    }

    if ($db->query($updateQuery) === TRUE) {
        $_SESSION['success'] = "Bank details updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating bank information: " . $db->error;
    }
    header("Location: view-profile.php");
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

<meta http-equiv="content-type" content="text/html;charset=UTF-8" />

<head>
    <title>All Employees</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="" />
    <meta name="keywords" content="">
    <meta name="author" content="" />

    <link rel="icon" href="<?php echo $favicon; ?>" type="image/x-icon">

    <link rel="stylesheet" href="assets/css/plugins/dataTables.bootstrap4.min.css">

    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.tiny.cloud/1/l0jt1pl0jxgk8lnq5hkx6x384hqvgjse7l8c3mnanxhhzju3/tinymce/6/tinymce.min.js"
        referrerpolicy="origin"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <!-- Bootstrap CSS -->


</head>

<body class="">

    <div class="loader-bg">
        <div class="loader-track">
            <div class="loader-fill"></div>
        </div>
    </div>

    <!-- Header -->
    <?php
    include("header.php");
    ?>
    <!-- /Header -->

    <!-- navbar -->
    <?php
    include("navbar.php");
    ?>
    <!-- /navbar -->

    <section class="pcoded-main-container">
        <div class="pcoded-content">
            <div class="page-header">
                <div class="page-block">
                    <div class="row align-items-center">
                        <div class="col-md-12">
                            <div class="page-header-title">
                                <h5 class="m-b-10"><?php echo htmlspecialchars($emp['name']); ?>
                            </div>
                            <?php if (isset($_SESSION['success'])): ?>
                                <div class="alert alert-success"><?php echo $_SESSION['success'];
                                                                    unset($_SESSION['success']); ?></div>
                            <?php endif; ?>

                            <?php if (isset($_SESSION['error'])): ?>
                                <div class="alert alert-danger"><?php echo $_SESSION['error'];
                                                                unset($_SESSION['error']); ?></div>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-body add-product pb-0">
                    <div class="table-responsive">
                        <!-- <table class="table table-bordered">
                            <tr class="bg-light">
                               <th>Personal Details</th> 
                            </tr>
                            <th>
                                <td>Name  : <?php echo htmlspecialchars($emp['name']); ?></td>
                            </th>
                        </table> -->
                      <?php
if (!empty($emp)) {
    echo "<table class='table table-bordered'>";
    echo "<thead class='table-light'><tr><th colspan='5'>Personal Details</th>
    <th><button type='button' class='btn btn-primary' data-toggle='modal' data-target='#editPersonalModal'>
        Edit Personal Details
    </button></th></tr></thead>";

    echo "<tr>
        <th>Name</th><td>{$emp['name']}</td>
        <th>Father Name</th><td>{$emp['father_name']}</td>
        <th>DOB</th><td>" . date('d-m-Y', strtotime($emp['dob'])) . "</td>
    </tr>";

    $wrappedAddress = wordwrap($emp['address'], 20, '<br>', true);
    $wrappedEmails = wordwrap($emp['email'], 20, '<br>', true);

    echo "<tr>
        <th>Mobile</th><td>{$emp['mobile']}</td>
        <th>Email</th><td>{$wrappedEmails}</td>
        <th>Address</th><td>{$wrappedAddress}</td>
    </tr>";

    echo "<tr>
        <th>Adhar No</th><td>{$emp['adhar_no']}</td>
        <th>PAN No</th><td>{$emp['pan_no']}</td>
    </tr>";

    echo "<thead class='table-light'><tr><th colspan='5'>Bank Details</th>
    <th><button type='button' class='btn btn-primary' data-toggle='modal' data-target='#editBankModal'>
        Edit Bank Details
    </button></th></tr></thead>";

    $wrappedBank = wordwrap($emp['bank_name'], 20, '<br>', true);

    echo "<tr>
        <th>Bank Name</th><td>{$wrappedBank}</td>
        <th>Account No</th><td>{$emp['account_no']}</td>
        <th>IFSC Code</th><td>{$emp['ifsc_code']}</td>
    </tr>";

    echo "<thead class='table-light'><tr><th colspan='6'>Company Details</th></tr></thead>";

    $wrappedEmail = wordwrap($emp['company_email'], 15, '<br>', true);
    $wrappedDesignation = wordwrap($emp['designation'], 20, '<br>', true);

    echo "<tr>
        <th>Designation</th><td>{$wrappedDesignation}</td>
        <th>Employee Code</th><td>{$emp['employee_code']}</td>
        <th>Joining Date</th><td>" . (!empty($emp['doj']) ? date('d-m-Y', strtotime($emp['doj'])) : '-') . "</td>
    </tr>";

    $wrappedClients = wordwrap((string)($emp['assigned_clients'] ?? ''), 20, '<br>', true);
    $wrappedWork = wordwrap($emp['work_assigned'], 20, '<br>', true);

    echo "<tr>
        <th>Official Email</th><td>{$wrappedEmail}</td>
        <th>Clients Assigned</th><td>{$wrappedClients}</td>
        <th>Work Assigned</th><td>{$wrappedWork}</td>
    </tr>";

    $wrappedDevice = wordwrap($emp['device_details'] ?? '', 30, '<br>', true);
    echo "<tr>
        <th>ID Card</th><td>" . ($emp['id_card'] == '1' ? 'Yes' : 'No') . "</td>
        <th>Laptop/Mobile</th><td>";
    
    if ($emp['laptop_mobile'] == '1') {
        echo "Yes";
        if (!empty($emp['device_details'])) {
            echo "<br><strong>Device Details:</strong> {$wrappedDevice}";
        }
    } else {
        echo "No";
    }

    echo "</td>
        <th>Employee Status</th><td>" . ($emp['employee_status'] == '1' ? 'Active' : 'Inactive') . "</td>
    </tr>";

    echo "<tr>
        <th>Last Working Day</th><td>" . (!empty($emp['last_working_day']) ? date('d-m-Y', strtotime($emp['last_working_day'])) : '-') . "</td>
        <th>Handover Name</th><td>{$emp['verified_by']}</td>
        <th>Handover Date</th><td>" . (!empty($emp['verification_date']) ? date('d-m-Y', strtotime($emp['verification_date'])) : '-') . "</td>
    </tr>";

    echo "</table>";
} else {
    echo "<div class='alert alert-danger'>Employee details not found.</div>";
}
?>

                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Modal -->
    <!-- Edit Personal Details Modal -->
    <div class="modal fade" id="editPersonalModal" tabindex="-1" role="dialog" aria-labelledby="editPersonalModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Personal Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Your form goes here -->
                    <form method="post" action="" enctype="multipart/form-data" autocomplete="off">
                        <div class="row">
                            <form method="post" action="" enctype="multipart/form-data" autocomplete="off">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="name" class="form-label">Full Name</label>
                                            <input type="text" class="form-control" id="name" name="name" value="<?php echo $names; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="father_name" class="form-label">Father's Name</label>
                                            <input type="text" class="form-control" id="father_name" name="father_name" value="<?php echo $father_name; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="mobile" class="form-label">Mobile No.</label>
                                            <input type="text" class="form-control" id="mobile" name="mobile" value="<?php echo $phone; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="email" name="email" value="<?php echo $gmail; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="adhar_no" class="form-label">Aadhaar No.</label>
                                            <input type="text" class="form-control" id="adhar_no" name="adhar_no" value="<?php echo $adhar; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="pan_no" class="form-label">PAN No.</label>
                                            <input type="text" class="form-control" id="pan_no" name="pan_no" value="<?php echo $pan; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="blood_group" class="form-label">Blood Group</label>
                                            <input type="text" class="form-control" id="blood_group" name="blood_group" value="<?php echo $blood; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="dob" class="form-label">Date of Birth</label>
                                            <input type="date" class="form-control" id="dob" name="dob" value="<?php echo $dob; ?>">
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-md-4 col-sm-12 col-12">
                                        <label for="state" class="form-label">State</label>
                                        <select name="state" class="form-control select" id="state">
                                            <option value="" disabled>Choose</option>
                                            <?php
                                            $state_query = "SELECT * FROM state WHERE status='1'";
                                            $result = $db->query($state_query);
                                            if ($result->num_rows > 0) {
                                                while ($row = $result->fetch_assoc()) {
                                                    $selected = ($row['state_id'] == $state) ? 'selected' : '';
                                                    echo "<option value='{$row['state_id']}' $selected>{$row['state_name']}</option>";
                                                }
                                            }
                                            ?>
                                        </select>


                                    </div>

                                    <div class="col-xl-3 col-lg-6 col-md-4 col-sm-12 col-12">
                                        <label for="city" class="form-label">City</label>
                                        <select name="city" class="form-control select" id="city">
                                            <option value="" selected disabled>Select State First</option>
                                        </select>

                                    </div>


                                    <div class="col-xl-12 col-lg-6 col-md-12 col-sm-12 col-12 mb-3">
                                        <label for="address" class="form-label">Permanent Address</label>
                                        <textarea class="form-control" name="address" id="address" rows="3" placeholder="Enter your permanent address"><?php echo $address; ?></textarea>
                                    </div>

                                    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">

                                        <button type="submit" class="btn custom-btn btn-primary" name="personal_submit" id="submit">
                                            <i class="feather icon-save lg"></i>&nbsp; Submit
                                        </button>


                                    </div>
                                </div>
                            </form>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- Edit Personal Details Modal -->
    <div class="modal fade" id="editBankModal" tabindex="-1" role="dialog" aria-labelledby="editBankModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Bank Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form method="post" action="" enctype="multipart/form-data" autocomplete="off">
                        <input type="hidden" name="user_id" value="<?php echo $_SESSION['user_id'] ?? ''; ?>">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="bankname" class="form-label">Bank Name</label>
                                    <input type="text" class="form-control" id="bankname" name="bank_name" value="<?php echo $bankname; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="accountNumber" class="form-label">Account Number</label>
                                    <input type="text" class="form-control" id="accountNumber" name="account_no" value="<?php echo $account; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="ifsc" class="form-label">IFSC Code</label>
                                    <input type="text" class="form-control" id="ifsc" name="ifsc_code" value="<?php echo $ifsc; ?>">
                                </div>
                            </div>
                            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">

                                <button type="submit" class="btn custom-btn btn-primary" name="bank_submit" id="submit">
                                    <i class="feather icon-save lg"></i>&nbsp; Submit
                                </button>


                            </div>
                        </div>



                    </form>
                </div>
            </div>
        </div>
    </div>
    </div>
    <!-- Edit Lead Status Modal -->
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <!-- Bootstrap JS (Popper included) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(".multiple-select").select2({
            //   maximumSelectionLength: 2
        });
    </script>
    <script>
        $(document).ready(function() {
            $("#goldmessage").delay(5000).slideUp(300);
        });
    </script>





</body>

</html>