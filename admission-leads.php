    <?php
    // error_reporting(E_ALL);
    session_start();
    require_once 'db/config.php';

    if (!isset($_SESSION['login_user_id'])) {
        header('Location: login.php');
        exit;
    }

    $login_user_id = $_SESSION['login_user_id'];

    // Fetch role and username
    $get_admin_query = "SELECT _id, username, admin_role FROM admin WHERE _id = ?";
    $stmt = mysqli_prepare($db, $get_admin_query);
    mysqli_stmt_bind_param($stmt, "i", $login_user_id);
    mysqli_stmt_execute($stmt);
    $get_admin_result = mysqli_stmt_get_result($stmt);
    $admin_row = mysqli_fetch_assoc($get_admin_result);
    mysqli_stmt_close($stmt);

    $admin_id = $admin_row['_id'];
    $username = $admin_row['username'];
    $role_id = $admin_row['admin_role'];

    // Get role name
    $get_role_query = "SELECT role_name FROM roles WHERE role_id = ?";
    $stmt = mysqli_prepare($db, $get_role_query);
    mysqli_stmt_bind_param($stmt, "i", $role_id);
    mysqli_stmt_execute($stmt);
    $get_role_result = mysqli_stmt_get_result($stmt);
    $role_row = mysqli_fetch_assoc($get_role_result);
    mysqli_stmt_close($stmt);

    $role_name = strtolower($role_row['role_name']);

    // Set permissions based on role
    $can_assign_leads = false;
    $can_delete_leads = false;
    $can_download_leads = false;

    // Logic based on role
    if ($role_name == 'admin') {
        $can_assign_leads = true;
        $can_delete_leads = true;
        $can_download_leads = true;
    }

    // Build query based on role
    if ($role_name == 'admin') {
        $query = "
    SELECT 
        ae.admission_id,
        ae.name,
        ae.email,
        ae.mobile,
        ae.course_type,
        ae.remarks,
        ae.state,
        ae.city,
        ae.lead_status,
        ae.follow_up_stage,
        ae.symptoms,
        ae.history,
        ae.date,
        a.username AS assigned_admin
    FROM admission_enquiry ae
    LEFT JOIN lead_assignments la ON ae.admission_id = la.admission_id
    LEFT JOIN admin a ON la.admin_id = a._id
    WHERE DATE(ae.date) = CURDATE()
    ORDER BY ae.date DESC
    ";
        $result = mysqli_query($db, $query);
    } else {
        // ✅ For all other roles, show only their assigned leads from TODAY
        $query = "
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
        ae.symptoms,
        ae.history,
        ae.date,
        a.username AS assigned_admin
    FROM admission_enquiry ae
    LEFT JOIN lead_assignments la ON ae.admission_id = la.admission_id
    LEFT JOIN admin a ON la.admin_id = a._id
    WHERE la.admin_id = ? AND DATE(ae.date) = CURDATE()
    ORDER BY ae.date DESC
    ";
        $stmt = mysqli_prepare($db, $query);
        mysqli_stmt_bind_param($stmt, "i", $admin_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    }
    // Handle Lead Update (All fields) - NO VALIDATION
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_lead'])) {
        $leadid = base64_decode($_POST['admission_id']);

        // Get all form values
        $name = trim($_POST['name']);
        $mobile = trim($_POST['mobile']);
        $email = !empty($_POST['email']) ? trim($_POST['email']) : '';
        $state = trim($_POST['state']);
        $city = trim($_POST['city']);
        $course_type = trim($_POST['course_type']);
        $leadstatus = $_POST['lead_status'];
        $follow_up_stage = !empty($_POST['follow_up_stage']) ? $_POST['follow_up_stage'] : null;
        $remarks = trim($_POST['remarks']);
        $symptoms = !empty($_POST['symptoms']) ? trim($_POST['symptoms']) : '';
        $history = !empty($_POST['history']) ? trim($_POST['history']) : '';

        // ✅ UPDATE ALL LEAD FIELDS - NO VALIDATION
        $update_query = "UPDATE admission_enquiry SET 
        name = ?, 
        mobile = ?, 
        email = ?, 
        state = ?, 
        city = ?, 
        course_type = ?, 
        lead_status = ?, 
        follow_up_stage = ?, 
        remarks = ?,
        symptoms = ?,
        history = ?
        WHERE admission_id = ?";

        if ($stmt = mysqli_prepare($db, $update_query)) {
            mysqli_stmt_bind_param(
                $stmt,
                "sssssssssssi",
                $name,
                $mobile,
                $email,
                $state,
                $city,
                $course_type,
                $leadstatus,
                $follow_up_stage,
                $remarks,
                $symptoms,
                $history,
                $leadid
            );

            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['msg'] = '<div class="alert alert-success">Lead updated successfully!</div>';

                // ✅ LOG THE CALL
                $call_date = date('Y-m-d');
                $call_time = date('H:i:s');

                $log_query = "INSERT INTO call_logs 
                (admin_id, username, admission_id, lead_name, lead_mobile, call_date, call_time, lead_status, follow_up_stage, remarks) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                if ($log_stmt = mysqli_prepare($db, $log_query)) {
                    mysqli_stmt_bind_param(
                        $log_stmt,
                        "isisssssss",
                        $login_user_id,
                        $username,
                        $leadid,
                        $name,
                        $mobile,
                        $call_date,
                        $call_time,
                        $leadstatus,
                        $follow_up_stage,
                        $remarks
                    );
                    mysqli_stmt_execute($log_stmt);
                    mysqli_stmt_close($log_stmt);
                }
            } else {
                $_SESSION['msg'] = '<div class="alert alert-danger">Error updating lead.</div>';
            }
            mysqli_stmt_close($stmt);
        }
        header('Location: admission-leads.php');
        exit;
    }

    // Handle Create Lead
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_lead'])) {
        $name = trim($_POST['name']);
        $mobile = trim($_POST['mobile']);
        $email = !empty($_POST['email']) ? trim($_POST['email']) : '';
        $state = trim($_POST['state']);
        $city = trim($_POST['city']);

        // ✅ Get course_type - should be comma-separated string now
        $course_type = isset($_POST['course_type']) ? trim($_POST['course_type']) : '';

        // Debug: Log what we received
        error_log("Create Lead - course_type received: " . $course_type);

        $history = !empty($_POST['history']) ? trim($_POST['history']) : '';
        $symptoms = !empty($_POST['symptoms']) ? trim($_POST['symptoms']) : '';

        // ✅ Insert with symptoms field - NO VALIDATION
        $insertQuery = "INSERT INTO admission_enquiry (name, email, mobile, state, city, course_type, symptoms, history, lead_status, date) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'untouched', NOW())";
        $stmt_insert = mysqli_prepare($db, $insertQuery);
        mysqli_stmt_bind_param($stmt_insert, "ssssssss", $name, $email, $mobile, $state, $city, $course_type, $symptoms, $history);

        if (mysqli_stmt_execute($stmt_insert)) {
            $new_lead_id = mysqli_insert_id($db);
            $assign_query = "INSERT INTO lead_assignments (admission_id, admin_id) VALUES (?, ?)";
            $stmt_assign = mysqli_prepare($db, $assign_query);
            mysqli_stmt_bind_param($stmt_assign, "ii", $new_lead_id, $login_user_id);
            mysqli_stmt_execute($stmt_assign);
            mysqli_stmt_close($stmt_assign);

            $_SESSION['msg'] = '<div class="alert alert-success">Lead created successfully!</div>';
        } else {
            $_SESSION['msg'] = '<div class="alert alert-danger">Error creating lead: ' . mysqli_error($db) . '</div>';
        }
        mysqli_stmt_close($stmt_insert);

        header('Location: admission-leads.php');
        exit;
    }

    // Handle Lead Update
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_lead'])) {
        $leadid = base64_decode($_POST['admission_id']);

        // Get all form values
        $name = trim($_POST['name']);
        $mobile = trim($_POST['mobile']);
        $email = !empty($_POST['email']) ? trim($_POST['email']) : '';
        $state = trim($_POST['state']);
        $city = trim($_POST['city']);

        // ✅ Get course_type - should be comma-separated string now
        $course_type = isset($_POST['course_type']) ? trim($_POST['course_type']) : '';

        // Debug: Log what we received
        error_log("Update Lead - course_type received: " . $course_type);

        $leadstatus = $_POST['lead_status'];
        $follow_up_stage = !empty($_POST['follow_up_stage']) ? $_POST['follow_up_stage'] : null;
        $remarks = trim($_POST['remarks']);
        $symptoms = !empty($_POST['symptoms']) ? trim($_POST['symptoms']) : '';
        $history = !empty($_POST['history']) ? trim($_POST['history']) : '';

        // ✅ UPDATE ALL LEAD FIELDS - NO VALIDATION
        $update_query = "UPDATE admission_enquiry SET 
    name = ?, 
    mobile = ?, 
    email = ?, 
    state = ?, 
    city = ?, 
    course_type = ?, 
    lead_status = ?, 
    follow_up_stage = ?, 
    remarks = ?,
    symptoms = ?,
    history = ?
    WHERE admission_id = ?";

        if ($stmt = mysqli_prepare($db, $update_query)) {
            mysqli_stmt_bind_param(
                $stmt,
                "sssssssssssi",
                $name,
                $mobile,
                $email,
                $state,
                $city,
                $course_type,
                $leadstatus,
                $follow_up_stage,
                $remarks,
                $symptoms,
                $history,
                $leadid
            );

            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['msg'] = '<div class="alert alert-success">Lead updated successfully!</div>';

                // ✅ LOG THE CALL
                $call_date = date('Y-m-d');
                $call_time = date('H:i:s');

                $log_query = "INSERT INTO call_logs 
            (admin_id, username, admission_id, lead_name, lead_mobile, call_date, call_time, lead_status, follow_up_stage, remarks) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                if ($log_stmt = mysqli_prepare($db, $log_query)) {
                    mysqli_stmt_bind_param(
                        $log_stmt,
                        "isisssssss",
                        $login_user_id,
                        $username,
                        $leadid,
                        $name,
                        $mobile,
                        $call_date,
                        $call_time,
                        $leadstatus,
                        $follow_up_stage,
                        $remarks
                    );
                    mysqli_stmt_execute($log_stmt);
                    mysqli_stmt_close($log_stmt);
                }
            } else {
                $_SESSION['msg'] = '<div class="alert alert-danger">Error updating lead.</div>';
            }
            mysqli_stmt_close($stmt);
        }
        header('Location: admission-leads.php');
        exit;
    }
    // Fetch settings
    $query = "SELECT * FROM login_settings LIMIT 1";
    $settingsResult = mysqli_query($db, $query);
    $settings = mysqli_fetch_assoc($settingsResult);

    $logoPath = $settings['backend_panel_logo'] ?? '';
    $helpdeskNumber = $settings['helpdesk_no'] ?? '';
    $favicon = $settings['favicon'] ?? 'assets/images/favicon.ico';
    ?>

    <!DOCTYPE html>
    <html lang="en">

    <meta http-equiv="content-type" content="text/html;charset=UTF-8" />

    <head>
        <title>Leads</title>

        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="description" content="" />
        <meta name="keywords" content="">
        <meta name="author" content="Codedthemes" />

        <link rel="icon" href="<?php echo $favicon; ?>" type="image/x-icon">

        <link rel="stylesheet" href="assets/css/plugins/dataTables.bootstrap4.min.css">

        <link rel="stylesheet" href="assets/css/style.css">
        <style>
            /* ✅ Multi-Select Container */
            .multi-select-container {
                position: relative;
                width: 100%;
            }

            .multi-select-wrapper {
                position: relative;
            }

            /* ✅ Selected Items Display */
            .multi-select-selected {
                width: 100%;
                min-height: 42px;
                padding: 8px 35px 8px 12px;
                background: #fff;
                border: 1px solid #e0e0e0;
                border-radius: 4px;
                cursor: pointer;
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 5px;
                transition: border-color 0.3s;
            }

            .multi-select-selected:hover {
                border-color: #a0a0a0;
            }

            .multi-select-selected:focus {
                border-color: #0d8aff;
                box-shadow: 0 0 0 0.2rem rgba(13, 138, 255, 0.25);
            }

            /* ✅ Selected Item Tag */
            .multi-select-tag {
                background: #0d8aff;
                color: white;
                padding: 3px 8px;
                border-radius: 3px;
                font-size: 13px;
                display: flex;
                align-items: center;
                gap: 5px;
            }

            .multi-select-tag-remove {
                cursor: pointer;
                font-weight: bold;
                font-size: 14px;
                padding: 0 2px;
            }

            .multi-select-tag-remove:hover {
                color: #ffeb3b;
            }

            .multi-select-placeholder {
                color: #999;
                font-style: italic;
            }

            /* ✅ Dropdown Arrow */
            .multi-select-arrow {
                position: absolute;
                right: 12px;
                top: 50%;
                transform: translateY(-50%);
                pointer-events: none;
                transition: transform 0.3s;
                font-size: 12px;
                color: #666;
            }

            .multi-select-arrow.rotate {
                transform: translateY(-50%) rotate(180deg);
            }

            /* ✅ Dropdown Options */
            .multi-select-options {
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: white;
                border: 1px solid #e0e0e0;
                border-radius: 4px;
                margin-top: 5px;
                max-height: 250px;
                overflow-y: auto;
                z-index: 1000;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                display: none;
            }

            .multi-select-options.show {
                display: block;
            }

            /* ✅ Option Item */
            .multi-select-option {
                padding: 10px 12px;
                cursor: pointer;
                display: flex;
                align-items: center;
                gap: 10px;
                transition: background 0.2s;
            }

            .multi-select-option:hover {
                background: #f5f5f5;
            }

            .multi-select-option input[type="checkbox"] {
                margin: 0;
                cursor: pointer;
            }

            .multi-select-option label {
                margin: 0;
                cursor: pointer;
                flex: 1;
                font-size: 14px;
            }

            /* ✅ Scrollbar Styling */
            .multi-select-options::-webkit-scrollbar {
                width: 8px;
            }

            .multi-select-options::-webkit-scrollbar-track {
                background: #f1f1f1;
                border-radius: 4px;
            }

            .multi-select-options::-webkit-scrollbar-thumb {
                background: #888;
                border-radius: 4px;
            }

            .multi-select-options::-webkit-scrollbar-thumb:hover {
                background: #555;
            }

            /* ✅ Search Box (Optional) */
            .multi-select-search {
                padding: 8px 12px;
                border-bottom: 1px solid #e0e0e0;
                position: sticky;
                top: 0;
                background: white;
                z-index: 1;
            }

            .multi-select-search input {
                width: 100%;
                padding: 6px 10px;
                border: 1px solid #ddd;
                border-radius: 4px;
                font-size: 13px;
            }

            .multi-select-search input:focus {
                outline: none;
                border-color: #0d8aff;
            }

            /* ✅ Ensure table is always visible */
            #leadsTable {
                display: table !important;
                width: 100% !important;
            }

            #leadsTable tbody {
                display: table-row-group !important;
            }

            #leadsTable tr {
                display: table-row !important;
            }

            #leadsTable td,
            #leadsTable th {
                display: table-cell !important;
            }

            .dataTables_wrapper {
                display: block !important;
            }

            /* ✅ Loading spinner */
            .spinner-border {
                width: 1.5rem;
                height: 1.5rem;
                border: 0.2em solid currentColor;
                border-right-color: transparent;
                border-radius: 50%;
                animation: spinner-border 0.75s linear infinite;
            }

            @keyframes spinner-border {
                to {
                    transform: rotate(360deg);
                }
            }
        </style>
        <style>
            .filter-card {
                background: #f8f9fa;
                border-radius: 8px;
                padding: 20px;
                margin-bottom: 20px;
                border: 1px solid #e9ecef;
            }

            .filter-label {
                font-size: 12px;
                font-weight: 600;
                color: #495057;
                margin-bottom: 5px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            .form-control-sm {
                font-size: 13px;
                border-radius: 4px;
            }

            .status-badge {
                padding: 6px 12px;
                border-radius: 20px;
                font-size: 11px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            .btn-action {
                padding: 4px 8px;
                font-size: 12px;
                border-radius: 4px;
                margin: 0 2px;
            }

            .spinner-border {
                width: 1.5rem;
                height: 1.5rem;
            }

            .empty-state {
                text-align: center;
                padding: 40px;
                color: #6c757d;
            }

            .empty-state i {
                font-size: 48px;
                margin-bottom: 15px;
                opacity: 0.5;
            }
        </style>
        <!-- SweetAlert2 CSS -->
        <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.0/dist/sweetalert2.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.datatables.net/2.3.0/css/dataTables.dataTables.css" />
    </head>

    <body class="">

        <div class="loader-bg">
            <div class="loader-track">
                <div class="loader-fill"></div>
            </div>
        </div>

        <!-- Header -->
        <?php
        include("header.php");
        ?>
        <!-- /Header -->

        <!-- navbar -->
        <?php
        include("navbar.php");
        ?>
        <!-- /navbar -->

        <section class="pcoded-main-container">
            <div class="pcoded-content">

                <div class="page-header">
                    <div class="page-block">
                        <div class="row align-items-center">
                            <div class="col-md-12">
                                <div class="page-header-title">
                                    <h5 class="m-b-10">Admission Lead
                                    </h5>
                                    <?php
                                    if (isset($_SESSION['msg'])) {
                                        echo $_SESSION['msg'];
                                        unset($_SESSION['msg']); // remove after showing
                                    }
                                    ?>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>


                <div class="row">
                    <div class="col-sm-12">
                        <div class="card">
                            <div class="card-header table-card-header">
                                <div class="card-body">
                                    <button type="button" class="btn btn-success ml-2" data-bs-toggle="modal" data-bs-target="#createLeadModal">
                                        <i class="feather icon-plus"></i> Create Lead
                                    </button>
                                    <hr>
                                    <?php if ($role_name == 'admin' || $can_download_leads): ?>
                                        <form method="POST" action="download-leads.php" class="d-flex flex-wrap align-items-end gap-2 mb-3">

                                            <div class="form-group mb-2">
                                                <label for="dateFrom" class="small mb-1">Date From:</label>
                                                <input type="date" class="form-control form-control-sm" name="dateFrom" id="dateFrom" style="width: 150px;">
                                            </div>

                                            <div class="form-group mb-2">
                                                <label for="dateTo" class="small mb-1">Date To:</label>
                                                <input type="date" class="form-control form-control-sm" name="dateTo" id="dateTo" style="width: 150px;">
                                            </div>

                                            <div class="form-group mb-2">
                                                <label for="statusFilter" class="small mb-1">Lead Status:</label>
                                                <select id="statusFilter" name="status" class="form-control form-control-sm" style="width: 160px;">
                                                    <option value="">Select Lead Status</option>
                                                </select>
                                            </div>

                                            <!-- User Filter -->
                                            <div class="form-group mb-2">
                                                <label for="userFilter" class="small mb-1">User:</label>
                                                <select name="userFilter" id="userFilter" class="form-control form-control-sm" style="width: 140px;">
                                                    <option value="">Select User</option>
                                                    <?php
                                                    $users = mysqli_query($db, "SELECT _id, username FROM admin");
                                                    while ($u = mysqli_fetch_assoc($users)) {
                                                        $selected = (isset($_POST['userFilter']) && $_POST['userFilter'] == $u['_id']) ? 'selected' : '';
                                                        echo "<option value='{$u['_id']}' $selected>{$u['username']}</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>

                                            <!-- ✅ Follow-Up Stage Filter -->
                                            <div class="form-group mb-2">
                                                <label for="followUpFilter" class="small mb-1">Follow-Up:</label>
                                                <select name="followUpFilter" id="followUpFilter" class="form-control form-control-sm" style="width: 160px;">
                                                    <option value="">All Stages</option>
                                                    <option value="Untouched">Untouched</option>
                                                    <option value="1st Contact">1st Contact</option>
                                                    <option value="2nd Contact">2nd Contact</option>
                                                    <option value="3rd Contact">3rd Contact</option>
                                                    <option value="4th Contact">4th Contact</option>
                                                    <option value="5th Contact">5th Contact</option>
                                                    <option value="6th Contact">6th Contact</option>
                                                    <option value="7th Contact">7th Contact</option>
                                                    <option value="Converted">Converted</option>
                                                    <option value="Lost">Lost</option>
                                                    <option value="Not Set">Not Set</option>
                                                </select>
                                            </div>

                                            <!-- ✅ Buttons -->
                                            <div class="form-group mb-2 d-flex align-items-end">
                                                <button type="submit" class="btn btn-primary btn-sm mr-2" name="download">
                                                    <i class='feather icon-download'></i> Download
                                                </button>
                                                <button type="button" id="clearFilters" class="btn btn-warning btn-sm">
                                                    <i class="feather icon-x-circle"></i> Clear
                                                </button>
                                            </div>

                                        </form>
                                    <?php endif; ?>
                                    <form id="deleteMForm" class="mt-4" action="delete-leads.php" method="post">
                                        <div class="d-flex align-items-center mb-2">
                                            <?php if ($role_name == 'admin' || $can_delete_leads): ?>

                                                <button type="button" id="deleteSelected" class="btn btn-danger">
                                                    <i class='feather icon-trash'></i> Delete Selected
                                                </button>

                                            <?php endif; ?> &nbsp;
                                            <?php if ($role_name == 'admin' || $can_assign_leads): ?>
                                                <button type="button" id="assigselected" class="btn btn-info mr-2" data-bs-toggle="modal" data-bs-target="#bulkAssignModal">
                                                    <i class="feather icon-user-plus"></i> Assign Selected
                                                </button>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Table -->
                                        <div class="dt-responsive table-responsive">
                                            <table class="table table-striped table-bordered nowrap" id="leadsTable">
                                                <thead>
                                                    <tr>
                                                        <th><input type='checkbox' id='selectAll'></th>
                                                        <th>SNO</th>
                                                        <th>MOBILE</th>
                                                        <th>NAME</th>
                                                        <th>STATE</th>
                                                        <th>CITY</th>
                                                        <th>TREATMENT</th>
                                                        <th>REMARKS</th>
                                                        <th>STATUS</th>
                                                        <th>FOLLOW-UP</th>
                                                        <th>DATE</th>
                                                        <th>ACTION</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    $count = 1;
                                                    while ($row = mysqli_fetch_assoc($result)) {
                                                        $encoded_id = base64_encode($row['admission_id']);
                                                        $status = strtolower($row['lead_status']);

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

                                                        $remarks = $row['remarks'];
                                                        $shortRemarks = strlen($remarks) > 40 ? substr($remarks, 0, 40) . '...' : $remarks;

                                                        echo "<tr>";
                                                        echo "<td><input type='checkbox' class='menuCheckbox' name='advt_ids[]' value='$encoded_id'></td>";
                                                        echo "<td>$count</td>";
                                                        echo "<td>" . htmlspecialchars($row['mobile']) . "</td>";
                                                        echo "<td><strong>" . htmlspecialchars($row['name']) . "</strong></td>";
                                                        echo "<td>" . htmlspecialchars($row['state']) . "</td>";
                                                        echo "<td>" . htmlspecialchars($row['city']) . "</td>";
                                                        echo "<td>" . htmlspecialchars($row['course_type']) . "</td>";
                                                        echo "<td title='" . htmlspecialchars($row['remarks']) . "'>" . htmlspecialchars($shortRemarks) . "</td>";
                                                        echo "<td><span class='badge status-badge' style='background-color: $badgeColor; color: #fff;'>" . ucfirst($status) . "</span></td>";

                                                        // Follow-up stage badge colors
                                                        $followUpMap = [
                                                            'Untouched' => '#A76545',
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

                                                        $followUpStage = $row['follow_up_stage'] ?? '';
                                                        $followUpColor = $followUpMap[$followUpStage] ?? '#e9ecef';
                                                        $followUpText = !empty($followUpStage) ? $followUpStage : 'Not Set';

                                                        echo "<td><span class='badge status-badge' style='background-color: $followUpColor; color: #fff;'>"
                                                            . htmlspecialchars($followUpText) . "</span></td>";
                                                        echo "<td>" . date("d-m-Y", strtotime($row['date'])) . "</td>";

                                                        echo "<td>";
                                                        echo "<a href='javascript:void(0)' class='btn btn-sm btn-warning btn-action viewLead' data-id='$encoded_id' title='View'><i class='feather icon-eye'></i></a>";
                                                        $followUpData = htmlspecialchars($row['follow_up_stage'] ?? '');
                                                        echo "<a href='javascript:void(0)' class='btn btn-sm btn-success btn-action editLead' 
    data-lead-id='$encoded_id' 
    data-name='" . htmlspecialchars($row['name']) . "' 
    data-mobile='" . htmlspecialchars($row['mobile']) . "' 
    data-email='" . htmlspecialchars($row['email'] ?? '') . "' 
    data-state='" . htmlspecialchars($row['state']) . "' 
    data-city='" . htmlspecialchars($row['city']) . "' 
    data-course='" . htmlspecialchars($row['course_type']) . "' 
    data-status='" . htmlspecialchars($row['lead_status']) . "' 
    data-follow-up='" . htmlspecialchars($row['follow_up_stage'] ?? '') . "' 
    data-remark='" . htmlspecialchars($row['remarks'] ?? '') . "'
    data-symptoms='" . htmlspecialchars($row['symptoms'] ?? '') . "'
    data-history='" . htmlspecialchars($row['history'] ?? '') . "'
    title='Edit'><i class='feather icon-edit'></i></a>";
                                                        if ($role_name == 'admin' || $can_delete_leads) {
                                                            echo "<a href='javascript:void(0)' class='btn btn-sm btn-danger btn-action delete-btn' data-id='$encoded_id' data-bs-toggle='modal' data-bs-target='#deleteModal' title='Delete'><i class='feather icon-trash'></i></a>";
                                                        }
                                                        echo "</td>";
                                                        echo "</tr>";

                                                        $count++;
                                                    }

                                                    // if ($count == 1) {
                                                    //     // ✅ Updated colspan to 12 since CATEGORY column is removed
                                                    //     echo "<tr><td colspan='5' class='text-center py-5'><div class='empty-state'><i class='feather icon-inbox'></i><h5>No Leads Found</h5><p class='text-muted'>Create a lead or adjust your filters</p></div></td></tr>";
                                                    // }
                                                    ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <br />
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
        </section>

        <!-- Bulk Assign Modal -->
        <div class="modal fade" id="bulkAssignModal" tabindex="-1" aria-labelledby="bulkAssignModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <form id="bulkAssignForm">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="bulkAssignModalLabel">Assign Selected Leads</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="selected_ids" id="selected_ids_input">

                            <label for="bulkUser">Select Admin</label>
                            <select class="form-control" id="bulkUser" name="admin_id" required>
                                <option value="">Select Admin</option>
                                <?php
                                $query_admin = "SELECT _id, username FROM admin";
                                $admin_result = $db->query($query_admin);
                                while ($admin_row = $admin_result->fetch_assoc()) {
                                    echo '<option value="' . $admin_row['_id'] . '">' . htmlspecialchars($admin_row['username']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-success">Assign</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal fade" id="viewLeadModal" tabindex="-1" aria-labelledby="viewLeadModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="viewLeadModalLabel">Lead Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">X</button>
                    </div>
                    <div class="modal-body" id="leadDetails">
                        Loading details...
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Lead Modal - Updated with Treatment Dropdown, Symptoms and History -->
        <div class="modal fade" id="editLeadStatusModal" tabindex="-1" aria-labelledby="editLeadStatusModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Edit Lead</h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">X</button>
                    </div>
                    <div class="modal-body">
                        <form method="post" id="editLeadForm">
                            <input type="hidden" id="edit_admission_id" name="admission_id">

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="edit_name" class="form-label">Name</label>
                                    <input type="text" class="form-control" id="edit_name" name="name">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="edit_mobile" class="form-label">Phone Number</label>
                                    <input type="text" class="form-control" id="edit_mobile" name="mobile" >
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="edit_email" class="form-label">Email (Optional)</label>
                                    <input type="email" class="form-control" id="edit_email" name="email">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="edit_state" class="form-label">State</label>
                                    <input type="text" class="form-control" id="edit_state" name="state">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="edit_city" class="form-label">City</label>
                                    <input type="text" class="form-control" id="edit_city" name="city">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="edit_course" class="form-label">Treatment</label>
                                    <select class="form-control" name="course_type" id="edit_course" multiple>
                                        <option value="Autism">Autism</option>
                                        <option value="ADHD">ADHD</option>
                                        <option value="Speech Disorder">Speech Disorder</option>
                                        <option value="Behaviour Disorder">Behaviour Disorder</option>
                                        <option value="Cerebral Palsy">Cerebral Palsy</option>
                                        <option value="Brain Disorder">Brain Disorder</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <!-- ✅ Other Treatment Textbox (Hidden by default) -->
                                <div class="col-md-12 mb-3" id="editOtherTreatmentDiv" style="display: none;">
                                    <label for="edit_other_treatment" class="form-label">Specify Treatment</label>
                                    <input type="text" class="form-control" id="edit_other_treatment" name="other_treatment" placeholder="Enter treatment name...">
                                </div>

                                <!-- ✅ Symptoms Section -->
                                <div class="col-md-12 mb-3" id="editSymptomsDiv" style="display: none;">
                                    <label class="form-label">Symptoms:</label>
                                    <div class="row" id="editSymptomsContainer">
                                        <!-- Symptoms will be dynamically inserted here -->
                                    </div>
                                    <input type="hidden" name="symptoms" id="editSymptomsInput">
                                </div>


                                <div class="col-md-12 mb-3">
                                    <label for="edit_history" class="form-label">History:</label>
                                    <select name="history" id="edit_history" class="form-control">
                                        <option value="" selected disabled>Select History</option>
                                        <option value="old">Old</option>
                                        <option value="new">New</option>
                                        <option value="visited after long time">Visited After Long Time</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="edit_lead_status" class="form-label">Lead Status:</label>
                                    <select id="edit_lead_status" name="lead_status" class="form-control">
                                        <!-- Options will be dynamically loaded here -->
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="edit_follow_up_stage" class="form-label">Follow-Up Stage:</label>
                                    <select id="edit_follow_up_stage" name="follow_up_stage" class="form-control">
                                        <option value="">-- Select Stage --</option>
                                        <option value="Untouched">Untouched</option>
                                        <option value="1st Contact">1st Contact</option>
                                        <option value="2nd Contact">2nd Contact</option>
                                        <option value="3rd Contact">3rd Contact</option>
                                        <option value="4th Contact">4th Contact</option>
                                        <option value="5th Contact">5th Contact</option>
                                        <option value="6th Contact">6th Contact</option>
                                        <option value="7th Contact">7th Contact</option>
                                        <option value="Converted">Converted</option>
                                        <option value="Lost">Lost</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="edit_remarks" class="form-label">Remarks:</label>
                                <textarea id="edit_remarks" name="remarks" class="form-control" rows="3" placeholder="Enter remarks..."></textarea>
                            </div>

                            <button type="submit" name="update_lead" class="btn btn-primary">Update Lead</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal -->
        <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">

                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteModalLabel">Delete</h5>
                        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        Are you sure you want to delete the leads?
                        <input type="hidden" id="deleteId">
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>

                    </div>

                </div>
            </div>
        </div>

        <!-- Alert Modal -->
        <div class="modal fade" id="alertModal" tabindex="-1" aria-labelledby="alertModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="alertModalLabel">No Selection</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">X</button>
                    </div>
                    <div class="modal-body">
                        Please select at least one lead to delete.
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-warning" data-bs-dismiss="modal">OK</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Multi Delete Confirmation Modal -->
        <div class="modal fade" id="deleteSelectedModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirm Deletion</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal">X</button>
                    </div>
                    <div class="modal-body">
                        Are you sure you want to delete the selected leads?
                    </div>
                    <div class="modal-footer">
                        <button type="button" id="confirmMultiDelete" class="btn btn-danger">Delete</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Create Lead Modal -->
        <div class="modal fade" id="createLeadModal" tabindex="-1" aria-labelledby="createLeadModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="createLeadModalLabel">Create New Lead</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">X</button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" action="" id="createLeadForm">
                            <div class="mb-3">
                                <label for="new_name" class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="new_name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="new_mobile" class="form-label">Phone Number</label>
                                <input type="text" class="form-control" id="new_mobile" name="mobile" >
                            </div>
                            <div class="mb-3">
                                <label for="new_email" class="form-label">Email (Optional)</label>
                                <input type="email" class="form-control" id="new_email" name="email">
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="new_state" class="form-label">State</label>
                                    <input type="text" class="form-control" id="new_state" name="state">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="new_city" class="form-label">City</label>
                                    <input type="text" class="form-control" id="new_city" name="city">
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label for="history">Patient History</label>
                                    <select name="history" id="history" class="form-control">
                                        <option value="" selected disabled>Select History</option>
                                        <option value="old">Old</option>
                                        <option value="new">New</option>
                                        <option value="visited after long time">Visited After Long Time</option>

                                    </select>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label for="new_course" class="form-label">Treatment <span class="text-danger">*</span></label>
                                    <select class="form-control" name="course_type" id="new_course" multiple required>
                                        <option value="Autism">Autism</option>
                                        <option value="ADHD">ADHD</option>
                                        <option value="Speech Disorder">Speech Disorder</option>
                                        <option value="Behaviour Disorder">Behaviour Disorder</option>
                                        <option value="Cerebral Palsy">Cerebral Palsy</option>
                                        <option value="Brain Disorder">Brain Disorder</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>

                                <!-- ✅ Other Treatment Textbox (Hidden by default) -->
                                <div class="col-md-12 mb-3" id="otherTreatmentDiv" style="display: none;">
                                    <label for="other_treatment" class="form-label">Specify Treatment <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="other_treatment" name="other_treatment" placeholder="Enter treatment name...">
                                </div>

                                <!-- ✅ Symptoms Section -->
                                <div class="col-md-12 mb-3" id="symptomsDiv" style="display: none;">
                                    <label class="form-label">Symptoms/History:</label>
                                    <div class="row" id="symptomsContainer">
                                        <!-- Symptoms will be dynamically inserted here -->
                                    </div>
                                    <input type="hidden" name="symptoms" id="symptomsInput">
                                </div>
                            </div>

                            <button type="submit" name="create_lead" class="btn btn-primary w-100">Create Lead</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>


        <script src="assets/js/vendor-all.min.js"></script>
        <script src="assets/js/pcoded.min.js"></script>
        <script src="assets/js/plugins/jquery.dataTables.min.js"></script>
        <script src="assets/js/plugins/dataTables.bootstrap4.min.js"></script>
        <script src="assets/js/plugins/buttons.colVis.min.js"></script>
        <script src="assets/js/plugins/buttons.print.min.js"></script>
        <script src="assets/js/plugins/pdfmake.min.js"></script>
        <script src="assets/js/plugins/jszip.min.js"></script>
        <script src="assets/js/plugins/dataTables.buttons.min.js"></script>
        <script src="assets/js/plugins/buttons.html5.min.js"></script>
        <script src="assets/js/plugins/buttons.bootstrap4.min.js"></script>

        <!-- jQuery (Ensure it is loaded first) -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.datatables.net/2.3.0/js/dataTables.js"></script>

        <script>
            /**
             * Multi-Select Class
             */
            class MultiSelect {
                constructor(selectElement, options = {}) {
                    this.select = selectElement;
                    this.options = options;
                    this.container = null;
                    this.selectedDiv = null;
                    this.optionsDiv = null;
                    this.arrow = null;

                    this.init();
                }

                init() {
                    // Create container
                    this.container = document.createElement('div');
                    this.container.className = 'multi-select-container';

                    // Hide original select
                    this.select.style.display = 'none';
                    this.select.parentNode.insertBefore(this.container, this.select);
                    this.container.appendChild(this.select);

                    // Create wrapper
                    const wrapper = document.createElement('div');
                    wrapper.className = 'multi-select-wrapper';
                    this.container.appendChild(wrapper);

                    // Create selected items display
                    this.selectedDiv = document.createElement('div');
                    this.selectedDiv.className = 'multi-select-selected';
                    this.selectedDiv.innerHTML = '<span class="multi-select-placeholder">Select treatments...</span>';
                    wrapper.appendChild(this.selectedDiv);

                    // Create arrow
                    this.arrow = document.createElement('span');
                    this.arrow.className = 'multi-select-arrow';
                    this.arrow.innerHTML = '▼';
                    wrapper.appendChild(this.arrow);

                    // Create options container
                    this.optionsDiv = document.createElement('div');
                    this.optionsDiv.className = 'multi-select-options';

                    // Optional: Add search
                    if (this.options.search !== false) {
                        const searchDiv = document.createElement('div');
                        searchDiv.className = 'multi-select-search';
                        const searchInput = document.createElement('input');
                        searchInput.type = 'text';
                        searchInput.placeholder = 'Search...';
                        searchInput.addEventListener('input', (e) => this.filterOptions(e.target.value));
                        searchDiv.appendChild(searchInput);
                        this.optionsDiv.appendChild(searchDiv);
                    }

                    // Create checkboxes for each option
                    Array.from(this.select.options).forEach(option => {
                        const optionDiv = document.createElement('div');
                        optionDiv.className = 'multi-select-option';

                        const checkbox = document.createElement('input');
                        checkbox.type = 'checkbox';
                        checkbox.id = `ms_${this.select.id}_${option.value}`;
                        checkbox.value = option.value;
                        checkbox.checked = option.selected;

                        const label = document.createElement('label');
                        label.htmlFor = checkbox.id;
                        label.textContent = option.textContent;

                        optionDiv.appendChild(checkbox);
                        optionDiv.appendChild(label);
                        this.optionsDiv.appendChild(optionDiv);

                        // Event listener
                        checkbox.addEventListener('change', () => {
                            option.selected = checkbox.checked;
                            this.updateSelectedDisplay();
                            this.select.dispatchEvent(new Event('change'));
                        });
                    });

                    wrapper.appendChild(this.optionsDiv);

                    // Toggle dropdown
                    this.selectedDiv.addEventListener('click', (e) => {
                        if (!e.target.classList.contains('multi-select-tag-remove')) {
                            this.toggle();
                        }
                    });

                    // Close when clicking outside
                    document.addEventListener('click', (e) => {
                        if (!this.container.contains(e.target)) {
                            this.close();
                        }
                    });

                    // Initial display
                    this.updateSelectedDisplay();
                }

                toggle() {
                    this.optionsDiv.classList.toggle('show');
                    this.arrow.classList.toggle('rotate');
                }

                open() {
                    this.optionsDiv.classList.add('show');
                    this.arrow.classList.add('rotate');
                }

                close() {
                    this.optionsDiv.classList.remove('show');
                    this.arrow.classList.remove('rotate');
                }

                updateSelectedDisplay() {
                    const selectedOptions = Array.from(this.select.selectedOptions);
                    this.selectedDiv.innerHTML = '';

                    if (selectedOptions.length === 0) {
                        this.selectedDiv.innerHTML = '<span class="multi-select-placeholder">Select treatments...</span>';
                    } else {
                        selectedOptions.forEach(option => {
                            const tag = document.createElement('span');
                            tag.className = 'multi-select-tag';
                            tag.innerHTML = `
                    ${option.textContent}
                    <span class="multi-select-tag-remove" data-value="${option.value}">&times;</span>
                `;
                            this.selectedDiv.appendChild(tag);
                        });

                        // Add remove event listeners
                        this.selectedDiv.querySelectorAll('.multi-select-tag-remove').forEach(btn => {
                            btn.addEventListener('click', (e) => {
                                e.stopPropagation();
                                const value = btn.getAttribute('data-value');
                                const option = Array.from(this.select.options).find(opt => opt.value === value);
                                if (option) {
                                    option.selected = false;
                                    const checkbox = this.optionsDiv.querySelector(`input[value="${value}"]`);
                                    if (checkbox) checkbox.checked = false;
                                    this.updateSelectedDisplay();
                                    this.select.dispatchEvent(new Event('change'));
                                }
                            });
                        });
                    }
                }

                filterOptions(searchTerm) {
                    const term = searchTerm.toLowerCase();
                    const options = this.optionsDiv.querySelectorAll('.multi-select-option');

                    options.forEach(option => {
                        const label = option.querySelector('label');
                        const text = label.textContent.toLowerCase();
                        option.style.display = text.includes(term) ? 'flex' : 'none';
                    });
                }

                getSelectedValues() {
                    return Array.from(this.select.selectedOptions).map(opt => opt.value);
                }

                setSelectedValues(values) {
                    Array.from(this.select.options).forEach(option => {
                        option.selected = values.includes(option.value);
                    });
                    this.updateSelectedDisplay();

                    // Update checkboxes
                    this.optionsDiv.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                        checkbox.checked = values.includes(checkbox.value);
                    });
                }
            }

            // ✅ Initialize Multi-Select on page load
            document.addEventListener('DOMContentLoaded', function() {
                // Initialize for Create Lead
                const createSelect = document.getElementById('new_course');
                if (createSelect) {
                    window.createMultiSelect = new MultiSelect(createSelect, {
                        search: true
                    });

                    // Listen for changes to show/hide symptoms
                    createSelect.addEventListener('change', function() {
                        const selectedTreatments = Array.from(this.selectedOptions).map(opt => opt.value);
                        handleTreatmentChange(selectedTreatments, 'create');
                    });
                }

                // Initialize for Edit Lead
                const editSelect = document.getElementById('edit_course');
                if (editSelect) {
                    window.editMultiSelect = new MultiSelect(editSelect, {
                        search: true
                    });

                    // Listen for changes to show/hide symptoms
                    editSelect.addEventListener('change', function() {
                        const selectedTreatments = Array.from(this.selectedOptions).map(opt => opt.value);
                        handleTreatmentChange(selectedTreatments, 'edit');
                    });
                }
            });

            // ✅ Handle treatment change (show symptoms)
            function handleTreatmentChange(selectedTreatments, type) {
                const symptomsData = {
                    'Autism': [
                        'Autism', 'Speech Disorder', 'Eye Contact', 'Toe Walking', 'Stubborn',
                        'Repetitive Behaviour', 'Seizers', 'Hand Flapping', 'Sleep Problem',
                        'Choosy at Eat', 'Teeth Grinding', 'Sweating', 'Stool Trained', 'Concentration'
                    ],
                    'ADHD': [
                        'ADHD', 'Super Hyper', 'Hyperactive', 'Aggressive', 'Understanding',
                        'Chewing Problem', 'Command Follow', 'Socialization', 'Jumping',
                        'Sensory Nerves', 'Motor Nerves', 'Self Talk', 'Self Bite',
                        'Bite Other', 'Self Hit', 'Hit Other'
                    ],
                    'Speech Disorder': [
                        'Delayed Speech', 'Unclear Speech', 'Stuttering', 'Limited Vocabulary',
                        'Difficulty Understanding', 'Articulation Problems', 'Voice Disorders'
                    ],
                    'Behaviour Disorder': [
                        'Aggressive Behavior', 'Defiance', 'Tantrums', 'Impulsivity',
                        'Difficulty Following Rules', 'Social Problems', 'Emotional Outbursts'
                    ],
                    'Cerebral Palsy': [
                        'Muscle Stiffness', 'Poor Coordination', 'Tremors', 'Delayed Development',
                        'Difficulty Walking', 'Speech Problems', 'Seizures', 'Drooling'
                    ],
                    'Brain Disorder': [
                        'Memory Problems', 'Confusion', 'Mood Changes', 'Headaches',
                        'Seizures', 'Difficulty Concentrating', 'Sleep Disturbances'
                    ]
                };

                const prefix = type === 'create' ? '' : 'edit';
                const symptomsDiv = document.getElementById(`${prefix}symptomsDiv`);
                const symptomsContainer = document.getElementById(`${prefix}symptomsContainer`);
                const otherDiv = document.getElementById(`${prefix}otherTreatmentDiv`);

                if (!symptomsDiv || !symptomsContainer) return;

                // Check if "Other" is selected
                if (selectedTreatments.includes('Other')) {
                    if (otherDiv) otherDiv.style.display = 'block';
                } else {
                    if (otherDiv) otherDiv.style.display = 'none';
                }

                // Combine symptoms from all selected treatments
                const standardTreatments = selectedTreatments.filter(t => t !== 'Other');

                if (standardTreatments.length > 0) {
                    symptomsDiv.style.display = 'block';
                    renderCombinedSymptoms(standardTreatments, symptomsData, symptomsContainer, prefix);
                } else {
                    symptomsDiv.style.display = 'none';
                    symptomsContainer.innerHTML = '';
                }
            }

            // ✅ Render combined symptoms
            function renderCombinedSymptoms(treatments, symptomsData, container, prefix) {
                container.innerHTML = '';

                let allSymptoms = [];
                treatments.forEach(treatment => {
                    if (symptomsData[treatment]) {
                        allSymptoms = allSymptoms.concat(symptomsData[treatment]);
                    }
                });

                // Remove duplicates
                allSymptoms = [...new Set(allSymptoms)];

                // Get existing symptoms (for edit mode)
                let existingSymptoms = [];
                if (prefix === 'edit') {
                    const editSymptomsInput = document.getElementById('editSymptomsInput');
                    if (editSymptomsInput && editSymptomsInput.value) {
                        existingSymptoms = editSymptomsInput.value.split(',').map(s => s.trim());
                    }
                }

                allSymptoms.forEach((symptom, index) => {
                    const isChecked = existingSymptoms.includes(symptom) ? 'checked' : '';
                    const col = document.createElement('div');
                    col.className = 'col-md-4 mb-2';
                    col.innerHTML = `
            <div class="form-check">
                <input class="form-check-input ${prefix}-symptom-checkbox" type="checkbox" 
                       value="${symptom}" id="${prefix}symptom_${index}" ${isChecked}>
                <label class="form-check-label" for="${prefix}symptom_${index}">
                    ${symptom}
                </label>
            </div>
        `;
                    container.appendChild(col);
                });

                // Add event listeners
                container.querySelectorAll(`.${prefix}-symptom-checkbox`).forEach(checkbox => {
                    checkbox.addEventListener('change', () => {
                        updateSymptomsInput(prefix);
                    });
                });

                updateSymptomsInput(prefix);
            }

            // ✅ Update hidden symptoms input
            function updateSymptomsInput(prefix) {
                const container = document.getElementById(`${prefix}symptomsContainer`);
                const input = document.getElementById(`${prefix}symptomsInput`);

                if (!container || !input) return;

                const checkedSymptoms = Array.from(container.querySelectorAll(`.${prefix}-symptom-checkbox:checked`))
                    .map(cb => cb.value);
                input.value = checkedSymptoms.join(', ');
            }
        </script>

        <script>
            let table;

            function initDataTable() {
                // ✅ Check if already initialized
                if ($.fn.DataTable.isDataTable('#leadsTable')) {
                    console.log('DataTable already initialized, destroying first...');
                    table.destroy();
                    table = null;
                }

                try {
                    table = new DataTable('#leadsTable', {
                        pageLength: 25,
                        // ✅ Updated order index to 10 because CATEGORY column was removed
                        order: [
                            [10, 'desc']
                        ],
                        dom: 'Bfrtip',
                        buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
                        columnDefs: [{
                                orderable: false,
                                targets: 0
                            },
                            {
                                orderable: false,
                                targets: -1
                            }
                        ],
                        language: {
                            search: "Search:",
                            lengthMenu: "Show _MENU_ entries",
                            info: "Showing _START_ to _END_ of _TOTAL_ leads"
                        },
                        initComplete: function(settings, json) {
                            console.log('✅ DataTable initialized successfully');
                            // ✅ Force table visibility
                            $('#leadsTable').show();
                            $('.dataTables_wrapper').show();
                        },
                        drawCallback: function(settings) {
                            console.log('✅ DataTable drawn');
                        }
                    });
                } catch (error) {
                    console.error('❌ DataTable init error:', error);
                    // ✅ Fallback: Show table without DataTable
                    $('#leadsTable').show();
                }
            }


            $(document).ready(function() {
                initDataTable();

                // ✅ Select All checkbox functionality
                $(document).on('change', '#selectAll', function() {
                    const isChecked = $(this).is(':checked');
                    $('.menuCheckbox').prop('checked', isChecked);
                });

                // ✅ Individual checkbox - update select all state
                $(document).on('change', '.menuCheckbox', function() {
                    const allChecked = $('.menuCheckbox').length === $('.menuCheckbox:checked').length;
                    $('#selectAll').prop('checked', allChecked);
                });
            });
        </script>

        <!-- SweetAlert2 JS -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.0/dist/sweetalert2.min.js"></script>

        <!-- Bootstrap Bundle (Includes Popper.js and Bootstrap JS) -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>

        <script>
            $(document).ready(function() {
                $("#updateuser").delay(5000).slideUp(300);
            });
        </script>

        <script>
            $(document).on('click', '.viewLead', function() {
                var encodedId = $(this).data('id'); // Get the encoded ID from the button

                // Show the modal
                var modal = new bootstrap.Modal(document.getElementById('viewLeadModal'));
                modal.show();

                // Fetch details with AJAX
                $.ajax({
                    url: 'fetch-lead-details.php', // Backend script
                    type: 'POST',
                    data: {
                        id: encodedId
                    },
                    success: function(response) {
                        // Populate modal body with response
                        $('#leadDetails').html(response);
                    },
                    error: function() {
                        $('#leadDetails').html('<p>Error fetching details.</p>');
                    }
                });
            });
        </script>


        <script>
            $(document).ready(function() {
                // Populate the dropdown with lead statuses
                $.ajax({
                    url: 'fetch_lead_status.php',
                    type: 'GET',
                    success: function(response) {
                        $('#statusFilter').append(response);
                    }
                });

                // ✅ Trigger fetch on ANY filter change
                $('#statusFilter, #dateFrom, #dateTo, #userFilter, #followUpFilter').on('change', function() {
                    fetchLeads();
                });

                // ✅ Unified fetch function - reads values from DOM
                function fetchLeads() {
                    const status = $('#statusFilter').val();
                    const dateFrom = $('#dateFrom').val();
                    const dateTo = $('#dateTo').val();
                    const userFilter = $('#userFilter').val();
                    const followUpFilter = $('#followUpFilter').val();

                    const canAssignLeads = <?php echo $can_assign_leads ? 'true' : 'false'; ?>;
                    // ✅ Updated colspan to 12/13 since CATEGORY column was removed
                    const colspan = canAssignLeads ? 13 : 12;

                    $.ajax({
                        url: 'fetch_filtered_leads.php',
                        type: 'POST',
                        data: {
                            status: status,
                            dateFrom: dateFrom,
                            dateTo: dateTo,
                            userFilter: userFilter,
                            followUpFilter: followUpFilter
                        },
                        beforeSend: function() {
                            $('#leadsTable tbody').html('<tr><td colspan="' + colspan + '" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div><br>Loading leads...</td></tr>');
                        },
                        success: function(response) {
                            console.log('Response received, length:', response.length);

                            // ✅ PROPER DataTable cleanup
                            if ($.fn.DataTable.isDataTable('#leadsTable')) {
                                table.destroy();
                                table = null;
                            }

                            // ✅ Remove DataTable wrapper elements
                            $('#leadsTable').removeClass('dataTable');
                            $('.dataTables_wrapper').find('#leadsTable').unwrap();

                            // ✅ Clear and update tbody
                            $('#leadsTable tbody').empty().html(response);

                            console.log('Table HTML updated, reinitializing DataTable...');

                            // ✅ Reinitialize DataTable with slight delay
                            setTimeout(function() {
                                initDataTable();
                                $('#selectAll').prop('checked', false);
                                console.log('DataTable reinitialized');
                            }, 100);
                        },
                        error: function(xhr, status, error) {
                            console.error("AJAX Error:", error);
                            console.error("Response:", xhr.responseText);
                            $('#leadsTable tbody').html('<tr><td colspan="' + colspan + '" class="text-center text-danger"><i class="feather icon-alert-triangle"></i><br>Error: ' + error + '</td></tr>');
                        }
                    });
                }

                // ✅ Clear filters - reset all inputs AND trigger fetch
                $('#clearFilters').on('click', function() {
                    $('#dateFrom').val('');
                    $('#dateTo').val('');
                    $('#statusFilter').val('');
                    $('#userFilter').val('');
                    $('#followUpFilter').val('');

                    fetchLeads();
                });
            });
        </script>

        <script>
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.href);
            }
        </script>

        <script>
            // When trash icon is clicked
            document.querySelectorAll('.delete-btn').forEach(function(button) {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    document.getElementById('deleteId').value = id;
                });
            });

            // When Delete button in modal is clicked
            document.getElementById('confirmDelete').addEventListener('click', function() {
                const id = document.getElementById('deleteId').value;
                if (id) {
                    window.location.href = 'delete-leads.php?id=' + encodeURIComponent(id);
                } else {
                    alert('No ID found to delete.');
                }
            });
        </script>

        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <script>
            document.getElementById('assigselected').addEventListener('click', function() {
                const checkboxes = document.querySelectorAll('input.menuCheckbox:checked');
                const selectedIds = Array.from(checkboxes).map(cb => cb.value);
                document.getElementById('selected_ids_input').value = selectedIds.join(',');
            });

            document.getElementById('bulkAssignForm').addEventListener('submit', function(e) {
                e.preventDefault();

                const formData = new FormData(this);

                fetch('bulk-assign.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(async res => {
                        const text = await res.text();
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            throw new Error("Invalid JSON response: " + text);
                        }
                    })
                    .then(data => {
                        if (data.status === 'success') {
                            const modalEl = document.getElementById('bulkAssignModal');
                            const modal = bootstrap.Modal.getInstance(modalEl);
                            modal.hide();

                            Swal.fire({
                                title: 'Success!',
                                text: data.message,
                                icon: 'success',
                                confirmButtonText: 'OK'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: data.message,
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    })
                    .catch(err => {
                        Swal.fire({
                            title: 'Error',
                            text: err.message,
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    });

            });
        </script>

        <script>
            document.getElementById("deleteSelected").addEventListener("click", function() {
                var form = document.getElementById("deleteMForm");
                var checkboxes = form.querySelectorAll("input[name='advt_ids[]']:checked");

                if (checkboxes.length === 0) {
                    var alertModal = new bootstrap.Modal(document.getElementById('alertModal'));
                    alertModal.show();
                } else {
                    var deleteSelectedModal = new bootstrap.Modal(document.getElementById('deleteSelectedModal'));
                    deleteSelectedModal.show();
                }
            });

            document.getElementById("confirmMultiDelete").addEventListener("click", function() {
                document.getElementById("deleteMForm").submit();
            });
        </script>
        <!-- ✅ MOVE THIS SCRIPT BEFORE THE CLICK HANDLER -->
        <script>
            // Symptoms data for each treatment (same as create modal)
            const editSymptomsData = {
                'Autism': [
                    'Autism', 'Speech Disorder', 'Eye Contact', 'Toe Walking', 'Stubborn',
                    'Repetitive Behaviour', 'Seizers', 'Hand Flapping', 'Sleep Problem',
                    'Choosy at Eat', 'Teeth Grinding', 'Sweating', 'Stool Trained', 'Concentration'
                ],
                'ADHD': [
                    'ADHD', 'Super Hyper', 'Hyperactive', 'Aggressive', 'Understanding',
                    'Chewing Problem', 'Command Follow', 'Socialization', 'Jumping',
                    'Sensory Nerves', 'Motor Nerves', 'Self Talk', 'Self Bite',
                    'Bite Other', 'Self Hit', 'Hit Other'
                ],
                'Speech Disorder': [
                    'Delayed Speech', 'Unclear Speech', 'Stuttering', 'Limited Vocabulary',
                    'Difficulty Understanding', 'Articulation Problems', 'Voice Disorders'
                ],
                'Behaviour Disorder': [
                    'Aggressive Behavior', 'Defiance', 'Tantrums', 'Impulsivity',
                    'Difficulty Following Rules', 'Social Problems', 'Emotional Outbursts'
                ],
                'Cerebral Palsy': [
                    'Muscle Stiffness', 'Poor Coordination', 'Tremors', 'Delayed Development',
                    'Difficulty Walking', 'Speech Problems', 'Seizures', 'Drooling'
                ],
                'Brain Disorder': [
                    'Memory Problems', 'Confusion', 'Mood Changes', 'Headaches',
                    'Seizures', 'Difficulty Concentrating', 'Sleep Disturbances'
                ]
            };

            const editCourseSelect = document.getElementById('edit_course');
            const editOtherDiv = document.getElementById('editOtherTreatmentDiv');
            const editOtherInput = document.getElementById('edit_other_treatment');
            const editSymptomsDiv = document.getElementById('editSymptomsDiv');
            const editSymptomsContainer = document.getElementById('editSymptomsContainer');
            const editSymptomsInput = document.getElementById('editSymptomsInput');
            const editForm = document.getElementById('editLeadForm');

            // ✅ Handle multiple treatment selection
            if (editCourseSelect) {
                editCourseSelect.addEventListener('change', function() {
                    const selectedTreatments = Array.from(this.selectedOptions).map(opt => opt.value);

                    // Check if "Other" is selected
                    if (selectedTreatments.includes('Other')) {
                        editOtherDiv.style.display = 'block';
                    } else {
                        editOtherDiv.style.display = 'none';
                        editOtherInput.value = '';
                    }

                    // ✅ Combine symptoms from all selected treatments
                    if (selectedTreatments.length > 0 && !selectedTreatments.includes('Other')) {
                        editSymptomsDiv.style.display = 'block';
                        renderEditSymptomsMultiple(selectedTreatments, '');
                    } else {
                        editSymptomsDiv.style.display = 'none';
                        editSymptomsContainer.innerHTML = '';
                    }
                });
            }

            // ✅ Render symptoms checkboxes for multiple treatments
            function renderEditSymptomsMultiple(treatments, existingSymptoms) {
                const editSymptomsContainer = document.getElementById('editSymptomsContainer');
                const editSymptomsInput = document.getElementById('editSymptomsInput');

                if (!editSymptomsContainer) return;

                editSymptomsContainer.innerHTML = '';

                // ✅ Combine all symptoms from selected treatments (remove duplicates)
                let allSymptoms = [];
                treatments.forEach(treatment => {
                    if (editSymptomsData[treatment]) {
                        allSymptoms = allSymptoms.concat(editSymptomsData[treatment]);
                    }
                });

                // Remove duplicates
                allSymptoms = [...new Set(allSymptoms)];

                const existingSymptomsArray = existingSymptoms ? existingSymptoms.split(',').map(s => s.trim()) : [];

                allSymptoms.forEach((symptom, index) => {
                    const isChecked = existingSymptomsArray.includes(symptom) ? 'checked' : '';
                    const col = document.createElement('div');
                    col.className = 'col-md-4 mb-2';
                    col.innerHTML = `
                <div class="form-check">
                    <input class="form-check-input edit-symptom-checkbox" type="checkbox" 
                           value="${symptom}" id="edit_symptom_${index}" ${isChecked}>
                    <label class="form-check-label" for="edit_symptom_${index}">
                        ${symptom}
                    </label>
                </div>
            `;
                    editSymptomsContainer.appendChild(col);
                });

                // Add event listeners to checkboxes
                document.querySelectorAll('.edit-symptom-checkbox').forEach(checkbox => {
                    checkbox.addEventListener('change', updateEditSymptomsInput);
                });

                updateEditSymptomsInput();
            }

            // ✅ Keep old function for backward compatibility
            function renderEditSymptoms(treatment, existingSymptoms) {
                renderEditSymptomsMultiple([treatment], existingSymptoms);
            }

            // Update hidden input with selected symptoms
            function updateEditSymptomsInput() {
                const editSymptomsContainer = document.getElementById('editSymptomsContainer');
                const editSymptomsInput = document.getElementById('editSymptomsInput');

                if (!editSymptomsContainer || !editSymptomsInput) return;
                const checkedSymptoms = Array.from(document.querySelectorAll('.edit-symptom-checkbox:checked'))
                    .map(cb => cb.value);
                editSymptomsInput.value = checkedSymptoms.join(', ');
            }

            // On form submit
            if (editForm) {
                editForm.addEventListener('submit', function(e) {
                    // ✅ Convert multiple select values to comma-separated string
                    if (editCourseSelect) {
                        const selectedTreatments = Array.from(editCourseSelect.selectedOptions).map(opt => opt.value);

                        // Handle "Other" treatment
                        if (selectedTreatments.includes('Other') && editOtherInput) {
                            const otherIndex = selectedTreatments.indexOf('Other');
                            selectedTreatments[otherIndex] = editOtherInput.value.trim();
                        }

                        // Create a hidden input to submit the treatments
                        let hiddenInput = document.querySelector('input[name="course_type_hidden"]');
                        if (!hiddenInput) {
                            hiddenInput = document.createElement('input');
                            hiddenInput.type = 'hidden';
                            hiddenInput.name = 'course_type';
                            editForm.appendChild(hiddenInput);
                        }
                        hiddenInput.value = selectedTreatments.join(', ');

                        // Disable the select so it doesn't submit
                        editCourseSelect.disabled = true;
                    }

                    updateEditSymptomsInput();
                });
            }
        </script>

        <script>
            $(document).ready(function() {
                $(document).on("click", ".editLead", function() {
                    let admissionId = $(this).data("lead-id");
                    let name = $(this).data("name");
                    let mobile = $(this).data("mobile");
                    let email = $(this).data("email");
                    let state = $(this).data("state");
                    let city = $(this).data("city");
                    let course = $(this).data("course");
                    let currentStatus = $(this).data("status")?.toLowerCase().trim();
                    let followUpStage = $(this).data("follow-up")?.trim();
                    let remark = $(this).data("remark");
                    let symptoms = $(this).data("symptoms") || '';
                    let history = $(this).data("history") || '';

                    console.log('=== EDIT LEAD DEBUG ===');
                    console.log('Course:', course);
                    console.log('Symptoms:', symptoms);
                    console.log('History:', history);

                    // Set all field values
                    $("#edit_admission_id").val(admissionId);
                    $("#edit_name").val(name);
                    $("#edit_mobile").val(mobile);
                    $("#edit_email").val(email);
                    $("#edit_state").val(state);
                    $("#edit_city").val(city);
                    $("#edit_remarks").val(remark);

                    // ✅ Set history value
                    const historySelect = document.getElementById('edit_history');
                    if (historySelect && history) {
                        const historyLower = history.toLowerCase().trim();
                        for (let i = 0; i < historySelect.options.length; i++) {
                            if (historySelect.options[i].value.toLowerCase() === historyLower) {
                                historySelect.selectedIndex = i;
                                break;
                            }
                        }
                    }

                    // Set follow-up stage
                    if (followUpStage) {
                        $("#edit_follow_up_stage").val(followUpStage);
                    } else {
                        $("#edit_follow_up_stage").val('');
                    }

                    // ✅ Handle multiple treatments using MultiSelect instance
                    const editCourseSelect = document.getElementById('edit_course');
                    const editOtherDiv = document.getElementById('editOtherTreatmentDiv');
                    const editOtherInput = document.getElementById('edit_other_treatment');
                    const editSymptomsDiv = document.getElementById('editSymptomsDiv');

                    // Reset first
                    if (editOtherDiv) editOtherDiv.style.display = 'none';
                    if (editOtherInput) editOtherInput.value = '';
                    if (editSymptomsDiv) editSymptomsDiv.style.display = 'none';

                    // ✅ Parse comma-separated treatments
                    const treatments = course ? course.split(',').map(t => t.trim()) : [];
                    const standardTreatments = ['Autism', 'ADHD', 'Speech Disorder', 'Behaviour Disorder', 'Cerebral Palsy', 'Brain Disorder'];

                    let hasOther = false;
                    let otherValue = '';
                    let standardSelected = [];

                    treatments.forEach(treatment => {
                        if (standardTreatments.includes(treatment)) {
                            standardSelected.push(treatment);
                        } else {
                            hasOther = true;
                            otherValue = treatment;
                        }
                    });

                    // ✅ Use MultiSelect instance to set values
                    if (window.editMultiSelect) {
                        window.editMultiSelect.setSelectedValues(standardSelected);

                        // Show/hide Other input
                        if (hasOther) {
                            if (editOtherDiv) editOtherDiv.style.display = 'block';
                            if (editOtherInput) editOtherInput.value = otherValue;
                            // Also select "Other" in multi-select
                            const otherOption = Array.from(editCourseSelect.options).find(opt => opt.value === 'Other');
                            if (otherOption) {
                                otherOption.selected = true;
                                window.editMultiSelect.updateSelectedDisplay();
                            }
                        }

                        // ✅ Show combined symptoms AFTER modal is shown
                        setTimeout(() => {
                            if (standardSelected.length > 0) {
                                if (editSymptomsDiv) editSymptomsDiv.style.display = 'block';
                                renderEditSymptomsMultiple(standardSelected, symptoms);
                                console.log('✅ Rendering symptoms for:', standardSelected, 'with existing:', symptoms);
                            }
                        }, 300); // Wait for modal to fully show
                    }

                    // Fetch lead status options
                    $.ajax({
                        url: "fetch_lead_status.php",
                        method: "GET",
                        success: function(data) {
                            $("#edit_lead_status").html(data);

                            $("#edit_lead_status option").each(function() {
                                if ($(this).val().toLowerCase().trim() === currentStatus) {
                                    $(this).prop("selected", true);
                                    return false;
                                }
                            });

                            // ✅ FIXED: Use consistent quotes
                            $("#editLeadStatusModal").modal('show');
                        },
                        error: function() {
                            alert("Error fetching lead statuses.");
                        }
                    });
                });
            });
        </script>

        <script>
            // ✅ Render symptoms checkboxes for multiple treatments
            function renderEditSymptomsMultiple(treatments, existingSymptoms) {
                const editSymptomsContainer = document.getElementById('editSymptomsContainer');
                const editSymptomsInput = document.getElementById('editSymptomsInput');

                if (!editSymptomsContainer) {
                    console.error('❌ editSymptomsContainer not found');
                    return;
                }

                editSymptomsContainer.innerHTML = '';

                // ✅ Combine all symptoms from selected treatments (remove duplicates)
                let allSymptoms = [];
                treatments.forEach(treatment => {
                    if (editSymptomsData[treatment]) {
                        allSymptoms = allSymptoms.concat(editSymptomsData[treatment]);
                    }
                });

                // Remove duplicates
                allSymptoms = [...new Set(allSymptoms)];

                console.log(' All symptoms:', allSymptoms);
                console.log('🔍 Existing symptoms:', existingSymptoms);

                const existingSymptomsArray = existingSymptoms ? existingSymptoms.split(',').map(s => s.trim()) : [];
                console.log('🔍 Parsed existing symptoms:', existingSymptomsArray);

                allSymptoms.forEach((symptom, index) => {
                    const isChecked = existingSymptomsArray.includes(symptom) ? 'checked' : '';
                    const col = document.createElement('div');
                    col.className = 'col-md-4 mb-2';
                    col.innerHTML = `
                <div class="form-check">
                    <input class="form-check-input edit-symptom-checkbox" type="checkbox" 
                           value="${symptom}" id="edit_symptom_${index}" ${isChecked}>
                    <label class="form-check-label" for="edit_symptom_${index}">
                        ${symptom}
                    </label>
                </div>
            `;
                    editSymptomsContainer.appendChild(col);
                });

                // Add event listeners to checkboxes
                document.querySelectorAll('.edit-symptom-checkbox').forEach(checkbox => {
                    checkbox.addEventListener('change', updateEditSymptomsInput);
                });

                updateEditSymptomsInput();

                console.log('✅ Symptoms rendered, container has', editSymptomsContainer.children.length, 'items');
            }

            // Update hidden input with selected symptoms
            function updateEditSymptomsInput() {
                const editSymptomsContainer = document.getElementById('editSymptomsContainer');
                const editSymptomsInput = document.getElementById('editSymptomsInput');

                if (!editSymptomsContainer || !editSymptomsInput) return;
                const checkedSymptoms = Array.from(document.querySelectorAll('.edit-symptom-checkbox:checked'))
                    .map(cb => cb.value);
                editSymptomsInput.value = checkedSymptoms.join(', ');
                console.log('💾 Symptoms input updated to:', editSymptomsInput.value);
            }
        </script>
        <script>
            // ✅ Create Lead Form Submit Handler
            document.addEventListener('DOMContentLoaded', function() {
                const createForm = document.getElementById('createLeadForm');
                const createSelect = document.getElementById('new_course');
                const otherInput = document.getElementById('other_treatment');

                if (createForm && createSelect) {
                    createForm.addEventListener('submit', function(e) {
                        // ✅ Get selected treatments from MultiSelect instance
                        let selectedTreatments = [];

                        if (window.createMultiSelect) {
                            selectedTreatments = window.createMultiSelect.getSelectedValues();
                        } else {
                            // Fallback: get from original select
                            selectedTreatments = Array.from(createSelect.selectedOptions).map(opt => opt.value);
                        }

                        console.log('📋 Selected treatments:', selectedTreatments);

                        // Handle "Other" treatment
                        if (selectedTreatments.includes('Other')) {
                            if (!otherInput || !otherInput.value.trim()) {
                                e.preventDefault();
                                alert('Please specify the treatment name for "Other".');
                                otherInput.focus();
                                return false;
                            }
                            const otherIndex = selectedTreatments.indexOf('Other');
                            selectedTreatments[otherIndex] = otherInput.value.trim();
                        }

                        // ✅ Create or update hidden input for course_type
                        let hiddenCourseInput = createForm.querySelector('input[name="course_type"]');
                        if (!hiddenCourseInput) {
                            hiddenCourseInput = document.createElement('input');
                            hiddenCourseInput.type = 'hidden';
                            hiddenCourseInput.name = 'course_type';
                            createForm.appendChild(hiddenCourseInput);
                        }

                        // Set the comma-separated value
                        hiddenCourseInput.value = selectedTreatments.join(', ');
                        console.log('✅ Final course_type value:', hiddenCourseInput.value);

                        // ✅ Disable original select to prevent duplicate submission
                        createSelect.disabled = true;
                        createSelect.removeAttribute('name');

                        // ✅ Update symptoms - get ALL checked checkboxes
                        const symptomsContainer = document.getElementById('symptomsContainer');
                        const symptomsInput = document.getElementById('symptomsInput');

                        if (symptomsContainer && symptomsInput) {
                            // ✅ Use querySelectorAll on the container specifically
                            const checkedSymptoms = Array.from(symptomsContainer.querySelectorAll('input[type="checkbox"]:checked'))
                                .map(cb => cb.value);

                            symptomsInput.value = checkedSymptoms.join(', ');
                            console.log('✅ Final symptoms value:', symptomsInput.value);
                        }

                        // ✅ Log all form data for debugging
                        const formData = new FormData(createForm);
                        console.log('📦 All form data:');
                        for (let [key, value] of formData.entries()) {
                            console.log(`  ${key}: ${value}`);
                        }
                    });
                }

                // ✅ Reset create form when modal closes
                const createModal = document.getElementById('createLeadModal');
                if (createModal) {
                    createModal.addEventListener('hidden.bs.modal', function() {
                        if (createSelect) {
                            createSelect.disabled = false;
                            createSelect.setAttribute('name', 'course_type');
                        }

                        // Remove hidden course_type input
                        const hiddenCourseInput = createForm.querySelector('input[name="course_type"]');
                        if (hiddenCourseInput && hiddenCourseInput.type === 'hidden') {
                            hiddenCourseInput.remove();
                        }

                        // Reset MultiSelect
                        if (window.createMultiSelect) {
                            window.createMultiSelect.setSelectedValues([]);
                        }

                        // Clear symptoms
                        const symptomsInput = document.getElementById('symptomsInput');
                        if (symptomsInput) symptomsInput.value = '';

                        const symptomsContainer = document.getElementById('symptomsContainer');
                        if (symptomsContainer) symptomsContainer.innerHTML = '';
                    });
                }
            });
        </script>

    </body>

    </html>