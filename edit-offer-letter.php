<?php
session_start();
if (isset($_SESSION['msg'])) {
    echo $_SESSION['msg'];
    unset($_SESSION['msg']);
}
error_reporting(E_ALL);
if (!isset($_SESSION["login_user"])) {
    header("location: index.php");
    exit;
}

$name = $_SESSION['login_user'];
include("db/config.php");

// Fetch existing offer letter details for update
if (isset($_GET['id'])) {
    $encodedOfferId = $_GET['id'];
    $offerId = base64_decode($encodedOfferId);

    if (!is_numeric($offerId)) {
        echo "Invalid Offer ID!";
        exit;
    }

    $query = "SELECT 
        offer_letter.*,
        position.name AS position_name
        FROM offer_letter
        LEFT JOIN position ON offer_letter.position = position.position_id
        WHERE offer_letter.offer_id = ?";

    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "i", $offerId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        $existingNo = $row['reference_no'];
        $existingName = $row['candidate_name'];
        $existingDesignation = $row['position'];
        $existingSalary = $row['salary'];
        $existingDoj = $row['doj'];
        $existingOfferDate = $row['offer_date'];
        $existingEmployee = $row['employee_type'];
        $existingManager = $row['reporting_manager'];
        $existingDuration = $row['duration'] ?? 'Six Months';
        $existingSign = $row['sign'] ?? 'sign/default-sign.png';
    } else {
        echo "Letter not found!";
        exit;
    }
}

// Handle Update
if (isset($_POST['submit'])) {
    $reference = $_POST['reference_no'];
    $candidate_name = $_POST['candidate_name'];
    $position_id = $_POST['position'];
    $doj = $_POST['doj'];
    $offerdate = $_POST['offer_date'];
    $salary = $_POST['salary'];
    $employee = $_POST['employee_type'];
    $manager_id = $_POST['reporting_manager'];
    $duration = $_POST['duration'] ?? 'Six Months';

    // Handle Sign Image Upload (only if new file uploaded)
    $emp_img = $_FILES['sign']['name'];
    if (!empty($emp_img)) {
        $emp_tmp = $_FILES['sign']['tmp_name'];
        $emp_img_path = "sign/" . time() . "_" . basename($emp_img);
        move_uploaded_file($emp_tmp, $emp_img_path);
    } else {
        // Keep existing sign
        $emp_img_path = $existingSign;
    }

    // ✅ UPDATED UPDATE QUERY (letter_type aur content hata diya hai)
    $updateStmt = mysqli_prepare($db, "UPDATE offer_letter SET 
        reference_no = ?, 
        candidate_name = ?, 
        position = ?, 
        doj = ?, 
        salary = ?, 
        employee_type = ?, 
        reporting_manager = ?, 
        sign = ?, 
        offer_date = ?, 
        duration = ?
        WHERE offer_id = ?");

    mysqli_stmt_bind_param($updateStmt, "ssssssssssi", 
        $reference, $candidate_name, $position_id, $doj, $salary, 
        $employee, $manager_id, $emp_img_path, 
        $offerdate, $duration, $offerId);

    if (mysqli_stmt_execute($updateStmt)) {
        $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible fade show' role='alert' style='font-size:16px;' id='goldmessage'>
        <strong><i class='feather icon-check'></i> Success!</strong> Offer Letter updated successfully.
        <button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
        </div>";
    } else {
        $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible fade show' role='alert' style='font-size:16px;' id='goldmessage'>
        <strong><i class='feather icon-x'></i> Error!</strong> Failed to Update Offer Letter.
        <button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
        </div>";
    }
    mysqli_stmt_close($updateStmt);

    header("Location: manage-letters.php");
    exit();
}

// Fetch dropdown data
$positionquery = "SELECT position_id, name FROM position WHERE status = 1";
$positionresult = mysqli_query($db, $positionquery);

$personalquery = "SELECT pd.personal_id, pd.name, cd.company_id 
                  FROM personal_details pd 
                  INNER JOIN company_details cd ON pd.personal_id = cd.user_id 
                  WHERE cd.employee_status = 1";

$personalresult = mysqli_query($db, $personalquery);

$query = "SELECT * FROM login_settings";
$settingsResult = mysqli_query($db, $query);
$settings = mysqli_fetch_assoc($settingsResult);
$favicon = $settings['favicon'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Update Offer Letter</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="<?php echo $favicon; ?>" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet">
    <style>.red-text { color: red; }</style>
</head>
<body>
    <div class="loader-bg"><div class="loader-track"><div class="loader-fill"></div></div></div>
    <?php include("header.php"); ?>
    <?php include("navbar.php"); ?>

    <section class="pcoded-main-container">
        <div class="pcoded-content">
            <div class="page-header">
                <div class="page-block">
                    <div class="row align-items-center">
                        <div class="col-md-12">
                            <div class="page-header-title"><h5 class="m-b-10">Update Offer Letter</h5></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header table-card-header">
                            <form class="contact-us" method="post" action="" enctype="multipart/form-data" autocomplete="off">
                                <div class="row">
                                    <!-- Reference No -->
                                    <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
                                        <div class="form-group">
                                            <label>Reference No</label>
                                            <input name="reference_no" type="text" placeholder="Enter Reference No" class="form-control input-md" required value="<?php echo htmlspecialchars($existingNo); ?>">
                                        </div>
                                    </div>

                                    <!-- Duration -->
                                    <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
                                        <div class="form-group">
                                            <label>Duration</label>
                                            <input name="duration" type="text" placeholder="E.g. Six Months" class="form-control input-md" required value="<?php echo htmlspecialchars($existingDuration); ?>">
                                        </div>
                                    </div>

                                    <!-- Candidate Name -->
                                    <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
                                        <div class="form-group">
                                            <label>Candidate Name</label>
                                            <input name="candidate_name" type="text" placeholder="Enter Candidate Name" class="form-control input-md" required value="<?php echo htmlspecialchars($existingName); ?>">
                                        </div>
                                    </div>

                                    <!-- Designation -->
                                    <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
                                        <div class="form-group">
                                            <label>Select Designation <span class="red-text">*</span></label>
                                            <select class="form-control" name="position" required>
                                                <option value="" disabled>Choose a Designation</option>
                                                <?php 
                                                // Reset pointer
                                                mysqli_data_seek($positionresult, 0);
                                                while ($row = mysqli_fetch_assoc($positionresult)) { 
                                                    $selected = ($row['position_id'] == $existingDesignation) ? 'selected' : '';
                                                ?>
                                                    <option value="<?php echo $row['position_id']; ?>" <?php echo $selected; ?>><?php echo $row['name']; ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Joining Date -->
                                    <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
                                        <div class="form-group">
                                            <label>Joining Date</label>
                                            <input name="doj" type="date" class="form-control input-md" required value="<?php echo $existingDoj; ?>">
                                        </div>
                                    </div>

                                    <!-- Letter Generation Date -->
                                    <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
                                        <div class="form-group">
                                            <label>Letter Generation Date</label>
                                            <input name="offer_date" type="date" class="form-control input-md" required value="<?php echo $existingOfferDate; ?>">
                                        </div>
                                    </div>

                                    <!-- Salary -->
                                    <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
                                        <div class="form-group">
                                            <label>Salary</label>
                                            <input name="salary" type="text" placeholder="Enter the Salary" class="form-control input-md" 
                                                oninput="this.value = this.value.replace(/[^0-9,]/g, '')"
                                                value="<?php echo htmlspecialchars($existingSalary); ?>">
                                        </div>
                                    </div>

                                    <!-- Employment Type -->
                                    <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
                                        <div class="form-group">
                                            <label>Employment Type</label>
                                            <select name="employee_type" class="form-control" required>
                                                <option value="On Site" <?php echo ($existingEmployee == 'On Site') ? 'selected' : ''; ?>>On Site</option>
                                                <option value="Work From Home" <?php echo ($existingEmployee == 'Work From Home') ? 'selected' : ''; ?>>Work From Home</option>
                                                <option value="Remote" <?php echo ($existingEmployee == 'Remote') ? 'selected' : ''; ?>>Remote</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Reporting Manager -->
                                    <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
                                        <div class="form-group">
                                            <label>Reporting Manager</label>
                                            <select name="reporting_manager" class="form-control" required>
                                                <option value="" disabled>Choose Manager</option>
                                                <?php 
                                                // Reset pointer
                                                mysqli_data_seek($personalresult, 0);
                                                while ($row = mysqli_fetch_assoc($personalresult)) { 
                                                    $selected = ($row['personal_id'] == $existingManager) ? 'selected' : '';
                                                ?>
                                                    <option value="<?php echo $row['personal_id']; ?>" <?php echo $selected; ?>><?php echo $row['name']; ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Signature Upload -->
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Upload Signature <span class="text-danger">*</span></label>
                                        <input type="file" name="sign" class="form-control" id="signInput">
                                        <small class="text-muted">Leave empty to keep existing signature</small>
                                        <img id="signPreview" 
                                             src="<?php echo htmlspecialchars($existingSign); ?>" 
                                             alt="Signature Preview" 
                                             style="width:250px; margin-top:10px; border:1px solid #ddd; padding:5px;">
                                    </div>

                                    <!-- Submit Button -->
                                    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
                                        <button type="submit" class="btn btn-secondary" name="submit">
                                            <i class="feather icon-save"></i>&nbsp; Update Details
                                        </button>
                                        <a href="manage-letters.php" class="btn btn-danger">
                                            <i class="feather icon-x"></i>&nbsp; Cancel
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="assets/js/vendor-all.min.js"></script>
    <script src="assets/js/plugins/bootstrap.min.js"></script>
    <script src="assets/js/pcoded.min.js"></script>
    <script>
        $(document).ready(function() {
            $("#goldmessage").delay(5000).slideUp(300);
            
            // Image preview logic
            $('#signInput').on('change', function() {
                var file = this.files[0];
                if (file) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        $('#signPreview').attr('src', e.target.result);
                    }
                    reader.readAsDataURL(file);
                }
            });
        });
    </script>
</body>
</html>