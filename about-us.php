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
$query = "SELECT * FROM about_us LIMIT 1";
if ($stmt = mysqli_prepare($db, $query)) {
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
}

// Handle form submission
if (isset($_POST['submit'])) {
    $status = mysqli_real_escape_string($db, $_POST['status']);
    $heading = mysqli_real_escape_string($db, $_POST['heading']);
    $subHeading = mysqli_real_escape_string($db, $_POST['sub_heading']);
    $description = mysqli_real_escape_string($db, $_POST['description']);

    // Initialize file names to existing images
    $image1Filename = $existingImage1;
    $image2Filename = $existingImage2;
    $image3Filename = $existingImage3;

    // Allowed file types
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];

    // Function to handle image upload
    function uploadImage($file, $existingFile)
    {
        global $upload_directory, $allowedTypes;

        if (!empty($file['name'])) {
            if ($file['error'] !== UPLOAD_ERR_OK) {
                die("Error uploading file: " . $file['error']);
            }

            if (!in_array($file['type'], $allowedTypes)) {
                die("Invalid file type. Only JPG, PNG, GIF allowed.");
            }

            $filename = uniqid() . '_' . basename($file['name']);
            $uploadPath = $upload_directory . $filename;

            if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
                die("Failed to upload file.");
            }

            return $filename;
        }
        return $existingFile; // Return existing file if no new upload
    }

    // Upload new images if provided
    $image1Filename = uploadImage($_FILES['uploaded_image1'], $existingImage1);
    $image2Filename = uploadImage($_FILES['uploaded_image2'], $existingImage2);
    $image3Filename = uploadImage($_FILES['uploaded_image3'], $existingImage3);

    // Check if any value has changed
    if (
        $heading === $existingHeading &&
        $subHeading === $existingSubHeading &&
        $description === $existingDescription &&
        $status === $existingStatus &&
        $image1Filename === $existingImage1 &&
        $image2Filename === $existingImage2 &&
        $image3Filename === $existingImage3
    ) {
        die("No changes detected. Update not performed.");
    }

    // Update query with timestamp to force update
    $updateQuery = "UPDATE about_us SET 
                        heading = ?, 
                        sub_heading = ?, 
                        description = ?, 
                        status = ?, 
                        about_image1 = ?, 
                        about_image2 = ?, 
                        about_icon = ?, 
                        updated_at = NOW()"; // Ensure update is applied

    // Execute update query
    if ($stmt = mysqli_prepare($db, $updateQuery)) {
        mysqli_stmt_bind_param($stmt, 'sssssss', $heading, $subHeading, $description, $status, $image1Filename, $image2Filename, $image3Filename);
        if (mysqli_stmt_execute($stmt)) {
            if (mysqli_affected_rows($db) > 0) {
                header("Location: about-us.php?status=" . base64_encode(1));
                exit;
            } else {
                die("No rows were updated. Try changing the values.");
            }
        } else {
            die("Error executing the query: " . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
    } else {
        die("Error preparing update query: " . mysqli_error($db));
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
    <title> Update About Us </title>

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
                                <h5 class="m-b-10">Update About
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

                            <form class="contact-us" method="post" id="submitForm" action="" enctype="multipart/form-data"
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
                                                    oninput="setCustomValidity('')" value="<?php echo $existingHeading ?>">
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
                                                    oninput="setCustomValidity('')" value="<?php echo $existingSubHeading ?>">
                                            </div>
                                        </div>

                                        <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
                                            <div class="form-group">Image1 <span class="red-text">*</span>
                                                <label class="sr-only control-label" for="name">Image1<span class=" "></span></label>
                                                <div class="input-group">
                                                    <input name="uploaded_image1" type="file" class="form-control input-md mr-2" accept="image/*" onchange="showPreviewButton()">
                                                    <div class="input-group-append">
                                                        <button type="button" id="previewBtn" class="btn btn-secondary" onclick="showPreviewModal()" style="display: none;">
                                                            <i class="far fa-eye"></i> Preview *
                                                        </button>
                                                    </div>
                                                </div>
                                                <small class="text-muted"><span style="color: red;">*Upload supported file(Max 2MB)</span></small>
                                            </div>
                                        </div>

                                        <?php
                                        // Fetch and display images
                                        $upload_directory = "services/"; // Ensure this matches the actual upload directory

                                        // Replace `$sliderId` with a valid ID or remove the WHERE clause if not required
                                        $selectExistingImagesQuery = "SELECT about_image1 FROM about_us LIMIT 1";
                                        $existingImagesResult = mysqli_query($db, $selectExistingImagesQuery);

                                        if ($existingImagesResult && mysqli_num_rows($existingImagesResult) > 0) {
                                            echo '<div class="form-group">';
                                            echo '<label for="current_images" class="form-label" style="margin-left:20px;">Recent Image *</label>';
                                            echo '<div class="row">';

                                            while ($row = mysqli_fetch_assoc($existingImagesResult)) {
                                                $imageFilename = $row['about_image1'];
                                                $imagePath = $upload_directory . $imageFilename;

                                                echo '<div class="col-xl-3 col-lg-3 col-md-4 col-sm-6 col-12 mb-3">';
                                                echo '<img src="' . $imagePath . '" class="img-fluid" alt="Uploaded Image" style="width: 500px; margin-top: 5px;margin-left:20px;">';
                                                echo '</div>';
                                            }

                                            echo '</div>';
                                            echo '</div>';
                                        } else {
                                            echo '<p>No images found.</p>';
                                        }
                                        ?>

                                        <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
                                            <div class="form-group">Image2 <span class="red-text">*</span>
                                                <label class="sr-only control-label" for="name">Image2<span class=" "></span></label>
                                                <div class="input-group">
                                                    <input name="uploaded_image2" type="file" class="form-control input-md mr-2" accept="image/*" onchange="showPreviewButton()">
                                                    <div class="input-group-append">
                                                        <button type="button" id="previewBtn" class="btn btn-secondary" onclick="showPreviewModal()" style="display: none;">
                                                            <i class="far fa-eye"></i> Preview *
                                                        </button>
                                                    </div>
                                                </div>
                                                <small class="text-muted"><span style="color: red;">*Upload supported file(Max 2MB)</span></small>
                                            </div>
                                        </div>


                                        <?php
                                        // Fetch and display images
                                        $upload_directory = "services/"; // Ensure this matches the actual upload directory

                                        // Replace `$sliderId` with a valid ID or remove the WHERE clause if not required
                                        $selectExistingImagesQuery = "SELECT about_image2 FROM about_us LIMIT 1";
                                        $existingImagesResult = mysqli_query($db, $selectExistingImagesQuery);

                                        if ($existingImagesResult && mysqli_num_rows($existingImagesResult) > 0) {
                                            echo '<div class="form-group">';
                                            echo '<label for="current_images" class="form-label" style="margin-left:20px;">Recent Image *</label>';
                                            echo '<div class="row">';

                                            while ($row = mysqli_fetch_assoc($existingImagesResult)) {
                                                $imageFilename = $row['about_image2'];
                                                $imagePath = $upload_directory . $imageFilename;

                                                echo '<div class="col-xl-3 col-lg-3 col-md-4 col-sm-6 col-12 mb-3">';
                                                echo '<img src="' . $imagePath . '" class="img-fluid" alt="Uploaded Image" style="width: 500px; margin-top: 5px;margin-left:20px;">';
                                                echo '</div>';
                                            }

                                            echo '</div>';
                                            echo '</div>';
                                        } else {
                                            echo '<p>No images found.</p>';
                                        }
                                        ?>

                                        <!-- Add Service Icon Field -->
                                        <!-- <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
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

                                        <?php
                                        $upload_directory = "services/";
                                        $selectExistingImagesQuery = "SELECT about_icon FROM about_us LIMIT 1";
                                        $existingImagesResult = mysqli_query($db, $selectExistingImagesQuery);

                                        if ($existingImagesResult && mysqli_num_rows($existingImagesResult) > 0) {
                                            echo '<div class="form-group">';
                                            echo '<label for="current_images" class="form-label" style="margin-left:20px;">Recent Image *</label>';
                                            echo '<div class="row">';

                                            while ($row = mysqli_fetch_assoc($existingImagesResult)) {
                                                $imageFilename = $row['about_icon'];
                                                $imagePath = $upload_directory . $imageFilename;

                                                echo '<div class="col-xl-3 col-lg-3 col-md-4 col-sm-6 col-12 mb-3">';
                                                echo '<img src="' . $imagePath . '" class="img-fluid" alt="Uploaded Image" style="width: 500px; margin-top: 5px;margin-left:20px;">';
                                                echo '</div>';
                                            }

                                            echo '</div>';
                                            echo '</div>';
                                        } else {
                                            echo '<p>No images found.</p>';
                                        }
                                        ?> -->

                                        <div class="col-md-6 mb-4">
                                            <label for="status">Status</label>
                                            <select name="status" class="form-control" required>
                                                <option value="1" <?php echo ($existingStatus == 1) ? 'selected' : ''; ?>>Enable</option>
                                                <option value="0" <?php echo ($existingStatus == 0) ? 'selected' : ''; ?>>Disable</option>
                                            </select>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="col-md-12">
                                                <div id="summernote"></div>
                                                <!-- Hidden Input to Hold Summernote Content -->
                                                <input type="hidden" name="description" id="description" value="<?php echo isset($existingDescription) ? htmlspecialchars($existingDescription, ENT_QUOTES, 'UTF-8') : ''; ?>">

                                            </div>
                                            <!-- Button -->
                                            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
                                                <button type="submit" class="btn btn-secondary" name="submit" id="submit">
                                                    <i class="feather icon-save"></i>&nbsp; Update About Us
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
        // Initialize Summernote and set content from the hidden input
        $(document).ready(function() {
            var existingDescription = $('#description').val();
            $('#summernote').summernote({
                height: 400, // Set editor height
            });
            $('#summernote').summernote('code', existingDescription);

            // Sync Summernote content to hidden input on change
            $('#summernote').on('summernote.change', function(e) {
                $('#description').val($('#summernote').summernote('code'));
            });
        });
    </script>
    <script>
        $(document).ready(function() {
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