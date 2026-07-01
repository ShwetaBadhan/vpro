<?php
session_start();
require_once 'db/config.php';

if (!isset($_SESSION['login_user_id'])) {
    echo '<tr><td colspan="12" class="text-center text-danger">Session expired. Please login again.</td></tr>';
    exit;
}

$login_user_id = $_SESSION['login_user_id'];

// Get admin role and ID
$get_admin_query = "SELECT _id, admin_role FROM admin WHERE _id = ?";
$stmt = mysqli_prepare($db, $get_admin_query);
mysqli_stmt_bind_param($stmt, "i", $login_user_id);
mysqli_stmt_execute($stmt);
$get_admin_result = mysqli_stmt_get_result($stmt);
$admin_row = mysqli_fetch_assoc($get_admin_result);
mysqli_stmt_close($stmt);

if (!$admin_row) {
    echo '<tr><td colspan="12" class="text-center text-danger">Admin not found.</td></tr>';
    exit;
}

$admin_id = $admin_row['_id'];

// Get role name
$role_query = "SELECT role_name FROM roles WHERE role_id = ?";
$stmt = mysqli_prepare($db, $role_query);
mysqli_stmt_bind_param($stmt, "i", $admin_row['admin_role']);
mysqli_stmt_execute($stmt);
$role_result = mysqli_stmt_get_result($stmt);
$role_row = mysqli_fetch_assoc($role_result);
mysqli_stmt_close($stmt);

$role_name = strtolower($role_row['role_name']);

// Get filter values
$status = isset($_POST['status']) ? trim($_POST['status']) : '';
$dateFrom = isset($_POST['dateFrom']) ? trim($_POST['dateFrom']) : '';
$dateTo = isset($_POST['dateTo']) ? trim($_POST['dateTo']) : '';
$userFilter = isset($_POST['userFilter']) ? intval($_POST['userFilter']) : 0;
$followUpFilter = isset($_POST['followUpFilter']) ? trim($_POST['followUpFilter']) : '';

// Base query - REMOVED category joins
$baseQuery = "
SELECT 
    ae.admission_id,
    ae.name,
    ae.email,
    ae.mobile,
    ae.course_type,
    ae.follow_up_stage,
    ae.remarks,
    ae.state,
    ae.city,
    ae.lead_status,
    ae.date,
    a.username AS assigned_admin
FROM admission_enquiry ae
LEFT JOIN lead_assignments la ON ae.admission_id = la.admission_id
LEFT JOIN admin a ON la.admin_id = a._id
WHERE 1=1
";

$conditions = [];
$params = [];
$types = "";

// Role-based filtering
if ($role_name != 'admin') {
    $conditions[] = "la.admin_id = ?";
    $params[] = $admin_id;
    $types .= "i";
}

// Status filter
if (!empty($status)) {
    $conditions[] = "ae.lead_status = ?";
    $params[] = $status;
    $types .= "s";
}

// Date range filter
if (!empty($dateFrom)) {
    $conditions[] = "DATE(ae.date) >= ?";
    $params[] = $dateFrom;
    $types .= "s";
}

if (!empty($dateTo)) {
    $conditions[] = "DATE(ae.date) <= ?";
    $params[] = $dateTo;
    $types .= "s";
}

// User filter
if ($userFilter > 0) {
    $conditions[] = "la.admin_id = ?";
    $params[] = $userFilter;
    $types .= "i";
}

// Follow-up filter
if (!empty($followUpFilter)) {
    if ($followUpFilter == 'Not Set') {
        $conditions[] = "(ae.follow_up_stage IS NULL OR ae.follow_up_stage = '')";
    } else {
        $conditions[] = "ae.follow_up_stage = ?";
        $params[] = $followUpFilter;
        $types .= "s";
    }
}

// Build final query
if (!empty($conditions)) {
    $baseQuery .= " AND " . implode(" AND ", $conditions);
}

$baseQuery .= " ORDER BY ae.date DESC";

// Execute query
if (!empty($params)) {
    $stmt = mysqli_prepare($db, $baseQuery);
    if (!$stmt) {
        echo '<tr><td colspan="12" class="text-center text-danger">Database error: ' . mysqli_error($db) . '</td></tr>';
        exit;
    }
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = mysqli_query($db, $baseQuery);
    if (!$result) {
        echo '<tr><td colspan="12" class="text-center text-danger">Database error: ' . mysqli_error($db) . '</td></tr>';
        exit;
    }
}

// Badge maps
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

$followUpMap = [
    '1st Contact' => '#17a2b8',
    '2nd Contact' => '#ffc107',
    '3rd Contact' => '#FE7743',
    '4th Contact' => '#FFB22C',
    '5th Contact' => '#E52020',
    '6th Contact' => '#A76545',
    '7th Contact' => '#854836',
    'Converted'   => '#28a745',
    'Lost'        => '#6c757d'
];

// Check permissions
$can_assign_leads = ($role_name == 'admin');
$can_delete_leads = ($role_name == 'admin');

// Total columns: 12 (checkbox, sno, mobile, name, state, city, course, remarks, status, followup, date, action)
$totalColumns = 12;

// Generate table rows
$count = 1;
if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $encoded_id = base64_encode($row['admission_id']);
        $leadStatus = strtolower($row['lead_status']);
        $badgeColor = $badgeMap[$leadStatus] ?? '#6c757d';

        $remarks = $row['remarks'];
        $shortRemarks = strlen($remarks) > 40 ? substr($remarks, 0, 40) . '...' : $remarks;

        $followUpStage = $row['follow_up_stage'] ?? '';
        $followUpColor = $followUpMap[$followUpStage] ?? '#e9ecef';
        $followUpText = !empty($followUpStage) ? $followUpStage : 'Not Set';

        echo "<tr>";
        echo "<td><input type='checkbox' class='menuCheckbox' name='advt_ids[]' value='$encoded_id'></td>";
        echo "<td>$count</td>";
        echo "<td>" . htmlspecialchars($row['mobile']) . "</td>";
        echo "<td><strong>" . htmlspecialchars($row['name']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($row['state']) . "</td>";
        echo "<td>" . htmlspecialchars($row['city']) . "</td>";
        echo "<td>" . htmlspecialchars($row['course_type']) . "</td>";
        // ✅ REMOVED CATEGORY COLUMN
        echo "<td title='" . htmlspecialchars($row['remarks']) . "'>" . htmlspecialchars($shortRemarks) . "</td>";
        echo "<td><span class='badge status-badge' style='background-color: $badgeColor; color: #fff;'>" . ucfirst($leadStatus) . "</span></td>";
        echo "<td><span class='badge status-badge' style='background-color: $followUpColor; color: #fff;'>" . htmlspecialchars($followUpText) . "</span></td>";
        echo "<td>" . date("d-m-Y", strtotime($row['date'])) . "</td>";
        
        echo "<td>";
        echo "<a href='javascript:void(0)' class='btn btn-sm btn-warning btn-action viewLead' data-id='$encoded_id' title='View'><i class='feather icon-eye'></i></a>";
        $followUpData = htmlspecialchars($row['follow_up_stage'] ?? '');
        echo "<a href='javascript:void(0)' class='btn btn-sm btn-success btn-action editLead' 
            data-lead-id='$encoded_id' 
            data-status='" . htmlspecialchars($row['lead_status']) . "' 
            data-follow-up='" . $followUpData . "' 
            data-remark='" . htmlspecialchars($row['remarks']) . "' 
            title='Edit'><i class='feather icon-edit'></i></a>";
        if ($can_delete_leads) {
            echo "<a href='javascript:void(0)' class='btn btn-sm btn-danger btn-action delete-btn' data-id='$encoded_id' data-bs-toggle='modal' data-bs-target='#deleteModal' title='Delete'><i class='feather icon-trash'></i></a>";
        }
        echo "</td>";
        echo "</tr>";

        $count++;
    }
} else {
    echo "<tr><td colspan='$totalColumns' class='text-center py-5'><div class='empty-state'><i class='feather icon-inbox'></i><h5>No Leads Found</h5><p class='text-muted'>No leads match your filters</p></div></td></tr>";
}
?>