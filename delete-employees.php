<?php
include("db/config.php");


$successCount = 0;

if (isset($_POST['employee_id'])) {
    $personalID = intval(base64_decode($_POST['employee_id']));
    if ($personalID > 0) {

        // 🚫 Disable foreign key checks
        mysqli_query($db, "SET FOREIGN_KEY_CHECKS = 0");

        // Delete from all related tables
        mysqli_query($db, "DELETE FROM bank_details WHERE user_id = $personalID");
        mysqli_query($db, "DELETE FROM company_details WHERE user_id = $personalID");
        mysqli_query($db, "DELETE FROM salary_slip_requests WHERE employee_id = $personalID");
        mysqli_query($db, "DELETE FROM personal_details WHERE personal_id = $personalID");

        // ✅ Re-enable foreign key checks
        mysqli_query($db, "SET FOREIGN_KEY_CHECKS = 1");

        header("Location: manage-employees.php?status=" . base64_encode(1));
        exit();
    }
} elseif (isset($_POST['personal_ids']) && is_array($_POST['personal_ids'])) {
    // Multi delete
    foreach ($_POST['personal_ids'] as $encoded_id) {
        $personalID = intval(base64_decode($encoded_id));
        if ($personalID > 0) {
            $deleteBank = mysqli_query($db, "DELETE FROM bank_details WHERE user_id = $personalID");
            $deleteCompany = mysqli_query($db, "DELETE FROM company_details WHERE user_id = $personalID");
            $deletePersonal = mysqli_query($db, "DELETE FROM personal_details WHERE personal_id = $personalID");

            if ($deleteBank && $deleteCompany && $deletePersonal) {
                $successCount++;
            }
        }
    }
}

$status = base64_encode($successCount > 0 ? 1 : -1);
echo ("<script>window.location.href='manage-employees.php?delete_status=$status';</script>");
exit();
