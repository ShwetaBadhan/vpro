<?php
session_start();

// Facebook App Credentials
$app_id = "2327236491109328";
$redirect_uri = "http://localhost/login/fb-callback.php";

// Required Permissions (Lead Ads ke liye)
$permissions = [
    'email',
    'public_profile'
];


// Scope string
$scope = implode(',', $permissions);

// Facebook OAuth Login URL
$login_url = "https://www.facebook.com/v19.0/dialog/oauth?" . http_build_query([
    'client_id' => $app_id,
    'redirect_uri' => $redirect_uri,
    'scope' => $scope,
    'response_type' => 'code'
]);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login with Facebook</title>
    <style>
        body {
            display:flex;
            align-items:center;
            justify-content:center;
            height:100vh;
            font-family: Arial, sans-serif;
            background:#f4f6f8;
        }
        .fb-btn {
            background:#1877f2;
            color:#fff;
            padding:14px 28px;
            border-radius:6px;
            font-size:16px;
            text-decoration:none;
            font-weight:600;
        }
        .fb-btn:hover {
            background:#145dbf;
        }
    </style>
</head>
<body>

    <a href="<?= htmlspecialchars($login_url) ?>" class="fb-btn">
        Login with Facebook
    </a>

</body>
</html>
