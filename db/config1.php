<?php
define('DB_HOST', 'localhost');
define('DB_USERNAME_TRAINING', 'root');
define('DB_PASSWORD_TRAINING', '');
define('DB_NAME_TRAINING', 'training');

$db_training = mysqli_connect(DB_HOST, DB_USERNAME_TRAINING, DB_PASSWORD_TRAINING, DB_NAME_TRAINING);
if (!$db_training) {
    die("Training database connection failed: " . mysqli_connect_error());
}
?>