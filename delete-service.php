<?php
include('db/config.php');

// Handle single category delete
if (isset($_GET['id'])) {
    $id = base64_decode($_GET['id']);

    // Delete category record
    $delete_sql = "DELETE FROM service WHERE id = '".mysqli_real_escape_string($db, $id)."'";
    if (mysqli_query($db, $delete_sql)) {
        header("location: manage-service.php?status=" . base64_encode(1));
    } else {
        header("location: manage-service.php?status=" . base64_encode(-1));
    }
    exit();
}

// Handle multiple category deletes
if (isset($_POST['ids'])) {
    // echo '<pre>';
    // print_r($_POST);
    // exit;
    $ids = $_POST['ids'];
    $success_count = 0;

    foreach ($ids as $encoded_id) {
        $id = base64_decode($encoded_id);
        $id = mysqli_real_escape_string($db, $id);

        $delete_sql = "DELETE FROM service WHERE id = '$id'";
        if (mysqli_query($db, $delete_sql)) {
            $success_count++;
        }
    }

    if ($success_count > 0) {
        header("location: manage-service.php?status=" . base64_encode(1));
    } else {
        header("location: manage-service.php?status=" . base64_encode(-1));
    }
    exit();
}

// Default fallback
header("location: manage-service.php?status=" . base64_encode(-1));
exit();
?>
