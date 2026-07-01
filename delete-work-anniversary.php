<?php
session_start();
include("db/config.php");

// ✅ Function to delete by ID
function deleteMonth($db, $work_id)
{
    $delete_query = "DELETE FROM work_anniversary WHERE work_id = '$work_id'";
    return mysqli_query($db, $delete_query);
}

// ✅ Single delete
if (isset($_GET['id'])) {
    $work_id = base64_decode($_GET['id']);
    $work_id = mysqli_real_escape_string($db, $work_id);

    if (deleteMonth($db, $work_id)) {
        $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible fade show' role='alert' style='font-size:16px;' id='gold'>
        <strong><i class='feather icon-check'></i> Deleted!</strong> Work Anniversary deleted successfully.
        <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
        <span aria-hidden='true'>&times;</span>
        </button></div>";
    } else {
        $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible fade show' role='alert' style='font-size:16px;' id='gold'>
        <strong>Error!</strong> Work Anniversary could not be deleted.
        <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
        <span aria-hidden='true'>&times;</span>
        </button></div>";
    }

    header("Location: manage-work-anniversary.php");
    exit();
}

// ✅ Multiple delete
if (isset($_POST['work_ids'])) {
    $work_ids = $_POST['work_ids'];

    $success = 0;
    foreach ($work_ids as $encoded_id) {
        $work_id = base64_decode($encoded_id);
        if (deleteMonth($db, $work_id)) {
            $success++;
        }
    }

    if ($success > 0) {
        $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible fade show' role='alert' style='font-size:16px;' id='gold'>
        <strong>Success!</strong> $success Work Anniversary deleted successfully.
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

    header("Location: manage-work-anniversary.php");
    exit();
}

// If nothing matched, still redirect
header("Location: manage-work-anniversary.php");
exit();
