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
    // Get form values
    $employee_id = $_POST['employee_id'];
    $status = $_POST['status'];

    // Default image paths (if user doesn’t upload new templates)
    $defaultFrontPath = 'assets/images/template/idcard/default_front.png';
    $defaultBackPath = 'assets/images/template/idcard/default_back.png';

    // Directory to store uploaded templates
    $uploadDir = 'idcard_templates/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Handle front template image upload
    $template_front = $defaultFrontPath;
    if (!empty($_FILES['template_front']['name'])) {
        $frontName = time() . '_front_' . basename($_FILES['template_front']['name']);
        $frontPath = $uploadDir . $frontName;

        if (move_uploaded_file($_FILES['template_front']['tmp_name'], $frontPath)) {
            $template_front = $frontPath;
        }
    }

    // Handle back template image upload
    $template_back = $defaultBackPath;
    if (!empty($_FILES['template_back']['name'])) {
        $backName = time() . '_back_' . basename($_FILES['template_back']['name']);
        $backPath = $uploadDir . $backName;

        if (move_uploaded_file($_FILES['template_back']['tmp_name'], $backPath)) {
            $template_back = $backPath;
        }
    }

    // Handle employee photo upload
    $employee_image = null;
    if (!empty($_FILES['employee_image']['name'])) {
        $empName = time() . '_emp_' . basename($_FILES['employee_image']['name']);
        $empPath = 'employeeimage/' . $empName;
        if (!file_exists('employeeimage/')) {
            mkdir('employeeimage/', 0777, true);
        }

        if (move_uploaded_file($_FILES['employee_image']['tmp_name'], $empPath)) {
            $employee_image = $empPath;
        }
    }

    // Fetch employee details for display (optional, you can inject them into the ID later)
    $empQuery = "
        SELECT 
            p.name, p.address, p.emergency_no, p.photo,
            c.designation, c.employee_code
        FROM personal_details p
        LEFT JOIN company_details c ON p.personal_id = c.user_id
        WHERE p.personal_id = ?
    ";
    $stmt = $db->prepare($empQuery);
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $empResult = $stmt->get_result();
    $employee = $empResult->fetch_assoc();

    if (!$employee) {
        $_SESSION['msg'] = '<div class="alert alert-danger">Employee not found!</div>';
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // Insert into id_cards table
    $insertQuery = "
        INSERT INTO id_cards (employee_id, template_front, template_back, status, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ";
    $stmt = $db->prepare($insertQuery);
    $stmt->bind_param("issi", $employee_id, $template_front, $template_back, $status);

    if ($stmt->execute()) {
        $_SESSION['msg'] = '<div class="alert alert-success">ID Card Template added successfully!</div>';
    } else {
        $_SESSION['msg'] = '<div class="alert alert-danger">Error saving ID Card Template.</div>';
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
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


                            <form method="post" action="" enctype="multipart/form-data" autocomplete="off">
                                <?php
                                if (isset($_SESSION['msg'])) {
                                    echo $_SESSION['msg'];
                                    unset($_SESSION['msg']); // Clear after showing
                                } ?>
                                <div class="row">
                                    <!-- Front Template Image -->
                                    <div class="col-md-6">
                                        <label class="form-label">Front Side Template
                                            <small>(Leave empty to use default)</small>
                                        </label>
                                        <input type="file" name="template_front" class="form-control" accept="image/*" disabled>

                                        <?php
                                        // Default image paths
                                        $defaultFrontPath = 'assets/images/template/idcard/default_front.png';
                                        $defaultBackPath = 'assets/images/template/idcard/default_back.png';

                                        // Fetch latest uploaded templates (if any)
                                        $query = "
                                            SELECT template_front, template_back 
                                            FROM idcard_templates 
                                            WHERE (template_front IS NOT NULL OR template_back IS NOT NULL)
                                            ORDER BY id DESC 
                                            LIMIT 1
                                        ";
                                        $result = mysqli_query($db, $query);

                                        $frontImagePath = $defaultFrontPath;
                                        $backImagePath = $defaultBackPath;

                                        if ($result && mysqli_num_rows($result) > 0) {
                                            $row = mysqli_fetch_assoc($result);

                                            if (!empty($row['template_front']) && file_exists($row['template_front'])) {
                                                $frontImagePath = $row['template_front'];
                                            }

                                            if (!empty($row['template_back']) && file_exists($row['template_back'])) {
                                                $backImagePath = $row['template_back'];
                                            }
                                        }
                                        ?>

                                        <!-- Preview Front -->
                                        <div class="form-group mt-3">
                                            <label>Current Front Template</label>
                                            <div class="row">
                                                <div class="col-md-12 mb-3">
                                                    <img src="<?= $frontImagePath ?>" class="img-fluid"
                                                        style="height:100px; width:100px; object-fit:cover; border:1px solid #ddd;">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Back Template Image -->
                                    <div class="col-md-6">
                                        <label class="form-label">Back Side Template
                                            <small>(Leave empty to use default)</small>
                                        </label>
                                        <input type="file" name="template_back" class="form-control" accept="image/*" disabled>

                                        <!-- Preview Back -->
                                        <div class="form-group mt-3">
                                            <label>Current Back Template</label>
                                            <div class="row">
                                                <div class="col-md-12 mb-3">
                                                    <img src="<?= $backImagePath ?>" class="img-fluid"
                                                        style="height:100px; width:100px; object-fit:cover; border:1px solid #ddd;">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                              

                                <!-- Select Employee -->
                                <div class="col-md-6 mt-3">
                                    <label class="form-label">Select Employee <span class="text-danger">*</span></label>
                                    <select name="employee_id" id="employee_id" class="form-control" required>
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

                                <!-- Employee Details Section -->
                                <div class="col-md-6 mt-3">
                                    <label class="form-label">Employee Image</label><br>
                                     <!-- <input type="file" name="employee_image" class="form-control" accept="image/*"> -->
                                    <img id="employee_image" class="mt-3" src="" alt="Employee Image" width="100" style="display:none; border-radius:8px; border:1px solid #ccc;">
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
                                        <i class="feather icon-save"></i> Add I'd Card
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
                preview.src = 'assets/images/template/template.png';
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