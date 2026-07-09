<?php
error_reporting(E_ALL);
include('db/config.php');

// Check if the request contains the ID
if (isset($_POST['id'])) {
    $encoded_id = $_POST['id'];
    $admission_id = base64_decode($encoded_id); // Decode the ID

    // Query to fetch lead details for the specific admission_id
    $query = "
    SELECT 
        admission_enquiry.admission_id,
        admission_enquiry.name,
        admission_enquiry.email,
        admission_enquiry.mobile,
        admission_enquiry.course_type,
        admission_enquiry.symptoms,
        admission_enquiry.remarks,

        admission_enquiry.city,
       admission_enquiry.lead_status,
        admission_enquiry.date
    FROM 
        admission_enquiry
    WHERE 
        admission_enquiry.admission_id = '$admission_id'
    ORDER BY 
        admission_enquiry.date DESC";


    $result = mysqli_query($db, $query);

    if ($result && $result->num_rows > 0) {
        $lead = $result->fetch_assoc();

        // Display the lead details
        echo "
        <table class='table table-bordered'>";
        echo "   <tr><th>Name</th><td>{$lead['name']}</td></tr>";
        echo "  <tr><th>Email</th><td>{$lead['email']}</td></tr>";
        echo "  <tr><th>Mobile</th><td>{$lead['mobile']}</td></tr>";
        echo " <tr><th>Treatment</th><td>{$lead['course_type']}</td></tr>";
        echo " <tr><th>Symptoms</th><td>{$lead['symptoms']}</td></tr>";

        echo "  <tr><th>City</th><td>{$lead['city']}</td></tr>";
       echo "<tr><th>Date</th><td>" . date('d-m-Y', strtotime($lead['date'])) . "</td></tr>";
        echo "  <tr><th>Lead Status</th>";

        $status = strtolower($lead['lead_status']); 

        $badgeMap = [
            'untouched' => '#FF0B55',
            'verified' => '#28a745',
            'hot' => '#E52020',
            'cold' => '#17a2b8',
            'followup' => '#FE7743',
            'warm' => '#FFB22C',
            'not answering' => '#A76545',
            'call after sometime' => '#ffc107',
            'not reached' => '#854836',
            'lead own' => '#096B68'
        ];
        $badgeColor = $badgeMap[$status] ?? '#6c757d';
        $textColor = '#fff';

        // Output table cell with colored badge
        echo "<td><span class='badge' style='font-size:14px; background-color: $badgeColor; color: $textColor;'>" . ucfirst($status) . "</span></td>";
       $wrappedRemarks = wordwrap($lead['remarks'], 80, "<br>", true);
echo "<tr><th>Remarks</th><td>{$wrappedRemarks}</td></tr>";
        echo "  </table>";
    } else {
        echo "<p>No details found for this lead.</p>";
    }
} else {
    echo "<p>Invalid request.</p>";
}
