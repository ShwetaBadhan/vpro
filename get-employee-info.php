<?php
session_start();
error_reporting(E_ALL);

if (!isset($_SESSION["login_user"])) {
    header("location: index.php");
}

include("db/config.php");
$msg = "";
if (isset($_GET['user_id'])) {
    $userId = intval($_GET['user_id']);

   $query = "
    SELECT 
        p.father_name,
        p.dob,
        c.doj,
        c.employee_code,
        b.account_no,
        b.bank_name,
        
        pos.name AS designation
    FROM personal_details p
    LEFT JOIN company_details c ON c.user_id = p.personal_id
    LEFT JOIN bank_details b ON b.user_id = p.personal_id
    LEFT JOIN position pos ON pos.position_id = c.designation
    WHERE p.personal_id = $userId
";


    $result = mysqli_query($db, $query);
    $data = mysqli_fetch_assoc($result);

    echo json_encode($data);
}
?>