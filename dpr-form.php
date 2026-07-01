<?php
session_start();

if (!isset($_SESSION["login_user"])) {
    header("location: index.php");
    exit();
}

include("db/config.php");

$username = $_SESSION['login_user'];
$user_id = $_SESSION['login_user_id'] ?? 0;
$today = date('Y-m-d');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $report_date = mysqli_real_escape_string($db, $_POST['report_date']);
    $summary_notes = mysqli_real_escape_string($db, $_POST['summary_notes']);
    
    // Validate inputs
    if (empty($report_date) || empty($summary_notes)) {
        $error = "Please fill all required fields.";
    } else {
        // Check if DPR already exists for this date
        $check_sql = "SELECT id FROM daily_progress_reports WHERE user_id = $user_id AND report_date = '$report_date'";
        $check_result = mysqli_query($db, $check_sql);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error = "DPR for this date already exists. You can only submit one DPR per day.";
        } else {
            // Insert DPR record
            $insert_sql = "INSERT INTO daily_progress_reports (user_id, username, report_date, summary_notes) 
                          VALUES ($user_id, '$username', '$report_date', '$summary_notes')";
            
            if (mysqli_query($db, $insert_sql)) {
                $dpr_id = mysqli_insert_id($db);
                
                // Handle multiple screenshot uploads
                if (!empty($_FILES['screenshots']['name'][0])) {
                    $upload_dir = 'uploads/dpr-screenshots/';
                    
                    // Create directory if it doesn't exist
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $total_files = count($_FILES['screenshots']['name']);
                    $upload_success = true;
                    
                    for ($i = 0; $i < $total_files; $i++) {
                        if ($_FILES['screenshots']['error'][$i] === UPLOAD_ERR_OK) {
                            $file_name = $_FILES['screenshots']['name'][$i];
                            $file_tmp = $_FILES['screenshots']['tmp_name'][$i];
                            $file_size = $_FILES['screenshots']['size'][$i];
                            $file_type = $_FILES['screenshots']['type'][$i];
                            
                            // Validate file type
                            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                            if (!in_array($file_type, $allowed_types)) {
                                $upload_success = false;
                                $error = "Invalid file type for $file_name. Only JPG, PNG, GIF, and WEBP are allowed.";
                                break;
                            }
                            
                            // Validate file size (max 5MB)
                            if ($file_size > 5 * 1024 * 1024) {
                                $upload_success = false;
                                $error = "File $file_name is too large. Maximum size is 5MB.";
                                break;
                            }
                            
                            // Generate unique filename
                            $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
                            $new_filename = 'dpr_' . $dpr_id . '_' . time() . '_' . $i . '.' . $file_extension;
                            $upload_path = $upload_dir . $new_filename;
                            
                            // Move uploaded file
                            if (move_uploaded_file($file_tmp, $upload_path)) {
                                // Insert screenshot record
                                $screenshot_sql = "INSERT INTO dpr_screenshots (dpr_id, screenshot_path, screenshot_name) 
                                                  VALUES ($dpr_id, '$upload_path', '$file_name')";
                                mysqli_query($db, $screenshot_sql);
                            } else {
                                $upload_success = false;
                                $error = "Failed to upload $file_name.";
                                break;
                            }
                        }
                    }
                    
                    if ($upload_success) {
                        $success = "DPR submitted successfully with " . $total_files . " screenshot(s)!";
                        // Clear form
                        $_POST = array();
                    } else {
                        // Rollback - delete the DPR record
                        mysqli_query($db, "DELETE FROM daily_progress_reports WHERE id = $dpr_id");
                        // Delete uploaded files
                        $delete_screenshots = mysqli_query($db, "SELECT screenshot_path FROM dpr_screenshots WHERE dpr_id = $dpr_id");
                        while ($screenshot = mysqli_fetch_assoc($delete_screenshots)) {
                            if (file_exists($screenshot['screenshot_path'])) {
                                unlink($screenshot['screenshot_path']);
                            }
                        }
                    }
                } else {
                    // No screenshots uploaded - still save DPR
                    $success = "DPR submitted successfully!";
                    $_POST = array();
                }
            } else {
                $error = "Failed to submit DPR. Please try again.";
            }
        }
    }
}

// Fetch login settings
$query = "SELECT * FROM login_settings";
$settingsResult = mysqli_query($db, $query);
$settings = mysqli_fetch_assoc($settingsResult);
$favicon = $settings['favicon'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Daily Progress Report</title>
    <link rel="icon" href="<?php echo $favicon; ?>" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/style.css">
    
    <style>
        .dpr-form-container {
            max-width: 900px;
            margin: 30px auto;
            padding: 30px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        
        .form-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .form-header h3 {
            color: #2d3748;
            font-weight: 600;
            margin: 0;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-label {
            display: block;
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-control[readonly] {
            background: #f7fafc;
            cursor: not-allowed;
        }
        
        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }
        
        .file-upload-area {
            border: 2px dashed #cbd5e0;
            border-radius: 8px;
            padding: 40px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: #f7fafc;
        }
        
        .file-upload-area:hover {
            border-color: #667eea;
            background: #edf2f7;
        }
        
        .file-upload-area.dragover {
            border-color: #667eea;
            background: #e6fffa;
        }
        
        .upload-icon {
            font-size: 48px;
            color: #a0aec0;
            margin-bottom: 15px;
        }
        
        .upload-text {
            color: #4a5568;
            font-size: 16px;
            margin-bottom: 10px;
        }
        
        .upload-hint {
            color: #718096;
            font-size: 13px;
        }
        
        #fileInput {
            display: none;
        }
        
        .preview-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .preview-item {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            aspect-ratio: 1;
        }
        
        .preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .remove-btn {
            position: absolute;
            top: 8px;
            right: 8px;
            background: rgba(245, 87, 108, 0.9);
            color: white;
            border: none;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            transition: all 0.3s;
        }
        
        .remove-btn:hover {
            background: #f5576c;
            transform: scale(1.1);
        }
        
        .btn-submit {
            /* background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); */
            color: white;
            padding: 14px 40px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .file-count {
            margin-top: 10px;
            color: #667eea;
            font-weight: 600;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <?php include("header.php"); ?>
    <?php include("navbar.php"); ?>
    
    <section class="pcoded-main-container">
        <div class="pcoded-content">
            <div class="page-header">
                <div class="page-block">
                    <div class="row align-items-center">
                        <div class="col-md-12">
                            <div class="page-header-title">
                                <h5 class="m-b-10">Daily Progress Report</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="dpr-form-container">
                <div class="form-header">
                    <h3>Submit Your Daily Progress Report</h3>
                </div>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success">
                        <i class="feather icon-check-circle"></i>
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <i class="feather icon-alert-circle"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data" id="dprForm">
                    <div class="form-group">
                        <label class="form-label">User Name</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($username); ?>" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Date <span style="color: #f56565;">*</span></label>
                        <input type="date" name="report_date" class="form-control" value="<?php echo $today; ?>" max="<?php echo $today; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Screenshots (Multiple)</label>
                        <div class="file-upload-area" id="uploadArea">
                            <div class="upload-icon">
                                <i class="feather icon-upload-cloud"></i>
                            </div>
                            <div class="upload-text">Click to upload or drag and drop</div>
                            <div class="upload-hint">PNG, JPG, GIF, WEBP up to 5MB each</div>
                            <input type="file" name="screenshots[]" id="fileInput" multiple accept="image/*">
                            <div class="file-count" id="fileCount"></div>
                        </div>
                        <div class="preview-container" id="previewContainer"></div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Summary Notes <span style="color: #f56565;">*</span></label>
                        <textarea name="summary_notes" class="form-control" placeholder="Describe your work progress for the day..." required><?php echo isset($_POST['summary_notes']) ? htmlspecialchars($_POST['summary_notes']) : ''; ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn-submit btn-primary" id="submitBtn">
                        <i class="feather icon-send"></i> Submit DPR
                    </button>
                </form>
            </div>
        </div>
    </section>
    
    <script src="assets/js/vendor-all.min.js"></script>
    <script src="assets/js/plugins/bootstrap.min.js"></script>
    <script src="assets/js/pcoded.min.js"></script>
    
    <script>
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('fileInput');
        const previewContainer = document.getElementById('previewContainer');
        const fileCount = document.getElementById('fileCount');
        let selectedFiles = [];
        
        // Click to upload
        uploadArea.addEventListener('click', () => fileInput.click());
        
        // Drag and drop
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            handleFiles(e.dataTransfer.files);
        });
        
        // File input change
        fileInput.addEventListener('change', (e) => {
            handleFiles(e.target.files);
        });
        
        function handleFiles(files) {
            const newFiles = Array.from(files);
            
            newFiles.forEach(file => {
                if (!file.type.startsWith('image/')) {
                    alert(`${file.name} is not an image file.`);
                    return;
                }
                
                if (file.size > 5 * 1024 * 1024) {
                    alert(`${file.name} is too large. Maximum size is 5MB.`);
                    return;
                }
                
                selectedFiles.push(file);
                displayPreview(file, selectedFiles.length - 1);
            });
            
            updateFileInput();
            updateFileCount();
        }
        
        function displayPreview(file, index) {
            const reader = new FileReader();
            reader.onload = (e) => {
                const previewItem = document.createElement('div');
                previewItem.className = 'preview-item';
                previewItem.dataset.index = index;
                
                previewItem.innerHTML = `
                    <img src="${e.target.result}" alt="${file.name}">
                    <button type="button" class="remove-btn" onclick="removeFile(${index})">×</button>
                `;
                
                previewContainer.appendChild(previewItem);
            };
            reader.readAsDataURL(file);
        }
        
        function removeFile(index) {
            selectedFiles.splice(index, 1);
            updatePreview();
            updateFileInput();
            updateFileCount();
        }
        
        function updatePreview() {
            previewContainer.innerHTML = '';
            selectedFiles.forEach((file, index) => {
                displayPreview(file, index);
            });
        }
        
        function updateFileInput() {
            const dataTransfer = new DataTransfer();
            selectedFiles.forEach(file => dataTransfer.items.add(file));
            fileInput.files = dataTransfer.files;
        }
        
        function updateFileCount() {
            const count = selectedFiles.length;
            if (count > 0) {
                fileCount.textContent = `${count} file${count > 1 ? 's' : ''} selected`;
            } else {
                fileCount.textContent = '';
            }
        }
        
        // Form validation
        document.getElementById('dprForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="feather icon-loader"></i> Submitting...';
        });
    </script>
</body>
</html>