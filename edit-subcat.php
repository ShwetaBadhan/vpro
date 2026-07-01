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
    $encodedSubcat = $_GET['id'];
   $Subcat = base64_decode($encodedSubcat);

    // Validate$Subcat to ensure it's numeric and valid
    if (!is_numeric($Subcat)) {
        echo "Invalid Course ID!";
        exit;
    }

    // Corrected query to fetch the course details
   
$query = "SELECT 
sc.category_id,
sc.parent_category,
    sc.category_name,
    cat.name,
    sc.status  
FROM
    category as sc
JOIN parent_category as cat ON sc.parent_category = cat.id
              WHERE sc.category_id =$Subcat";

    $result = mysqli_query($db, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);

        // Existing course details
        $existingName = $row['category_name'];
        $existingCourseTypeId = $row['parent_category'];
        $existingStatus = $row['status'];
    } else {
        echo "SubCat not found!";
        exit;
    }
} else {
    // For adding new courses
    $existingName = "";
    $existingCourseTypeId = "";
    $existingStatus = "";
    $existingCourseTypeName = "";
}

// Add or update course
if (isset($_POST['submit'])) {
    $courseName = $_POST['category_name'];
    $courseTypeId = $_POST['parent_category'];
    $status = $_POST['status'];

    // Validate input fields to ensure correct data
    if (empty($courseName) || empty($courseTypeId) || !isset($status)) {
        echo "All fields are required!";
        exit;
    }

    // Update or insert course details
    if (isset($_GET['id'])) {
        // Update existing course
        $updateQuery = "UPDATE category 
                        SET category_name = '$courseName', parent_category = '$courseTypeId', status = '$status'
                        WHERE category_id =$Subcat";
    } else {
        // Insert new course
        $updateQuery = "INSERT INTO category (category_name, parent_category, status)
                        VALUES ('$courseName', '$courseTypeId', '$status')";
    }

    // Check if the query is not empty before executing
    if (!empty($updateQuery)) {
        $updateResult = mysqli_query($db, $updateQuery);

        if ($updateResult) {
            $statusCode = 1;
            $encodedStatus = base64_encode($statusCode);
            echo ("<SCRIPT LANGUAGE='JavaScript'>window.location.href='manage-subcategories.php?status=$encodedStatus';</SCRIPT>");
        } else {
            echo "Error executing query: " . mysqli_error($db);
        }
    } else {
        echo "Query is empty!";
    }
}
// Fetch course types from the database
$courseTypesQuery = "SELECT id,name FROM parent_category"; // Assuming your table is `course_type`
$courseTypesResult = mysqli_query($db, $courseTypesQuery);
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
                                <h5 class="m-b-10"><?php echo isset($_GET['id']) ? 'Update' : 'Add'; ?> Courses
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
                                            <div class="form-group"> Sub Category <span class="red-text">*</span>
                                                <label class="sr-only control-label" for="course_name"> Sub Category<span class=" ">
                                                        </span></label>
                                                <input id="category_name" name="category_name" type="text"
                                                    placeholder=" Enter the Name" class="form-control input-md"
                                                    value="<?php echo $existingName; ?>" required
                                                    oninvalid="this.setCustomValidity('Please Enter Name')"
                                                    oninput="setCustomValidity('')">
                                            </div>
                                        </div>
                                       
                                        <!-- Dynamic Select for Course Types -->
                                    <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
                                        <div class="form-group">
                                            <label for="parent_category" class="form-label">Select Parent Category <span class="red-text">*</span></label>
                                           <select class="form-control" id="parent_category" name="parent_category" required>
    <option value="" disabled>Choose Parent Category</option>
    
    <?php
    if (mysqli_num_rows($courseTypesResult) > 0) {
        while ($row = mysqli_fetch_assoc($courseTypesResult)) {

            $selected = ($row['id'] == $existingCourseTypeId) ? 'selected' : '';

            echo "<option value='" . $row['id'] . "' $selected>" . $row['name'] . "</option>";
        }
    } else {
        echo "<option value='' disabled>No categories found</option>";
    }
    ?>
</select>
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
                                                <i class="feather icon-save"></i>&nbsp; <?php echo isset($_GET['id']) ? 'Update' : 'Add'; ?> Sub Category
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
