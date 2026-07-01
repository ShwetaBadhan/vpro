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
$existingDesignation = "";
$existingEmpImage = "";
$exisitngTemplate = "";
$exisitngDesc = "";
$existingStatus = "";

$monthId = null;
$isEditMode = false;

// ================= FETCH IF EDIT ==================
if (isset($_GET['id'])) {
    $encodedmonthId = $_GET['id'];
    $monthId = base64_decode($encodedmonthId);

    if (!is_numeric($monthId)) {
        echo "Invalid ID!";
        exit;
    }

    $query = "SELECT * FROM work_anniversary WHERE work_id = $monthId";
    $result = mysqli_query($db, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);

        $existingName = $row['name'];
        $existingDesignation = $row['designation'];
        $exisitngDesc = $row['description'];
        $existingEmpImage = $row['employee_image'];
        $exisitngTemplate = $row['template_image'];
        $existingStatus = $row['status'];
        $existingYear = $row['work_year'];
        $isEditMode = true;
    } else {
        echo "Work Anniversary not found!";
        exit;
    }
}

// ================= FORM SUBMISSION ==================
if (isset($_POST['submit'])) {
    // ====== Basic form values ======
    $emp_name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $emp_designation = isset($_POST['designation']) ? trim($_POST['designation']) : '';
    $desc = isset($_POST['description']) ? trim($_POST['description']) : '';
    $status = isset($_POST['status']) ? (int)$_POST['status'] : 0;
    $year = isset($_POST['work_year']) ? (int)$_POST['work_year'] : null;
    $created_at = date('Y-m-d H:i:s');

    // ====== Existing paths (for edit) - ensure these variables are set earlier when loading the form ======
    $empImgPath = !empty($existingEmpImage) ? $existingEmpImage : '';
    $templateImgPath = !empty($existingTemplate) ? $existingTemplate : 'http://leadmatrix.technocoderz.com/assets/images/template/work/template.jpg';

    // ====== Handle cropped base64 image (if provided) ======
    if (!empty($_POST['cropped_emp_image'])) {
        $croppedData = $_POST['cropped_emp_image'];
        $croppedData = preg_replace('#^data:image/\w+;base64,#i', '', $croppedData);
        $imageData = base64_decode($croppedData);
        if ($imageData !== false) {
            if (!is_dir('work/')) mkdir('work/', 0755, true);
            $empImgPath = 'work/' . time() . '_cropped.png';
            file_put_contents($empImgPath, $imageData);
        }
    }
    // ====== Or handle standard file upload for employee image ======
    elseif (!empty($_FILES['employee_image']['name']) && $_FILES['employee_image']['error'] === UPLOAD_ERR_OK) {
        if (!is_dir('work/')) mkdir('work/', 0755, true);
        $empTmp = $_FILES['employee_image']['tmp_name'];
        $empNameOnServer = time() . '_' . basename($_FILES['employee_image']['name']);
        $empImgPath = 'work/' . $empNameOnServer;
        move_uploaded_file($empTmp, $empImgPath);
    }

    // ====== Template image upload (optional) ======
    if (!empty($_FILES['template_image']['name']) && $_FILES['template_image']['error'] === UPLOAD_ERR_OK) {
        if (!is_dir('assets/images/template/work/')) mkdir('assets/images/template/work/', 0755, true);
        $templateTmp = $_FILES['template_image']['tmp_name'];
        $templateNameOnServer = time() . '_' . basename($_FILES['template_image']['name']);
        $templateImgPath = 'assets/images/template/work/' . $templateNameOnServer;
        move_uploaded_file($templateTmp, $templateImgPath);
        // optional: overwrite default
        copy($templateImgPath, 'assets/images/template/work/default_template.jpg');
    }

    // ====== EDIT or INSERT ======
    if ($isEditMode) {
        // ensure $monthId is integer and defined
        $monthId = (int)$monthId;

        $sql = "UPDATE work_anniversary 
                SET name = ?, designation = ?, employee_image = ?, template_image = ?, work_year = ?, description = ?, status = ?
                WHERE work_id = ?";
        $stmt = $db->prepare($sql);
        if (!$stmt) {
            echo "Prepare failed: " . $db->error;
            exit;
        }

        // types: name(s), designation(s), employee_image(s), template_image(s), work_year(i), description(s), status(i), work_id(i)
        $stmt->bind_param("ssssisii", $emp_name, $emp_designation, $empImgPath, $templateImgPath, $year, $desc, $status, $monthId);

        if ($stmt->execute()) {
            header("Location: manage-work-anniversary.php?status=" . base64_encode(1));
            exit;
        } else {
            echo "Update error: " . $stmt->error;
        }
    } else {
        $sql = "INSERT INTO work_anniversary (name, designation, employee_image, template_image, status, work_year, description, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        if (!$stmt) {
            echo "Prepare failed: " . $db->error;
            exit;
        }

        // types: name(s), designation(s), employee_image(s), template_image(s), status(i), work_year(i), description(s), created_at(s)
        $stmt->bind_param("ssssiiss", $emp_name, $emp_designation, $empImgPath, $templateImgPath, $status, $year, $desc, $created_at);

        if ($stmt->execute()) {
            header("Location: add-work-anniversary.php?success=1");
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
    <title><?php echo isset($_GET['id']) ? 'Update' : 'Add'; ?> All Work Anniversary</title>
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
     <style>
        #cropImage {
            border: 2px dashed #00ff99;
            background: #222;
            box-shadow: 0 0 15px rgba(0, 255, 153, 0.5);
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" />


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
                                <h5 class="m-b-10"><?php echo isset($_GET['id']) ? 'Update' : 'Add'; ?> All Work Anniversary
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
                                        $selectExistingImagesQuery = "SELECT template_image FROM work_anniversary ORDER BY work_id DESC LIMIT 1";
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
                                        <select name="name" id="employee_id" class="form-control" required>
    <option value="" disabled selected>Choose Employee</option>
    <?php
    while ($row = mysqli_fetch_assoc($personalresult)) {
        $selected = ($row['personal_id'] == $existingName) ? "selected" : "";
        echo "<option value='{$row['personal_id']}' {$selected}>{$row['name']}</option>";
    }
    ?>
</select>

                                    </div>



                                   <!-- Text input for Course Name -->
                                    <div class="col-xl-6 col-lg-3 col-md-12 col-sm-12 col-12">
                                        <div class="form-group">
                                            <label for="designation" class="form-label">Select Designation <span class="red-text">*</span></label>
                                           <select name="designation" id="designation" class="form-control" required readonly>
    <option value="" disabled>Choose Designation</option>
    <?php
    mysqli_data_seek($positionresult, 0);
    while ($row = mysqli_fetch_assoc($positionresult)) {
        $selected = ((string)$row['position_id'] === (string)$existingDesignation) ? "selected" : "";
        echo "<option value='{$row['position_id']}' {$selected}>{$row['name']}</option>";
    }
    ?>
</select>


                                        </div>

                                    </div>

                                    <div class="col-md-6 mt-4">
                                        <label for="work_year" class="form-label">Work Year <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="work_year" placeholder="Enter Year E.g. 1st" required value="<?php echo $existingYear ?>">
                                    </div>
  <div class="col-md-6 mt-4">
                                        <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="description" placeholder="Enter description" value="<?php echo $exisitngDesc ?>">
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
                                            <i class="feather icon-save"></i> Update Anniversary
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
     <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>


    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
     <script>
document.getElementById('employee_id').addEventListener('change', function () {
    let empId = this.value;
    if (!empId) return;

    fetch("get-employee-designation.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "employee_id=" + empId
    })
    .then(res => res.json())
    .then(data => {
        if (data.designation_id) {
            document.getElementById('designation').value = data.designation_id;
        }
    });
});
</script>
    <script>
        let cropper;
        const empImageInput = document.getElementById('employee_image');
        const cropModal = new bootstrap.Modal(document.getElementById('cropModal'));
        const cropImage = document.getElementById('cropImage');
        const cropBtn = document.getElementById('cropBtn');
        const croppedInput = document.getElementById('cropped_emp_image');

        // Show crop modal when new image selected
        empImageInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = () => {
                    cropImage.src = reader.result;
                    cropModal.show();

                    if (cropper) {
                        cropper.destroy();
                    }

                    cropper = new Cropper(cropImage, {
                        aspectRatio: 1,
                        viewMode: 1,
                        responsive: true
                    });
                };
                reader.readAsDataURL(file);
            }
        });

        // Crop & Save
        cropBtn.addEventListener('click', () => {
            if (cropper) {
                const canvas = cropper.getCroppedCanvas({
                    width: 300,
                    height: 300,
                });
                const croppedImage = canvas.toDataURL('image/png');
                croppedInput.value = croppedImage; // Hidden input me base64 save hoga
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