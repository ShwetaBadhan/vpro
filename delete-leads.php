<?php
include("db/config.php");
session_start(); // Ensure session is started to check admin login


// Check if a single or multiple advertisement IDs are provided for deletion
if (isset($_GET['id'])) {
    // Single deletion
    $advt_id = base64_decode($_GET['id']);
    $ad_id = mysqli_real_escape_string($db, $advt_id);
    // 🚫 Disable foreign key checks
    mysqli_query($db, "SET FOREIGN_KEY_CHECKS = 0");
    // Move the record to the recycle bin instead of deleting it
    // moveToRecycleBin($db, $ad_id);
    if (moveToRecycleBin($db, $advt_id)) {
        $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible fade show' role='alert' style='font-size:16px;' id='gold'>
            <strong><i class='feather icon-check'></i> Deleted!</strong> Lead deleted successfully.
            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                <span aria-hidden='true'>&times;</span>
            </button>
        </div>";
    } else {
        $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible fade show' role='alert' style='font-size:16px;' id='gold'>
            <strong>Error!</strong> Failed to delete lead.
            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                <span aria-hidden='true'>&times;</span>
            </button>
        </div>";
    }
    // ✅ Re-enable foreign key checks
    mysqli_query($db, "SET FOREIGN_KEY_CHECKS = 1");
    // Redirect to the registeration.php page
    header("Location: admission-leads.php");
    exit(); // Terminate script execution after redirection
}elseif (isset($_POST['advt_ids'])) {
    // Multiple deletion
    $success_count = 0;
    $ad_ids = $_POST['advt_ids'];

    mysqli_query($db, "SET FOREIGN_KEY_CHECKS = 0");
    foreach ($ad_ids as $encoded_id) {
        $ad_id = base64_decode($encoded_id);
        $ad_id = mysqli_real_escape_string($db, $ad_id);

        // Only count successful moves
        if (moveToRecycleBin($db, $ad_id)) {
            $success_count++;
        }
    }
    mysqli_query($db, "SET FOREIGN_KEY_CHECKS = 1");

    if ($success_count > 0) {
        $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible fade show' role='alert' style='font-size:16px;' id='gold'>
            <strong>Success!</strong> $success_count Lead(s) deleted successfully.
            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                <span aria-hidden='true'>&times;</span>
            </button>
        </div>";
    } else {
        $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible fade show' role='alert' style='font-size:16px;' id='gold'>
            <strong>Error!</strong> No lead records deleted.
            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                <span aria-hidden='true'>&times;</span>
            </button>
        </div>";
    }

    header("Location: admission-leads.php");
    exit();
}


// If no advertisement ID provided, redirect to admission-leads.php
header("Location: admission-leads.php");
exit(); // Terminate script execution after redirection

// Function to move a record to the recycle bin
function moveToRecycleBin($db, $id)
{
    // First, check if the record exists
    $check_sql = "SELECT * FROM admission_enquiry WHERE admission_id = '$id'";
    $check_result = mysqli_query($db, $check_sql);

    if (mysqli_num_rows($check_result) == 0) {
        return false; // Record not found
    }

    // Attempt to insert into recycle bin
    $move_sql = "INSERT INTO recycle_bin (admission_id, name, email, mobile, course_name, state, city, remarks, lead_status, date, deleted_at)
        SELECT ae.admission_id, ae.name, ae.email, ae.mobile, ae.course_type, ae.state, ae.city, ae.remarks, ae.lead_status, ae.date, NOW()
        FROM admission_enquiry AS ae
        WHERE ae.admission_id = '$id'";

    if (mysqli_query($db, $move_sql) && mysqli_affected_rows($db) > 0) {
        // Delete original record
        $delete_sql = "DELETE FROM admission_enquiry WHERE admission_id = '$id'";
        if (mysqli_query($db, $delete_sql)) {
            return true; // ✅ Success
        }
    }

    return false; // ❌ Failed
}




// Function to permanently delete a record from the recycle bin
function deleteFromRecycleBin($db, $id)
{
    $delete_sql = "DELETE FROM recycle_bin WHERE recycle_id = '$id'";
    mysqli_query($db, $delete_sql);
}
