<?php
session_start();

error_reporting(E_ALL);

if (!isset($_SESSION["login_user"])) {
    header("location: index.php");
}

include("db/config.php");

// Initialize variables
$msg = "";


$IdCardid = null;
$isEditMode = false;

// Initialize defaults
$existingEmpId = "";
$existingTempFront = "";
$existingTempBack = "";
$existingStatus = "";

// ================= FETCH IF EDIT ==================
if (isset($_GET['id'])) {
    $encodedcardId = $_GET['id'];
    $IdCardid = base64_decode($encodedcardId);

    if (!is_numeric($IdCardid)) {
        echo "Invalid ID!";
        exit;
    }

    $query = "SELECT * FROM id_cards WHERE id_card_id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $IdCardid);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $row = $result->fetch_assoc()) {
        $existingEmpId = $row['employee_id'];
        $existingTempFront = $row['template_front'];
        $existingTempBack = $row['template_back'];
        $existingStatus = $row['status'];
        $isEditMode = true;
    } else {
        echo "ID Card not found!";
        exit;
    }
}

// ================= FORM SUBMISSION ==================
if (isset($_POST['submit'])) {
    $empId = $_POST['employee_id'];
    $status = $_POST['status'];
    $created_at = date('Y-m-d H:i:s');

    // Image paths (default)
    $templateFrontPath = $existingTempFront ?: 'assets/images/template/idcard/default_front.jpeg';
    $templateBackPath = $existingTempBack ?: 'assets/images/template/idcard/default_back.jpg';

    // Upload new front template
    if (!empty($_FILES['template_front']['name'])) {
        $frontImg = $_FILES['template_front']['name'];
        $frontTmp = $_FILES['template_front']['tmp_name'];
        $templateFrontPath = "assets/images/template/idcard/" . time() . "_front_" . basename($frontImg);
        move_uploaded_file($frontTmp, $templateFrontPath);
    }

    // Upload new back template
    if (!empty($_FILES['template_back']['name'])) {
        $backImg = $_FILES['template_back']['name'];
        $backTmp = $_FILES['template_back']['tmp_name'];
        $templateBackPath = "assets/images/template/idcard/" . time() . "_back_" . basename($backImg);
        move_uploaded_file($backTmp, $templateBackPath);
    }

    // ================= UPDATE ==================
    if ($isEditMode) {
        $update = "UPDATE id_cards 
                   SET employee_id = ?, template_front = ?, template_back = ?, status = ?
                   WHERE id_card_id = ?";
        $stmt = $db->prepare($update);
        $stmt->bind_param("issii", $empId, $templateFrontPath, $templateBackPath, $status, $IdCardid);

        if ($stmt->execute()) {
            header("Location: manage-id-cards.php?update=1");
            exit;
        } else {
            echo "Update error: " . $stmt->error;
        }

    // ================= INSERT ==================
    } else {
        $insert = "INSERT INTO id_cards (employee_id, template_front, template_back, status, created_at) VALUES (?, ?, ?, ?, ?)";
        $stmt = $db->prepare($insert);
        $stmt->bind_param("issis", $empId, $templateFrontPath, $templateBackPath, $status, $created_at);

        if ($stmt->execute()) {
            header("Location: add-idcard.php?success=1");
            exit;
        } else {
            echo "Insert error: " . $stmt->error;
        }
    }
}
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
    <title>Add I'd Card</title>
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
                                <h5 class="m-b-10">Add I'd Card</h5>
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


                          <!-- ================= HTML FORM ================= -->
<form method="POST" enctype="multipart/form-data">
    <div class="row">

        <!-- Front Template -->
        <div class="col-md-6">
            <label class="form-label">Front Side Template</label>
            <input type="file" name="template_front" class="form-control" accept="image/*">

            <div class="mt-3">
                <label>Current Front Template</label><br>
                <img src="<?= $existingTempFront ?: 'assets/images/template/idcard/default_front.jpeg' ?>" 
                     class="img-fluid" style="height:100px;width:100px;object-fit:cover;border:1px solid #ddd;">
            </div>
        </div>

        <!-- Back Template -->
        <div class="col-md-6">
            <label class="form-label">Back Side Template</label>
            <input type="file" name="template_back" class="form-control" accept="image/*">

            <div class="mt-3">
                <label>Current Back Template</label><br>
                <img src="<?= $existingTempBack ?: 'assets/images/template/idcard/default_back.jpg' ?>" 
                     class="img-fluid" style="height:100px;width:100px;object-fit:cover;border:1px solid #ddd;">
            </div>
        </div>

        <!-- Employee -->
<div class="col-md-6 mt-3">
    <label class="form-label">Select Employee</label>
    <select name="employee_id" id="employee_id" class="form-control" required>
        <option value="" disabled <?= !$existingEmpId ? 'selected' : '' ?>>Choose Employee</option>
        <?php
        $empQuery = mysqli_query($db, "SELECT personal_id, name FROM personal_details");
        while ($emp = mysqli_fetch_assoc($empQuery)) {
            $selected = ($existingEmpId == $emp['personal_id']) ? 'selected' : '';
            echo "<option value='{$emp['personal_id']}' $selected>{$emp['name']}</option>";
        }
        ?>
    </select>
</div>

        <!-- Employee Image (read-only in edit mode) -->
        <div class="col-md-6 mt-3">
            <label class="form-label">Employee Image</label><br>
            <img id="employee_image"
                src="<?php
                    if ($isEditMode) {
                        $empImgQ = mysqli_query($db, "SELECT photo FROM personal_details WHERE personal_id = $existingEmpId");
                        $empData = mysqli_fetch_assoc($empImgQ);
                        echo $empData['photo'] ?: 'assets/images/no-image.png';
                    } else {
                        echo '';
                    }
                ?>"
                alt="Employee Image" width="100" style="border-radius:8px;border:1px solid #ccc;<?= !$isEditMode ? 'display:none;' : '' ?>">
        </div>

        
        <!-- Status -->
        <div class="col-md-6 mt-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-control" required>
                <option value="1" <?= $existingStatus == "1" ? "selected" : "" ?>>Enable</option>
                <option value="0" <?= $existingStatus == "0" ? "selected" : "" ?>>Disable</option>
            </select>
        </div>

        <!-- Submit -->
        <div class="col-md-12 mt-3">
            <button type="submit" name="submit" class="btn btn-secondary">
                <i class="feather icon-save"></i>
                <?= $isEditMode ? 'Update ID Card' : 'Add ID Card' ?>
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
        $('#employee_id').change(function() {
            const selectedId = $(this).val(); // ✅ define it before using
            console.log("Selected employee ID:", selectedId);
            console.log("Dropdown changed, sending request...");

            $.ajax({
                url: 'fetch_employee_data.php',
                type: 'GET',
                data: {
                    employee_id: selectedId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const emp = response.data;

                        // ✅ Fill text fields
                        $('#designation').val(emp.designation || '');
                        $('#employee_code').val(emp.employee_code || '');
                        $('#address').val(emp.address || '');
                        $('#emergency_number').val(emp.emergency_no || '');

                        // ✅ Show photo if available
                        if (emp.photo) {
                            $('#employee_image')
                                .attr('src', '' + emp.photo) // adjust folder path as per your project
                                .show();
                        } else {
                            $('#employee_image').hide();
                        }
                    } else {
                        alert(response.message);
                    }
                },


                error: function(xhr, status, error) {
                    console.error('AJAX Error:', status, error);
                    console.log(xhr.responseText);
                }
            });
        });
    </script>


</body>

</html>