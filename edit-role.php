<?php
session_start();

error_reporting(E_ALL);
if (!isset($_SESSION["login_user"])) {
    header("location: index.php");
    exit;
}

$name = $_SESSION['login_user'];
include("db/config.php");

// Fetch existing course details for update
if (isset($_GET['id'])) {
    $encodedRoleId = $_GET['id'];
   $roleId = base64_decode($encodedRoleId);

    // Validate$roleId to ensure it's numeric and valid
    if (!is_numeric($roleId)) {
        echo "Invalid Role ID!";
        exit;
    }

    // Corrected query to fetch the course details
   
               $query = "SELECT * FROM roles
              WHERE role_id =$roleId";

    $result = mysqli_query($db, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);

        // Existing course details
        $existingName = $row['role_name'];
       
        $existingStatus = $row['status'];
    } else {
        echo "Role not found!";
        exit;
    }
} else {
    // For adding new Role
    $existingName = "";
  
    $existingStatus = "";
  
}

// Add or update course
if (isset($_POST['submit'])) {
    $roleName = $_POST['role_name'];
   
    $status = $_POST['status'];

 

    // Update or insert course details
    if (isset($_GET['id'])) {
        // Update existing course
        $updateQuery = "UPDATE roles 
                        SET role_name = '$roleName',  status = '$status'
                        WHERE role_id =$roleId";
    } else {
       
    }

    // Check if the query is not empty before executing
    if (!empty($updateQuery)) {
        $updateResult = mysqli_query($db, $updateQuery);

        if ($updateResult) {
            $statusCode = 1;
            $encodedStatus = base64_encode($statusCode);
            echo ("<SCRIPT LANGUAGE='JavaScript'>window.location.href='manage-role.php?status=$encodedStatus';</SCRIPT>");
        } else {
            echo "Error executing query: " . mysqli_error($db);
        }
    } else {
        echo "Query is empty!";
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
                                <h5 class="m-b-10"><?php echo isset($_GET['id']) ? 'Update' : 'Add'; ?> Role
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
                            <form class="contact-us" method="post" action=""
                                enctype="multipart/form-data" autocomplete="off">
                                <div class=" ">
                                    <!-- Text input-->
                                    <div class="row">
                                        <div class="col-xl- col-lg-6 col-md-12 col-sm-12 col-12">
                                            <div class="form-group"> Role Name<span class="red-text">*</span>
                                                <label class="sr-only control-label" for="course_name"> Role Name<span class=" ">
                                                        </span></label>
                                                <input id="role_name" name="role_name" type="text"
                                                    placeholder=" Enter the Name" class="form-control input-md"
                                                    value="<?php echo $existingName; ?>" required
                                                    oninvalid="this.setCustomValidity('Please Enter Name')"
                                                    oninput="setCustomValidity('')">
                                            </div>
                                        </div>
                                       
                                      
                               
                                        <div class="col-xl-6 col-lg-6 col-md-4 col-sm-12 col-12">
                                            <div class="form-group">Status <span class="red-text">*</span>
                                                <label class="sr-only control-label" for="name">Status<span class=" ">
                                                    </span></label>
                                                <select id="" name="status" class="form-control" required>
                                                    <option value="" selected disabled>Choose</option>
                                                    <option value="1" <?php echo (isset($existingStatus) && $existingStatus == 1) ? 'selected' : ''; ?>>
                                                        Enable
                                                    </option>
                                                    <option value="0" <?php echo (isset($existingStatus) && $existingStatus == 0) ? 'selected' : ''; ?>>
                                                        Disable
                                                </select>
                                            </div>
                                        </div>
                                      
                                        <!-- Text input-->
                                        <!-- Button -->
                                        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
                                            <button type="submit" class="btn btn-secondary" name="submit"
                                                id="submit">
                                                <i class="feather icon-save"></i>&nbsp; <?php echo isset($_GET['id']) ? 'Update' : 'Add'; ?> Role
                                            </button>
                                        </div>
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
        $(document).ready(function () {
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

            reader.onload = function (e) {
                modalImg.src = e.target.result;
                $(modal).modal('show'); // Show the modal
            }

            reader.readAsDataURL(file);
        }
    }
	</script>
    
</body>
</html>
