<?php
include("db/config.php");

// Function to delete the position and related duties
function deletePosition($db, $position_id) {
    $ok = true;

    // Delete all duties related to this position
    if (!mysqli_query($db, "DELETE FROM duties WHERE position_id = '$position_id'")) {
        $ok = false;
    }

    // Delete the position
    if (!mysqli_query($db, "DELETE FROM position WHERE position_id = '$position_id'")) {
        $ok = false;
    }

    return $ok;
}

// Delete single position
if (isset($_GET['id'])) {
    $position_id = mysqli_real_escape_string($db, base64_decode($_GET['id']));
    if (deletePosition($db, $position_id)) {
        header("Location: manage-positions.php?status=" . base64_encode("Position deleted successfully") . "&type=success");
    } else {
        header("Location: manage-positions.php?status=" . base64_encode("Failed to delete position") . "&type=error");
    }
    exit();
}

// Delete multiple positions
if (isset($_POST['position_ids'])) {
    $allOk = true;
    foreach ($_POST['position_ids'] as $encoded_id) {
        $position_id = mysqli_real_escape_string($db, base64_decode($encoded_id));
        if (!deletePosition($db, $position_id)) {
            $allOk = false;
        }
    }

    if ($allOk) {
        header("Location: manage-positions.php?status=" . base64_encode("Selected positions deleted successfully") . "&type=success");
    } else {
        header("Location: manage-positions.php?status=" . base64_encode("Some positions could not be deleted") . "&type=error");
    }
    exit();
}

// Default redirect
header("Location: manage-positions.php");
exit();
