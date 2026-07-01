<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION["login_user"])) {
    header("location: index.php");
    exit;
}

include('db/config.php');

if (!$db || mysqli_connect_errno()) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Settings fetch
$query = "SELECT * FROM login_settings LIMIT 1";
$settingsResult = mysqli_query($db, $query);
$settings = $settingsResult ? mysqli_fetch_assoc($settingsResult) : [];
$favicon = $settings['favicon'] ?? 'assets/images/favicon.ico';

// Handle Form Submission
if (isset($_POST['submit'])) {
    $letter_type = $_POST['letter_type'] ?? '';
    $description = $_POST['client_description'] ?? '';
    
    if (!in_array($letter_type, ['intern', 'employee'])) {
        $_SESSION['swal_msg'] = ['icon' => 'error', 'title' => 'Invalid Request', 'text' => 'Invalid letter type!', 'color' => '#dc3545'];
    } elseif (empty(trim($description))) {
        $_SESSION['swal_msg'] = ['icon' => 'warning', 'title' => 'Missing Content', 'text' => 'Description cannot be empty!', 'color' => '#ffc107'];
    } else {
        $letter_type_safe = mysqli_real_escape_string($db, $letter_type);
        $description_safe = mysqli_real_escape_string($db, $description);
        
        $checkQuery = mysqli_prepare($db, "SELECT COUNT(*) as cnt FROM offer_letter_content WHERE letter_type = ?");
        mysqli_stmt_bind_param($checkQuery, "s", $letter_type_safe);
        mysqli_stmt_execute($checkQuery);
        $checkRow = mysqli_fetch_assoc(mysqli_stmt_get_result($checkQuery));
        mysqli_stmt_close($checkQuery);
        
        if ($checkRow['cnt'] > 0) {
            $updateQuery = mysqli_prepare($db, "UPDATE offer_letter_content SET description = ?, updated_at = NOW() WHERE letter_type = ?");
            mysqli_stmt_bind_param($updateQuery, "ss", $description_safe, $letter_type_safe);
            $success = mysqli_stmt_execute($updateQuery);
            mysqli_stmt_close($updateQuery);
        } else {
            $insertQuery = mysqli_prepare($db, "INSERT INTO offer_letter_content (letter_type, description, created_at) VALUES (?, ?, NOW())");
            mysqli_stmt_bind_param($insertQuery, "ss", $letter_type_safe, $description_safe);
            $success = mysqli_stmt_execute($insertQuery);
            mysqli_stmt_close($insertQuery);
        }
        
        $message = $success ? ucfirst($letter_type) . " content saved successfully!" : "Error: " . mysqli_error($db);
        $_SESSION['swal_msg'] = [
            'icon' => $success ? 'success' : 'error',
            'title' => $success ? 'Success!' : 'Error',
            'text' => $message,
            'color' => $success ? '#28a745' : '#dc3545',
            'reload' => true
        ];
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Fetch existing content
$existingContent = [];
$contentQuery = mysqli_query($db, "SELECT letter_type, description FROM offer_letter_content WHERE letter_type IN ('intern', 'employee')");
if ($contentQuery) {
    while ($row = mysqli_fetch_assoc($contentQuery)) {
        $existingContent[$row['letter_type']] = $row['description'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add Offer Letter Content</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="<?php echo $favicon; ?>" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.0/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        .custom-btn { background-color: #192f59; color: #fff; border: 2px solid transparent; transition: all 0.2s ease; }
        .custom-btn.active { background-color: #0d6efd; border-color: #0a58ca; box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3); font-weight: 600; }
        .custom-btn:hover:not(.active) { background-color: #2c4a7a; border-color: #192f59; }
        .buttons { gap: 20px; flex-wrap: wrap; }
    </style>
</head>
<body>
    <div class="loader-bg"><div class="loader-track"><div class="loader-fill"></div></div></div>
    <?php include("header.php"); ?>
    <?php include("navbar.php"); ?>

    <section class="pcoded-main-container">
        <div class="pcoded-content">
            <div class="page-header">
                <div class="page-block">
                    <div class="row align-items-center">
                        <div class="col-md-12">
                            <div class="page-header-title">
                                <h5 class="m-b-10">Add Offer Letter Content</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header table-card-header">
                            <!-- Helpful Tip Box -->
                            <div class="alert alert-info mb-3">
                                <strong>💡 Tip:</strong> Use placeholders in your content. They will be automatically replaced when generating the letter!<br>
                                <code>[candidate_name]</code>, <code>[designation]</code>, <code>[doj]</code>, <code>[salary]</code>, <code>[reporting_manager]</code>, <code>[employee_type]</code>, <code>[offer_date]</code>, <code>[reference_no]</code>, <code>[sign]</code>
                            </div>

                            <div class="d-flex buttons mb-3" role="tablist">
                                <button class="btn custom-btn active" id="intern-tab" data-bs-toggle="tab" data-bs-target="#intern" type="button">Intern Offer Letter</button>
                                <button class="btn custom-btn" id="employee-tab" data-bs-toggle="tab" data-bs-target="#employee" type="button">Employee Offer Letter</button>
                            </div>

                            <div class="tab-content" id="letterTypeTabContent">
                                <!-- Intern Tab -->
                                <div class="tab-pane fade show active" id="intern" role="tabpanel">
                                    <form method="post" action="" id="form-intern">
                                        <input type="hidden" name="letter_type" value="intern">
                                        <div class="form-group mb-3">
                                            <label for="summernote-intern" class="form-label">Description <span class="text-danger">*</span></label>
                                            <textarea id="summernote-intern" name="client_description" class="form-control summernote-editor"><?php echo isset($existingContent['intern']) ? htmlspecialchars_decode($existingContent['intern']) : ''; ?></textarea>
                                        </div>
                                        <button type="submit" name="submit" class="btn btn-primary"><i class="feather icon-save"></i> Save Intern Content</button>
                                    </form>
                                </div>
                                
                                <!-- Employee Tab -->
                                <div class="tab-pane fade" id="employee" role="tabpanel">
                                    <form method="post" action="" id="form-employee">
                                        <input type="hidden" name="letter_type" value="employee">
                                        <div class="form-group mb-3">
                                            <label for="summernote-employee" class="form-label">Description <span class="text-danger">*</span></label>
                                            <textarea id="summernote-employee" name="client_description" class="form-control summernote-editor"><?php echo isset($existingContent['employee']) ? htmlspecialchars_decode($existingContent['employee']) : ''; ?></textarea>
                                        </div>
                                        <button type="submit" name="submit" class="btn btn-primary"><i class="feather icon-save"></i> Save Employee Content</button>
                                    </form>
                                </div>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.0/dist/sweetalert2.min.js"></script>

    <script>
        $(document).ready(function() {
            $('.summernote-editor').summernote({
                height: 300,
                toolbar: [
                    ['style', ['style']], ['font', ['bold', 'underline', 'italic', 'clear']], 
                    ['para', ['ul', 'ol', 'paragraph']], ['insert', ['link', 'picture']], ['view', ['fullscreen', 'codeview']]
                ]
            });
            
            <?php if (!empty($existingContent['intern'])): ?>
                $('#summernote-intern').summernote('code', <?php echo json_encode($existingContent['intern']); ?>);
            <?php endif; ?>
            <?php if (!empty($existingContent['employee'])): ?>
                $('#summernote-employee').summernote('code', <?php echo json_encode($existingContent['employee']); ?>);
            <?php endif; ?>
        });
    </script>

    <script>
    $(document).ready(function() {
        <?php if (isset($_SESSION['swal_msg'])): ?>
            Swal.fire({
                icon: '<?php echo $_SESSION['swal_msg']['icon']; ?>',
                title: '<?php echo $_SESSION['swal_msg']['title']; ?>',
                text: '<?php echo $_SESSION['swal_msg']['text']; ?>',
                confirmButtonColor: '<?php echo $_SESSION['swal_msg']['color'] ?? '#0d6efd'; ?>',
                timer: 2000, timerProgressBar: true
            }).then(() => { window.location.href = window.location.href; });
        <?php unset($_SESSION['swal_msg']); endif; ?>
    });
    </script>
</body>
</html>