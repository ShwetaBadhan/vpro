<?php
require 'vendor/autoload.php';
require_once 'db/config.php';
session_start();

require 'vendor/autoload.php'; // Composer autoload
echo '<pre>';
print_r($_SESSION);
exit;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_GET['emp_id'])) {
    $_SESSION['error'] = "Invalid employee ID.";
    header("Location: requested-salary-slips.php");
    exit();
}

$empId = base64_decode($_GET['emp_id']);
// echo $empId;
// exit;
// Get employee's company email
$stmt = $db->prepare("SELECT c.email, p.name FROM company_details c 
                      JOIN personal_details p ON p.personal_id = c.user_id 
                      WHERE c.user_id = ?");
$stmt->bind_param("i", $empId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Employee not found.";
    header("Location: requested-salary-slips.php");
    exit();
}

$row = $result->fetch_assoc();
$employeeEmail = $row['email'];
$employeeName = $row['name'];

// echo $employeeEmail;
// exit;

// Send email
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = 'smtp.hostinger.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'mailerbot@vibrantick.in'; // Must be a real SMTP address
    $mail->Password = 'MAiler@2025';
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    // Set valid "From"
    $mail->setFrom('mailerbot@vibrantick.in', 'HR Department');

    // Avoid Reply-To unless needed
    // $mail->addReplyTo('reply@email.com', 'Reply Name');

    $mail->addAddress($employeeEmail, $employeeName); // Must be a valid email

    $mail->isHTML(true);
    $mail->Subject = "Salary Slip Approved";
    $mail->Body = "
        <p>Dear $employeeName,</p>
        <p>Your salary slip has been <strong>approved</strong>.</p>
        <p>Please log in to your dashboard and download your salary slip.</p>
        <p><a href='https://yourdomain.com/login'>Go to Dashboard</a></p>
        <br><p>Regards,<br>HR Team</p>
    ";

    $mail->send();
    $_SESSION['success'] = "Email sent successfully to $employeeName.";
} catch (Exception $e) {
    $_SESSION['error'] = "Email could not be sent. Mailer Error: {$mail->ErrorInfo}";
}


header("Location: requested-salary-slips.php");
exit();
