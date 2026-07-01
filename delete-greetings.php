<?php
include("db/config.php");
session_start(); // Start session for flash messages

// Function to delete greeting
function deleteGreet($db, $greeting_id) {
    $delete_query = "DELETE FROM emp_greetings WHERE greeting_id = '$greeting_id'";
    return mysqli_query($db, $delete_query); // Return result (true/false)
}

// Single delete
if (isset($_GET['id'])) {
    $greeting_id = base64_decode($_GET['id']);
    $greeting_id = mysqli_real_escape_string($db, $greeting_id);

    if (deleteGreet($db, $greeting_id)) {
        $_SESSION['success'] = "Greeting deleted successfully.";
    } else {
        $_SESSION['error'] = "Failed to delete greeting.";
    }

    header("Location: manage-greetings.php");
    exit();
}

// Multiple delete
if (isset($_POST['greeting_ids'])) {
    $greeting_ids = $_POST['greeting_ids'];
    $deletedCount = 0;
    $failedCount = 0;

    foreach ($greeting_ids as $encoded_id) {
        $greeting_id = base64_decode($encoded_id);
        $greeting_id = mysqli_real_escape_string($db, $greeting_id);

        if (deleteGreet($db, $greeting_id)) {
            $deletedCount++;
        } else {
            $failedCount++;
        }
    }

    if ($deletedCount > 0 && $failedCount == 0) {
        $_SESSION['success'] = "$deletedCount greetings deleted successfully.";
    } elseif ($deletedCount > 0 && $failedCount > 0) {
        $_SESSION['error'] = "$deletedCount greetings deleted, but $failedCount failed.";
    } else {
        $_SESSION['error'] = "Failed to delete selected greetings.";
    }

    header("Location: manage-greetings.php");
    exit();
}

// If nothing selected
$_SESSION['error'] = "No greeting selected for deletion.";
header("Location: manage-greetings.php");
exit();
?>
