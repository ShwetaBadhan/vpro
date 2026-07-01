<?php
session_start();
include('db/config.php');
$msg="";

if(isset($_GET['id'])) {
    $encodedCategoryId = $_GET['id'];
    $categoryId = base64_decode($encodedCategoryId);
    
    $query = "SELECT * FROM service WHERE id = $categoryId";
    $result = mysqli_query($db, $query);
    
    if(mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        // Fetch category details from $row
        $categoryName = $row['name'];
        // $parentcategoryName = $row['parent_category'];
        $categoryStatus = $row['status'];
        
        // Check if the form is submitted for updating
        if(isset($_POST['submit'])) {
            // Get updated values from the form
            $updatedCategoryName = mysqli_real_escape_string($db, $_POST['name']);
            // $updatedparentCategoryName = isset($_POST['parent_category']) ? mysqli_real_escape_string($db, $_POST['parent_category']) : '';
            $updatedCategoryStatus = mysqli_real_escape_string($db, $_POST['status']);
            
            // Update the category in the database
            $updateQuery = "UPDATE service SET name = '$updatedCategoryName',status='$updatedCategoryStatus' WHERE id = $categoryId";
            if ($db->query($updateQuery) === TRUE)
            {
                $staus = 1;
                
                $re = base64_encode($staus);
                
                echo ("<SCRIPT LANGUAGE='JavaScript'>window.location.href='manage-service.php?status=$re';</SCRIPT>");
            } 
            else 
            {
                $staus = 1;
                
                $re = base64_encode($staus);
                
                echo ("<SCRIPT LANGUAGE='JavaScript'>window.location.href='manage-service.php?status=$re';</SCRIPT>");
            }
        }
    } else {
        echo "Service not found!";
        exit;
    }
} else {
    echo "Invalid request!";
    exit;
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
    <title>Update Service</title>

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
                                <h5 class="m-b-10">Update Service
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
                            <form class="contact-us" method="post" action="" enctype="multipart/form-data"
                                autocomplete="off">
                                <div class=" ">
                                    <!-- Text input-->
                                    <div class="row">
                                        <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
                                            <div class="form-group">
                                               <label for="name" class="form-label"> Service Name <span class="red-text">*</span></label>
                    						   <input type="text" name="name" class="form-control" value="<?php echo $categoryName; ?>" placeholder="Enter Gallery Name" required>
                                            </div>
                                        </div>


                                        
                                            
                                            <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
                                           		<div class="form-group">
                                               		<label for="name" class="form-label">Status <span class="red-text">*</span></label>
                                                		<select id="" name="status" class="form-control" required>
                                                			<option value="" selected disabled>Choose</option>
                                                    		<option value="1" <?php echo ($categoryStatus == 1) ? 'selected' : ''; ?>>Enable</option>
                                                    		<option value="0" <?php echo ($categoryStatus != 1) ? 'selected' : ''; ?>>Disable</option>
                                                	</select>
                                            	</div>
                                        	</div>

                                        <!-- Button -->
                                        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
                                            <button type="submit" class="btn btn-secondary" name="submit" id="submit">
                                                <i class="feather icon-save"></i>&nbsp; Update Service
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
    <script>
    $(document).ready(function() {
        $("#goldmessage").delay(5000).slideUp(300);
    });
    </script>

</body>

</html>