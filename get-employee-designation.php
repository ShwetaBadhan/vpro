<?php
include("db/config.php");

if (!isset($_POST['employee_id'])) {
    echo json_encode(['designation_id' => '']);
    exit;
}

$employee_id = mysqli_real_escape_string($db, $_POST['employee_id']);

/*
 IMPORTANT:
 company_id = employee_id (personal_id)
*/
$query = "SELECT designation FROM company_details WHERE user_id = '$employee_id' LIMIT 1";
$result = mysqli_query($db, $query);

if ($row = mysqli_fetch_assoc($result)) {
    echo json_encode([
        'designation_id' => $row['designation']
    ]);
} else {
    echo json_encode([
        'designation_id' => ''
    ]);
}
