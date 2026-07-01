<?php
define('DB_SERVER', 'localhost');
define('DB_USERNAME_VIBRANTICK', 'root');
define('DB_PASSWORD_VIBRANTICK', '');
define('DB_NAME_VIBRANTICK', 'vis');

$db_vibrantick = mysqli_connect(DB_SERVER, DB_USERNAME_VIBRANTICK, DB_PASSWORD_VIBRANTICK, DB_NAME_VIBRANTICK);
if (!$db_vibrantick) {
    die("Vibrantick database connection failed: " . mysqli_connect_error());
}
?>