<?php
error_reporting(E_ALL);
include('db/config.php');
session_start();

// Check if ID is provided
if (isset($_POST['id'])) {
    $encoded_id = $_POST['id'];
    $user_id = base64_decode($encoded_id);

    // Fetch employee details from all three tables
    $query = "
    SELECT 
    p.personal_id,
    p.name,
    p.father_name,
    p.dob,
    p.mobile,
    p.email,
    p.address,
    p.state,
    p.city,
    p.status AS personal_status,
    p.blood_group,
    p.adhar_no,
    p.pan_no,
    p.photo,

    b.ifsc_code,
    b.account_no,
    b.bank_name,

    c.doj,
    c.email AS company_email,
    c.laptop_mobile,
    c.employee_status,
    c.id_card,
    c.device_details,
    c.last_working_day,
    c.verified_by,
    c.verification_date,
    c.employee_code,
    c.work_assigned,
    

    position.name AS designation,

    GROUP_CONCAT(clients.name SEPARATOR ', ') AS assigned_clients,

    state.state_name,
    city.city_name

FROM personal_details p
LEFT JOIN bank_details b ON b.user_id = p.personal_id
LEFT JOIN company_details c ON c.user_id = p.personal_id
LEFT JOIN state ON state.state_id = p.state
LEFT JOIN city ON city.city_id = p.city
LEFT JOIN position ON position.position_id = c.designation
LEFT JOIN clients ON FIND_IN_SET(clients.client_id, c.assigned_client)

WHERE p.personal_id = '$user_id'

GROUP BY p.personal_id

";


    $result = mysqli_query($db, $query);

    if ($result && $result->num_rows > 0) {
        $emp = $result->fetch_assoc();

       echo "<table class='table table-bordered'>";
echo "<thead class='table-light'><tr><th colspan='6'>Personal Details</th></tr></thead>";
echo"<tr>
<td><img src='{$emp ['photo']}' style='width:100px; height: 100px'></td>

</tr>";
echo "<tr>
    <th>Name</th><td>{$emp['name']}</td>
    <th>Father Name</th><td>{$emp['father_name']}</td>
    <th>DOB</th><td>" . date('d-m-Y', strtotime($emp['dob'])) . "</td>
</tr>";



$wrappedAdderss = wordwrap($emp['address'], 20, '<br>', true);
$wrappedEmails = wordwrap($emp['email'], 20, '<br>', true);


echo "<tr>
    <th>Mobile</th><td>{$emp['mobile']}</td>
    <th>Email</th><td>{$wrappedEmails}</td>
    <th>Address</th><td>{$wrappedAdderss}</td>
</tr>";


echo "<tr>
    <th>Adhar No</th><td>{$emp['adhar_no']}</td>
    <th>PAN No</th><td>{$emp['pan_no']}</td>
    
</tr>";

echo "<thead class='table-light'><tr><th colspan='6'>Bank Details</th></tr></thead>";
$wrappedBank = wordwrap($emp['bank_name'], 20, '<br>', true);
echo "<tr>
<th>Bank Name</th><td>{$wrappedBank}</td>
    <th>Account No</th><td>{$emp['account_no']}</td>
    <th>IFSC Code</th><td>{$emp['ifsc_code']}</td>
    
</tr>";

echo "<thead class='table-light'><tr><th colspan='6'>Company Details</th></tr></thead>";
$wrappedEmail = wordwrap($emp['company_email'], 15, '<br>', true);
$wrappedDesignation = wordwrap($emp['designation'], 20, '<br>', true);

echo "<tr>
    <th>Designation</th><td>{$wrappedDesignation}</td>
    <th>Employee Code</th><td>{$emp['employee_code']}</td>
   <th>Joining Date</th><td>" . (!empty($emp['doj']) ? date('d-m-Y', strtotime($emp['doj'])) : '-') . "</td>
  
    
</tr>";
$wrappedClients = wordwrap($emp['assigned_clients'], 20, '<br>', true);
$wrappedWork = wordwrap($emp['work_assigned'], 20, '<br>', true);
echo "<tr>
  <th>Official Email</th><td>{$wrappedEmail}</td>
    <th>Clients Assigned</th>
    <td colspan=''>{$wrappedClients}</td>
    <th>Work Assigned</th>
    <td colspan=''>{$wrappedWork}</td>
</tr>";

echo "<tr>
    <th>ID Card</th><td>" . ($emp['id_card'] == '1' ? 'Yes' : 'No') . "</td>
    <th>Laptop/Mobile</th><td>";

$wrappedDevice = wordwrap($emp['device_details'] ?? '', 30, '<br>', true);

if ($emp['laptop_mobile'] == '1') {
    echo "Yes";
    if (!empty($emp['device_details'])) {
        echo "<br><strong>Device Details:</strong> " . $wrappedDevice;
    }
} else {
    echo "No";
}


echo "</td>
    <th>Employee Status</th><td>" . ($emp['employee_status'] == '1' ? 'Active' : 'Inactive') . "</td>
</tr>";

echo "<tr>
<th>Last Working Day</th><td>". (!empty($emp['last_working_day']) ? date('d-m-Y', strtotime($emp['last_working_day'])) : '-') . "</td>
<th>Handover Name</th><td>".($emp['verified_by'])."</td>
<th>Handover Date</th><td>".(!empty($emp['verification_date']) ? date('d-m-Y', strtotime($emp['verification_date'])) : '-')."</td>

</tr>";
echo "</table>";
    }else{
        echo 'Inavlid Request';
    }
}