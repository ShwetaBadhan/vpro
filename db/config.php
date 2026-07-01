<?php

$host = "127.0.0.1"; 
$username ="root"; 
$password = ""; 
$database = "ctgroup";

$db = mysqli_connect($host, $username, $password, $database);

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

?>