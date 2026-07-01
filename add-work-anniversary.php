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

    // echo '<pre>';
    // print_r($_POST);
    // exit;

    $emp_name = $_POST['name'];
    $emp_designation = $_POST['designation'];
    $status = $_POST['status'];
    $year = $_POST['work_year'];
    $description = $_POST['description'];
    $created_at = date('Y-m-d H:i:s');

    // === EMPLOYEE IMAGE UPLOAD ===
    $emp_img_path = "";
    if (!empty($_POST['cropped_emp_image'])) {
        // Save cropped image (base64)
        $croppedData = $_POST['cropped_emp_image'];
        $croppedData = str_replace('data:image/png;base64,', '', $croppedData);
        $croppedData = str_replace(' ', '+', $croppedData);
        $imageData   = base64_decode($croppedData);

        $emp_img_path = "employeemonth/" . time() . "_cropped.png";
        file_put_contents($emp_img_path, $imageData);

    } elseif (!empty($_FILES['employee_image']['name'])) {
        // Save uploaded file
        $emp_img     = $_FILES['employee_image']['name'];
        $emp_tmp     = $_FILES['employee_image']['tmp_name'];
        $emp_img_path = "employeemonth/" . time() . "_" . basename($emp_img);
        move_uploaded_file($emp_tmp, $emp_img_path);
    }


    // === TEMPLATE IMAGE UPLOAD (OPTIONAL) ===
    if (!empty($_FILES['template_image']['name'])) {
        $template_img = $_FILES['template_image']['name'];
        $template_tmp = $_FILES['template_image']['tmp_name'];
        $template_img_path = "assets/images/template/work/" . time() . "_" . basename($template_img);
        move_uploaded_file($template_tmp, $template_img_path);

        // Overwrite default template if needed
        copy($template_img_path, "assets/images/template/work/default_template.jpg");
    } else {
        $template_img_path = "http://leadmatrix.technocoderz.com/assets/images/template/work/template.jpg"; // fallback
    }

    // === DATABASE INSERTION ===
    $stmt = $db->prepare("INSERT INTO work_anniversary (name, designation, employee_image, template_image, status,work_year,description, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iississs", $emp_name, $emp_designation, $emp_img_path, $template_img_path, $status,$year,$description, $created_at);

    if ($stmt->execute()) {
        $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible fade show' role='alert' style='font-size:16px;' id='goldmessage'>
<strong><i class='feather icon-check'></i> Thanks!</strong> Work Anniversary added successfully.
<button type='button' class='close' data-dismiss='alert' aria-label='Close'>
  <span aria-hidden='true'>&times;</span>
</button>
</div>";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();;
    } else {
        $_SESSION['msg'] = "
        <div class='alert alert-danger alert-dismissible fade show' role='alert' style='font-size:16px;' id='goldmessage'>
            <strong><i class='feather icon-x'></i> Error!</strong> Failed to add Work Anniversary. " . $stmt->error . "
            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
              <span aria-hidden='true'>&times;</span>
            </button>
        </div>";
    }
}
// Fetch position from the database
$positionquery = "SELECT position_id, name FROM position WHERE status = 1";
$positionresult = mysqli_query($db, $positionquery);

$personalquery = "SELECT 
    p.*,
    c.*
FROM personal_details p
LEFT JOIN company_details c ON p.personal_id = c.user_id
WHERE c.employee_status = 1";
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
    <title>Add Work Anniversary</title>
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
                                <h5 class="m-b-10">Add Work Anniversary</h5>
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
                                    <!-- Template Image -->
                                    <div class="col-md-6">
                                        <label class="form-label">Template Image
                                            <small>(Leave empty to use default)</small>
                                        </label>
                                        <input type="file" name="template_image" class="form-control" accept="image/*" disabled>

                                        <?php
                                        // Set default image path
                                        $defaultImagePath = 'assets/images/template/work/default_template.jpg';

                                        // Query to fetch the most recent uploaded template image
                                        $selectExistingImagesQuery = "SELECT template_image FROM work_anniversary WHERE template_image IS NOT NULL AND template_image != '' ORDER BY work_id desc LIMIT 1";
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
                                            <input type="file" name="employee_image" id="employee_image" class="form-control" accept="image/*" required>
                                        </div>
                                    </div>

                                    <!-- Crop Modal -->
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


                                   
                                   <!-- Select Employee -->
                                    <div class="col-md-6">
                                        <label class="form-label">Select Employee <span class="text-danger">*</span></label>
                                        <select name="name" id="employee_id" class="form-control" required>

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
                                    <div class="col-md-6">
                                        <label class="form-label">Designation <span class="text-danger">*</span></label>
                                       <select name="designation" id="designation" class="form-control" required readonly>

    <option value="" disabled selected>Choose Designation</option>
    <?php
    while ($row = mysqli_fetch_assoc($positionresult)) {
        echo "<option value='{$row['position_id']}'>{$row['name']}</option>";
    }
    ?>
</select>

                                    </div>
                                    
                                    <div class="col-md-6 mt-4">
                                        <label for="work_year" class="form-label">Work Year <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="work_year" placeholder="Enter Year E.g. 1" required>
                                    </div>
                                    <div class="col-md-6 mt-4">
                                        <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="description" placeholder="Enter description" required>
                                    </div>
                                    <!-- Status -->
                                    <div class="col-md-6 mt-4">
                                        <label class="form-label">Status <span class="text-danger">*</span></label>
                                        <select name="status" class="form-control" required>
                                            <option value="1" selected>Enable</option>
                                            <option value="0">Disable</option>
                                        </select>
                                    </div>

                                    <!-- Submit -->
                                    <div class="col-md-12 mt-3">
                                        <button type="submit" class="btn btn-secondary" name="submit">
                                            <i class="feather icon-save"></i> Add Work Anniversary
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
      <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>


    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
     <script>
document.getElementById('employee_id').addEventListener('change', function () {
    var empId = this.value;
    console.log("Selected Employee ID:", empId);

    if (!empId) return;

    fetch("get-employee-designation.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: "employee_id=" + empId
    })
    .then(response => response.text()) // 👈 pehle text dekhenge
    .then(result => {
        console.log("Server response:", result);

        let data = JSON.parse(result);

        if (data.designation_id) {
            document.getElementById('designation').value = data.designation_id;
        } else {
            document.getElementById('designation').value = "";
        }
    })
    .catch(error => console.error("Error:", error));
});
</script>

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
                preview.src = 'http://leadmatrix.technocoderz.com/http://leadmatrix.technocoderz.com/assets/images/template/work/template.jpg';
            }
        }
    </script>

</body>

</html>