<?php
session_start();
error_reporting(E_ALL);

if (!isset($_SESSION["login_user"])) {
    header("location: index.php");
}

include("db/config.php");

// Initialize variables

// Show success message if redirected after insert
$msg = "";
if (isset($_GET['success'])) {
    $msg = "
    <div class='alert alert-success alert-dismissible fade show' role='alert' style='font-size:16px;' id='goldmessage'>
    <strong><i class='feather icon-check'></i> Thanks!</strong> Greeting added successfully.
    <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
      <span aria-hidden='true'>&times;</span>
    </button>
    </div>";
}
if (isset($_POST['submit'])) {
    $emp_name = $_POST['emp_name'];
    $emp_designation = $_POST['emp_designation'];
    $status = $_POST['status'];
    $created_at = date('Y-m-d H:i:s');

    // Default empty
    $emp_img_path = null;

    // If cropped image is posted
    if (!empty($_POST['cropped_emp_image'])) {
        $croppedData = $_POST['cropped_emp_image'];
        $croppedData = str_replace('data:image/png;base64,', '', $croppedData);
        $croppedData = str_replace(' ', '+', $croppedData);
        $imageData = base64_decode($croppedData);

        $emp_img_path = "greetings/" . time() . "_cropped.png";
        file_put_contents($emp_img_path, $imageData);

    } elseif (!empty($_FILES['emp_image']['name'])) {
        // Normal uploaded image
        $emp_img = $_FILES['emp_image']['name'];
        $emp_tmp = $_FILES['emp_image']['tmp_name'];
        $emp_img_path = "greetings/" . time() . "_" . basename($emp_img);
        move_uploaded_file($emp_tmp, $emp_img_path);
    }

    // ===== Template image =====
    if (!empty($_FILES['template_image']['name'])) {
        $template_img = $_FILES['template_image']['name'];
        $template_tmp = $_FILES['template_image']['tmp_name'];
        $template_img_path = "assets/images/template/" . time() . "_" . basename($template_img);
        move_uploaded_file($template_tmp, $template_img_path);

        copy($template_img_path, "assets/images/template/default_template.jpg"); 
    } else {
        $template_img_path = "assets/images/template/template.jpg";
    }

    // Prevent null for emp_image
    if (empty($emp_img_path)) {
        $emp_img_path = "greetings/default.png"; // or some default image
    }

    // Insert into DB
    $stmt = $db->prepare("INSERT INTO emp_greetings (emp_name, emp_designation, emp_image, template_image, status, created_at) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssis", $emp_name, $emp_designation, $emp_img_path, $template_img_path, $status, $created_at);

    if ($stmt->execute()) {
        header("Location: add-new-greeting.php?success=1");
        exit();
    } else {
        echo "Insert error: " . $stmt->error;
    }
}

// Fetch position from the database
$positionquery = "SELECT position_id, name FROM position WHERE status = 1";
$positionresult = mysqli_query($db, $positionquery);
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
    <title>Add New Greetings</title>
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
    <style>
        #cropImage {
            border: 2px dashed #00ff99;
            background: #222;
            box-shadow: 0 0 15px rgba(0, 255, 153, 0.5);
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" />
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
                                <h5 class="m-b-10">Add New Greetings</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header table-card-header">
                            <?php if ($msg) echo $msg; ?>
                            <br />

                            <form method="post" action="" enctype="multipart/form-data" autocomplete="off">
                                <div class="row">


                                    <div class="col-md-6">
                                        <label class="form-label">Template Image
                                            <small>(Leave empty to use default)</small>
                                        </label>
                                        <input type="file" name="template_image" class="form-control" accept="image/*" disabled>

                                        <?php
                                        // Set default image path
                                        $defaultImagePath = 'assets/images/template/default_template.jpg';

                                        // Query to fetch the most recent uploaded template image
                                        $selectExistingImagesQuery = "SELECT template_image FROM emp_greetings WHERE template_image IS NOT NULL AND template_image != '' ORDER BY greeting_id DESC LIMIT 1";
                                        $existingImagesResult = mysqli_query($db, $selectExistingImagesQuery);

                                        // Check if there is any custom uploaded image, otherwise fallback to default
                                        $imagePathToShow = $defaultImagePath;

                                        if ($existingImagesResult && mysqli_num_rows($existingImagesResult) > 0) {
                                            $row = mysqli_fetch_assoc($existingImagesResult);
                                            if (!empty($row['template_image']) && file_exists($row['template_image'])) {
                                                $imagePathToShow = $row['template_image'];
                                            }
                                        }

                                        // Display the image
                                        echo '<div class="form-group mt-3">
            <label>Current Template Image</label>
            <div class="row">
                <div class="col-md-12 mb-3">
                    <img src="' . $imagePathToShow . '" class="img-fluid" style="height:100px; width:100px; object-fit:cover; border:1px solid #ddd;">
                </div>
            </div>
        </div>';
                                        ?>
                                    </div>



                                    <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
                                        <div class="form-group">
                                            <label for="Position_name" class="form-label">Employee Image <span class="red-text">*</span></label>
                                            <input type="file" name="emp_image" id="emp_image" class="form-control" accept="image/*" required>
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




                                    <!-- Text input for Course Name -->
                                    <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
                                        <div class="form-group">
                                            <label for="emp_name" class="form-label">Employee Name <span class="red-text">*</span></label>
                                            <input type="text" name="emp_name" class="form-control" placeholder="Enter Employee name" required>
                                        </div>
                                    </div>

                                    <!-- Text input for Course Name -->
                                    <!-- Dynamic Select for Course Types -->
                                    <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
                                        <div class="form-group">
                                            <label for="designation" class="form-label">Select Designation<span class="red-text">*</span></label>
                                            <select class="form-control" id="designation" name="emp_designation" required>
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


                                    <!-- Status -->
                                    <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
                                        <div class="form-group">
                                            <label for="status" class="form-label">Status <span class="red-text">*</span></label>
                                            <select name="status" class="form-control" required>
                                                <option value="" disabled>Choose</option>
                                                <option value="1" selected>Enable</option> <!-- Default selected -->
                                                <option value="0">Disable</option>
                                            </select>
                                        </div>
                                    </div>


                                    <!-- Submit Button -->
                                    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
                                        <button type="submit" class="btn btn-secondary" name="submit">
                                            <i class="feather icon-save"></i>&nbsp; Add Greetings
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>


    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
    <script>
        let cropper;
        const empImageInput = document.getElementById('emp_image');
        const cropModal = new bootstrap.Modal(document.getElementById('cropModal'));
        const cropImage = document.getElementById('cropImage');
        const cropBtn = document.getElementById('cropBtn');
        const croppedInput = document.getElementById('cropped_emp_image');

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

</body>

</html>