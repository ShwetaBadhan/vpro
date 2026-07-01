<?php
session_start();
include('db/config.php');

$msg = "";

// Fetch personal data
// Fetch personal data
if (isset($_GET['id'])) {
    $encodedPersonalID = $_GET['id'];
    $PersonalID = intval(base64_decode($encodedPersonalID)); // Safely decode and cast to int

$query = "SELECT * FROM personal_details WHERE personal_id = $PersonalID";
$result = mysqli_query($db, $query);

if (mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $names = $row['name'];
    $father_name = $row['father_name'];
    $phone = $row['mobile'];
    $gmail = $row['email'];
    $address = $row['address'];
    $state = $row['state'];
    $city = $row['city'];
    $blood = $row['blood_group'];
    $dob = $row['dob'];
    $adhar = $row['adhar_no'];
    $pan = $row['pan_no'] ?? null;
    $existingEmpImage = $row['photo'] ?? null;
    $emergency_no = $row['emergency_no'] ?? null;
} else {
    $names = $father_name = $phone = $gmail = $address = $state = $city = $blood = $dob = $adhar = $emergency_no = $pan = $existingEmpImage = "";
}

if (isset($_POST['personal_submit'])) {

    // ==========================
    // IMAGE UPLOAD / UPDATE LOGIC
    // ==========================

    $emp_img_path = $existingEmpImage; // default: keep old image

    // CASE 1: Cropped image (Base64)
    if (!empty($_POST['cropped_emp_image'])) {
        $croppedData = $_POST['cropped_emp_image'];
        $croppedData = str_replace('data:image/png;base64,', '', $croppedData);
        $croppedData = str_replace(' ', '+', $croppedData);
        $imageData   = base64_decode($croppedData);

        $emp_img_path = "employeeimage/" . time() . "_cropped.png";
        file_put_contents($emp_img_path, $imageData);

    // CASE 2: Normal file upload
    } elseif (!empty($_FILES['photo']['name'])) {
        $emp_img = $_FILES['photo']['name'];
        $emp_tmp = $_FILES['photo']['tmp_name'];
        $emp_img_path = "employeeimage/" . time() . "_" . basename($emp_img);
        move_uploaded_file($emp_tmp, $emp_img_path);
    }
    // CASE 3: No new image uploaded → keep $existingEmpImage (default above)

    // ==========================
    // SANITIZE FORM DATA
    // ==========================

    $updatedName = mysqli_real_escape_string($db, $_POST['name']);
    $updateFather = mysqli_real_escape_string($db, $_POST['father_name']);
    $updatedPhone = mysqli_real_escape_string($db, $_POST['mobile']);
    $updatedGmail = mysqli_real_escape_string($db, $_POST['email']);
    $updatedAddress = mysqli_real_escape_string($db, $_POST['address']);
    $updatedState = intval($_POST['state']);
    $updatedCity = intval($_POST['city']);
    $updatedBlood = mysqli_real_escape_string($db, $_POST['blood_group']);
    $updateddob = mysqli_real_escape_string($db, $_POST['dob']);
    $updatedAdhar = mysqli_real_escape_string($db, $_POST['adhar_no']);
    $updatedPan = mysqli_real_escape_string($db, $_POST['pan_no']);
    $updateemergency =  mysqli_real_escape_string($db, $_POST['emergency_no']);
    // ==========================
    // CHECK IF RECORD EXISTS
    // ==========================
    $checkQuery = "SELECT * FROM personal_details WHERE personal_id = $PersonalID";
    $checkResult = mysqli_query($db, $checkQuery);

    if (mysqli_num_rows($checkResult) > 0) {
        // UPDATE existing record
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
            pan_no = '$updatedPan',
            photo = '$emp_img_path',
            emergency_no = '$updateemergency'
            WHERE personal_id = $PersonalID";
    } else {
        // INSERT new record
        $updateQuery = "INSERT INTO personal_details (
            personal_id, name, mobile, father_name, email, address, state, city, blood_group, dob, adhar_no, pan_no, photo, emergency_no
        ) VALUES (
            $PersonalID, '$updatedName', '$updatedPhone', '$updateFather', '$updatedGmail', '$updatedAddress',
            '$updatedState', '$updatedCity', '$updatedBlood', '$updateddob', '$updatedAdhar', '$updatedPan', '$emp_img_path', '$updateemergency')";
    }

    // ==========================
    // EXECUTE QUERY
    // ==========================
    if ($db->query($updateQuery) === TRUE) {
        $status = base64_encode(1);
        echo ("<script>window.location.href='manage-employees.php?status=$status';</script>");
    } else {
        echo "Error updating Personal Information: " . $db->error;
    }
}


    // Bank details
    $query = "SELECT * FROM bank_details WHERE user_id = $PersonalID";
    $result = mysqli_query($db, $query);

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $ifsc = $row['ifsc_code'];
        $account = $row['account_no'];
        $bankname = $row['bank_name'];
    } else {
        $ifsc = "";
        $account = "";
        $bankname = "";
    }

    if (isset($_POST['bank_submit'])) {
        $updateIFSC = mysqli_real_escape_string($db, $_POST['ifsc_code']);
        $updatedAccount = mysqli_real_escape_string($db, $_POST['account_no']);
        $updatedBank = mysqli_real_escape_string($db, $_POST['bank_name']);
        $checkQuery = "SELECT * FROM bank_details WHERE user_id = $PersonalID";
        $checkResult = mysqli_query($db, $checkQuery);

        if (mysqli_num_rows($checkResult) > 0) {
            $updateQuery = "UPDATE bank_details SET 
                ifsc_code = '$updateIFSC',
                account_no = '$updatedAccount',
                bank_name = '$updatedBank'
                WHERE user_id = $PersonalID";
        } else {
            $updateQuery = "INSERT INTO bank_details (user_id, ifsc_code, account_no, bank_name) VALUES (
                $PersonalID, '$updateIFSC', '$updatedAccount', '$$updatedBank')";
        }

        if ($db->query($updateQuery) === TRUE) {
            $status = base64_encode(1);
            echo ("<script>window.location.href='manage-employees.php?status=$status';</script>");
        } else {
            echo "Error updating Bank Information: " . $db->error;
        }
    }

    // Assigned clients
    $assignedClientIds = [];
    $clientQuery = "SELECT assigned_client FROM company_details WHERE user_id = $PersonalID";
    $clientIdResult = mysqli_query($db, $clientQuery);
    if ($clientIdResult && mysqli_num_rows($clientIdResult) > 0) {
        $row = mysqli_fetch_assoc($clientIdResult);
        $assignedString = $row['assigned_client'];
        if (!empty($assignedString)) {
            $assignedClientIds = array_map('trim', explode(',', $assignedString));
        }
    }

    // Assigned work
    $assignedWork = [];
    $workQuery = "SELECT work_assigned FROM company_details WHERE user_id = $PersonalID";
    $workResult = mysqli_query($db, $workQuery);
    if ($workResult && mysqli_num_rows($workResult) > 0) {
        $row = mysqli_fetch_assoc($workResult);
        $assignedWorkList = $row['work_assigned'];
        if (!empty($assignedWorkList)) {
            $assignedWork = array_map('trim', explode(',', $assignedWorkList));
        }
    }

    // Company details
    $query = "SELECT * FROM company_details WHERE user_id = $PersonalID";
    $result = mysqli_query($db, $query);

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $designation = $row['designation'];
        $doj = $row['doj'];
        $email = $row['email'];
        $id = $row['id_card'];
        $laptop = $row['laptop_mobile'];
        $status = $row['employee_status'];
        $device = $row['device_details'];
        $lastday = $row['last_working_day'];
        $verified = $row['verified_by'];
        $verification = $row['verification_date'];
        $emp_code = $row['employee_code'];
        $assignclient = $row['assigned_client'];
        $workassigned = $row['work_assigned'];
        
$selectedEmployementType = $row['employement_type'] ?? '';
    } else {
        $designation = "";
        $doj = "";
        $email = "";
        $id = "";
        $laptop = "";
        $status = "";
        $device = "";
        $lastday = "";
        $verified = "";
        $verification = "";
        $emp_code = "";
        $assignclient = "";
        $workassigned = "";
        $employement_type = "";
    }

    if (isset($_POST['company_submit'])) {
        $updatedesignation = mysqli_real_escape_string($db, $_POST['designation']);
        $updatedoj = mysqli_real_escape_string($db, $_POST['doj']);
        $updateemail = mysqli_real_escape_string($db, $_POST['email']);
        $updateid = mysqli_real_escape_string($db, $_POST['id_card']);
        $updatelaptop = mysqli_real_escape_string($db, $_POST['laptop_mobile']);
        $updatestatus = mysqli_real_escape_string($db, $_POST['employee_status']);
        $updatedevice = mysqli_real_escape_string($db, $_POST['device_details']);
        $updatedLastDay = mysqli_real_escape_string($db, $_POST['last_working_day']);
        $updatedVerified = mysqli_real_escape_string($db, $_POST['verified_by']);
        $updatedVerification = mysqli_real_escape_string($db, $_POST['verification_date']);
        $updatedCode = mysqli_real_escape_string($db, $_POST['employee_code']);
        $assignedClients = isset($_POST['assigned_client']) ? implode(',', $_POST['assigned_client']) : '';
        $assignedWork = isset($_POST['work_assigned']) ? implode(',', $_POST['work_assigned']) : '';
        $updatedEmployementType = $_POST['employement_type'];
        $checkQuery = "SELECT * FROM company_details WHERE user_id = $PersonalID";
        $checkResult = mysqli_query($db, $checkQuery);

        if (mysqli_num_rows($checkResult) > 0) {
            $updateQuery = "UPDATE company_details SET 
                designation = '$updatedesignation',
                doj = '$updatedoj',
                email = '$updateemail',
                id_card = '$updateid',
                laptop_mobile = '$updatelaptop',
                employee_status = '$updatestatus',
                device_details = '$updatedevice',
                last_working_day = '$updatedLastDay',
                verified_by = '$updatedVerified',
                verification_date = '$updatedVerification',
                employee_code = '$updatedCode',
                assigned_client = '$assignedClients',
                work_assigned = '$assignedWork',
                employement_type = '$updatedEmployementType'
                WHERE user_id = $PersonalID";
        } else {
            $updateQuery = "INSERT INTO company_details (
                user_id, designation, doj, email, id_card, laptop_mobile, employee_status, device_details, last_working_day,
                verified_by, verification_date, employee_code, assigned_client, work_assigned , employement_type
            ) VALUES (
                $PersonalID, '$updatedesignation', '$updatedoj', '$updateemail', '$updateid', '$updatelaptop', '$updatestatus',
                '$updatedevice', '$updatedLastDay', '$updatedVerified', '$updatedVerification', '$updatedCode', '$assignedClients', '$assignedWork' , '$updatedEmployementType')";
        }

        if ($db->query($updateQuery) === TRUE) {
            $status = base64_encode(1);
            echo ("<script>window.location.href='manage-employees.php?status=$status';</script>");
        } else {
            echo "Error updating Company Information: " . $db->error;
        }
    }

} else {
    echo "Invalid request!";
    exit;
}





if (isset($_POST['state_id'])) {
    $state_id = mysqli_real_escape_string($db, $_POST['state_id']);

    $query = "SELECT * FROM city WHERE state_id = '$state_id' AND status='1'";
    $result = mysqli_query($db, $query);

    echo "<option value='' selected disabled>Choose</option>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<option value='{$row['city_id']}'>{$row['city_name']}</option>";
    }

    exit(); // stop further HTML output
}
// Fetch position from the database
$positionquery = "SELECT position_id, name FROM position WHERE status = 1"; // Assuming your table is `city_type`
$positionresult = mysqli_query($db, $positionquery);


// Fetch state from the database
$clientquery = "SELECT client_id, name FROM clients WHERE status = 1";
$clientresult = mysqli_query($db, $clientquery);
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
    <title>Update Employee</title>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="" />
    <meta name="keywords" content="">
    <meta name="author" content="" />

    <link rel="icon" href="<?php echo $favicon; ?>" type="image/x-icon">

    <link rel="stylesheet" href="assets/css/plugins/dataTables.bootstrap4.min.css">

    <link rel="stylesheet" href="assets/css/style.css">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet">

    <style>
        .custom-btn {
            background-color: #192f59;
            color: #fff;

        }

        .buttons {
            gap: 20px;
        }

        .box {
            border: 1px solid #EEEFE0;
            padding: 10px;
            border-radius: 10px;

        }
    </style>
    <Style>
        .select2-container--default .select2-selection--multiple .select2-selection__rendered li {
            list-style: none;
            color: #003399;
            ;
            background: #fff;
        }

        .red-text {
            color: red;
        }
    </Style>
      <style>
        #cropImage {
            border: 2px dashed #00ff99;
            background: #222;
            box-shadow: 0 0 15px rgba(0, 255, 153, 0.5);
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" />

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
                                <h5 class="m-b-10">Update Employee
                                </h5>
                            </div>
                            <!--                             <ul class="breadcrumb"> -->
                            <!--                                 <li class="breadcrumb-item"><a href="index.php"><i class="feather icon-home"></i></a> -->
                            <!--                                 </li> -->

                            <!--                             </ul> -->
                        </div>
                    </div>
                </div>
            </div>


            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header table-card-header">
                            <?php
                            if ($msg) {
                                echo $msg;
                            }
                            ?>
                            <br />
                            <div class="container mt-5">
                                <div class="d-flex buttons mb-3" role="tablist">
                                    <button class="btn active custom-btn" id="personal-tab" data-bs-toggle="tab" data-bs-target="#personal" type="button" role="tab">
                                        Personal Details
                                    </button>
                                    <button class="btn custom-btn" id="bank-tab" data-bs-toggle="tab" data-bs-target="#bank" type="button" role="tab">
                                        Bank Details
                                    </button>
                                    <button class="btn custom-btn" id="company-tab" data-bs-toggle="tab" data-bs-target="#company" type="button" role="tab">
                                        Company Details
                                    </button>
                                </div>



                                <div class="tab-content mt-3" id="formTabsContent">
                                    <!-- Personal Details -->
                                    <div class="tab-pane fade show active box" id="personal" role="tabpanel">
                                        <form method="post" action="" enctype="multipart/form-data" autocomplete="off">
                                            <div class="row">
                                                <div class="col-xl-3 col-lg-3 col-md-3 col-sm-12 col-3">
                                        <div class="form-group">
                                            <label for="emp_image" class="form-label">Employee Image <span class="red-text">*</span></label>
                                            <input type="file" name="photo" id="emp_photo" class="form-control">

                                            <?php if (!empty($existingEmpImage)) { ?>
                                                <div class="form-group mt-3">
                                                    <label class="form-label">Current Employee Image</label>
                                                    <div>
                                                        <img src="<?php echo $existingEmpImage; ?>"
                                                            class="img-thumbnail"
                                                            alt="Employee Image"
                                                            style="height: 150px; width: 150px;">
                                                    </div>
                                                </div>
                                            <?php } ?>
                                        </div>
                                    </div>

                                    <!-- Crop Modal -->
                                    <div class="modal fade" id="cropModal" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-fullscreen">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Crop Employee Image</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">X</button>
                                                </div>
                                                <div class="modal-body d-flex justify-content-center align-items-center bg-dark">
                                                    <img id="cropImage" style="max-width: 90%; max-height: 85vh; display:block; margin:auto;" />
                                                </div>
                                                <div class="modal-footer justify-content-center">
                                                    <button type="button" id="cropBtn" class="btn btn-success px-4">✅ Crop & Save</button>
                                                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">❌ Cancel</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Hidden input to store cropped image (Base64) -->
                                    <input type="hidden" name="cropped_emp_image" id="cropped_emp_image">

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
                                                        <label for="mobile" class="form-label">Emergency Mobile No.</label>
                                                        <input type="text" class="form-control" id="emergency_no" name="emergency_no" value="<?php echo $emergency_no; ?>">
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

                                                    <button type="submit" class="btn custom-btn" name="personal_submit" id="submit">
                                                        <i class="feather icon-save lg"></i>&nbsp; Submit
                                                    </button>


                                                </div>
                                            </div>
                                        </form>
                                    </div>

                                    <!-- Bank Details -->
                                    <div class="tab-pane fade box" id="bank" role="tabpanel">
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

                                                    <button type="submit" class="btn custom-btn" name="bank_submit" id="submit">
                                                        <i class="feather icon-save lg"></i>&nbsp; Submit
                                                    </button>


                                                </div>
                                            </div>



                                        </form>
                                    </div>

                                    <!-- Company Details -->
                                    <div class="tab-pane fade box" id="company" role="tabpanel">
                                        <form method="post" action="" enctype="multipart/form-data" autocomplete="off">
                                            <input type="hidden" name="company_id" value="<?php echo $_SESSION['company_id'] ?? ''; ?>">
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <div class="mb-3">
                                                        <label for="designation" class="form-label">Employee Code</label>
                                                        <input type="text" class="form-control" id="employee_code" name="employee_code" value="<?php echo $emp_code ?>">
                                                    </div>
                                                </div>
                                                <div class="col-xl-3 col-lg-3 col-md-12 col-sm-12 col-12">
                                                    <div class="form-group">
                                                        <label for="designation" class="form-label">Select Designation <span class="red-text">*</span></label>
                                                        <select class="form-control" id="designation" name="designation" required>
                                                            <option value="" disabled>Choose a position</option>
                                                            <?php
                                                            if (mysqli_num_rows($positionresult) > 0) {
                                                                while ($row = mysqli_fetch_assoc($positionresult)) {
                                                                    $positionId = $row['position_id'];
                                                                    $positionName = $row['name'];

                                                                    // Compare with ID, not name
                                                                    $selected = ($positionId == $designation) ? "selected" : "";

                                                                    echo "<option value='" . $positionId . "' $selected>" . $positionName . "</option>";
                                                                }
                                                            } else {
                                                                echo "<option value='' disabled>No positions found</option>";
                                                            }
                                                            ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="mb-3">
                                                        <label for="doj" class="form-label">Joining Date</label>
                                                        <input type="date" class="form-control" id="doj" name="doj" value="<?php echo $doj; ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="mb-3">
                                                        <label for="email" class="form-label">Official Mail</label>
                                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo $email; ?>">
                                                    </div>

                                                </div>
                                                <div class="col-md-3">
                                                    <div class="mb-3">
                                                        <label for="email" class="form-label">ID Card</label>
                                                        <select id="" name="id_card" class="form-control">
                                                            <option value="" selected disabled>Choose</option>
                                                            <option value="1" <?php echo ($id == 1) ? 'selected' : ''; ?>>Yes</option>
                                                            <option value="0" <?php echo ($id != 1) ? 'selected' : ''; ?>>No</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="mb-3">
                                                        <label for="laptop_mobile" class="form-label">Laptop/Mobile</label>
                                                        <select id="laptop_mobile" name="laptop_mobile" class="form-control">
                                                            <option value="" selected disabled>Choose</option>
                                                            <option value="1" <?php echo ($laptop == 1) ? 'selected' : ''; ?>>Yes</option>
                                                            <option value="0" <?php echo ($laptop != 1) ? 'selected' : ''; ?>>No</option>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="col-md-6" id="deviceDetailsBox">
                                                    <div class="mb-3">
                                                        <label for="device_details" class="form-label">Device Details</label>
                                                        <textarea id="device_details" name="device_details" class="form-control" rows="3"
                                                            placeholder="Enter device details here..."><?php echo $device; ?></textarea>
                                                    </div>
                                                </div>
                                           <div class="col-md-3">
    <div class="mb-3">
        <label for="employment_type" class="form-label">Employment Type</label>
        <select name="employement_type" class="form-control"
            oninvalid="this.setCustomValidity('Please Select Status')"
            oninput="setCustomValidity('')" required>
            <option value="" disabled <?= $selectedEmployementType == '' ? 'selected' : '' ?>>Choose</option>
            <option value="Regular" <?= $selectedEmployementType == 'Regular' ? 'selected' : '' ?>>Regular</option>
            <option value="Contract" <?= $selectedEmployementType == 'Contract' ? 'selected' : '' ?>>Contract</option>
            <option value="WFM" <?= $selectedEmployementType == 'WFM' ? 'selected' : '' ?>>Work From Home</option>
            <option value="Intern" <?= $selectedEmployementType == 'Intern' ? 'selected' : '' ?>>Intern</option>
            <option value="Trainee" <?= $selectedEmployementType == 'Trainee' ? 'selected' : '' ?>>Trainee</option>
        </select>
    </div>
</div>

                                                <div class="col-md-3">
                                                    <div class="mb-3">
                                                        <label for="email" class="form-label">Employee Status</label>
                                                        <select id="" name="employee_status" class="form-control">
                                                            <option value="" selected disabled>Choose</option>
                                                            <option value="1" <?php echo ($status == 1) ? 'selected' : ''; ?>>Enable</option>
                                                            <option value="0" <?php echo ($status != 1) ? 'selected' : ''; ?>>Disable</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-xl-3 col-lg-3 col-md-4 col-sm-12 col-12">
                                                    <div class="form-group">
                                                        <label for="">Assigned Client</label>
                                                        <select id="multiSelect" name="assigned_client[]" multiple="multiple" class="form-control">
                                                            <?php
                                                            mysqli_data_seek($clientresult, 0); // Reset result pointer
                                                            while ($row = mysqli_fetch_assoc($clientresult)) {
                                                                $clientId = (string)$row['client_id']; // Cast to string for safe comparison
                                                                $selected = in_array($clientId, $assignedClientIds) ? 'selected' : '';
                                                                echo "<option value='{$row['client_id']}' $selected>{$row['name']}</option>";
                                                            }
                                                            ?>
                                                        </select>

                                                    </div>
                                                </div>
                                                <div class="col-xl-3 col-lg-3 col-md-4 col-sm-12 col-12">
                                                    <div class="form-group">
                                                        <label for="">Work Assigned</label>
                                                        <select id="multiSelect2" name="work_assigned[]" multiple="multiple" class="form-control">
                                                            <?php
                                                            $options = [
                                                                "Design",
                                                                "Posting",
                                                                "Meta Ads",
                                                                "Google Ads",
                                                                "Website Development",
                                                                "Maintenance",
                                                                "Server Access"
                                                            ];
                                                            foreach ($options as $option) {
                                                                $selected = in_array($option, $assignedWork) ? 'selected' : '';
                                                                echo "<option value=\"$option\" $selected>$option</option>";
                                                            }
                                                            ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="mb-3">
                                                        <label for="last_working_date" class="form-label">Last Working Date</label>
                                                        <input type="date" class="form-control" id="last_working_date" name="last_working_day" value="<?php echo $lastday; ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="mb-3">
                                                        <label for="verified_by" class="form-label">Handover Name</label>
                                                        <input type="text" class="form-control" id="verified_by" name="verified_by" value="<?php echo $verified; ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="mb-3">
                                                        <label for="verification_by" class="form-label">Handover Date</label>
                                                        <input type="date" class="form-control" id="verification_by" name="verification_date" value="<?php echo $verification; ?>">
                                                    </div>
                                                </div>
                                                <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">

                                                    <button type="submit" class="btn custom-btn" name="company_submit" id="submit">
                                                        <i class="feather icon-save lg"></i>&nbsp; Submit
                                                    </button>


                                                </div>
                                            </div>


                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="dt-responsive table-responsive">


                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </section>





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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
    <script>
        let cropper;
        const empImageInput = document.getElementById('emp_photo');
        const cropModal = new bootstrap.Modal(document.getElementById('cropModal'));
        const cropImage = document.getElementById('cropImage');
        const cropBtn = document.getElementById('cropBtn');
        const croppedInput = document.getElementById('cropped_emp_image');
        console.log("hello");
        
        empImageInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = () => {
                    cropImage.src = reader.result;
                    cropModal.show();

                    // Destroy old instance if any
                    if (cropper) {
                        cropper.destroy();
                    }

                    // Initialize Cropper.js
                    cropper = new Cropper(cropImage, {
                        aspectRatio: 1, // Square crop, change to NaN for free crop
                        viewMode: 1,
                        responsive: true
                    });
                };
                reader.readAsDataURL(file);
            }
        });

        cropBtn.addEventListener('click', () => {
            if (cropper) {
                const canvas = cropper.getCroppedCanvas({
                    width: 300,
                    height: 300,
                });

                // Convert to base64
                const croppedImage = canvas.toDataURL('image/png');
                croppedInput.value = croppedImage; // Store in hidden input

                cropModal.hide();
            }
        });
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#multiSelect').select2({
                width: '100%',
                closeOnSelect: false,
                templateSelection: function(data, container) {
                    // Add a cross icon to selected items    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet">

                    var $option = $(data.element);
                    var text = $option.text();
                    container.text(text);
                    container.append('<span class="remove-item" data-value="' + data.id + '">&times;</span>');
                }
            });

            // Handle removal of selected items
            $('#multiSelect').on('click', '.remove-item', function() {
                var valueToRemove = $(this).data('value');
                var $select = $('#multiSelect');
                $select.find('option[value="' + valueToRemove + '"]').prop('selected', false);
                $select.trigger('change.select2');
            });
        });
    </script>
    <script>
        $(document).ready(function() {
            $('#multiSelect2').select2({
                width: '100%',
                closeOnSelect: false,
                templateSelection: function(data, container) {
                    // Add a cross icon to selected items    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet">

                    var $option = $(data.element);
                    var text = $option.text();
                    container.text(text);
                    container.append('<span class="remove-item" data-value="' + data.id + '">&times;</span>');
                }
            });

            // Handle removal of selected items
            $('#multiSelect2').on('click', '.remove-item', function() {
                var valueToRemove = $(this).data('value');
                var $select = $('#multiSelect2');
                $select.find('option[value="' + valueToRemove + '"]').prop('selected', false);
                $select.trigger('change.select2');
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            $("#goldmessage").delay(5000).slideUp(300);
        });
    </script>
    <script>
        $(document).ready(function() {
            var stateId = "<?php echo $state; ?>";
            var selectedCityId = "<?php echo $city; ?>";

            if (stateId) {
                $.ajax({
                    type: 'POST',
                    url: '',
                    data: {
                        state_id: stateId
                    },
                    success: function(response) {
                        $('#city').html(response); // fill city dropdown
                        $('#city').val(selectedCityId); // select the correct city
                    }
                });
            }
        });
    </script>
    <script>
        function toggleDeviceDetails() {
            const select = document.getElementById('laptop_mobile');
            const detailsBox = document.getElementById('deviceDetailsBox');

            if (select.value === "1") {
                detailsBox.style.display = "block";
            } else {
                detailsBox.style.display = "none";
            }
        }
    </script>

</body>

</html>