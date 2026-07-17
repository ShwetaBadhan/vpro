<?php
session_start();
date_default_timezone_set('Asia/Kolkata');
require_once 'db/config.php';

if (!isset($_SESSION['login_user_id'])) {
    header('Location: login.php');
    exit;
}

$login_user_id = $_SESSION['login_user_id'];

// Get current user info
$get_admin_query = "SELECT _id, username, admin_role FROM admin WHERE _id = ?";
$stmt = mysqli_prepare($db, $get_admin_query);
mysqli_stmt_bind_param($stmt, "i", $login_user_id);
mysqli_stmt_execute($stmt);
$admin_row = mysqli_stmt_get_result($stmt)->fetch_assoc();
mysqli_stmt_close($stmt);

$role_id = $admin_row['admin_role'];
$get_role_query = "SELECT role_name FROM roles WHERE role_id = ?";
$stmt = mysqli_prepare($db, $get_role_query);
mysqli_stmt_bind_param($stmt, "i", $role_id);
mysqli_stmt_execute($stmt);
$role_row = mysqli_stmt_get_result($stmt)->fetch_assoc();
mysqli_stmt_close($stmt);
$role_name = strtolower($role_row['role_name']);

$is_admin = in_array($role_name, ['admin', 'manager']);

// Date filter
$filter_date = $_GET['date'] ?? date('Y-m-d');
$filter_from = $_GET['from_date'] ?? '';
$filter_to = $_GET['to_date'] ?? '';
$filter_user = $_GET['user'] ?? '';

// Build WHERE clause
$where_conditions = [];

if (!empty($filter_from) && !empty($filter_to)) {
    $where_conditions[] = "call_date BETWEEN '" . mysqli_real_escape_string($db, $filter_from) . "' 
                           AND '" . mysqli_real_escape_string($db, $filter_to) . "'";
    $date_range_display = date('d M Y', strtotime($filter_from)) . ' to ' . date('d M Y', strtotime($filter_to));
} elseif (!empty($filter_date)) {
    $where_conditions[] = "call_date = '" . mysqli_real_escape_string($db, $filter_date) . "'";
    $date_range_display = date('d M Y', strtotime($filter_date));
} else {
    $date_range_display = date('d M Y');
}

if (!empty($filter_user)) {
    $where_conditions[] = "admin_id = " . intval($filter_user);
}

if (!$is_admin) {
    $where_conditions[] = "admin_id = " . intval($login_user_id);
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// ✅ EXCEL DOWNLOAD - MUST BE AT TOP BEFORE ANY HTML OUTPUT
if (isset($_GET['download']) && $_GET['download'] == 'excel') {
    // Get summary statistics
    $summary_query = "SELECT 
        COUNT(*) as total_calls,
        COUNT(DISTINCT admin_id) as total_callers,
        COUNT(DISTINCT admission_id) as unique_leads_called,
        SUM(CASE WHEN lead_status = 'interested' THEN 1 ELSE 0 END) as interested_count,
        SUM(CASE WHEN lead_status = 'hot' THEN 1 ELSE 0 END) as hot_count,
        SUM(CASE WHEN lead_status = 'warm' THEN 1 ELSE 0 END) as warm_count,
        SUM(CASE WHEN follow_up_stage = 'Converted' THEN 1 ELSE 0 END) as converted_count,
        SUM(CASE WHEN follow_up_stage = 'Lost' THEN 1 ELSE 0 END) as lost_count
        FROM call_logs $where_clause";
    $summary_result = mysqli_query($db, $summary_query);
    $summary = mysqli_fetch_assoc($summary_result);

    // Get user-wise breakdown
    $user_wise_query = "SELECT 
        admin_id,
        username,
        COUNT(*) as total_calls,
        COUNT(DISTINCT admission_id) as unique_leads,
        SUM(CASE WHEN lead_status = 'interested' THEN 1 ELSE 0 END) as interested,
        SUM(CASE WHEN lead_status = 'hot' THEN 1 ELSE 0 END) as hot,
        SUM(CASE WHEN lead_status = 'warm' THEN 1 ELSE 0 END) as warm,
        SUM(CASE WHEN follow_up_stage = 'Converted' THEN 1 ELSE 0 END) as converted,
        SUM(CASE WHEN follow_up_stage = 'Lost' THEN 1 ELSE 0 END) as lost,
        MIN(call_time) as first_call,
        MAX(call_time) as last_call
        FROM call_logs $where_clause
        GROUP BY admin_id, username
        ORDER BY total_calls DESC";
    $user_wise_result = mysqli_query($db, $user_wise_query);

    // Get all call logs
    $logs_query = "SELECT cl.*, a.username as caller_name
        FROM call_logs cl
        LEFT JOIN admin a ON cl.admin_id = a._id
        $where_clause
        ORDER BY cl.call_date DESC, cl.call_time DESC";
    $logs_result = mysqli_query($db, $logs_query);

    // Clear any output buffers
    if (ob_get_level()) ob_end_clean();

    // Set headers for Excel download
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=calling_report_" . date('Y-m-d') . ".xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    $output = '';

    // Title
    $output = '';
    $output .= "DAILY CALLING REPORT\n";
    $output .= "Date: " . $date_range_display . "\n";
    $output .= "Generated On: " . date('d M Y H:i:s') . "\n\n";

    // Summary Section
    $output .= "SUMMARY\n";
    $output .= "Total Calls\t" . ($summary['total_calls'] ?? 0) . "\n";
    $output .= "Active Callers\t" . ($summary['total_callers'] ?? 0) . "\n";
    $output .= "Unique Leads Called\t" . ($summary['unique_leads_called'] ?? 0) . "\n";
    $output .= "Interested\t" . ($summary['interested_count'] ?? 0) . "\n";
    $output .= "Hot Leads\t" . ($summary['hot_count'] ?? 0) . "\n";
    $output .= "Warm Leads\t" . ($summary['warm_count'] ?? 0) . "\n";
    $output .= "Converted\t" . ($summary['converted_count'] ?? 0) . "\n";
    $output .= "Lost\t" . ($summary['lost_count'] ?? 0) . "\n\n";

    // User-wise breakdown
    $output .= "USER-WISE BREAKDOWN\n";
    $output .= "Caller\tTotal Calls\tUnique Leads\tInterested\tHot\tWarm\tConverted\tLost\tFirst Call\tLast Call\n";

    while ($row = mysqli_fetch_assoc($user_wise_result)) {
        $output .= $row['username'] . "\t" .
            $row['total_calls'] . "\t" .
            $row['unique_leads'] . "\t" .
            $row['interested'] . "\t" .
            $row['hot'] . "\t" .
            $row['warm'] . "\t" .
            $row['converted'] . "\t" .
            $row['lost'] . "\t" .
            ($row['first_call'] ? date('H:i:s', strtotime($row['first_call'])) : '-') . "\t" .
            ($row['last_call'] ? date('H:i:s', strtotime($row['last_call'])) : '-') . "\n";
    }


    $output .= "\n\nDETAILED CALL LOG\n";
    $output .= "Date\tTime\tCaller\tLead Name\tMobile\tStatus\tFollow-Up Stage\tRemarks\n";

    while ($log = mysqli_fetch_assoc($logs_result)) {
        $output .= date('d M Y', strtotime($log['call_date'])) . "\t" .
            date('H:i:s', strtotime($log['call_time'])) . "\t" .
            $log['username'] . "\t" .
            $log['lead_name'] . "\t" .
            $log['lead_mobile'] . "\t" .
            ucfirst($log['lead_status']) . "\t" .
            ($log['follow_up_stage'] ?: 'Not Set') . "\t" .
            str_replace(["\r", "\n", "\t"], ' ', $log['remarks'] ?? '') . "\n";
    }

    echo $output;
    exit;
}

// Get summary statistics
$summary_query = "SELECT 
    COUNT(*) as total_calls,
    COUNT(DISTINCT admin_id) as total_callers,
    COUNT(DISTINCT admission_id) as unique_leads_called,
    SUM(CASE WHEN lead_status = 'interested' THEN 1 ELSE 0 END) as interested_count,
    SUM(CASE WHEN lead_status = 'hot' THEN 1 ELSE 0 END) as hot_count,
    SUM(CASE WHEN lead_status = 'warm' THEN 1 ELSE 0 END) as warm_count,
    SUM(CASE WHEN follow_up_stage = 'Converted' THEN 1 ELSE 0 END) as converted_count,
    SUM(CASE WHEN follow_up_stage = 'Lost' THEN 1 ELSE 0 END) as lost_count
    FROM call_logs $where_clause";
$summary_result = mysqli_query($db, $summary_query);
$summary = mysqli_fetch_assoc($summary_result);

// Get user-wise breakdown
$user_wise_query = "SELECT 
    admin_id,
    username,
    COUNT(*) as total_calls,
    COUNT(DISTINCT admission_id) as unique_leads,
    SUM(CASE WHEN lead_status = 'interested' THEN 1 ELSE 0 END) as interested,
    SUM(CASE WHEN lead_status = 'hot' THEN 1 ELSE 0 END) as hot,
    SUM(CASE WHEN lead_status = 'warm' THEN 1 ELSE 0 END) as warm,
    SUM(CASE WHEN follow_up_stage = 'Converted' THEN 1 ELSE 0 END) as converted,
    SUM(CASE WHEN follow_up_stage = 'Lost' THEN 1 ELSE 0 END) as lost,
    MIN(call_time) as first_call,
    MAX(call_time) as last_call
    FROM call_logs $where_clause
    GROUP BY admin_id, username
    ORDER BY total_calls DESC";
$user_wise_result = mysqli_query($db, $user_wise_query);

// Get all call logs
$logs_query = "SELECT cl.*, a.username as caller_name
    FROM call_logs cl
    LEFT JOIN admin a ON cl.admin_id = a._id
    $where_clause
    ORDER BY cl.call_date DESC, cl.call_time DESC";
$logs_result = mysqli_query($db, $logs_query);

// Get users for filter dropdown
$users_query = "SELECT _id, username FROM admin ORDER BY username ASC";
$users_result = mysqli_query($db, $users_query);
$users = [];
while ($u = mysqli_fetch_assoc($users_result)) {
    $users[] = $u;
}

// Fetch settings
$settings_query = "SELECT * FROM login_settings LIMIT 1";
$settingsResult = mysqli_query($db, $settings_query);
$settings = mysqli_fetch_assoc($settingsResult);
$favicon = $settings['favicon'] ?? 'assets/images/favicon.ico';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Daily Calling Report</title>
    <link rel="icon" href="<?php echo $favicon; ?>" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/plugins/dataTables.bootstrap4.min.css">
    <style>
        /* ✅ BASIC COLORS - No Gradients */
        .report-header {
            background: #007bff;
            /* Primary Blue */
            color: white;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 25px;
        }

        .report-title {
            font-size: 24px;
            font-weight: 600;
            margin: 0 0 5px 0;
        }

        .report-subtitle {
            opacity: 0.9;
            font-size: 14px;
        }

        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border-left: 4px solid;
        }

        .stat-card.primary {
            border-color: #007bff;
        }

        .stat-card.success {
            border-color: #28a745;
        }

        .stat-card.warning {
            border-color: #ffc107;
        }

        .stat-card.danger {
            border-color: #dc3545;
        }

        .stat-card.info {
            border-color: #17a2b8;
        }

        .stat-card.secondary {
            border-color: #6c757d;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 13px;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .filter-section {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            align-items: end;
        }

        .filter-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #495057;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 14px;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #007bff;
        }

        .btn-excel {
            background: #28a745;
            /* Success Green */
            color: white;
            padding: 10px 25px;
            border: none;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-excel:hover {
            background: #218838;
            color: white;
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #2d3748;
            margin: 25px 0 15px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-table {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .user-table table {
            margin: 0;
        }

        .user-table thead th {
            background: #f8f9fa;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #495057;
            padding: 12px;
            border-bottom: 2px solid #dee2e6;
        }

        .user-table tbody td {
            padding: 12px;
            vertical-align: middle;
        }

        .user-avatar-sm {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: #007bff;
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 13px;
            margin-right: 8px;
        }

        .mini-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-primary {
            background: #e7f1ff;
            color: #007bff;
        }

        .badge-success {
            background: #d4edda;
            color: #28a745;
        }

        .badge-warning {
            background: #fff3cd;
            color: #ffc107;
        }

        .badge-danger {
            background: #f8d7da;
            color: #dc3545;
        }

        .badge-info {
            background: #d1ecf1;
            color: #17a2b8;
        }

        .badge-secondary {
            background: #e2e3e5;
            color: #6c757d;
        }

        .progress-bar-custom {
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 5px;
        }

        .progress-fill {
            height: 100%;
            background: #007bff;
            border-radius: 4px;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
    </style>
</head>

<body>
    <?php include("header.php"); ?>
    <?php include("navbar.php"); ?>

    <section class="pcoded-main-container">
        <div class="pcoded-content">
            <div class="page-header">
                <div class="page-block">
                    <div class="row align-items-center">
                        <div class="col-md-12">
                            <div class="page-header-title">
                                <h5 class="m-b-10">Daily Calling Report</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Report Header -->
            <div class="report-header">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <h2 class="report-title">
                            <i class="feather icon-phone-call"></i> Calling Report
                        </h2>
                        <div class="report-subtitle">
                            <i class="feather icon-calendar"></i> <?php echo $date_range_display; ?>
                        </div>
                    </div>
                    <div class="action-buttons">
                        <a href="?download=excel&date=<?php echo urlencode($filter_date); ?>&from_date=<?php echo urlencode($filter_from); ?>&to_date=<?php echo urlencode($filter_to); ?>&user=<?php echo urlencode($filter_user); ?>" class="btn-excel">
                            <i class="feather icon-download"></i> Download Excel
                        </a>
                    </div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <form method="GET" action="">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label>Specific Date</label>
                            <input type="date" name="date" value="<?php echo htmlspecialchars($filter_date); ?>">
                        </div>
                        <div class="filter-group">
                            <label>From Date</label>
                            <input type="date" name="from_date" value="<?php echo htmlspecialchars($filter_from); ?>">
                        </div>
                        <div class="filter-group">
                            <label>To Date</label>
                            <input type="date" name="to_date" value="<?php echo htmlspecialchars($filter_to); ?>">
                        </div>
                        <?php if ($is_admin): ?>
                            <div class="filter-group">
                                <label>User</label>
                                <select name="user">
                                    <option value="">All Users</option>
                                    <?php foreach ($users as $u): ?>
                                        <option value="<?php echo $u['_id']; ?>" <?php echo $filter_user == $u['_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($u['username']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>
                        <div class="filter-group">
                            <label>&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="feather icon-filter"></i> Apply
                                </button>
                                <a href="daily-calling-report.php" class="btn btn-secondary btn-sm">
                                    <i class="feather icon-x"></i> Clear
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Statistics -->
            <div class="stat-grid">
                <div class="stat-card primary">
                    <div class="stat-value"><?php echo $summary['total_calls'] ?? 0; ?></div>
                    <div class="stat-label">Total Calls</div>
                </div>
                <div class="stat-card success">
                    <div class="stat-value"><?php echo $summary['total_callers'] ?? 0; ?></div>
                    <div class="stat-label">Active Callers</div>
                </div>
                <div class="stat-card warning">
                    <div class="stat-value"><?php echo $summary['unique_leads_called'] ?? 0; ?></div>
                    <div class="stat-label">Unique Leads</div>
                </div>
                <div class="stat-card info">
                    <div class="stat-value"><?php echo $summary['interested_count'] ?? 0; ?></div>
                    <div class="stat-label">Interested</div>
                </div>
                <div class="stat-card danger">
                    <div class="stat-value"><?php echo $summary['hot_count'] ?? 0; ?></div>
                    <div class="stat-label">Hot Leads</div>
                </div>
                <div class="stat-card secondary">
                    <div class="stat-value"><?php echo $summary['converted_count'] ?? 0; ?></div>
                    <div class="stat-label">Converted</div>
                </div>
            </div>

            <!-- User-wise Breakdown -->
            <h4 class="section-title">
                <i class="feather icon-users"></i> User-wise Calling Summary
            </h4>
            <div class="user-table">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Caller</th>
                            <th>Total Calls</th>
                            <th>Unique Leads</th>
                            <th>Interested</th>
                            <th>Hot</th>
                            <th>Warm</th>
                            <th>Converted</th>
                            <th>Lost</th>
                            <th>First Call</th>
                            <th>Last Call</th>
                            <th>Performance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $max_calls = 0;
                        $user_data = [];
                        while ($row = mysqli_fetch_assoc($user_wise_result)) {
                            $user_data[] = $row;
                            if ($row['total_calls'] > $max_calls) $max_calls = $row['total_calls'];
                        }

                        if (empty($user_data)):
                        ?>
                            <tr>
                                <td colspan="12" class="text-center py-4 text-muted">
                                    <i class="feather icon-inbox" style="font-size: 32px;"></i><br>
                                    No calls recorded for this period
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($user_data as $index => $row):
                                $initials = strtoupper(substr($row['username'], 0, 2));
                                $performance = $max_calls > 0 ? round(($row['total_calls'] / $max_calls) * 100) : 0;
                            ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td>
                                        <span class="user-avatar-sm"><?php echo $initials; ?></span>
                                        <strong><?php echo htmlspecialchars($row['username']); ?></strong>
                                    </td>
                                    <td><span class="mini-badge badge-primary"><?php echo $row['total_calls']; ?></span></td>
                                    <td><?php echo $row['unique_leads']; ?></td>
                                    <td><span class="mini-badge badge-info"><?php echo $row['interested']; ?></span></td>
                                    <td><span class="mini-badge badge-danger"><?php echo $row['hot']; ?></span></td>
                                    <td><span class="mini-badge badge-warning"><?php echo $row['warm']; ?></span></td>
                                    <td><span class="mini-badge badge-success"><?php echo $row['converted']; ?></span></td>
                                    <td><span class="mini-badge badge-secondary"><?php echo $row['lost']; ?></span></td>
                                    <td><?php echo $row['first_call'] ? date('H:i:s', strtotime($row['first_call'])) : '-'; ?></td>
                                    <td><?php echo $row['last_call'] ? date('H:i:s', strtotime($row['last_call'])) : '-'; ?></td>
                                    <td style="min-width: 150px;">
                                        <div class="progress-bar-custom">
                                            <div class="progress-fill" style="width: <?php echo $performance; ?>%;"></div>
                                        </div>
                                        <small class="text-muted"><?php echo $performance; ?>%</small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- All Call Logs -->
            <h4 class="section-title mt-4">
                <i class="feather icon-list"></i> Detailed Call Log
            </h4>
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered" id="callLogsTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Caller</th>
                                    <th>Lead Name</th>
                                    <th>Mobile</th>
                                    <th>Status</th>
                                    <th>Follow-Up</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $count = 1;
                                while ($log = mysqli_fetch_assoc($logs_result)):
                                    $status = strtolower($log['lead_status']);
                                    // Updated badge colors for your actual statuses
                                    $badgeMap = [
                                        'untouched' => '#6c757d',      // Gray
                                        'hot' => '#dc3545',            // Red (Danger)
                                        'warm' => '#fd7e14',           // Orange (Warning)
                                        'interested' => '#007bff',     // Blue (Primary)
                                        'not interested' => '#6c757d'  // Gray (Secondary)
                                    ];
                                    $badgeColor = $badgeMap[$status] ?? '#6c757d';

                                    // Follow-up stage badge colors
                                    $followUpMap = [
                                        '1st Contact' => '#17a2b8',    // Info
                                        '2nd Contact' => '#28a745',    // Success
                                        '3rd Contact' => '#ffc107',    // Warning
                                        '4th Contact' => '#fd7e14',    // Warning darker
                                        '5th Contact' => '#dc3545',    // Danger
                                        '6th Contact' => '#6f42c1',    // Purple
                                        '7th Contact' => '#e83e8c',    // Pink
                                        'Converted' => '#28a745',      // Success
                                        'Lost' => '#6c757d',           // Secondary
                                        'Not Set' => '#adb5bd'         // Light gray
                                    ];
                                    $followUpStage = $log['follow_up_stage'] ?: 'Not Set';
                                    $followUpColor = $followUpMap[$followUpStage] ?? '#adb5bd';
                                ?>
                                    <tr>
                                        <td><?php echo $count++; ?></td>
                                        <td><?php echo date('d M Y', strtotime($log['call_date'])); ?></td>
                                        <td><?php echo date('h:i A', strtotime($log['call_time'])); ?></td>
                                        <td><strong><?php echo htmlspecialchars($log['username']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($log['lead_name']); ?></td>
                                        <td><?php echo htmlspecialchars($log['lead_mobile']); ?></td>
                                        <td><span class="badge" style="background: <?php echo $badgeColor; ?>; color: white;"><?php echo ucfirst($status); ?></span></td>
                                        <td><span class="badge" style="background: <?php echo $followUpColor; ?>; color: white;"><?php echo htmlspecialchars($followUpStage); ?></span></td>
                                        <td title="<?php echo htmlspecialchars($log['remarks']); ?>">
                                            <?php echo htmlspecialchars(substr($log['remarks'] ?? '', 0, 50)); ?>
                                            <?php echo strlen($log['remarks'] ?? '') > 50 ? '...' : ''; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="assets/js/vendor-all.min.js"></script>
    <script src="assets/js/plugins/bootstrap.min.js"></script>
    <script src="assets/js/pcoded.min.js"></script>
    <script src="assets/js/plugins/jquery.dataTables.min.js"></script>
    <script src="assets/js/plugins/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#callLogsTable').DataTable({
                pageLength: 25,
                order: [
                    [1, 'desc'],
                    [2, 'desc']
                ]
            });
        });
    </script>
</body>

</html>