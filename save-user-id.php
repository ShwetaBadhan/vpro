<?php
require_once "db/config.php";
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   
    $adminId = $_POST['admin_id'] ?? null;
    echo $adminId;
    exit;
    $admissionId = $_POST['admission_id'] ?? null;
   
    if ($adminId && $admissionId) {

        // 1. Check if the lead is already assigned
        $stmt = $db->prepare("SELECT assign_id, admin_id FROM lead_assignments WHERE admission_id = ?");
        $stmt->bind_param("i", $admissionId);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($existingId, $existingUserId);

        if ($stmt->num_rows > 0) {
            $stmt->fetch();
            $stmt->close();

            if ($existingUserId == $adminId) {
                echo json_encode(['status' => 'error', 'message' => 'This lead is already assigned to the selected user.']);
                exit;
            } else {
                // UPDATE existing record
                $update = $db->prepare("UPDATE lead_assignments SET admin_id = ? WHERE admission_id = ?");
                $update->bind_param("ii", $adminId, $admissionId);
                if ($update->execute()) {
                    echo json_encode(['status' => 'success', 'message' => 'Lead reassigned to new user.']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to update assignment.']);
                }
                $update->close();
            }
        } else {
            $stmt->close();
            // INSERT new assignment
            $insert = $db->prepare("INSERT INTO lead_assignments (admission_id, admin_id) VALUES (?, ?)");
            $insert->bind_param("ii", $admissionId, $adminId);
            if ($insert->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Lead assigned successfully.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to assign user.']);
            }
            $insert->close();
        }

    } else {
        echo json_encode(['status' => 'error', 'message' => 'Missing admin_id or admission_id.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
