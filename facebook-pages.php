<?php
session_start();
include("db/config.php");

// Check if user is logged in
if(!isset($_SESSION['login_user'])) {
    header("Location: dashboard.php");
    exit;
}

// Facebook Access Token from session
if(!isset($_SESSION['fb_access_token'])) {
    echo "Please login with Facebook first.";
    exit;
}

$fb_access_token = $_SESSION['fb_access_token'];

// Fetch pages from Facebook
$pages_url = "https://graph.facebook.com/v21.0/me/accounts?access_token=$fb_access_token";
$pages_data = json_decode(file_get_contents($pages_url), true);
$pages = $pages_data['data'] ?? [];

?>
<!DOCTYPE html>
<html>
<head>
    <title>Connect Facebook Pages</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h3>Connect Facebook Pages</h3>
    <?php if(empty($pages)): ?>
        <p>No pages found. Make sure your account has pages and proper permissions.</p>
    <?php else: ?>
        <form method="post" action="save-pages.php">
            <div class="mb-3">
                <label class="form-label">Select pages to connect:</label>
                <?php foreach($pages as $page): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="pages[]" value="<?= $page['id'] ?>" id="page_<?= $page['id'] ?>" data-token="<?= $page['access_token'] ?>">
                        <label class="form-check-label" for="page_<?= $page['id'] ?>">
                            <?= $page['name'] ?>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="submit" class="btn btn-primary">Connect Selected Pages</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
