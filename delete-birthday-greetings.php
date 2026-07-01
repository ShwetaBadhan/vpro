<?php
session_start();
include("db/config.php");

// ✅ Function to delete by ID
function deleteBirth($db, $birthday_id) {
    $delete_query = "DELETE FROM birthday_greetings WHERE birthday_id = '$birthday_id'";
    return mysqli_query($db, $delete_query);
}

// ✅ Single delete
if (isset($_GET['id'])) {
    $birthday_id = base64_decode($_GET['id']);
    $birthday_id = mysqli_real_escape_string($db, $birthday_id);

    if (deleteBirth($db, $birthday_id)) {
        $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible fade show' role='alert' style='font-size:16px;' id='gold'>
        <strong><i class='feather icon-check'></i> Deleted!</strong> Birthday Greetings deleted successfully.
        <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
        <span aria-hidden='true'>&times;</span>
        </button></div>";
    } else {
        $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible fade show' role='alert' style='font-size:16px;' id='gold'>
        <strong>Error!</strong> Birthday Greetings could not be deleted.
        <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
        <span aria-hidden='true'>&times;</span>
        </button></div>";
    }

    header("Location:manage-birthday-greetings.php");
    exit();
}

// ✅ Multiple delete
if (isset($_POST['birthday_ids'])) {
    $birthday_ids = $_POST['birthday_ids'];

    $success = 0;
    foreach ($birthday_ids as $encoded_id) {
        $birthday_id = base64_decode($encoded_id);
        if (deleteBirth($db, $birthday_id)) {
            $success++;
        }
    }

    if ($success > 0) {
        $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible fade show' role='alert' style='font-size:16px;' id='gold'>
        <strong>Success!</strong> $success Birthday Greetings deleted successfully.
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

    header("Location:manage-birthday-greetings.php");
    exit();
}

// If nothing matched, still redirect
header("Location:manage-birthday-greetings.php");
exit();
?>
