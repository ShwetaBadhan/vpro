<?php
session_start();
error_reporting(E_ALL);
include("db/config.php");

if (!isset($_SESSION["login_user"])) {
    header("location: index.php");
    exit;
}

// Initialize default values
$existingName = "";

$existingEmpImage = "";
$exisitngTemplate = "";

$existingStatus = "";

$birthId = null;
$isEditMode = false;

// ================= FETCH IF EDIT ==================
if (isset($_GET['id'])) {
    $encodedbirthId = $_GET['id'];
    $birthId = base64_decode($encodedbirthId);

    if (!is_numeric($birthId)) {
        echo "Invalid ID!";
        exit;
    }

    $query = "SELECT * FROM birthday_greetings WHERE birthday_id = $birthId";
    $result = mysqli_query($db, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);

        $existingName = $row['employee_id'];

        $existingEmpImage = $row['employee_image'];
        $exisitngTemplate = $row['template_image'];
        $existingStatus = $row['status'];
        $isEditMode = true;
    } else {
        echo "Birthday Greetings not found!";
        exit;
    }
}

// ================= FORM SUBMISSION ==================
if (isset($_POST['submit'])) {
    // echo '<pre>';
    // print_r($_FILES);
    // print_r($_POST);
    // exit;

    $emp_name = $_POST['employee_id'];


    $status = $_POST['status'];
    $created_at = date('Y-m-d H:i:s');

    // Image paths default to existing (for edit)
    $empImgPath = $existingEmpImage ?: '';
    $templateImgPath = $exisitngTemplate ?: 'assets/images/template/birthday/template.jpg';

     // Upload new employee image (with cropping support)
    if (!empty($_POST['cropped_emp_image'])) {
        // Cropped image (base64) ko file me save karo
        $croppedData = $_POST['cropped_emp_image'];
        $croppedData = str_replace('data:image/png;base64,', '', $croppedData);
        $croppedData = str_replace(' ', '+', $croppedData);
        $imageData = base64_decode($croppedData);

        $empImgPath = "employeemonth/" . time() . "_cropped.png";
        file_put_contents($empImgPath, $imageData);
    }elseif (!empty($_FILES['employee_image']['name'])) {
        $empImg = $_FILES['employee_image']['name'];
        $empTmp = $_FILES['employee_image']['tmp_name'];
        $empImgPath = "employeemonth/" . time() . "_" . basename($empImg);
        move_uploaded_file($empTmp, $empImgPath);
    }

    // Upload new template image
    if (!empty($_FILES['template_image']['name'])) {
        $templateImg = $_FILES['template_image']['name'];
        $templateTmp = $_FILES['template_image']['tmp_name'];
        $templateImgPath = "assets/images/template/birthday/" . time() . "_" . basename($templateImg);
        move_uploaded_file($templateTmp, $templateImgPath);

        // Replace the default if desired
        copy($templateImgPath, "assets/images/template/birthday/default_template.jpg");
    }

    // ================= UPDATE ==================
    if ($isEditMode) {
        $updateQuery = "UPDATE birthday_greetings
                        SET employee_id = '$emp_name', 
                        
                            employee_image = '$empImgPath',
                            template_image = '$templateImgPath',
                       
                            status = '$status'
                        WHERE birthday_id = $birthId";

        $updateResult = mysqli_query($db, $updateQuery);

        if ($updateResult) {
            header("Location: manage-birthday-greetings.php?status=" . base64_encode(1));
            exit;
        } else {
            echo "Update error: " . mysqli_error($db);
        }

        // ================= INSERT ==================
    } else {
        // === DATABASE INSERTION ===
        $stmt = $db->prepare("INSERT INTO birthday_greetings (employee_id, employee_image, template_image, status, created_at) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issis", $emp_name,  $emp_img_path, $template_img_path, $status, $created_at);

        if ($stmt->execute()) {
            header("Location: add-birthday-greetings.php?success=1");
            exit;
        } else {
            echo "Insert error: " . $stmt->error;
        }
    }
}
// Fetch position from the database

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
    <title><?php echo isset($_GET['id']) ? 'Update' : 'Add'; ?> Birthday Greetings</title>
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
        #cropImage {
            border: 2px dashed #00ff99;
            background: #222;
            box-shadow: 0 0 15px rgba(0, 255, 153, 0.5);
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" />

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
                                <h5 class="m-b-10"><?php echo isset($_GET['id']) ? 'Update' : 'Add'; ?> Birthday Greetings
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
                                    <!-- Template Image -->
                                    <div class="col-md-6">
                                        <label class="form-label">Template Image <small>(Leave empty to use default)</small></label>
                                        <input type="file" name="template_image" class="form-control" accept="image/*" disabled>

                                        <?php
                                        $selectExistingImagesQuery = "SELECT template_image FROM birthday_greetings ORDER BY birthday_id DESC LIMIT 1";
                                        $existingImagesResult = mysqli_query($db, $selectExistingImagesQuery);
                                        if ($existingImagesResult && mysqli_num_rows($existingImagesResult) > 0) {
                                            echo '<div class="form-group"><label>Recent Image</label><div class="row">';
                                            while ($row = mysqli_fetch_assoc($existingImagesResult)) {
                                                $imagePath = $row['template_image'];
                                                echo '<div class="col-md-12 mb-3"><img src="' . $imagePath . '" class="img-fluid" style="height:100px; width:100px;"></div>';
                                            }
                                            echo '</div></div>';
                                        }
                                        ?>
                                    </div>

                                    <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
                                        <div class="form-group">
                                            <label for="emp_image" class="form-label">Employee Image <span class="red-text">*</span></label>
                                            <input type="file" name="employee_image" id="employee_image" class="form-control">

                                            <?php if (!empty($existingEmpImage)) { ?>
                                                <div class="form-group mt-3">
                                                    <label class="form-label">Current Employee Image</label>
                                                    <div>
                                                        <img src="<?php echo $existingEmpImage; ?>"
                                                            class="img-thumbnail"
                                                            alt="Employee Image"
                                                            style="height: 150px; width: 150px;">
                                                    </div>
                                                </div>
                                            <?php } ?>
                                        </div>
                                    </div>

                                    <!-- Crop Modal -->
                                    <div class="modal fade" id="cropModal" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-fullscreen">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Crop Employee Image</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">X</button>
                                                </div>
                                                <div class="modal-body d-flex justify-content-center align-items-center bg-dark">
                                                    <img id="cropImage" style="max-width: 90%; max-height: 85vh; display:block; margin:auto;" />
                                                </div>
                                                <div class="modal-footer justify-content-center">
                                                    <button type="button" id="cropBtn" class="btn btn-success px-4">✅ Crop & Save</button>
                                                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">❌ Cancel</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Hidden input to store cropped image (Base64) -->
                                    <input type="hidden" name="cropped_emp_image" id="cropped_emp_image">


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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>


    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
    <script>
        let cropper;
        const empImageInput = document.getElementById('employee_image');
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