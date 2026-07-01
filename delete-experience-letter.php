<?php
session_start();
include("db/config.php");

// ✅ Function to delete by ID
function deleteMonth($db, $exp_id) {
    $delete_query = "DELETE FROM experience_letter WHERE experience_id = '$exp_id'";
    return mysqli_query($db, $delete_query);
}

// ✅ Single delete
if (isset($_GET['id'])) {
    $exp_id = base64_decode($_GET['id']);
    $exp_id = mysqli_real_escape_string($db, $exp_id);

    if (deleteMonth($db, $exp_id)) {
        $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible fade show' role='alert' style='font-size:16px;' id='gold'>
        <strong><i class='feather icon-check'></i> Deleted!</strong> Experience Letter deleted successfully.
        <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
        <span aria-hidden='true'>&times;</span>
        </button></div>";
    } else {
        $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible fade show' role='alert' style='font-size:16px;' id='gold'>
        <strong>Error!</strong> Experience Letter could not be deleted.
        <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
        <span aria-hidden='true'>&times;</span>
        </button></div>";
    }

    header("Location: manage-experience-letters.php");
    exit();
}

// ✅ Multiple delete
if (isset($_POST['experience_ids'])) {
    $exp_ids = $_POST['experience_ids'];

    $success = 0;
    foreach ($exp_ids as $encoded_id) {
        $exp_id = base64_decode($encoded_id);
        if (deleteMonth($db, $exp_id)) {
            $success++;
        }
    }

    if ($success > 0) {
        $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible fade show' role='alert' style='font-size:16px;' id='gold'>
        <strong>Success!</strong> $success  Experience Letter(s) deleted successfully.
        <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
        <span aria-hidden='true'>&times;</span>
        </button></div>";
    } else {
        $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible fade show' role='alert' style='font-size:16px;' id='gold'>
        <strong>Error!</strong> No records were deleted.
        <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
        <span aria-hidden='true'>&times;</span>
        </button></div>";
    }

    header("Location: manage-experience-letters.php");
    exit();
}

// If nothing matched, still redirect
header("Location: manage-experience-letters.php");
exit();
?>
