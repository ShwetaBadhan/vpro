<?php
include("db/config.php");
error_reporting(E_ALL);
ini_set('display_errors', 1); // Display errors for debugging

if (isset($_GET['id'])) {
    $id = base64_decode($_GET['id']);
    $id = mysqli_real_escape_string($db, $id);

    // Join all required IDs in a single query
    $select_query = "
    SELECT 
        rb.name, rb.email, rb.mobile, rb.date, rb.course_name, rb.state, rb.city, rb.lead_status , rb.remarks
    FROM recycle_bin rb
    WHERE rb.recycle_id = '$id'
    ";

    $result = mysqli_query($db, $select_query);

    if (!$result) {
        die("Error fetching data: " . mysqli_error($db));
    }

    $row = mysqli_fetch_assoc($result);
    // echo '<pre>';
    // print_r($row);
    // exit();
    if ($row) {
        $name = mysqli_real_escape_string($db, $row['name']);
        $email = mysqli_real_escape_string($db, $row['email']);
        $mobile = mysqli_real_escape_string($db, $row['mobile']);
        $date = mysqli_real_escape_string($db, $row['date']);
        $course_id = $row['course_name'];
        $state_id = $row['state'];
        $city_id = $row['city'];
        $remarks = $row['remarks'];
        $lead_status_id = $row['lead_status'];
        // echo '<pre>';
        // print_r($row);
        // exit();
        // Check for missing IDs
        // if (!$course_id || !$state_id || !$city_id || !$lead_status_id) {
        //     error_log("Missing one or more IDs: course_name=$course_id, state_id=$state_id, city_id=$city_id, lead_status_id=$lead_status_id");
        //     header("Location: admission-leads.php?status=" . base64_encode(0) . "&error=" . urlencode("Missing reference IDs"));
        //     exit();
        // }

        // Insert back into admission_enquiry
       
        $insert_query = "
        
        INSERT INTO admission_enquiry (name, email, mobile, course_type, state, city,remarks, lead_status, date)
        VALUES ('$name', '$email', '$mobile', '$course_id', '$state_id', '$city_id','$remarks', '$lead_status_id', '$date')
        ";

        if (mysqli_query($db, $insert_query)) {
            // Delete from recycle_bin
            $delete_query = "DELETE FROM recycle_bin WHERE recycle_id = '$id'";
            mysqli_query($db, $delete_query);
            header("Location: admission-leads.php?status=" . base64_encode(1));
            exit();
        } else {
            die("Error restoring record: " . mysqli_error($db));
        }
    } else {
        // No record found
        header("Location: admission-leads.php?status=" . base64_encode(0) . "&error=" . urlencode("No data found"));
        exit();
    }
} else {
    header("Location: admission-leads.php?status=" . base64_encode(0) . "&error=" . urlencode("Invalid Request"));
    exit();
}
?>
