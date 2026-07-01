<?php
require 'db/config.php';

$result = $db->query("SELECT personal_id, name FROM personal_details ORDER BY name ASC");

$employees = [];
while ($row = $result->fetch_assoc()) {
    $employees[] = $row;
}

echo json_encode($employees);
?>
