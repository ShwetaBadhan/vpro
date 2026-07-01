<?php
include("db/config.php");

// Check if a single testimonial ID is provided for deletion
if (isset($_GET['id'])) {
//     echo '<pre>';
// print_r($_GET);
// exit();

    $duty_id = base64_decode($_GET['id']);
    $duty_id = mysqli_real_escape_string($db, $duty_id);
    
    // Delete the testimonial image file from the server directory

    
    // Delete the testimonial record from the database
    deleteDuty($db, $duty_id);
    
    // Redirect to the manage-testimonials.php page
    header("Location: manage-duties.php");
    exit(); // Terminate script execution after redirection
}

// Check if multiple testimonial IDs are provided for deletion
if (isset($_POST['duty_ids'])) {
    $duty_ids = $_POST['duty_ids'];
    
    // Delete testimonials and their associated image files with provided IDs
    foreach ($duty_ids as $encoded_id) {
        $duty_id = base64_decode($encoded_id);
        
        // Delete the testimonial image file from the server directory
       
        
        // Delete the testimonial record from the database
        deleteDuty($db, $duty_id);
    }
    
    // Redirect to the manage-testimonials.php page
    header("Location: manage-duties.php");
    exit(); // Terminate script execution after redirection
}

// If no testimonial ID provided, redirect to manage-testimonials.php
header("Location: manage-duties.php");
exit(); // Terminate script execution after redirection

// Function to delete the testimonial image file from the server directory

// Function to delete the testimonial record from the database
function deleteDuty($db, $duty_id) {
    $delete_query = "DELETE FROM duties WHERE position_id = '$duty_id'";
    mysqli_query($db, $delete_query);
}
?>
