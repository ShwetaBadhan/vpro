<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION["login_user"])) {
    header("location: index.php");
    exit;
}
$msg = "";
$name = $_SESSION['login_user'];
include("db/config.php");
// At the very top of your edit page, before any HTML
if (isset($_GET['id'])) {
    $encodedId = $_GET['id'];
    $edit_id = base64_decode($encodedId);

    // Validate $edit_id to ensure it's numeric and valid
    if (!is_numeric($edit_id)) {
        die("Invalid Designation ID!");
    }
    //   echo $encodedId;
    //     exit;
    // Additional security - ensure the ID exists in database
    $check_query = "SELECT position_id FROM position WHERE position_id = '$edit_id'";
    $check_result = mysqli_query($db, $check_query);

    if (mysqli_num_rows($check_result) == 0) {
        die("Designation not found or inactive!");
    }

    // Now proceed with your existing code
    $existing_duties = array();
    $query = "SELECT * FROM duties WHERE position_id = '$edit_id'";
    $result = mysqli_query($db, $query);

    while ($row = mysqli_fetch_assoc($result)) {
        $existing_duties[] = $row;
    }

    $first_duty = $existing_duties[0] ?? null;
}

// Handle form submission
if (isset($_POST['submit']) || isset($_POST['update'])) {
    // echo '<pre>';
    // print_r($_POST);
    // exit;
    $position_id = mysqli_real_escape_string($db, $_POST['position_id']);
    $status = mysqli_real_escape_string($db, $_POST['status']);
    
    // Process deletions first (only if duty_ids exist)
    if (!empty($_POST['delete_duty_ids'])) {
        foreach ($_POST['delete_duty_ids'] as $duty_id) {
            if (!empty($duty_id)) {
                $clean_duty_id = mysqli_real_escape_string($db, $duty_id);
                $delete_query = "DELETE FROM duties WHERE duty_id = '$clean_duty_id' AND position_id = '$position_id'";
                mysqli_query($db, $delete_query);
            }
        }
    }
    
    // Update existing duties
    if (!empty($_POST['duty_ids'])) {
        foreach ($_POST['duty_ids'] as $index => $duty_id) {
            // Only update if not marked for deletion and has content
            if (!empty($duty_id) && 
                !in_array($duty_id, $_POST['delete_duty_ids'] ?? []) &&
                !empty($_POST['responsibilities'][$index])) {
                
                $clean_duty_id = mysqli_real_escape_string($db, $duty_id);
                $clean_duty = mysqli_real_escape_string($db, $_POST['responsibilities'][$index]);
                
                $update_query = "UPDATE duties SET 
                                duty_name = '$clean_duty', 
                                status = '$status'
                                WHERE duty_id = '$clean_duty_id' AND position_id = '$position_id'";
                mysqli_query($db, $update_query);
            }
        }
    }
    
    // Add new responsibilities
    if (!empty($_POST['responsibilities'])) {
        foreach ($_POST['responsibilities'] as $index => $duty) {
            // Only add if:
            // 1. It's a new responsibility (no duty_id at this index)
            // 2. Not empty
            // 3. Not marked as new but actually has content
            if (empty($_POST['duty_ids'][$index]) && 
                !empty($duty) &&
                (!empty($_POST['new_responsibility'][$index]) || $index >= count($_POST['duty_ids'] ?? []))) {
                
                $clean_duty = mysqli_real_escape_string($db, $duty);
                $insert_query = "INSERT INTO duties (position_id, status, duty_name) 
                                VALUES ('$position_id', '$status', '$clean_duty')";
                mysqli_query($db, $insert_query);
            }
        }
    }
    
    $_SESSION['success_message'] = "Responsibilities updated successfully!";
    header("Location: ".$_SERVER['REQUEST_URI']);
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title><?php echo isset($_GET['id']) ? 'Update' : 'Add'; ?> </title>
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
                                <h5 class="m-b-10"><?php echo isset($_GET['id']) ? 'Update' : 'Add'; ?> Role
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
                            <?php if ($msg) echo $msg; ?>
                            <br />



                            <form method="post" action="" enctype="multipart/form-data" autocomplete="off">
                                <div class="row">
                                    <!-- Dynamic Select for Designation -->
                                    <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
                                        <div class="form-group">
                                            <label for="position_id" class="form-label">Select Designation<span class="red-text">*</span></label>
                                            <select class="form-control" id="position_id" name="position_id" required <?php echo $edit_id ? 'disabled' : ''; ?>>
                                                <option value="" disabled>Choose a Designation</option>
                                                <?php
                                                $positionquery = "SELECT position_id, name FROM position WHERE status = 1";
                                                $positionresult = mysqli_query($db, $positionquery);

                                                if (mysqli_num_rows($positionresult) > 0) {
                                                    while ($row = mysqli_fetch_assoc($positionresult)) {
                                                        $selected = '';
                                                        if ($edit_id && $row['position_id'] == $edit_id) {
                                                            $selected = 'selected';
                                                        }
                                                        echo "<option value='" . $row['position_id'] . "' $selected>" . $row['name'] . "</option>";
                                                    }
                                                } else {
                                                    echo "<option value='' disabled>No designations found</option>";
                                                }
                                                ?>
                                            </select>
                                            <?php if ($edit_id): ?>
                                    <input type="hidden" name="position_id" value="<?php echo $edit_id; ?>">
                                <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Responsibilities Column -->
                                    <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
                                        <div class="form-group">
                                            <label class="form-label">Responsibilities <span class="red-text">*</span></label>
                                             <!-- <input type="hidden" name="position_id" value="<?= $encodedId ?>"> -->
    
   
    <div id="responsibilities-container">
        <?php foreach ($existing_duties as $index => $duty): ?>
        <div class="responsibility-group mb-2">
            <div class="input-group">
                <input type="text" name="responsibilities[]" 
                       value="<?= htmlspecialchars($duty['duty_name']) ?>" 
                       class="form-control" required>
                <div class="input-group-append">
                    <button type="button" class="btn btn-danger remove-responsibility">×</button>
                </div>
            </div>
            <input type="hidden" name="duty_ids[]" value="<?= $duty['duty_id'] ?>">
        </div>
        <?php endforeach; ?>
    </div>
    
    <button type="button" class="btn btn-primary mt-2" onclick="addResponsibility()">
        + Add Responsibility
    </button>
    
                                        </div>
                                    </div>

                                    <!-- Status -->
                                    <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
                                        <div class="form-group">
                                            <label for="status" class="form-label">Status <span class="red-text">*</span></label>
                                            <select name="status" class="form-control" required>
                                                <option value="1" <?php echo (isset($first_duty) && $first_duty['status'] == 1) ? 'selected' : ''; ?>>Enable</option>
                                                <option value="0" <?php echo (isset($first_duty) && $first_duty['status'] == 0) ? 'selected' : ''; ?>>Disable</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Submit Button -->
                                    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
                                        <button type="submit" class="btn btn-secondary" name="<?php echo isset($_GET['id']) ? 'update' : 'submit'; ?>">
                                            <i class="feather icon-save"></i>&nbsp;
                                            <?php echo isset($_GET['id']) ? 'Update' : 'Add'; ?> Responsibility
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
    <script>
// Initialize remove buttons for existing duties
document.querySelectorAll('.remove-responsibility').forEach(button => {
    button.addEventListener('click', function() {
        removeResponsibility(this);
    });
});
</script>
 <script>
function addResponsibility() {
    const container = document.getElementById('responsibilities-container');
    const newGroup = document.createElement('div');
    newGroup.className = 'responsibility-group mb-2';
    newGroup.innerHTML = `
        <div class="input-group">
            <input type="text" name="responsibilities[]" class="form-control" required>
            <div class="input-group-append">
                <button type="button" class="btn btn-danger remove-responsibility" onclick="removeResponsibility(this)">×</button>
            </div>
        </div>
        <input type="hidden" name="new_responsibility[]" value="1">
    `;
    container.appendChild(newGroup);
}

function removeResponsibility(button) {
    const group = button.closest('.responsibility-group');
    const dutyIdInput = group.querySelector('input[name="duty_ids[]"]');
    
    if (dutyIdInput && dutyIdInput.value) {
        // For existing duties - create delete marker
        if (!group.querySelector('input[name="delete_duty_ids[]"]')) {
            const deleteInput = document.createElement('input');
            deleteInput.type = 'hidden';
            deleteInput.name = 'delete_duty_ids[]';
            deleteInput.value = dutyIdInput.value;
            group.appendChild(deleteInput);
        }
    }
    
    // Hide the group instead of removing (so form submission still processes it)
    group.style.display = 'none';
    
    // Ensure at least one visible responsibility remains
    const visibleGroups = document.querySelectorAll('.responsibility-group:not([style*="display: none"])');
    if (visibleGroups.length === 0) {
        // If none left, show the first one (but empty)
        const firstGroup = document.querySelector('.responsibility-group');
        firstGroup.style.display = '';
        firstGroup.querySelector('input[type="text"]').value = '';
    }
}
</script>

</body>

</html>