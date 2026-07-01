<?php
// Run this via cron job at 11:59 PM daily
// crontab: 59 23 * * * php /path/to/auto-daily-report.php

require_once 'db/config.php';

$yesterday = date('Y-m-d', strtotime('-1 day'));

// Get summary for yesterday
$summary_query = "SELECT 
    COUNT(*) as total_calls,
    COUNT(DISTINCT admin_id) as total_callers
    FROM call_logs WHERE call_date = '$yesterday'";
$summary = mysqli_fetch_assoc(mysqli_query($db, $summary_query));

// Skip if no calls
if ($summary['total_calls'] == 0) exit;

// Get user-wise data
$user_wise_query = "SELECT 
    username,
    COUNT(*) as total_calls,
    SUM(CASE WHEN lead_status = 'verified' THEN 1 ELSE 0 END) as verified,
    SUM(CASE WHEN lead_status = 'hot' THEN 1 ELSE 0 END) as hot,
    SUM(CASE WHEN lead_status = 'converted' THEN 1 ELSE 0 END) as converted
    FROM call_logs 
    WHERE call_date = '$yesterday'
    GROUP BY admin_id, username
    ORDER BY total_calls DESC";
$user_wise_result = mysqli_query($db, $user_wise_query);

// Generate Excel content
$output = "DAILY CALLING REPORT - " . date('d M Y', strtotime($yesterday)) . "\n\n";
$output .= "SUMMARY\n";
$output .= "Total Calls\t" . $summary['total_calls'] . "\n";
$output .= "Active Callers\t" . $summary['total_callers'] . "\n\n";
$output .= "USER-WISE BREAKDOWN\n";
$output .= "Caller\tTotal Calls\tVerified\tHot\tConverted\n";

while ($row = mysqli_fetch_assoc($user_wise_result)) {
    $output .= $row['username'] . "\t" . 
               $row['total_calls'] . "\t" . 
               $row['verified'] . "\t" . 
               $row['hot'] . "\t" . 
               $row['converted'] . "\n";
}

// Save to file
$filename = 'daily_reports/calling_report_' . $yesterday . '.xls';
if (!is_dir('daily_reports')) mkdir('daily_reports', 0755, true);
file_put_contents($filename, $output);

// Optional: Email to admin
// mail('admin@example.com', 'Daily Calling Report - ' . $yesterday, 'Please find attached report.', ...);

echo "Report generated: $filename\n";
?>