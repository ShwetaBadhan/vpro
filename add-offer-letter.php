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

$logged_in_user = $_SESSION['login_user'];
include("db/config.php");

if (isset($_POST['submit'])) {
    $reference = $_POST['reference_no'];
    $candidate_name = $_POST['candidate_name'];
    $position_id = $_POST['position'];
    $doj = $_POST['doj'];
    $salary = $_POST['salary'];
    $employee = $_POST['employee_type'];
    $manager_id = $_POST['reporting_manager'];
    $offerdate = $_POST['offer_date'];
    $duration = $_POST['duration'];
    
    // 1. Handle Sign Image Upload
    $emp_img = $_FILES['sign']['name'];
    if (!empty($emp_img)) {
        $emp_tmp = $_FILES['sign']['tmp_name'];
        $emp_img_path = "sign/" . time() . "_" . basename($emp_img);
        move_uploaded_file($emp_tmp, $emp_img_path);
    } else {
        $emp_img_path = "sign/default-sign.png";
    }

    // ✅ UPDATED INSERT QUERY (letter_type aur content hata diya hai)
    $stmt = mysqli_prepare($db, "INSERT INTO offer_letter (reference_no, candidate_name, position, doj, salary, employee_type, reporting_manager, sign, offer_date, duration) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    // Bind parameters (10 parameters)
    mysqli_stmt_bind_param($stmt, "ssssssssss", $reference, $candidate_name, $position_id, $doj, $salary, $employee, $manager_id, $emp_img_path, $offerdate, $duration);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible fade show' role='alert' style='font-size:16px;' id='goldmessage'>
        <strong><i class='feather icon-check'></i> Thanks!</strong> Offer Letter added successfully.
        <button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
        </div>";
    } else {
        $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible fade show' role='alert' style='font-size:16px;' id='goldmessage'>
        <strong><i class='feather icon-check'></i> Error!</strong> Failed to Add Offer Letter.
        <button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
        </div>";
    }
    mysqli_stmt_close($stmt);

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
    <title>Add Offer Letter</title>
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
                            <div class="page-header-title"><h5 class="m-b-10">Add Offer Letter</h5></div>
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
                                    <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
                                        <div class="form-group">
                                            <label>Reference No</label>
                                            <input name="reference_no" type="text" placeholder="Enter Reference No" class="form-control input-md" required>
                                        </div>
                                    </div>
                                    <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
                                        <div class="form-group">
                                            <label>Duration</label>
                                            <input name="duration" type="text" placeholder="E.g. Six Months" class="form-control input-md">
                                        </div>
                                    </div>

                                    <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
                                        <div class="form-group">
                                            <label>Candidate Name</label>
                                            <input name="candidate_name" type="text" placeholder="Enter Candidate Name" class="form-control input-md" required>
                                        </div>
                                    </div>

                                    <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
                                        <div class="form-group">
                                            <label>Select Designation <span class="red-text">*</span></label>
                                            <select class="form-control" name="position" required>
                                                <option value="" selected disabled>Choose a Designation</option>
                                                <?php while ($row = mysqli_fetch_assoc($positionresult)) { ?>
                                                    <option value="<?php echo $row['position_id']; ?>"><?php echo $row['name']; ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
                                        <div class="form-group">
                                            <label>Joining Date</label>
                                            <input name="doj" type="date" class="form-control input-md" required>
                                        </div>
                                    </div>

                                    <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
                                        <div class="form-group">
                                            <label>Letter Generation Date</label>
                                            <input name="offer_date" type="date" class="form-control input-md" required>
                                        </div>
                                    </div>

                                    <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
                                        <div class="form-group">
                                            <label>Salary</label>
                                            <input name="salary" type="text" placeholder="Enter the Salary" class="form-control input-md">
                                        </div>
                                    </div>

                                    <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
                                        <div class="form-group">
                                            <label>Employment Type</label>
                                            <select name="employee_type" class="form-control" required>
                                                <option value="" disabled selected>Choose Type</option>
                                                <option value="On Site">On Site</option>
                                                <option value="Work From Home">Work From Home</option>
                                                <option value="Remote">Remote</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
                                        <div class="form-group">
                                            <label>Reporting Manager</label>
                                            <select name="reporting_manager" class="form-control" required>
                                                <option value="" disabled selected>Choose Manager</option>
                                                <?php while ($row = mysqli_fetch_assoc($personalresult)) { ?>
                                                    <option value="<?php echo $row['personal_id']; ?>"><?php echo $row['name']; ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Upload Signature <span class="text-danger">*</span></label>
                                        <input type="file" name="sign" class="form-control" id="signInput">
                                        <img id="signPreview" src="sign/default-sign.png" alt="Signature Preview" style="width:250px; margin-top:10px; border:1px solid #ddd; padding:5px;">
                                    </div>

                                    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
                                        <button type="submit" class="btn btn-secondary" name="submit">
                                            <i class="feather icon-save"></i>&nbsp; Save Candidate Details
                                        </button>
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