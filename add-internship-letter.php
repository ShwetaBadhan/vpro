<?php
session_start();
error_reporting(E_ALL);

if (!isset($_SESSION["login_user"])) {
    header("location: index.php");
    exit;
}

include("db/config.php");

$msg = "";

if (isset($_POST['submit'])) {
    $student_name = $_POST['student_name'];
    $designation = $_POST['designation'];
    $date_from = $_POST['date_from'];
    $date_to = !empty($_POST['date_to']) ? $_POST['date_to'] : '0000-00-00'; // ✅ Empty string instead of NULL
    $certificate = $_POST['certificate_id'];
    $training = $_POST['training_type'];
    
    // ✅ AUTO STATUS DETECTION
    if (!empty($_POST['date_to']) && $_POST['date_to'] !== '0000-00-00') {
        $status = 'Successfully Completed';
    } else {
        $status = 'Currently Pursuing';
    }
    
    $created_at = date('Y-m-d H:i:s');

    // ✅ DATABASE INSERTION WITH STATUS
    $stmt = $db->prepare("INSERT INTO internship_certificate (student_name, designation, date_from, date_to, certificate_id, training_type, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss", $student_name, $designation, $date_from, $date_to, $certificate, $training, $status, $created_at);

    if ($stmt->execute()) {
        $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible fade show' role='alert' style='font-size:16px;' id='goldmessage'>
            <strong><i class='feather icon-check'></i> Thanks!</strong> Internship Letter added successfully with status: <b>$status</b>.
            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                <span aria-hidden='true'>&times;</span>
            </button>
        </div>";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible fade show' role='alert' style='font-size:16px;' id='goldmessage'>
            <strong><i class='feather icon-x'></i> Error!</strong> Failed to add Internship Letter. " . $stmt->error . "
            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                <span aria-hidden='true'>&times;</span>
            </button>
        </div>";
    }
    $stmt->close();
}

$query = "SELECT * FROM login_settings";
$settingsResult = mysqli_query($db, $query);
$settings = mysqli_fetch_assoc($settingsResult);
$favicon = $settings['favicon'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add Internship Letter</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="<?php echo $favicon; ?>" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/plugins/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .red-text { color: red; }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            margin-top: 10px;
        }
        .status-completed {
            background: #28a745;
            color: white;
        }
        .status-pursuing {
            background: #ffc107;
            color: #000;
        }
    </style>
</head>
<body>
    <div class="loader-bg">
        <div class="loader-track">
            <div class="loader-fill"></div>
        </div>
    </div>

    <?php include("header.php"); ?>
    <?php include("navbar.php"); ?>

    <section class="pcoded-main-container">
        <div class="pcoded-content">
            <div class="page-header">
                <div class="page-block">
                    <div class="row align-items-center">
                        <div class="col-md-12">
                            <div class="page-header-title">
                                <h5 class="m-b-10">Add Internship Letter</h5>
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
                            if (isset($_SESSION['msg'])) {
                                echo $_SESSION['msg'];
                                unset($_SESSION['msg']);
                            }
                            ?>
                            <br />
                            
                          
                            <form method="post" action="" enctype="multipart/form-data" autocomplete="off">
                                <div class="row">
                                    <div class="col-md-6 mb-4">
                                        <label class="form-label">Certificate No <span class="text-danger">*</span></label>
                                        <input type="text" name="certificate_id" class="form-control" placeholder="Enter Certificate No." required>
                                    </div>
                                    
                                    <div class="col-md-6 mb-4">
                                        <label class="form-label">Training Type <span class="text-danger">*</span></label>
                                        <input type="text" name="training_type" class="form-control" placeholder="Enter Training Type" required>
                                    </div>

                                    <div class="col-md-6 mb-4">
                                        <label class="form-label">Name <span class="text-danger">*</span></label>
                                        <input type="text" name="student_name" class="form-control" placeholder="Enter Name" required>
                                    </div>

                                    <div class="col-md-6 mb-4">
                                        <label class="form-label">Designation <span class="text-danger">*</span></label>
                                        <input type="text" name="designation" class="form-control" placeholder="Enter Designation" required>
                                    </div>

                                    <div class="col-md-6 mb-4">
                                        <label class="form-label">Date From <span class="text-danger">*</span></label>
                                        <input type="date" name="date_from" class="form-control" required>
                                    </div>

                                    <div class="col-md-6 mb-4">
                                        <label class="form-label">Date To <span class="text-muted">(Optional - Leave blank if currently pursuing)</span></label>
                                        <input type="date" name="date_to" class="form-control" id="date_to">
                                        <small class="text-muted">
                                          
                                          
                                        </small>
                                    </div>

                                  

                                    <div class="col-md-12 mt-3">
                                        <button type="submit" class="btn btn-secondary" name="submit">
                                            <i class="feather icon-save"></i> Add Internship
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="assets/js/vendor-all.min.js"></script>
    <script src="assets/js/plugins/bootstrap.min.js"></script>
    <script src="assets/js/pcoded.min.js"></script>
    
    <!-- ✅ LIVE STATUS PREVIEW SCRIPT -->
    <script>
        $(document).ready(function() {
            function updateStatusPreview() {
                var dateTo = $('#date_to').val();
                var statusDiv = $('#statusPreview');
                
                if (dateTo && dateTo.trim() !== '') {
                    statusDiv.html('<span class="status-badge status-completed">✓ Successfully Completed</span>');
                } else {
                    statusDiv.html('<span class="status-badge status-pursuing">⏳ Currently Pursuing</span>');
                }
            }
            
            // Update on date change
            $('#date_to').on('change', updateStatusPreview);
            
            // Initial check
            updateStatusPreview();
        });
    </script>
    
    <script>
        $(document).ready(function() {
            $("#goldmessage").delay(5000).slideUp(300);
        });
    </script>
</body>
</html>