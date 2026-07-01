<?php
error_reporting(E_ALL);
include('db/config.php');

// Check if the request contains the ID
if (isset($_POST['id'])) {
    $encoded_id = $_POST['id'];
    $client_id = base64_decode($encoded_id); // Decode the ID

    // Query to fetch lead details for the specific client_id
    $query = "
    SELECT  
    clients.client_id,
    clients.name, 
    clients.client_primary_person, 
    clients.phone, 
    clients.email, 
    clients.address, 
    clients.services, 
    clients.active_date, 
    clients.deactive_date, 
    clients.created_at, 
    clients.renewal_date,
    clients.remarks,
    clients.status,
    state.state_name,
   city.city_name
FROM clients
LEFT JOIN state ON state.state_id = clients.state
LEFT JOIN city ON city.city_id = clients.city
    WHERE 
        clients.client_id = '$client_id'
    ORDER BY 
        clients.created_at DESC";


    $result = mysqli_query($db, $query);

    if ($result && $result->num_rows > 0) {
        $lead = $result->fetch_assoc();

        // Display the lead details
        echo "
        <table class='table table-bordered'>";
        echo "   <tr><th>Name</th><td>{$lead['name']}</td></tr>";
        echo "   <tr><th>Primary Person</th><td>{$lead['client_primary_person']}</td></tr>";
        echo "  <tr><th>Email</th><td>{$lead['email']}</td></tr>";
        echo "  <tr><th>Mobile</th><td>{$lead['phone']}</td></tr>";
        $wrappedAdderss = wordwrap($lead['address'], 100, '<br>', true);
        echo " <tr><th>Address</th><td>{$wrappedAdderss}</td></tr>";
         $wrappedServices = wordwrap($lead['services'], 100, '<br>', true);
        echo " <tr><th>Services</th><td>{$wrappedServices}</td></tr>";
        echo " <tr><th>State</th><td>{$lead['state_name']}</td></tr>";
        echo "  <tr><th>City</th><td>{$lead['city_name']}</td></tr>";
        echo "  <tr><th>Active Date</th><td>" . $lead['active_date']."</td></tr>";
        echo "  <tr><th>Deactive Date</th><td>" . $lead['deactive_date']."</td></tr>";
         echo "  <tr><th>Renewal Date</th><td>" . $lead['renewal_date']."</td></tr>";
          $wrappedRemarks = wordwrap($lead['remarks'], 80, "<br>", true);
echo "<tr><th>Remarks</th><td>{$wrappedRemarks}</td></tr>";
       echo "<tr><th>Created At</th><td>" . date('d-m-Y', strtotime($lead['created_at'])) . "</td></tr>";
        
        echo "  </table>";
    } else {
        echo "<p>No details found for this Client.</p>";
    }
} else {
    echo "<p>Invalid request.</p>";
}
