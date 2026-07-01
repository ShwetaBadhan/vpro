<?php
session_start();

include("db/config.php");
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION["login_user"])) {
    header("location: index.php");
    exit;
}

// Initialize default values
$existingName = "";
$existingDesignation = "";
$exisitingDateTo = "";
$exisitingDateFrom = "";
$exisitingCertificate = "";
$exisitngTrainig = "";
$existingStatus = "Currently Pursuing";
$internId = null;
$isEditMode = false;

// ================= FETCH IF EDIT ==================
if (isset($_GET['id'])) {
    $encodedInternId = $_GET['id'];
    $internId = base64_decode($encodedInternId);

    if (!is_numeric($internId)) {
        echo "Invalid ID!";
        exit;
    }

    $query = "SELECT * FROM internship_certificate WHERE internship_id = $internId";
    $result = mysqli_query($db, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);

        $existingName = $row['student_name'];
        $existingDesignation = $row['designation'];
        $exisitingDateTo = $row['date_to'];
        $exisitingDateFrom = $row['date_from']; 
        $exisitingCertificate = $row['certificate_id'];
        $exisitngTrainig = $row['training_type'];
        $existingStatus = $row['status'] ?? 'Currently Pursuing';
        $isEditMode = true;
      
    } else {
        echo "Internship Letter not found!";
        exit;
    }
}

// ================= FORM SUBMISSION ==================
if (isset($_POST['submit'])) {
    $student_name = $_POST['student_name'];
    $designation = $_POST['designation'];
    $date_from = $_POST['date_from'];
    $date_to = !empty($_POST['date_to']) ? $_POST['date_to'] : null;
    $certificate = $_POST['certificate_id'];
    $training = $_POST['training_type'];
    $created_at = date('Y-m-d H:i:s');

    // ✅ AUTO STATUS DETECTION
    if (!empty($_POST['date_to'])) {
        $status = 'Successfully Completed';
    } else {
        $status = 'Currently Pursuing';
    }

    // ================= UPDATE ==================
    if ($isEditMode) {
        $stmt = $db->prepare("UPDATE internship_certificate 
                              SET student_name = ?, 
                                  designation = ?, 
                                  date_from = ?,
                                  date_to = ?,
                                  certificate_id = ?,
                                  training_type = ?,
                                  status = ?
                              WHERE internship_id = ?");
        
        $stmt->bind_param("sssssssi", $student_name, $designation, $date_from, $date_to, $certificate, $training, $status, $internId);

        if ($stmt->execute()) {
            header("Location: manage-internship-letters.php?status=" . base64_encode(1));
            exit;
        } else {
            echo "Update error: " . $stmt->error;
        }
        $stmt->close();

    // ================= INSERT ==================
    } else {
        $stmt = $db->prepare("INSERT INTO internship_certificate (student_name, designation, date_from, date_to, certificate_id, training_type, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $student_name, $designation, $date_from, $date_to, $certificate, $training, $status, $created_at);

        if ($stmt->execute()) {
            header("Location: manage-internship-letters.php?status=" . base64_encode(1));
            exit;
        } else {
            echo "Insert error: " . $stmt->error;
        }
        $stmt->close();
    }
}

$query = "SELECT * FROM login_settings";
$settingsResult = mysqli_query($db, $query);
$settings = mysqli_fetch_assoc($settingsResult);
$favicon = $settings['favicon'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo isset($_GET['id']) ? 'Update' : 'Add'; ?> Internship Letter</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="<?php echo $favicon; ?>" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/plugins/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .red-text { color: red; }
        .status-badge {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
            margin-top: 5px;
        }
        .status-completed {
            background: #28a745;
            color: white;
        }
        .status-pursuing {
            background: #ffc107;
            color: #000;
        }
        .status-info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
    </style>
</head>
<body class="">
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
                                <h5 class="m-b-10"><?php echo isset($_GET['id']) ? 'Update' : 'Add'; ?> Internship Letter</h5>
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
                                <div class="row">
                                    <div class="col-md-6 mb-4">
                                        <label class="form-label">Certificate No <span class="text-danger">*</span></label>
                                        <input type="text" name="certificate_id" class="form-control" value="<?php echo htmlspecialchars($exisitingCertificate); ?>" required>
                                    </div>

                                    <div class="col-md-6 mb-4">
                                        <label class="form-label">Name <span class="text-danger">*</span></label>
                                        <input type="text" name="student_name" class="form-control" value="<?php echo htmlspecialchars($existingName); ?>" required>
                                    </div> 

                                    <div class="col-md-6 mb-4">
                                        <label class="form-label">Training Type <span class="text-danger">*</span></label>
                                        <input type="text" name="training_type" class="form-control" value="<?php echo htmlspecialchars($exisitngTrainig); ?>" required>
                                    </div> 

                                    <div class="col-md-6 mb-4">
                                        <label class="form-label">Designation <span class="text-danger">*</span></label>
                                        <input type="text" name="designation" class="form-control" value="<?php echo htmlspecialchars($existingDesignation); ?>" required>
                                    </div> 

                                    <div class="col-md-6 mb-4">
                                        <label class="form-label">Date From <span class="text-danger">*</span></label>
                                        <input type="date" name="date_from" class="form-control" value="<?php echo $exisitingDateFrom; ?>" required>
                                    </div>

                                    <div class="col-md-6 mb-4">
                                        <label class="form-label">Date To <span class="text-muted">(Optional - Leave blank if currently pursuing)</span></label>
                                        <input type="date" name="date_to" class="form-control" id="date_to" value="<?php echo ($exisitingDateTo == '0000-00-00' || empty($exisitingDateTo)) ? '' : $exisitingDateTo; ?>">
                                       
                                    </div>


                                    <!-- Submit -->
                                    <div class="col-md-12 mt-3">
                                        <button type="submit" class="btn btn-secondary" name="submit">
                                            <i class="feather icon-save"></i> <?php echo $isEditMode ? 'Update' : 'Add'; ?> Internship Letter
                                        </button>
                                        <a href="manage-internship-letters.php" class="btn btn-danger">
                                            <i class="feather icon-x"></i> Cancel
                                        </a>
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