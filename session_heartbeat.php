<?php
session_start();
date_default_timezone_set('Asia/Kolkata');
ini_set('date.timezone', 'Asia/Kolkata');

include("db/config.php");

// Set MySQL timezone
mysqli_query($db, "SET time_zone = '+05:30'");

header('Content-Type: application/json');

if (isset($_SESSION['login_user']) && isset($_SESSION['log_id'])) {
    $_SESSION['last_activity'] = time();
    
    $log_id = intval($_SESSION['log_id']);
    $current_time = date('Y-m-d H:i:s');
    
    // Update last_seen with correct timezone
    $update = "UPDATE user_logs 
               SET last_seen = '$current_time' 
               WHERE id = $log_id";
    
    if (!mysqli_query($db, $update)) {
        error_log("Heartbeat update failed: " . mysqli_error($db));
    }
    
    echo json_encode(['status' => 'active', 'time' => $current_time]);
} else {
    echo json_encode(['status' => 'expired']);
}
?>