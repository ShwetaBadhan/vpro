<?php
require_once __DIR__ . '/vendor/autoload.php'; // Path to SDK

$fb = new \Facebook\Facebook([
  'app_id' => '2327236491109328',
  'app_secret' => 'YOUR_APP_SECRET',
  'default_graph_version' => 'v19.0',
]);

$callbackURL = 'http://localhost/login/fb-callback.php';
$permissions = ['email']; // optional permissions
$helper = $fb->getRedirectLoginHelper();
