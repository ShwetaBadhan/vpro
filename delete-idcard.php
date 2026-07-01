<?php
include("db/config.php");
session_start(); // Start session for flash messages

// Function to delete Id Card
function deleteGreet($db, $id_card) {
    $delete_query = "DELETE FROM id_cards WHERE id_card_id = '$id_card'";
    return mysqli_query($db, $delete_query); // Return result (true/false)
}

// Single delete
if (isset($_GET['id'])) {
    $id_card = base64_decode($_GET['id']);
    $id_card = mysqli_real_escape_string($db, $id_card);

    if (deleteGreet($db, $id_card)) {
        $_SESSION['success'] = "Id Card deleted successfully.";
    } else {
        $_SESSION['error'] = "Failed to delete Id Card.";
    }

    header("Location: manage-id-cards.php");
    exit();
}

// Multiple delete
if (isset($_POST['cards_ids'])) {
    $id_cards = $_POST['cards_ids'];
    $deletedCount = 0;
    $failedCount = 0;

    foreach ($id_cards as $encoded_id) {
        $id_card = base64_decode($encoded_id);
        $id_card = mysqli_real_escape_string($db, $id_card);

        if (deleteGreet($db, $id_card)) {
            $deletedCount++;
        } else {
            $failedCount++;
        }
    }

    if ($deletedCount > 0 && $failedCount == 0) {
        $_SESSION['success'] = "$deletedCount Id Cards deleted successfully.";
    } elseif ($deletedCount > 0 && $failedCount > 0) {
        $_SESSION['error'] = "$deletedCount Id Cards deleted, but $failedCount failed.";
    } else {
        $_SESSION['error'] = "Failed to delete selected Id Cards.";
    }

    header("Location: manage-id-cards.php");
    exit();
}

// If nothing selected
$_SESSION['error'] = "No Id Card selected for deletion.";
header("Location: manage-id-cards.php");
exit();
?>
