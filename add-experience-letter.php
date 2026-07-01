<?php
session_start();

error_reporting(E_ALL);

if (!isset($_SESSION["login_user"])) {
    header("location: index.php");
}

include("db/config.php");

// Initialize variables
$msg = "";

if (isset($_POST['submit'])) {

    // echo'<pre>';
    // print_r($_POST);
    // exit;

    $emp_name = $_POST['employee_id'];
    $emp_designation = $_POST['designation'];
    $date_from = $_POST['date_from'];
    $date_to = $_POST['date_to'];
    $reference = $_POST['reference_no'];
    $status = $_POST['status'];
    $created_at = date('Y-m-d H:i:s');

    // Sign image upload
$emp_img = $_FILES['sign']['name'];

if (!empty($emp_img)) {
    $emp_tmp = $_FILES['sign']['tmp_name'];
    $emp_img_path = "sign/" . time() . "_" . basename($emp_img);
    move_uploaded_file($emp_tmp, $emp_img_path);
} else {
    // Default sign if no image uploaded
    $emp_img_path = "sign/default-sign.png";
}

   
    // === DATABASE INSERTION ===
    $stmt = $db->prepare("INSERT INTO experience_letter (employee_id, designation, date_from, date_to, reference_no, status,sign, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisssiss", $emp_name, $emp_designation, $date_from, $date_to, $reference, $status,$emp_img_path, $created_at);

    if ($stmt->execute()) {
        $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible fade show' role='alert' style='font-size:16px;' id='goldmessage'>
<strong><i class='feather icon-check'></i> Thanks!</strong> Experience Letter added successfully.
<button type='button' class='close' data-dismiss='alert' aria-label='Close'>
  <span aria-hidden='true'>&times;</span>
</button>
</div>";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();;
    } else {
        $_SESSION['msg'] = "
        <div class='alert alert-danger alert-dismissible fade show' role='alert' style='font-size:16px;' id='goldmessage'>
            <strong><i class='feather icon-x'></i> Error!</strong> Failed to add Experience Letter. " . $stmt->error . "
            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
              <span aria-hidden='true'>&times;</span>
            </button>
        </div>";
    }
}
// Fetch position from the database
$positionquery = "SELECT position_id, name FROM position WHERE status = 1";
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
    <title>Add Experience Letter</title>
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
            <div class="page-header">
                <div class="page-block">
                    <div class="row align-items-center">
                        <div class="col-md-12">
                            <div class="page-header-title">
                                <h5 class="m-b-10">Add Experience Letter</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header table-card-header">

                            <br />

                           
                            <form method="post" action="" enctype="multipart/form-data" autocomplete="off">
                                 <?php
                            if (isset($_SESSION['msg'])) {
                                echo $_SESSION['msg'];
                                unset($_SESSION['msg']); // Clear after showing
                            } ?>
                                <div class="row">
                                   <div class="col-md-6">
                                        <label class="form-label">Reference No <span class="text-danger">*</span></label>
                                        <input type="text" name="reference_no" class="form-control" required placeholder="Enter Reference No.">
                                    </div>
                                    <!-- Select Employee -->
                                    <div class="col-md-6 mb-4">
                                        <label class="form-label">Select Employee <span class="text-danger">*</span></label>
                                        <select name="employee_id" class="form-control" required>
                                            <option value="" disabled selected>Choose Employee</option>
                                            <?php
                                            if (mysqli_num_rows($personalresult) > 0) {
                                                while ($row = mysqli_fetch_assoc($personalresult)) {
                                                    echo "<option value='" . $row['personal_id'] . "'>" . $row['name'] . "</option>";
                                                }
                                            } else {
                                                echo "<option disabled>No Employees Found</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                      <!-- Select Designation -->
                                    <div class="col-md-6 mb-4">
                                        <label class="form-label">Designation <span class="text-danger">*</span></label>
                                        <select name="designation" class="form-control" required>
                                            <option value="" disabled selected>Choose Designation</option>
                                            <?php
                                            if (mysqli_num_rows($positionresult) > 0) {
                                                while ($row = mysqli_fetch_assoc($positionresult)) {
                                                    echo "<option value='" . $row['position_id'] . "'>" . $row['name'] . "</option>";
                                                }
                                            } else {
                                                echo "<option disabled>No Designations Found</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>

                                    <!-- Month -->
                                    <div class="col-md-6 mb-4">
                                        <label class="form-label">Date From <span class="text-danger">*</span></label>
                                        <input type="date" name="date_from" class="form-control" required>
                                    </div>

                                    <!-- Month -->
                                    <div class="col-md-6">
                                        <label class="form-label">Date To <span class="text-danger">*</span></label>
                                        <input type="date" name="date_to" class="form-control" required>
                                    </div>
                                        
                                    <div class="col-md-6">
    <label class="form-label">Upload Signature <span class="text-danger">*</span></label>
    <input type="file" name="sign" class="form-control" id="signInput">

    <!-- Preview Image -->
    <img id="signPreview" 
         src="sign/default-sign.png" 
         alt="Signature Preview" 
         style="width:250px; margin-top:10px; border:1px solid #ddd; padding:5px;">
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
                                            <i class="feather icon-save"></i> Add Experience
                                        </button>
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
<script>
document.getElementById("signInput").addEventListener("change", function(event) {
    let reader = new FileReader();

    reader.onload = function() {
        document.getElementById("signPreview").src = reader.result;
    };

    if (event.target.files[0]) {
        reader.readAsDataURL(event.target.files[0]);
    }
});
</script>

</body>

</html>