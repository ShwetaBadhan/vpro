<?php
include('db/config.php');

// Handle single delete (POST)
if (isset($_POST['event_id'])) {

    $id = base64_decode($_POST['event_id']);
    $id = mysqli_real_escape_string($db, $id);

    // echo $id;
    // exit;

    $delete_sql = "DELETE FROM event_calendar WHERE event_id = '$id'";
    if (mysqli_query($db, $delete_sql)) {
        header("Location: manage-events.php?status=" . base64_encode(1));
    } else {
        header("Location: manage-events.php?status=" . base64_encode(-1));
    }
    exit();
}

// Handle multiple deletes (optional)
if (isset($_POST['events_ids'])) {
    $ids = $_POST['events_ids'];
    $success_count = 0;

    foreach ($ids as $encoded_id) {
        $id = base64_decode($encoded_id);
        $id = mysqli_real_escape_string($db, $id);

        $delete_sql = "DELETE FROM event_calendar WHERE event_id = '$id'";
        if (mysqli_query($db, $delete_sql)) {
            $success_count++;
        }
    }

    if ($success_count > 0) {
        header("Location: manage-events.php?status=" . base64_encode(1));
    } else {
        header("Location: manage-events.php?status=" . base64_encode(-1));
    }
    exit();
}

// Fallback redirect if nothing matched
header("Location: manage-events.php?status=" . base64_encode(-1));
exit();
?>
