<?php
session_start();
include('db/config.php');

/* ==============================
   CONFIG
============================== */

$appId = "740150892272064";
$appSecret = "88ab30823b7c821abd3196069a4a9888";
$redirectUri = "http://localhost/login/facebook_callback.php";

if (!isset($_SESSION["login_user"])) {
    die("User not logged in");
}

if (!isset($_GET['code'])) {
    die("No code received from Facebook");
}

$code = $_GET['code'];

/* ==============================
   STEP 1: GET SHORT-LIVED TOKEN
============================== */

$tokenUrl = "https://graph.facebook.com/v19.0/oauth/access_token?" . http_build_query([
    'client_id' => $appId,
    'client_secret' => $appSecret,
    'redirect_uri' => $redirectUri,
    'code' => $code
]);

$response = file_get_contents($tokenUrl);
$data = json_decode($response, true);

if (!isset($data['access_token'])) {
    echo "<pre>";
    print_r($data);
    die("Failed to get access token");
}

$userAccessToken = $data['access_token'];

/* ==============================
   STEP 2: CONVERT TO LONG-LIVED TOKEN
============================== */

$longLivedUrl = "https://graph.facebook.com/v19.0/oauth/access_token?" . http_build_query([
    'grant_type' => 'fb_exchange_token',
    'client_id' => $appId,
    'client_secret' => $appSecret,
    'fb_exchange_token' => $userAccessToken
]);

$longResponse = file_get_contents($longLivedUrl);
$longData = json_decode($longResponse, true);

if (isset($longData['access_token'])) {
    $userAccessToken = $longData['access_token'];
}

/* ==============================
   STORE USER TOKEN
============================== */

$userId = mysqli_real_escape_string($db, $_SESSION["login_user"]);

mysqli_query($db,
    "INSERT INTO facebook_accounts (user_id, user_access_token)
     VALUES ('$userId', '$userAccessToken')
     ON DUPLICATE KEY UPDATE user_access_token='$userAccessToken'"
);

/* ==============================
   STEP 3: FETCH USER PAGES
============================== */

$pageUrl = "https://graph.facebook.com/v19.0/me/accounts?access_token=$userAccessToken";
$pageResponse = file_get_contents($pageUrl);
$pageData = json_decode($pageResponse, true);

if (!empty($pageData['data'])) {

    foreach ($pageData['data'] as $page) {

        $pageId = $page['id'];
        $pageName = mysqli_real_escape_string($db, $page['name']);
        $pageToken = $page['access_token'];

        // Store page token
        mysqli_query($db,
            "INSERT INTO facebook_pages 
            (user_id, page_id, page_name, page_access_token)
            VALUES ('$userId', '$pageId', '$pageName', '$pageToken')
            ON DUPLICATE KEY UPDATE 
            page_access_token='$pageToken'"
        );
    }
} else {
    echo "<pre>";
    print_r($pageData);
    die("No pages found or permission missing");
}

/* ==============================
   REDIRECT BACK
============================== */

header("Location: facebook-leadsync.php");
exit;