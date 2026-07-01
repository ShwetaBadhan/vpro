<?php
include('db/config.php');

$status = -1; // default fail
// echo '<pre>';
//     print_r($_POST);
//     exit;
// Handle single client delete
if (isset($_GET['id'])) {
    $id = base64_decode($_GET['id']);
    $id = mysqli_real_escape_string($db, $id);

    $status = 0;

    $update_sql = "UPDATE clients SET status = 0, is_deleted = 1 WHERE client_id = '$id'";
    if (mysqli_query($db, $update_sql)) {
        $status = 1;
    }

    header("Location: manage-client.php?action=delete&status=" . base64_encode($status));
    exit();
}

// Handle multiple client deletes
if (isset($_POST['client_ids']) && is_array($_POST['client_ids']) && isset($_POST['delete'])) {
    // echo '<pre>';
    // print_r($_POST);
    // exit;
    $ids = $_POST['client_ids'];
    $success_count = 0;

    foreach ($ids as $encoded_id) {
        $id = base64_decode($encoded_id);
        $id = mysqli_real_escape_string($db, $id);

        $update_sql = "UPDATE clients SET is_deleted = 1 WHERE client_id = '$id'";
        if (mysqli_query($db, $update_sql)) {
            $success_count++;
        }
    }

    $status = ($success_count > 0) ? 1 : -1;

    header("Location: manage-client.php?action=delete&status=" . base64_encode($status));
    exit();
}

// Fallback
header("Location: manage-client.php?action=delete&status=" . base64_encode($status));
exit();
?>
