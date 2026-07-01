<?php
session_start();

include("db/config.php");

// Modify the query to count only the leads with the "untouched" status
$query = "SELECT COUNT(1) 
          FROM admission_enquiry 
          WHERE lead_status = (SELECT status_id FROM lead_status WHERE status_name = 'untouched')";

$result = mysqli_query($db, $query);
$row = mysqli_fetch_array($result);

if ($row > 0) {
    $total = $row[0]; // The number of untouched leads
    echo $total;
}
?>
