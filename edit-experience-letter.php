<?php
session_start();

include("db/config.php");
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION["login_user"])) {
    header("location: index.php");
    exit;
}

// Initialize default values
$existingName = "";
$existingDesignation = "";

$exisitingMonth = "";
$existingStatus = "";
$existingSign = "";

$expId = null;
$isEditMode = false;

// ================= FETCH IF EDIT ==================
if (isset($_GET['id'])) {
    $encodedexpID = $_GET['id'];
    $expId = base64_decode($encodedexpID);

    if (!is_numeric($expId)) {
        echo "Invalid ID!";
        exit;
    }

    $query = "SELECT * FROM experience_letter WHERE experience_id = $expId";
    $result = mysqli_query($db, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);

        $existingName = $row['employee_id'];
        $existingDesignation = $row['designation'];
        $exisitingDateTo = $row['date_to'];
        $exisitingDateFrom = $row['date_from'];
        $existingStatus = $row['status'];
        $existingReference = $row['reference_no'];
        $existingSign = $row['sign'];
        $isEditMode = true;
     
    } else {
        echo "Experience Letter not found!";
        exit;
    }
}

// ================= FORM SUBMISSION ==================
if (isset($_POST['submit'])) {
    // echo '<pre>';

    // print_r($_POST);
    // exit;

     $emp_name = $_POST['employee_id'];
    $emp_designation = $_POST['designation'];
    $date_from = $_POST['date_from'];
    $date_to = $_POST['date_to'];
    $reference = $_POST['reference_no'];
    $status = $_POST['status'];
    $created_at = date('Y-m-d H:i:s');
   // Image paths default to existing (for edit)
    $empImgPath = $existingSign ?: '';
 // Upload new employee image
    if (!empty($_FILES['sign']['name'])) {
        $empImg = $_FILES['sign']['name'];
        $empTmp = $_FILES['sign']['tmp_name'];
        $empImgPath = "sign/" . time() . "_" . basename($empImg);
        move_uploaded_file($empTmp, $empImgPath);
    }


    // ================= UPDATE ==================
    if ($isEditMode) {
        $updateQuery = "UPDATE experience_letter
                        SET employee_id = '$emp_name', 
                            designation = '$emp_designation', 
                            date_from = '$date_from',
                            date_to = '$date_to',
                            reference_no = '$reference',
                            sign = '$empImgPath',
                            status = '$status'
                        WHERE experience_id = $expId";

        $updateResult = mysqli_query($db, $updateQuery);

        if ($updateResult) {
           header("Location: manage-experience-letters.php?status=" . base64_encode(1));
exit;
        } else {
            echo "Update error: " . mysqli_error($db);
        }

        // ================= INSERT ==================
    } else {
       $stmt = $db->prepare("INSERT INTO experience_letter (employee_id, designation, date_from, date_to, reference_no, status,sign, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisssiss", $emp_name, $emp_designation, $date_from, $date_to, $reference, $status,$empImgPath, $created_at);

        if ($stmt->execute()) {
            header("Location: add-new-greeting.php?success=1");
            exit;
        } else {
            echo "Insert error: " . $stmt->error;
        }
    }
}
// Fetch position from the database
$positionquery = "SELECT position_id, name FROM position WHERE status = 1"; // Assuming your table is `city_type`
$positionresult = mysqli_query($db, $positionquery);
$personalquery = "SELECT personal_id, name FROM personal_details";
$personalresult = mysqli_query($db, $personalquery);
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
    <title><?php echo isset($_GET['id']) ? 'Update' : 'Add'; ?> Experience Letter</title>
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
                                <h5 class="m-b-10"><?php echo isset($_GET['id']) ? 'Update' : 'Add'; ?> Experience Letter
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

                            <br />
                          
                           <?php if (!empty($msg)) echo $msg; ?>
<form method="post" action="" enctype="multipart/form-data" autocomplete="off">
    <div class="row">
        
       <div class="col-md-6 mb-4">
            <label class="form-label">Reference No <span class="text-danger">*</span></label>
            <input type="text" name="reference_no" class="form-control" value="<?php echo $existingReference ?>">
        </div>
<!-- Select Employee -->
        <div class="col-md-6">
            <label class="form-label">Select Employee <span class="text-danger">*</span></label>
            <select name="employee_id" class="form-control">
                <option value="" disabled selected>Choose Employee</option>
                <?php
                if (mysqli_num_rows($personalresult) > 0) {
                    while ($row = mysqli_fetch_assoc($personalresult)) {
                        $personalId = $row['personal_id'];
                        $name = $row['name'];
                        $selected = ($personalId == $existingName) ? "selected" : "";
                        echo "<option value='" . $personalId . "' $selected>" . $name . "</option>";
                    }
                } else {
                    echo "<option disabled>No Employees Found</option>";
                }
                ?>
            </select>
        </div>
               <div class="col-xl-6 col-lg-3 col-md-12 col-sm-12 col-12">
                                        <div class="form-group">
                                            <label for="designation" class="form-label">Select Designation <span class="red-text">*</span></label>
                                            <select class="form-control" id="designation" name="designation">
                                                <option value="" disabled>Choose a position</option>
                                                <?php
                                                if (mysqli_num_rows($positionresult) > 0) {
                                                    while ($row = mysqli_fetch_assoc($positionresult)) {
                                                        $positionId = $row['position_id'];
                                                        $positionName = $row['name'];

                                                        // Compare with ID, not name
                                                        $selected = ($positionId == $emp_designation) ? "selected" : "";

                                                        echo "<option value='" . $positionId . "' $selected>" . $positionName . "</option>";
                                                    }
                                                } else {
                                                    echo "<option value='' disabled>No positions found</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>

                                    </div>

        <!-- Month -->
        <div class="col-md-6">
            <label class="form-label">Date From <span class="text-danger">*</span></label>
            <input type="date" name="date_from" class="form-control" value="<?php echo $exisitingDateFrom ?>">
        </div>

         <!-- Month -->
        <div class="col-md-6">
            <label class="form-label">Date To <span class="text-danger">*</span></label>
            <input type="date" name="date_to" class="form-control" value="<?php echo $exisitingDateTo ?>">
        </div>


        <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
                                        <div class="form-group">
                                            <label for="template_image" class="form-label">Sign <span class="red-text">*</span></label>
                                            <small>(Leave empty to use existing)</small>
                                            <input type="file" name="template_image" id="template_image" class="form-control" accept="image/*" onchange="previewTemplateImage(event)">
                                            <!-- Existing Image Display Section -->
                                            <?php if (!empty($existingSign)) { ?>
                                                <div class="form-group">
                                                    <label class="form-label">Current Sign Image</label>
                                                    <div>
                                                        <img src="<?php echo $existingSign; ?>" class="img-fluid" alt="Employee Image" style="height: 150px; width: 350px;">
                                                    </div>
                                                </div>
                                            <?php } ?>

                                        </div>
                                    </div>

    
                             
        <!-- Status -->
        <div class="col-md-6">
            <label class="form-label">Status <span class="text-danger">*</span></label>
            <select name="status" class="form-control" required>
                <option value="1" selected>Enable</option>
                <option value="0">Disable</option>
            </select>
        </div>

        <!-- Submit -->
        <div class="col-md-12 mt-3">
            <button type="submit" class="btn btn-secondary" name="submit">
                <i class="feather icon-save"></i> Update Employee 
            </button>
        </div>
    </div>
</form>
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

</body>

</html>