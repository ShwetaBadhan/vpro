<?php
include("db/config.php");
session_start();

// Function to delete a slip and related data
function deleteSlip($db, $slipId) {
    // Sanitize
    $slipId = mysqli_real_escape_string($db, $slipId);

    // Check if slip exists
    $check = mysqli_query($db, "SELECT slip_id FROM salary_slips WHERE slip_id = '$slipId'");
    if (mysqli_num_rows($check) == 0) {
        return false; // Not found
    }

    // Delete related earnings and deductions
    mysqli_query($db, "DELETE FROM salary_earnings WHERE slip_id = '$slipId'");
    mysqli_query($db, "DELETE FROM salary_deductions WHERE slip_id = '$slipId'");

    // Delete from main table
    return mysqli_query($db, "DELETE FROM salary_slips WHERE slip_id = '$slipId'");
}

// Single delete (GET)
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $slipId = base64_decode($_GET['id']);
    if (deleteSlip($db, $slipId)) {
        $_SESSION['success'] = "Salary Slip deleted successfully.";
    } else {
        $_SESSION['error'] = "Failed to delete Salary Slip.";
    }
    header("Location: manage-salary-slip.php");
    exit;
}

// Multiple delete (POST)
if (isset($_POST['slip_ids']) && is_array($_POST['slip_ids'])) {
    $successCount = 0;
    $errorCount = 0;

    foreach ($_POST['slip_ids'] as $encodedId) {
        $slipId = base64_decode($encodedId);
        if (deleteSlip($db, $slipId)) {
            $successCount++;
        } else {
            $errorCount++;
        }
    }

    if ($successCount > 0) {
        $_SESSION['success'] = "$successCount Salary Slip(s) deleted successfully.";
    }
    if ($errorCount > 0) {
        $_SESSION['error'] = "$errorCount Salary Slip(s) could not be deleted.";
    }

    header("Location: manage-salary-slip.php");
    exit;
}

// Invalid request
$_SESSION['error'] = "Invalid request.";
header("Location: manage-salary-slip.php");
exit;
?>
