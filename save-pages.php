<?php
session_start();
include("db/config.php");

if(!isset($_SESSION['login_user_id'])){
    header("Location: dashboard.php");
    exit;
}

$user_id = $_SESSION['login_user_id'];

if(isset($_POST['pages']) && is_array($_POST['pages'])){
    foreach($_POST['pages'] as $page_id){
        $page_token = $_POST["token_$page_id"] ?? '';

        // Insert or update page
        $stmt = $db->prepare("INSERT INTO fb_pages (user_id, page_id, page_name, page_access_token)
                              VALUES (?, ?, ?, ?)
                              ON DUPLICATE KEY UPDATE page_access_token=?");
        $page_name = $_POST["name_$page_id"] ?? '';
        $stmt->bind_param("issss", $user_id, $page_id, $page_name, $page_token, $page_token);
        $stmt->execute();
    }
}

header("Location: dashboard.php?pages_connected=1");
exit;
?>
