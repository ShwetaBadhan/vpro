<?php
session_start();
include("db/config.php");
if(isset($_SESSION['login_user'])){
    // already logged in, redirect to dashboard
    header("Location: dashboard.php");
    exit;
}

// Your App credentials
$app_id = "2327236491109328";
$app_secret = "31755258f9019259aae61f65b8d3e525"; // Replace with your actual secret
$redirect_uri = "http://localhost/login/fb-callback.php";

// Check if Facebook returned code
if (isset($_GET['code'])) {
    $code = $_GET['code'];

    // Get access token
    $token_url = "https://graph.facebook.com/v18.0/oauth/access_token?"
        . "client_id={$app_id}&redirect_uri=" . urlencode($redirect_uri)
        . "&client_secret={$app_secret}&code={$code}";

    $response = file_get_contents($token_url);
    $params = json_decode($response, true);

    if (isset($params['access_token'])) {
        $access_token = $params['access_token'];

        // Get user info
        $user_info_url = "https://graph.facebook.com/me?fields=id,name,email,picture&access_token={$access_token}";
        $user_info = json_decode(file_get_contents($user_info_url), true);

        $fb_id = $user_info['id'];
        $name = $user_info['name'];
        $email = $user_info['email'] ?? '';
        $picture = $user_info['picture']['data']['url'] ?? '';

        // Check if user exists in your DB
        $sql = "SELECT * FROM admin WHERE email = '".mysqli_real_escape_string($db, $email)."' LIMIT 1";
        $result = mysqli_query($db, $sql);

        if (mysqli_num_rows($result) > 0) {
            // Existing user, log them in
            $user = mysqli_fetch_assoc($result);
            $_SESSION['login_user'] = $user['username'];
            $_SESSION['login_user_id'] = $user['_id'];
            $_SESSION['admin_role'] = $user['admin_role'];
            $_SESSION['employee_id'] = $user['employee_id'];

            header("Location: dashboard.php");
            exit;
        } else {
            // New user, create an account
            $username = explode(' ', $name)[0] . rand(100,999); // simple username
            $password = md5(rand(100000,999999)); // random password, optional

            $stmt = $db->prepare("INSERT INTO admin (username, email, password, status) VALUES (?, ?, ?, 1)");
            $stmt->bind_param("sss", $username, $email, $password);
            $stmt->execute();

            $_SESSION['login_user'] = $username;
            $_SESSION['login_user_id'] = $stmt->insert_id;
            $_SESSION['admin_role'] = 'user'; // default role
            $_SESSION['employee_id'] = 0;

            header("Location: dashboard.php");
            exit;
        }
    } else {
        echo "Error fetching access token";
    }
} else {
    echo "No code returned from Facebook";
}
?>
