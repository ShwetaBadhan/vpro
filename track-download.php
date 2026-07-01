<?php
// download-tracker.php

// Include PhpSpreadsheet
require 'vendor/autoload.php'; // Include composer autoload file

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if ($_POST['action'] === 'download_leads') {
    // Your database connection
    include('db/config.php');

    // Create new Spreadsheet object
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Get data from database (replace with your actual query)
    $stmt = $pdo->query("SELECT name, email, mobile, state, city, course_type FROM admission_enquiry");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Set headers for the Excel sheet
    $sheet->setCellValue('A1', 'Name');
    $sheet->setCellValue('B1', 'Email');
    $sheet->setCellValue('C1', 'Mobile');
    $sheet->setCellValue('D1', 'State');
    $sheet->setCellValue('E1', 'City');
    $sheet->setCellValue('F1', 'Course Type');

    // Populate data into the spreadsheet
    $rowNum = 2; // Start from the second row
    foreach ($rows as $row) {
        $sheet->setCellValue('A' . $rowNum, $row['name']);
        $sheet->setCellValue('B' . $rowNum, $row['email']);
        $sheet->setCellValue('C' . $rowNum, $row['mobile']);
        $sheet->setCellValue('D' . $rowNum, $row['state']);
        $sheet->setCellValue('E' . $rowNum, $row['city']);
        $sheet->setCellValue('F' . $rowNum, $row['course_type']);
        $rowNum++;
    }

    // Create a temporary file for the Excel file
    $filePath = 'uploads/downloaded_leads_' . time() . '.xlsx';  // Dynamic file name
    $writer = new Xlsx($spreadsheet);
    $writer->save($filePath);

    // Update the download count in the database (optional)
    // Example: $stmt = $pdo->prepare("UPDATE your_table SET download_count = download_count + 1 WHERE some_condition");
    // $stmt->execute();

    // Return the file path to the frontend
    echo json_encode(['filePath' => $filePath]);
}
?>
