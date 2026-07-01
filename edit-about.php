<?php
session_start();
$upload_directory = "services/";
error_reporting(E_ALL); // Enable all error reporting for debugging

if (!isset($_SESSION["login_user"])) {
    header("location: index.php");
    exit;
}

include("db/config.php");
$msg = "";

// Fetch existing about_us details for update
if (isset($_GET['id'])) {
    $encodedAboutId = $_GET['id'];
    $aboutId = base64_decode($encodedAboutId);

    // Fetch existing About Us record details
    $query = "SELECT * FROM about_us WHERE about_id = ?";
    if ($stmt = mysqli_prepare($db, $query)) {
        mysqli_stmt_bind_param($stmt, 'i', $aboutId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $existingHeading = $row['heading'];
            $existingSubHeading = $row['sub_heading'];
            $existingDescription = $row['description'];
            $existingStatus = $row['status'];
            $existingImage1 = $row['about_image1'];
            $existingImage2 = $row['about_image2'];
            $existingImage3 = $row['about_icon'];
        } else {
            die("About Us record not found!");
        }
        mysqli_stmt_close($stmt);
    } else {
        die("Error fetching About Us details: " . mysqli_error($db));
    }
} else {
    die("Invalid request!");
}

// Handle the form submission
if (isset($_POST['submit'])) {
    $status = mysqli_real_escape_string($db, $_POST['status']);
    $heading = mysqli_real_escape_string($db, $_POST['heading']);
    $subHeading = mysqli_real_escape_string($db, $_POST['sub_heading']);
    $description = mysqli_real_escape_string($db, $_POST['description']);

    // Initialize file names to existing images in case no new files are uploaded
    $image1Filename = $existingImage1;
    $image2Filename = $existingImage2;
    $image3Filename = $existingImage3;

    // Handle image 1 upload
    if (!empty($_FILES['uploaded_image1']['name'])) {
        $uploadedImage1 = $_FILES['uploaded_image1'];
        if ($_FILES['uploaded_image1']['error'] !== UPLOAD_ERR_OK) {
            echo "Error uploading image 1: " . $_FILES['uploaded_image1']['error'];
            exit;
        }
        
        // Validate file type (only allow jpg, png, gif)
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($uploadedImage1['type'], $allowedTypes)) {
            echo "Invalid file type for image 1. Only JPG, PNG, GIF allowed.";
            exit;
        }

        // Generate a unique filename and move the uploaded file
        $image1Filename = uniqid() . '_' . basename($uploadedImage1['name']);
        $uploadPath1 = $upload_directory . $image1Filename;
        if (!move_uploaded_file($uploadedImage1['tmp_name'], $uploadPath1)) {
            echo "Failed to upload image 1.";
            exit;
        }
    }

    // Handle image 2 upload
    if (!empty($_FILES['uploaded_image2']['name'])) {
        $uploadedImage2 = $_FILES['uploaded_image2'];
        if ($_FILES['uploaded_image2']['error'] !== UPLOAD_ERR_OK) {
            echo "Error uploading image 2: " . $_FILES['uploaded_image2']['error'];
            exit;
        }

        // Validate file type (only allow jpg, png, gif)
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($uploadedImage2['type'], $allowedTypes)) {
            echo "Invalid file type for image 2. Only JPG, PNG, GIF allowed.";
            exit;
        }

        // Generate a unique filename and move the uploaded file
        $image2Filename = uniqid() . '_' . basename($uploadedImage2['name']);
        $uploadPath2 = $upload_directory . $image2Filename;
        if (!move_uploaded_file($uploadedImage2['tmp_name'], $uploadPath2)) {
            echo "Failed to upload image 2.";
            exit;
        }
    }

    // Handle image 3 upload
    if (!empty($_FILES['uploaded_image3']['name'])) {
        $uploadedImage3 = $_FILES['uploaded_image3'];
        if ($_FILES['uploaded_image3']['error'] !== UPLOAD_ERR_OK) {
            echo "Error uploading image 3: " . $_FILES['uploaded_image3']['error'];
            exit;
        }

        // Validate file type (only allow jpg, png, gif)
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($uploadedImage3['type'], $allowedTypes)) {
            echo "Invalid file type for image 3. Only JPG, PNG, GIF allowed.";
            exit;
        }

        // Generate a unique filename and move the uploaded file
        $image3Filename = uniqid() . '_' . basename($uploadedImage3['name']);
        $uploadPath3 = $upload_directory . $image3Filename;
        if (!move_uploaded_file($uploadedImage3['tmp_name'], $uploadPath3)) {
            echo "Failed to upload image 3.";
            exit;
        }
    }

    // Construct SQL query to update the About Us record
    $updateQuery = "UPDATE about_us SET 
                        heading = ?, 
                        sub_heading = ?, 
                        description = ?, 
                        status = ?, 
                        about_image1 = ?, 
                        about_image2 = ?, 
                        about_icon = ? 
                    WHERE about_id = ?";

    // Prepare and execute the update query
    if ($stmt = mysqli_prepare($db, $updateQuery)) {
        mysqli_stmt_bind_param($stmt, 'ssssssss', $heading, $subHeading, $description, $status, $image1Filename, $image2Filename, $image3Filename, $aboutId);
        if (mysqli_stmt_execute($stmt)) {
            if (mysqli_affected_rows($db) > 0) {
                $statusMessage = base64_encode(1); // Successful update status
                header("Location: manage-about.php?status=$statusMessage");
                exit;
            } else {
                echo "No rows were updated. Check if the values are actually different from existing values.";
                exit;
            }
        } else {
            echo "Error executing the query: " . mysqli_stmt_error($stmt);
            exit;
        }
        mysqli_stmt_close($stmt);
    } else {
        echo "Error preparing update query: " . mysqli_error($db);
        exit;
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

<meta http-equiv="content-type" content="text/html;charset=UTF-8" />

<head>
    <title> Update Logos </title>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="" />
    <meta name="keywords" content="">
    <meta name="author" content="" />

  <link rel="icon" href="<?php echo $favicon; ?>" type="image/x-icon">

    <link rel="stylesheet" href="assets/css/plugins/dataTables.bootstrap4.min.css">

    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- <script src="https://cdn.tiny.cloud/1/w9s9fz3wcjh5gsl1vp3uc6ka4gjtwty0jxcq12z65kb0svwi/tinymce/6/tinymce.min.js"
        referrerpolicy="origin"></script> -->
         <!-- summer note links -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet">
<link href="https://stackpath.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote.min.css" rel="stylesheet">
     
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
                                <h5 class="m-b-10">Update Logos
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
                            if ($msg) {
                                echo $msg;
                            }
                            ?>
                            <br />

                            <form class="contact-us" method="post" action=""  enctype="multipart/form-data"
                                autocomplete="off">
                                <div class=" ">
                                    <!-- Text input-->
                                    <div class="row">
                                    
                                        <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
                                            <div class="form-group"> About us Heading <span class="red-text">*</span>
                                                <label class="sr-only control-label" for="name"> About us Heading<span
                                                        class=" ">
                                                    </span></label>
                                                	<input id="title" name="heading" type="text"
                                                    placeholder=" Enter the  About us Heading" class="form-control input-md"
                                                    required
                                                    oninvalid="this.setCustomValidity('Please  About us Heading')"
                                                    oninput="setCustomValidity('')" value="<?php echo $existingHeading?>">
                                            </div>
                                        </div>
                                        <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
                                            <div class="form-group"> About us Sub Heading <span class="red-text">*</span>
                                                <label class="sr-only control-label" for="name"> About us Sub Heading <span
                                                        class=" ">
                                                    </span></label>
                                                	<input id="title" name="sub_heading" type="text"
                                                    placeholder=" Enter the  About us Sub Heading " class="form-control input-md"
                                                    required
                                                    oninvalid="this.setCustomValidity('Please  About us Sub Heading ')"
                                                    oninput="setCustomValidity('')" value="<?php echo $existingSubHeading?>">
                                            </div>
                                        </div>
                                       
                                        <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
    										<div class="form-group">Image1 <span class="red-text">*</span>
        										<label class="sr-only control-label" for="name">Image1<span class=" "></span></label>
        											<div class="input-group">
            											<input name="uploaded_file1" type="file" class="form-control input-md mr-2"  accept="image/*" onchange="showPreviewButton()">
            											<div class="input-group-append">
                                                            <button type="button" id="previewBtn" class="btn btn-secondary" onclick="showPreviewModal()" style="display: none;">
                                                                <i class="far fa-eye"></i> Preview *
                                                            </button>
            											</div>
        											</div>
        										<small class="text-muted"><span style="color: red;">*Upload supported file(Max 2MB)</span></small>
    										</div>
										</div>
										
										
                                        <!-- Modal Start -->
                                        <div class="modal fade" id="imagePreviewModal" tabindex="-1" role="dialog" aria-labelledby="imagePreviewModalLabel" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                                            <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="imagePreviewModalLabel">Image Preview</h5>
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                <img id="previewImage" src="#" alt="Preview Image" style="max-width: 100%; height: auto;">
                                            </div>
                                            </div>
                                        </div>
                                        </div>
                                        <!-- Modal end -->

                                        <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
    										<div class="form-group">Image2 <span class="red-text">*</span>
        										<label class="sr-only control-label" for="name">Image2<span class=" "></span></label>
        											<div class="input-group">
            											<input name="uploaded_file2" type="file" class="form-control input-md mr-2"  accept="image/*" onchange="showPreviewButton()">
            											<div class="input-group-append">
                                                            <button type="button" id="previewBtn" class="btn btn-secondary" onclick="showPreviewModal()" style="display: none;">
                                                                <i class="far fa-eye"></i> Preview *
                                                            </button>
            											</div>
        											</div>
        										<small class="text-muted"><span style="color: red;">*Upload supported file(Max 2MB)</span></small>
    										</div>
										</div>
										
										
                                        <!-- Modal Start -->
                                        <div class="modal fade" id="imagePreviewModal" tabindex="-1" role="dialog" aria-labelledby="imagePreviewModalLabel" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                                            <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="imagePreviewModalLabel">Image Preview</h5>
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                <img id="previewImage" src="#" alt="Preview Image" style="max-width: 100%; height: auto;">
                                            </div>
                                            </div>
                                        </div>
                                        </div>
                                        <!-- Modal end -->
                                        
                                        <!-- Add Service Icon Field -->
    									<div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
        									<div class="form-group"> About Us Icon <span class="red-text">*</span>
            									<label class="sr-only control-label" for="name">About Us Icon<span class=" "></span></label>
            										<div class="input-group">
                										<input name="uploaded_icon" type="file" class="form-control input-md mr-2"  accept="image/*" onchange="showIconPreviewButton()">
                										<div class="input-group-append">
                   		 									<button type="button" id="previewIconBtn" class="btn btn-secondary" onclick="showIconPreviewModal()" style="display: none;">
                        										<i class="far fa-eye"></i> Preview *
                    										</button>
                										</div>
            										</div>
            									<small class="text-muted"><span style="color: red;">*Upload supported file(Max 2MB)</span></small>
        									</div>
    									</div>

                                        <!-- Icon Preview Modal start -->
    									<div class="modal fade" id="iconPreviewModal" tabindex="-1" role="dialog" aria-labelledby="iconPreviewModalLabel" aria-hidden="true">
        									<div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            									<div class="modal-content">
                									<div class="modal-header">
                    									<h5 class="modal-title" id="iconPreviewModalLabel">Icon Preview</h5>
                    									<button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        									<span aria-hidden="true">&times;</span>
                    									</button>
                									</div>
                									<div class="modal-body">
                    									<img id="previewIcon" src="#" alt="Preview Icon" style="max-width: 100%; height: auto;">
                									</div>
            									</div>
        									</div>
    									</div>
                                        <!-- Icon Preview Modal end -->
                                        
                                        <div class="col-md-6">
                        <label for="status">Status</label>
                        <select name="status" class="form-control" required>
                            <option value="1" <?php echo ($existingStatus == 1) ? 'selected' : ''; ?>>Enable</option>
                            <option value="0" <?php echo ($existingStatus == 0) ? 'selected' : ''; ?>>Disable</option>
                        </select>
                    </div>
                    <div class="col-md-12">
                    <div id="summernote"></div>
                                          <!-- Hidden Input to Hold Summernote Content -->
                                <input type="hidden" name="description" id="description" value="<?php echo isset($row['description']) ? htmlspecialchars($row['description']) : ''; ?>">
                                        <!-- Text input-->
                                        </div>
                                        <!-- Button -->
                                        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
                                            <button type="submit" class="btn btn-secondary" name="submit" id="submit">
                                                <i class="feather icon-save"></i>&nbsp; Add Service
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
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize Summernote Editor
            $('#summernote').summernote({
                height: 300 // Set editor height
            });
            // Load existing content into Summernote
            let content = $("#description").val(); // Get content from the hidden input
            $('#summernote').summernote('code', content); // Set it in the editor
            // Sync Summernote content with the hidden input on form submission
            $('form').on('submit', function(e) {
                let summernoteContent = $('#summernote').summernote('code'); // Get content from Summernote
                $('#description').val(summernoteContent); // Update the hidden input
            });
        });
    </script>
    <script>
        $(document).ready(function () {
            $("#goldmessage").delay(5000).slideUp(300);
        });
    </script>
    
   
    
    <script>
    function showPreviewButton() {
        var input = document.getElementById('imageInput');
        var previewButtonContainer = document.getElementById('previewButtonContainer');

        if (input.files && input.files[0]) {
            previewButtonContainer.style.display = 'block';
        } else {
            previewButtonContainer.style.display = 'none';
        }
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
