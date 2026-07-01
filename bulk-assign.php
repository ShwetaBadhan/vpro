<?php
require_once 'db/config.php'; // replace with your DB connection file

$response = ['status' => 'error', 'message' => 'Something went wrong'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin_id = intval($_POST['admin_id']);
    $selected_ids = $_POST['selected_ids'];

    if (!empty($admin_id) && !empty($selected_ids)) {
        $ids = explode(',', $selected_ids);
foreach ($ids as $id) {
    $admission_id = base64_decode($id); // decode if needed

    if (!empty($admission_id)) {
        // Update admission_enquiry table with assigned user_id
        $stmt = $db->prepare("UPDATE admission_enquiry SET user_id = ? WHERE admission_id = ?");
        $stmt->bind_param("ii", $admin_id, $admission_id);
        $stmt->execute();

        // Check if assignment already exists
        $checkStmt = $db->prepare("SELECT assign_id FROM lead_assignments WHERE admission_id = ?");
        $checkStmt->bind_param("i", $admission_id);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows > 0) {
            // If exists, update the admin_id and assigned_at timestamp
            $updateStmt = $db->prepare("UPDATE lead_assignments SET admin_id = ?, assigned_at = NOW() WHERE admission_id = ?");
            $updateStmt->bind_param("ii", $admin_id, $admission_id);
            $updateStmt->execute();
        } else {
            // If not exists, insert new assignment record
            $insertStmt = $db->prepare("INSERT INTO lead_assignments (admin_id, admission_id) VALUES (?, ?)");
            $insertStmt->bind_param("ii", $admin_id, $admission_id);
            $insertStmt->execute();
        }
    }
}
        $response['status'] = 'success';
        $response['message'] = 'Leads assigned successfully!';
    } else {
        $response['message'] = 'Admin ID or Lead IDs are missing.';
    }
}

echo json_encode($response);