<?php
session_start();
include("db/config.php");

// ✅ Function to delete by ID
function deleteMonth($db, $month_id) {
    $delete_query = "DELETE FROM emp_month WHERE month_id = '$month_id'";
    return mysqli_query($db, $delete_query);
}

// ✅ Single delete
if (isset($_GET['id'])) {
    $month_id = base64_decode($_GET['id']);
    $month_id = mysqli_real_escape_string($db, $month_id);

    if (deleteMonth($db, $month_id)) {
        $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible fade show' role='alert' style='font-size:16px;' id='gold'>
        <strong><i class='feather icon-check'></i> Deleted!</strong> Employee of the Month deleted successfully.
        <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
        <span aria-hidden='true'>&times;</span>
        </button></div>";
    } else {
        $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible fade show' role='alert' style='font-size:16px;' id='gold'>
        <strong>Error!</strong> Employee of the Month could not be deleted.
        <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
        <span aria-hidden='true'>&times;</span>
        </button></div>";
    }

    header("Location: manage-employee-month.php");
    exit();
}

// ✅ Multiple delete
if (isset($_POST['month_ids'])) {
    $month_ids = $_POST['month_ids'];

    $success = 0;
    foreach ($month_ids as $encoded_id) {
        $month_id = base64_decode($encoded_id);
        if (deleteMonth($db, $month_id)) {
            $success++;
        }
    }

    if ($success > 0) {
        $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible fade show' role='alert' style='font-size:16px;' id='gold'>
        <strong>Success!</strong> $success Employee(s) of the Month deleted successfully.
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

    header("Location: manage-employee-month.php");
    exit();
}

// If nothing matched, still redirect
header("Location: manage-employee-month.php");
exit();
?>
