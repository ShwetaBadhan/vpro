<?php
session_start();
include("db/config.php");

// ✅ Function to delete by ID
function deleteMonth($db, $intern_id)
{
    $delete_query = "DELETE FROM internship_certificate WHERE internship_id = '$intern_id'";
    return mysqli_query($db, $delete_query);
}

// ✅ Single delete
if (isset($_GET['id'])) {
    $intern_id = base64_decode($_GET['id']);
    $intern_id = mysqli_real_escape_string($db, $intern_id);

    if (deleteMonth($db, $intern_id)) {
        $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible fade show' role='alert' style='font-size:16px;' id='gold'>
        <strong><i class='feather icon-check'></i> Deleted!</strong> Internship Letter deleted successfully.
        <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
        <span aria-hidden='true'>&times;</span>
        </button></div>";
    } else {
        $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible fade show' role='alert' style='font-size:16px;' id='gold'>
        <strong>Error!</strong> Internship Letter could not be deleted.
        <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
        <span aria-hidden='true'>&times;</span>
        </button></div>";
    }

    header("Location: manage-internship-letters.php");
    exit();
}

// ✅ Multiple delete
if (isset($_POST['internship_ids'])) {
    $intern_ids = $_POST['internship_ids'];

    $success = 0;
    foreach ($intern_ids as $encoded_id) {
        $intern_id = base64_decode($encoded_id);
        if (deleteMonth($db, $intern_id)) {
            $success++;
        }
    }

    if ($success > 0) {
        $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible fade show' role='alert' style='font-size:16px;' id='gold'>
        <strong>Success!</strong> $success  Internship Letter(s) deleted successfully.
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

    header("Location: manage-internship-letters.php");
    exit();
}

// If nothing matched, still redirect
header("Location: manage-internship-letters.php");
exit();
