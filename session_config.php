<?php
// session_config.php — Prevent auto-logout

// Set session lifetime to 30 days (in seconds)
$maxLifetime = 2592000; // 30 days

// Configure session parameters BEFORE session_start()
ini_set('session.gc_maxlifetime', $maxLifetime);
ini_set('session.gc_probability', 1);
ini_set('session.gc_divisor', 100);
ini_set('session.cookie_lifetime', 0); // Until browser closes (change to $maxLifetime if you want it to persist after browser close)
ini_set('session.use_strict_mode', 1);

session_start();

// Extend session lifetime on every page load (sliding expiry)
if (isset($_SESSION['login_user'])) {
    $_SESSION['last_activity'] = time();
    
    // Update last seen time in database (optional, for tracking)
    if (isset($_SESSION['log_id'])) {
        include("db/config.php");
        $log_id = $_SESSION['log_id'];
        $update = "UPDATE user_logs SET last_seen = NOW() WHERE id = $log_id";
        mysqli_query($db, $update);
    }
}
?>