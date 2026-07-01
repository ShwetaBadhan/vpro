<?php
session_start();

include("db/config.php");

$result1 = mysqli_query($db, "select count(*) FROM admission_enquiry");
$row = mysqli_fetch_array($result1);

if ($row > 0) {

    $total = $row[0];

    echo $total;
}