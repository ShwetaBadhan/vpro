<?php
session_start();
// Include PHPMailer library
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include('db/config.php');

if (isset($_POST['download'])) {
    $status = $_POST['status'] ?? '';
    $dateFrom = $_POST['dateFrom'] ?? '';
    $dateTo = $_POST['dateTo'] ?? '';

    $conditions = [];

    if ($status != '') {
        $conditions[] = "admission_enquiry.lead_status = '$status'";
    }

    if ($dateFrom != '') {
        $conditions[] = "admission_enquiry.date >= '$dateFrom'";
    }

    if ($dateTo != '') {
        $conditions[] = "admission_enquiry.date <= '$dateTo'";
    }

    $query = "
    SELECT 
        admission_enquiry.admission_id,
        admission_enquiry.name,
        admission_enquiry.email,
        admission_enquiry.mobile,
        admission_enquiry.course_type,
        admission_enquiry.follow_up_stage,
        admission_enquiry.state,
        admission_enquiry.city,
        admission_enquiry.lead_status,
        admission_enquiry.date
    FROM 
        admission_enquiry";

    if (!empty($conditions)) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }

    $query .= " ORDER BY admission_enquiry.date DESC";

    $result = mysqli_query($db, $query);

    if ($result && $result->num_rows > 0) {
        // Update download count
        $increment_query = "
            UPDATE admission_enquiry 
            SET download_count = download_count + 1
            WHERE admission_id IN (
                SELECT admission_id FROM (
                    $query
                ) AS temp
            )";
        mysqli_query($db, $increment_query);

        // Export CSV
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename="filtered_leads.csv"');
        header('Cache-Control: max-age=0');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Admission ID', 'Name', 'Email', 'Mobile', 'Course Name', 'State', 'City', 'Lead Status', 'Date']);

        while ($lead = $result->fetch_assoc()) {
            fputcsv($output, [
                $lead['admission_id'],
                $lead['name'],
                $lead['email'],
                $lead['mobile'],
                $lead['course_type'],   // Fixed from 'course_name'
                $lead['state'],         // Fixed from 'state_name'
                $lead['city'],          // Fixed from 'city_name'
                $lead['lead_status'],
                $lead['follow_up_stage'],
                $lead['date']
            ]);
        }

        fclose($output);
    } else {
        echo "No data found matching the selected filters.";
    }
}



// Fetch user IP from user_logs table
$user_ip_query = "SELECT user_ip FROM user_logs ORDER BY id DESC LIMIT 1";
$user_ip_result = $db->query($user_ip_query);
$user_ip = $user_ip_result->fetch_assoc()['user_ip'];
$admin_id = $_SESSION['login_user_id'];

// Fetch username of the admin from the admin table
$admin_query = "SELECT username FROM admin WHERE _id = $admin_id LIMIT 1";
$admin_result = $db->query($admin_query);
$admin_username = $admin_result->fetch_assoc()['username'];

// Prepare the email content
$subject = "Leads Download History";
$message = "
<h4 style='color: #333;'>Leads Downloaded</h4>
<table style='width: 100%; border-collapse: collapse; border: 1px solid #ddd;'>
    <thead>
        <tr>
            <th style='background-color: #f4f4f4; border: 1px solid #ddd; padding: 8px;'>Downloaded By</th>
            <th style='background-color: #f4f4f4; border: 1px solid #ddd; padding: 8px;'>User IP</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style='border: 1px solid #ddd; padding: 8px;'>$admin_username</td>
            <td style='border: 1px solid #ddd; padding: 8px;'>$user_ip</td>
        </tr>
    </tbody>
</table>
";

try {
    $mail = new PHPMailer(true);

    // Server settings
    $mail->isSMTP();
    $mail->Host = 'smtp.hostinger.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'noreply@dsinnovativesolutions.com';
    $mail->Password = 'Dalip@Mano1997'; // Your SMTP password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    // Recipients
    $mail->setFrom('noreply@dsinnovativesolutions.com', 'Admin');
    $mail->addAddress('noreply@dsinnovativesolutions.com', 'Super Admin');

    // Content
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = $message;

    // Send email
    $mail->send();
} catch (Exception $e) {
    echo "Error sending email: {$mail->ErrorInfo}";
}
?>
