<?php
error_reporting(E_ALL);
include("db/config.php");

// Fetch lead statuses from lead_status table
$query = "SELECT status_id, status_name FROM lead_status";
$result = mysqli_query($db, $query);

// Populate dropdown
while ($row = mysqli_fetch_assoc($result)) {
   echo "<option value='" . strtolower(trim($row['status_name'])) . "'>" . $row['status_name'] . "</option>";

}
?>
