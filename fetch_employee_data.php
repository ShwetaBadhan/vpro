<?php
include 'db/config.php';

$employee_id = $_GET['employee_id'] ?? null;

if ($employee_id) {
    $query = "
        SELECT 
            p.personal_id,
            p.name,
            p.email,
            p.mobile,
            p.address,
            p.emergency_no,
            p.photo,
            c.designation AS designation_id,
            d.name AS designation,
            c.employee_code,
            c.doj
        FROM personal_details p
        LEFT JOIN company_details c 
            ON p.personal_id = c.user_id
        LEFT JOIN position d 
            ON c.designation = d.position_id
        WHERE p.personal_id = ?
    ";

    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $employee = $result->fetch_assoc();

    if ($employee) {
        echo json_encode([
            'success' => true,
            'data' => $employee
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Employee not found'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No employee ID provided'
    ]);
}
?>
