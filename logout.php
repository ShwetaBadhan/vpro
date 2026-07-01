<?php
session_start();
date_default_timezone_set('Asia/Kolkata');
ini_set('date.timezone', 'Asia/Kolkata');

include("db/config.php");

// Set MySQL timezone
mysqli_query($db, "SET time_zone = '+05:30'");

if (isset($_SESSION['log_id']) && !empty($_SESSION['log_id'])) {
    $log_id = intval($_SESSION['log_id']);
    $logout_time = date('Y-m-d H:i:s');
    
    $update = "UPDATE user_logs 
               SET logout_time = '$logout_time', 
                   status = 'logged_out',
                   last_seen = '$logout_time'
               WHERE id = $log_id 
               AND status = 'active'";
    
    if (!mysqli_query($db, $update)) {
        error_log("Logout failed: " . mysqli_error($db));
    }
}

session_unset();
session_destroy();
header("location: index.php?logout=success");
exit();
?>