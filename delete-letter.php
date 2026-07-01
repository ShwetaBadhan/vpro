<?php
session_start();
include("db/config.php");

// ✅ Show message on the redirected page
if (isset($_SESSION['msg'])) {
    echo $_SESSION['msg'];
    unset($_SESSION['msg']);
}

function deleteLetter($db, $letter_id) {
    $delete_query = "DELETE FROM offer_letter WHERE offer_id = '$letter_id'";
    return mysqli_query($db, $delete_query);
}

// ✅ Single delete
if (isset($_GET['id'])) {
    $letter_id = base64_decode($_GET['id']);
    $letter_id = mysqli_real_escape_string($db, $letter_id);

    if (deleteLetter($db, $letter_id)) {
        $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible fade show' role='alert' style='font-size:16px;' id='gold'>
        <strong><i class='feather icon-check'></i> Deleted!</strong> Letter deleted successfully.
        <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
        <span aria-hidden='true'>&times;</span>
        </button></div>";
    } else {
        $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible fade show' role='alert' style='font-size:16px;' id='gold'>
        <strong>Error!</strong> Letter could not be deleted.
        <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
        <span aria-hidden='true'>&times;</span>
        </button></div>";
    }

    header("Location: manage-letters.php");
    exit(); 
}

// ✅ Multiple delete
if (isset($_POST['letter_ids'])) {  // 🔁 Fixed key name (remove $ from string)
    $letter_ids = $_POST['letter_ids'];

    $success = 0;
    foreach ($letter_ids as $encoded_id) {
        $letter_id = base64_decode($encoded_id);
        if (deleteLetter($db, $letter_id)) {
            $success++;
        }
    }

    if ($success > 0) {
        $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible fade show' role='alert' style='font-size:16px;' id='gold'>
        <strong>Success!</strong> $success Letter(s) deleted successfully.
        <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
        <span aria-hidden='true'>&times;</span>
        </button></div>";
    } else {
        $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible fade show' role='alert' style='font-size:16px;' id='gold'>
        <strong>Error!</strong> No letters were deleted.
        <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
        <span aria-hidden='true'>&times;</span>
        </button></div>";
    }

    header("Location: manage-letters.php");
    exit();
}

header("Location: manage-letters.php");
exit();

?>
