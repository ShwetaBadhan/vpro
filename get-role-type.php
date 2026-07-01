<?php
require 'db/config.php';

if (isset($_POST['role_id'])) {
    $role_id = intval($_POST['role_id']);
    $query = $db->prepare("SELECT role_name FROM roles WHERE role_id = ?");
    $query->bind_param("i", $role_id);
    $query->execute();
    $result = $query->get_result();
    $row = $result->fetch_assoc();
    echo json_encode(['role_name' => strtolower($row['role_name'])]);
}
