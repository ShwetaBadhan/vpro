<?php
session_start();
// error_reporting(0);
$msg = "";
if (!isset($_SESSION["login_user"])) {
    header("location: index.php");
}
include('db/config.php');
if (isset($_SESSION['success_msg'])) {
    $msg = "
    <div class='alert alert-success alert-dismissible fade show' role='alert' style='font-size:16px;' id='goldmessage'>
        <strong><i class='feather icon-check'></i> Thanks!</strong> " . $_SESSION['success_msg'] . "
        <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
            <span aria-hidden='true'>&times;</span>
        </button>
    </div>";
    unset($_SESSION['success_msg']);
}

// Personal Details
if (isset($_POST['submit'])) {

    // === EMPLOYEE IMAGE UPLOAD ===
    $emp_img_path = "";

    if (!empty($_POST['cropped_emp_image'])) {
        // Save cropped image (base64)
        $croppedData = $_POST['cropped_emp_image'];
        $croppedData = str_replace('data:image/png;base64,', '', $croppedData);
        $croppedData = str_replace(' ', '+', $croppedData);
        $imageData   = base64_decode($croppedData);

        $emp_img_path = "employeeimage/" . time() . "_cropped.png";
        file_put_contents($emp_img_path, $imageData);
    } elseif (!empty($_FILES['photo']['name'])) {
        // Handle normal file upload (fallback)
        $emp_img = $_FILES['photo']['name'];
        $emp_tmp = $_FILES['photo']['tmp_name'];
        $emp_img_path = "employeeimage/" . time() . "_" . basename($emp_img);
        move_uploaded_file($emp_tmp, $emp_img_path);
    }

    // Required fields
    $name = $_POST['name'];
    $father_name = $_POST['father_name'];
    $phone = $_POST['mobile'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $state = $_POST['state'];
    $city = $_POST['city'];
    $emergency_no = $_POST['emergency_no'];
    $blood = $_POST['blood_group'];
    $dob = $_POST['dob'];
    $adhar = $_POST['adhar_no'];
    $pan = $_POST['pan_no'] ?? null;

    // echo '<pre>';
    // print_r($_POST);
    // exit();
    // Escape values (if you're not using prepared statements)
    $name = mysqli_real_escape_string($db, $name);
    $father_name = mysqli_real_escape_string($db, string: $father_name);
    $phone = mysqli_real_escape_string($db, $phone);
    $email = mysqli_real_escape_string($db, $email);
    $address = mysqli_real_escape_string($db, $address);
    $state = mysqli_real_escape_string($db, $state);
    $city = mysqli_real_escape_string($db, $city);
    $emergency_no = mysqli_real_escape_string($db, $emergency_no);
    $blood = mysqli_real_escape_string($db, $blood);
    $dob = mysqli_real_escape_string($db, $dob);
    $adhar = mysqli_real_escape_string($db, $adhar);
    $pan = mysqli_real_escape_string($db, $pan);

    // SQL Insert Query
    $query = "INSERT INTO personal_details (name, father_name, dob, mobile, blood_group, state, city, address, email, adhar_no, pan_no, photo, emergency_no) 
          VALUES ('$name', '$father_name', '$dob' , '$phone', '$blood', '$state' , '$city' , '$address', '$email' , '$adhar','$pan', '$emp_img_path', '$emergency_no')";


    if (mysqli_query($db, $query)) {
        // ✅ Get last inserted ID
        $last_insert_id = mysqli_insert_id($db);

        // Store it in session to use in other forms
        $_SESSION['user_id'] = $last_insert_id;
        $_SESSION['success_msg'] = "Personal Details added successfully.";
        echo 'User ID in Session: ' . ($_SESSION['user_id'] ?? 'Not set');
        header("Location: add-employee.php");
        exit();
    } else {
        $msg = "
        <div class='alert alert-danger alert-dismissible fade show' role='alert' style='font-size:16px;' id='goldmessage'>
            <strong><i class='feather icon-alert-circle'></i> Error!</strong> Failed to add employee. 
            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                <span aria-hidden='true'>&times;</span>
            </button>
        </div>";
    }
}


// Bank Details

if (isset($_POST['bank_submit'])) {
    // Required fields
    $ifsc = $_POST['ifsc_code'];
    $account = $_POST['account_no'];
    $bankname = $_POST['bank_name'];
    $user_id = $_SESSION['user_id'];
    // echo 'User ID in Session: ' . ($_SESSION['user_id'] ?? 'Not set');

    //     echo '<pre>';
    //     print_r($_POST);
    //     exit();
    // Escape values (if you're not using prepared statements)
    $ifsc = mysqli_real_escape_string($db, $ifsc);
    $account = mysqli_real_escape_string($db, string: $account);


    // SQL Insert Query
    $query = "INSERT INTO bank_details (ifsc_code, account_no,bank_name, user_id) 
          VALUES ('$ifsc' , '$account','$bankname' ,'$user_id')";


    if (mysqli_query($db, $query)) {
        $_SESSION['success_msg'] = "Bank Details added successfully.";

        // Redirect to the same page to prevent resubmission
        header("Location: add-employee.php");
        exit();
    } else {
        $msg = "
        <div class='alert alert-danger alert-dismissible fade show' role='alert' style='font-size:16px;' id='goldmessage'>
            <strong><i class='feather icon-alert-circle'></i> Error!</strong> Failed to add employee. 
            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                <span aria-hidden='true'>&times;</span>
            </button>
        </div>";
    }
}


// company Details

if (isset($_POST['company_submit'])) {
    // Required fields
    $designation = $_POST['designation'];
    $employement_type = $_POST['employement_type'];
    $doj = $_POST['doj'];
    $email = $_POST['email'];
    $idcard = $_POST['id_card'];
    $laptop = $_POST['laptop_mobile'];
    $status = $_POST['employee_status'];
    $user_id = $_SESSION['user_id'];
    $lastday = isset($_POST['last_working_day']) ? $_POST['last_working_day'] : null;
    $verifiedby = isset($_POST['verified_by']) ? $_POST['verified_by'] : null;
    $verificationdate = isset($_POST['verification_date']) ? $_POST['verification_date'] : null;
    $emp_code = $_POST['employee_code'];
    $client_assigned_array = $_POST['assigned_client']; // This will be an array
    $client_assigned = implode(',', $client_assigned_array); // Convert to comma-separated string for DB
    $work_assigned_array = $_POST['work_assigned']; // This will be an array
    $work_assigned = implode(',', $work_assigned_array); // Convert to comma-separated string for DB

    // Optional field
    $device_details = isset($_POST['device_details']) ? $_POST['device_details'] : null;
    //  echo '<pre>';
    //     print_r($_POST);
    //     exit();
    // Escape values
    $designation = mysqli_real_escape_string($db, $designation);

    $doj = mysqli_real_escape_string($db, $doj);
    $email = mysqli_real_escape_string($db, $email);
    $idcard = mysqli_real_escape_string($db, $idcard);
    $laptop = mysqli_real_escape_string($db, $laptop);
    $status = mysqli_real_escape_string($db, $status);
    $device_details = mysqli_real_escape_string($db, $device_details);
    $lastday = mysqli_real_escape_string($db, $lastday);
    $verifiedby = mysqli_real_escape_string($db, $verifiedby);
    $verificationdate = mysqli_real_escape_string($db, $verificationdate);


    if ($laptop == '1') {
        $deviceValue = "'$device_details'";
    } else {
        $deviceValue = "NULL";
    }

    $query = "INSERT INTO company_details (designation, doj, email, laptop_mobile, employee_status, id_card, user_id, last_working_day, verified_by, verification_date,employee_code,assigned_client,work_assigned, device_details, employement_type) 
VALUES ('$designation', '$doj', '$email', '$laptop', '$status', '$idcard', '$user_id', '$lastday', '$verifiedby', '$verificationdate','$emp_code','$client_assigned','$work_assigned', '$deviceValue', '$employement_type')";


    if (mysqli_query($db, $query)) {
        $_SESSION['success_msg'] = "Company Details added successfully.";
        header("Location: add-employee.php");
        exit();
    } else {
        $msg = "
        <div class='alert alert-danger alert-dismissible fade show' role='alert' style='font-size:16px;' id='goldmessage'>
            <strong><i class='feather icon-alert-circle'></i> Error!</strong> Failed to add employee. 
            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                <span aria-hidden='true'>&times;</span>
            </button>
        </div>";
    }
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
$positionquery = "SELECT position_id, name FROM position WHERE status = 1";
$positionresult = mysqli_query($db, $positionquery);

// Fetch state from the database
$clientquery = "SELECT client_id, name FROM clients WHERE status = 1 AND is_deleted = 0";
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
    <title>Add Employee</title>



    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="" />
    <meta name="keywords" content="">
    <meta name="author" content="#" />

    <link rel="icon" href="<?php echo $favicon; ?>" type="image/x-icon">

    <link rel="stylesheet" href="assets/css/plugins/dataTables.bootstrap4.min.css">

    <link rel="stylesheet" href="assets/css/style.css">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet">
    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"> -->
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
                                <h5 class="m-b-10">Add Employee
                                </h5>
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

                            echo "<span id='user-availability-status'></span>";
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
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="Position_name" class="form-label">Employee Image <span class="red-text">*</span></label>
                                                        <input type="file" name="photo" id="emp_photo" class="form-control" accept="image/*" required>
                                                    </div>
                                                </div>
                                                <!-- Crop Modal -->
                                                <div class="modal fade" id="cropModal" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog modal-fullscreen"> <!-- 👈 Fullscreen modal -->
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Crop Employee Image</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">X</button>
                                                            </div>
                                                            <div class="modal-body d-flex justify-content-center align-items-center bg-dark">
                                                                <!-- Image preview (fullscreen center) -->
                                                                <img id="cropImage" style="max-width: 90%; max-height: 85vh; display:block; margin:auto;" />
                                                            </div>
                                                            <div class="modal-footer justify-content-center">
                                                                <button type="button" id="cropBtn" class="btn btn-success px-4">✅ Crop & Save</button>
                                                                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">❌ Cancel</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>



                                                <!-- Hidden input to store cropped image (Base64 or blob) -->
                                                <input type="hidden" name="cropped_emp_image" id="cropped_emp_image">

                                                <div class="col-md-3">
                                                    <div class="mb-3">
                                                        <label for="name" class="form-label">Full Name <span class="red-text">*</span></label>
                                                        <input type="text" class="form-control" id="name" name="name" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="mb-3">
                                                        <label for="father_name" class="form-label">Father's Name <span class="red-text">*</span></label>
                                                        <input type="text" class="form-control" id="father_name" name="father_name" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="mb-3">
                                                        <label for="mobile" class="form-label">Mobile No. <span class="red-text">*</span></label>
                                                        <input type="number" class="form-control" id="mobile" name="mobile" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="mb-3">
                                                        <label for="mobile" class="form-label">Emergency Mobile No. <span class="red-text">*</span></label>
                                                        <input type="number" class="form-control" id="emergency_no" name="emergency_no" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="mb-3">
                                                        <label for="email" class="form-label">Email <span class="red-text">*</span></label>
                                                        <input type="email" class="form-control" id="email" name="email" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="mb-3">
                                                        <label for="adhar_no" class="form-label">Aadhaar No. <span class="red-text">*</span></label>
                                                        <input type="text" class="form-control" id="adhar_no" name="adhar_no" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="mb-3">
                                                        <label for="pan_no" class="form-label">PAN No.</label>
                                                        <input type="text" class="form-control" id="pan_no" name="pan_no">
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="mb-3">
                                                        <label for="blood_group" class="form-label">Blood Group <span class="red-text">*</span></label>
                                                        <input type="text" class="form-control" id="blood_group" name="blood_group" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="mb-3">
                                                        <label for="dob" class="form-label">Date of Birth <span class="red-text">*</span></label>
                                                        <input type="date" class="form-control" id="dob" name="dob" required>
                                                    </div>
                                                </div>
                                                <div class="col-xl-3 col-lg-6 col-md-3 col-sm-12 col-12">
                                                    <label for="state" class="form-label">State <span class="red-text">*</span></label>
                                                    <select name="state" class="form-control select" id="state">
                                                        <option value="" selected disabled>Choose</option>
                                                        <?php
                                                        $state_query = "SELECT * FROM state WHERE status='1'";
                                                        $result = $db->query($state_query);
                                                        if ($result->num_rows > 0) {
                                                            while ($row = $result->fetch_assoc()) {
                                                                echo "<option value='{$row['state_id']}'>{$row['state_name']}</option>";
                                                            }
                                                        }
                                                        ?>
                                                    </select>
                                                </div>

                                                <div class="col-xl-3 col-lg-6 col-md-3 col-sm-12 col-12 mb-3">
                                                    <label for="city" class="form-label">City <span class="red-text">*</span></label>
                                                    <select name="city" class="form-control select" id="city">
                                                        <option value="" selected disabled>Select State First</option>
                                                    </select>
                                                </div>
                                                <!-- <div class="col-xl-3 col-lg-6 col-md-4 col-sm-12 col-12 ">

                                                    <label>Status</label>
                                                    <select id="" name="status" class="form-control"
                                                        oninvalid="this.setCustomValidity('Please Select Status')"
                                                        oninput="setCustomValidity('')" required>
                                                        <option value="" selected disabled>Choose</option>
                                                        <option value="1">Enable</option>
                                                        <option value="0">Disabe</option>

                                                    </select>
                                                </div> -->

                                                <div class="col-xl-12 col-lg-6 col-md-12 col-sm-12 col-12 mb-3">
                                                    <label for="address" class="form-label">Permanent Address <span class="red-text">*</span></label>
                                                    <textarea class="form-control" name="address" id="address" rows="3" placeholder="Enter your permanent address" required></textarea>
                                                </div>

                                                <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">

                                                    <button type="submit" class="btn custom-btn" name="submit" id="submit">
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
                                                        <label for="bankname" class="form-label">Bank Name <span class="red-text">*</span></label>
                                                        <input type="text" class="form-control" id="bankname" name="bank_name" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="accountNumber" class="form-label">Account Number <span class="red-text">*</span></label>
                                                        <input type="text" class="form-control" id="accountNumber" name="account_no" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="ifsc" class="form-label">IFSC Code <span class="red-text">*</span></label>
                                                        <input type="text" class="form-control" id="ifsc" name="ifsc_code" required>
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

                                            <div class="row">
                                                <div class="col-md-3">
                                                    <div class="mb-3">
                                                        <label for="designation" class="form-label">Employee Code <span class="red-text">*</span></label>
                                                        <input type="text" class="form-control" id="employee_code" name="employee_code" required>
                                                    </div>
                                                </div>
                                                <!-- Dynamic Select for Course Types -->
                                                <div class="col-xl-3 col-lg-3 col-md-12 col-sm-12 col-12">
                                                    <div class="form-group">
                                                        <label for="designation" class="form-label">Select Designation<span class="red-text">*</span></label>
                                                        <select class="form-control" id="designation" name="designation" required>
                                                            <option value="" selected disabled>Choose a Designation</option>
                                                            <?php
                                                            if (mysqli_num_rows($positionresult) > 0) {
                                                                while ($row = mysqli_fetch_assoc($positionresult)) {
                                                                    echo "<option value='" . $row['position_id'] . "'>" . $row['name'] . "</option>";
                                                                }
                                                            } else {
                                                                echo "<option value='' disabled>No city types found</option>";
                                                            }
                                                            ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="mb-3">
                                                        <label for="doj" class="form-label">Joining Date <span class="red-text">*</span></label>
                                                        <input type="date" class="form-control" id="doj" name="doj" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="mb-3">
                                                        <label for="email" class="form-label">Official Mail <span class="red-text">*</span></label>
                                                        <input type="email" class="form-control" id="email" name="email" required>
                                                    </div>

                                                </div>
                                                <div class="col-md-3">
                                                    <div class="mb-3">
                                                        <label for="email" class="form-label">ID Card <span class="red-text">*</span></label>
                                                        <select id="" name="id_card" class="form-control"
                                                            oninvalid="this.setCustomValidity('Please Select Status')"
                                                            oninput="setCustomValidity('')" required>
                                                            <option value="" selected disabled>Choose</option>
                                                            <option value="1">Yes</option>
                                                            <option value="0">No</option>

                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="mb-3">
                                                        <label for="laptop_mobile" class="form-label">Laptop/Mobile <span class="red-text">*</span></label>
                                                        <select id="laptop_mobile" name="laptop_mobile" class="form-control"
                                                            oninvalid="this.setCustomValidity('Please Select Status')"
                                                            oninput="setCustomValidity('')" required onchange="toggleDeviceDetails()">
                                                            <option value="" selected disabled>Choose</option>
                                                            <option value="1">Yes</option>
                                                            <option value="0">No</option>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="col-md-6" id="deviceDetailsBox" style="display: none;">
                                                    <div class="mb-3">
                                                        <label for="device_details" class="form-label">Device Details</label>
                                                        <textarea id="device_details" name="device_details" class="form-control" rows="3"
                                                            placeholder="Enter device details here..."></textarea>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="mb-3">
                                                        <label for="email" class="form-label">Employement Type <span class="red-text">*</span></label>
                                                        <select id="" name="employement_type" class="form-control"
                                                            oninvalid="this.setCustomValidity('Please Select Status')"
                                                            oninput="setCustomValidity('')" required>
                                                            <option value="" selected disabled>Choose</option>
                                                            <option value="Regular">Regular</option>
                                                            <option value="Contract">Contract</option>
                                                            <option value="WFM">Work From Home</option>
                                                            <option value="Intern">Intern</option>
                                                            <option value="Trainee">Trainee</option>

                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="mb-3">
                                                        <label for="email" class="form-label">Employee Status <span class="red-text">*</span></label>
                                                        <select id="" name="employee_status" class="form-control"
                                                            oninvalid="this.setCustomValidity('Please Select Status')"
                                                            oninput="setCustomValidity('')" required>
                                                            <option value="" selected disabled>Choose</option>
                                                            <option value="1">Active</option>
                                                            <option value="0">Inactive</option>

                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-xl-3 col-lg-3 col-md-4 col-sm-12 col-12">
                                                    <div class="form-group">
                                                        <label for="">Assigned Client</label>
                                                        <select id="multiSelect" name="assigned_client[]" multiple="multiple" class="form-control">
                                                            <?php
                                                            while ($row = mysqli_fetch_assoc($clientresult)) {
                                                                // Check if the permission is assigned to the role

                                                                echo "<option value='{$row['client_id']}'>{$row['name']}</option>";
                                                            }
                                                            ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-xl-3 col-lg-3 col-md-4 col-sm-12 col-12">
                                                    <div class="form-group">
                                                        <label for="">Work Assigned</label>
                                                        <select id="multiSelect2" name="work_assigned[]" multiple="multiple" class="form-control">

                                                            <option value="Design">Design</option>
                                                            <option value="Posting">Posting</option>
                                                            <option value="Meta Ads">Meta Ads</option>
                                                            <option value="Google Ads">Google Ads</option>
                                                            <option value="Website Development">Website Development</option>
                                                            <option value="Maintenance">Maintenance</option>
                                                            <option value="Server Access">Server Access</option>

                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="mb-3">
                                                        <label for="lwd" class="form-label">Last Working Date</label>
                                                        <input type="date" class="form-control" id="lwd" name="last_working_day">
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="mb-3">
                                                        <label for="verfied by" class="form-label">Handover Name</label>
                                                        <input type="text" class="form-control" id="lwd" name="verified_by">
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="mb-3">
                                                        <label for="lwd" class="form-label">Handover Date</label>
                                                        <input type="date" class="form-control" id="lwd" name="verification_date">
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


    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
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
    <script>
        $('#state').on('change', function() {
            var stateId = $(this).val();

            $.ajax({
                url: '', // send to same page
                type: 'POST',
                data: {
                    state_id: stateId
                },
                success: function(data) {
                    $('#city').html(data);
                },
                error: function() {
                    alert('Something went wrong!');
                }
            });
        });
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var userId = "<?php echo $_SESSION['user_id'] ?? ''; ?>";
            if (!userId) {
                document.querySelector("a[href='#bank']").classList.add("disabled");
                document.querySelector("a[href='#company']").classList.add("disabled");
                alert("Please fill personal details first.");
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