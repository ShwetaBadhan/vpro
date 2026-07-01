<?php
error_reporting(E_ALL);
session_start();
include("db/config.php");

// Check if the database connection is working
if (!$db) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Query to get the first download count value
$query = "SELECT download_count FROM admission_enquiry LIMIT 1";
$result1 = mysqli_query($db, $query);

if (!$result1) {
    die("Error executing query: " . mysqli_error($db));
}

// Fetch the result
$row = mysqli_fetch_assoc($result1);

// Check if a row was returned
if ($row && isset($row['download_count'])) {
    echo "" . $row['download_count']; // Display the download count
} else {
    echo "0";
}
?>

