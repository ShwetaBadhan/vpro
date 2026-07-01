<?php
error_reporting(E_ALL);
session_start();

if (!isset($_SESSION["login_user"])) {
    header("location: index.php");
    exit;
}

require_once "db/config.php";

// ✅ Get current user permissions (same logic as main page)
$login_user_id = $_SESSION['login_user_id'] ?? 0;
$isAdmin = false;
$permissions = [];
$role_name = '';

if ($login_user_id) {
    $get_admin_query = "SELECT admin_role FROM admin WHERE _id = ?";
    $stmt = mysqli_prepare($db, $get_admin_query);
    mysqli_stmt_bind_param($stmt, "i", $login_user_id);
    mysqli_stmt_execute($stmt);
    $get_admin_result = mysqli_stmt_get_result($stmt);
    $admin_row = mysqli_fetch_assoc($get_admin_result);
    mysqli_stmt_close($stmt);
    
    if ($admin_row) {
        $role_id = $admin_row['admin_role'];
        $role_query = "SELECT role_name FROM roles WHERE role_id = ?";
        $stmt = mysqli_prepare($db, $role_query);
        mysqli_stmt_bind_param($stmt, "i", $role_id);
        mysqli_stmt_execute($stmt);
        $role_result = mysqli_stmt_get_result($stmt);
        $role_row = mysqli_fetch_assoc($role_result);
        mysqli_stmt_close($stmt);
        $role_name = strtolower($role_row['role_name'] ?? '');
        
        if ($role_name == 'admin') {
            $isAdmin = true;
            $permissions = ['Upload Leads', 'Download Leads', 'Delete Leads', 'Assign Leads'];
        } elseif ($role_name == 'manager') {
            $permissions = ['Upload Leads'];
        }
    }
}

// ✅ Dynamic status filter - default to 'verified' if not set
$status_filter = $_GET['status_filter'] ?? 'own';

// ✅ Base query with prepared statement support
$query = "
    SELECT 
        ae.admission_id,
        ae.name,
        ae.email,
        ae.mobile,
        ae.course_type,
        ae.state,
        ae.city,
        ae.lead_status,
        ae.remarks,
        ae.date,
        ae.category_id,
        ae.sub_category_id,
        pc.name AS parent_category_name,
        c.category_name AS sub_category_name,
        a.username AS assigned_admin
    FROM admission_enquiry ae
    LEFT JOIN parent_category pc ON ae.category_id = pc.id
    LEFT JOIN category c ON ae.sub_category_id = c.category_id
    LEFT JOIN lead_assignments la ON ae.admission_id = la.admission_id
    LEFT JOIN admin a ON la.admin_id = a._id
    WHERE ae.lead_status = ?
";

// ✅ Role-based restrictions
if ($role_name == 'telecaller') {
    $query .= " AND la.admin_id = ?";
} elseif (!in_array($role_name, ['admin', '']) && !empty($role_name)) {
    $query .= " AND la.admin_id = ?";
}

$query .= " ORDER BY ae.date DESC";

// ✅ Execute with prepared statement
$stmt = mysqli_prepare($db, $query);
if ($role_name == 'telecaller' || (!in_array($role_name, ['admin', '']) && !empty($role_name))) {
    mysqli_stmt_bind_param($stmt, "si", $status_filter, $login_user_id);
} else {
    mysqli_stmt_bind_param($stmt, "s", $status_filter);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

// ✅ Handle lead status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['editleads'])) {
    $leadid = base64_decode($_POST['admission_id']);
    $leadstatus = $_POST['lead_status'];
    $remarks = trim($_POST['remarks']);

    if (empty($leadstatus)) {
        $_SESSION['msg'] = '<div class="alert alert-danger">Lead status is required.</div>';
        header('Location: hot-leads.php');
        exit;
    }

    $update_query = "UPDATE admission_enquiry SET lead_status = ?, remarks = ? WHERE admission_id = ?";
    if ($stmt = mysqli_prepare($db, $update_query)) {
        mysqli_stmt_bind_param($stmt, "ssi", $leadstatus, $remarks, $leadid);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    $_SESSION['msg'] = '<div class="alert alert-success">Lead updated successfully!</div>';
    header('Location: hot-leads.php');
    exit;
}

// ✅ Fetch lead statuses for dropdown
$status_query = "SELECT status_id, status_name FROM lead_status ORDER BY status_name";
$status_result = mysqli_query($db, $status_query);

// ✅ Fetch settings
$settings_query = "SELECT * FROM login_settings LIMIT 1";
$settingsResult = mysqli_query($db, $settings_query);
$settings = mysqli_fetch_assoc($settingsResult);

$logoPath = $settings['backend_panel_logo'] ?? '';
$helpdeskNumber = $settings['helpdesk_no'] ?? '';
$favicon = $settings['favicon'] ?? 'assets/images/favicon.ico';

// ✅ Badge color map - SAME as main page
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="content-type" content="text/html;charset=UTF-8" />
    <title>Admission Leads - <?php echo ucfirst(htmlspecialchars($status_filter)); ?></title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="icon" href="<?php echo htmlspecialchars($favicon); ?>" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.0/css/dataTables.dataTables.css" />
    
    <!-- ✅ Same custom styles as main page -->
    <style>
        .category-badge {
            display: inline-flex;
            align-items: center;
            background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            border: 1px solid #667eea30;
        }
        .category-badge .parent-cat { color: #667eea; font-weight: 600; }
        .category-badge .separator { margin: 0 6px; color: #adb5bd; }
        .category-badge .sub-cat { color: #764ba2; }
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
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.0/dist/sweetalert2.min.css" rel="stylesheet">
</head>

<body class="">
    <div class="loader-bg">
        <div class="loader-track">
            <div class="loader-fill"></div>
        </div>
    </div>

    <?php include("header.php"); ?>
    <?php include("navbar.php"); ?>

    <section class="pcoded-main-container">
        <div class="pcoded-content">
            <div class="page-header">
                <div class="page-block">
                    <div class="row align-items-center">
                        <div class="col-md-12">
                            <div class="page-header-title">
                                <h5 class="m-b-10">Admission Leads - <?php echo ucfirst(htmlspecialchars($status_filter)); ?></h5>
                                <?php
                                if (isset($_SESSION['msg'])) {
                                    echo $_SESSION['msg'];
                                    unset($_SESSION['msg']);
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
                        <div class="card-header table-card-header"></div>
                        <div class="card-body">
                            <form id="deleteMForm" action="delete-leads.php" method="post">
                                
                                <?php if ($isAdmin || in_array('Delete Leads', $permissions)): ?>
                                    <button type="button" id="deleteSelected" class="btn btn-danger mr-2">
                                        <i class='feather icon-trash'></i> Delete Selected
                                    </button>
                                <?php endif; ?>

                                <div class="dt-responsive table-responsive">
                                    <table id="myTable" class="table table-striped table-bordered nowrap">
                                        <thead>
                                            <tr>
                                                <th><input type='checkbox' id='selectAll' onclick='toggleCheckboxes(this)'></th>
                                                <th>SNO</th>
                                                <th>NAME</th>
                                                <th>MOBILE</th>
                                                <th>EMAIL</th>
                                                <th>STATE</th>
                                                <th>CITY</th>
                                                <th>SERVICES</th>
                                                <th>CATEGORY</th>
                                                <th>REMARKS</th>
                                                <th>STATUS</th>
                                                <th>DATE</th>
                                                <?php if ($isAdmin || in_array('Assign Leads', $permissions)) echo "<th>ASSIGNED TO</th>"; ?>
                                                <th>ACTION</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $count = 1;
                                            while ($row = mysqli_fetch_assoc($result)) {
                                                $encoded_id = base64_encode($row['admission_id']);
                                                $status_val = strtolower($row['lead_status']);
                                                $badgeColor = $badgeMap[$status_val] ?? '#6c757d';
                                                
                                                // ✅ Remarks: 40 chars + title attribute
                                                $remarks = $row['remarks'] ?? '';
                                                $shortRemarks = strlen($remarks) > 40 ? substr($remarks, 0, 40) . '...' : $remarks;
                                                
                                                // ✅ Category badge display
                                                $parentCat = !empty($row['parent_category_name']) ? htmlspecialchars($row['parent_category_name']) : '-';
                                                $subCat = !empty($row['sub_category_name']) ? htmlspecialchars($row['sub_category_name']) : '-';
                                                
                                                echo "<tr>";
                                                echo "<td><input type='checkbox' class='menuCheckbox' name='advt_ids[]' value='$encoded_id'></td>";
                                                echo "<td>$count</td>";
                                                echo "<td><strong>" . htmlspecialchars($row['name']) . "</strong></td>";
                                                echo "<td>" . htmlspecialchars($row['mobile']) . "</td>";
                                                echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                                                echo "<td>" . htmlspecialchars($row['state']) . "</td>";
                                                echo "<td>" . htmlspecialchars($row['city']) . "</td>";
                                                echo "<td>" . htmlspecialchars($row['course_type']) . "</td>";
                                                
                                                // ✅ Category badge - SAME structure
                                                echo "<td><div class='category-badge'><span class='parent-cat'>$parentCat</span><span class='separator'>›</span><span class='sub-cat'>$subCat</span></div></td>";
                                                
                                                // ✅ Remarks with title
                                                echo "<td title='" . htmlspecialchars($row['remarks']) . "'>" . htmlspecialchars($shortRemarks) . "</td>";
                                                
                                                // ✅ Status badge with status-badge class
                                                echo "<td><span class='badge status-badge' style='background-color: $badgeColor; color: #fff;'>" . ucfirst($status_val) . "</span></td>";
                                                
                                                echo "<td>" . date("d-m-Y", strtotime($row['date'])) . "</td>";
                                                
                                                // ✅ Assigned To dropdown - ONLY if permission
                                                if ($isAdmin || in_array('Assign Leads', $permissions)) {
                                                    echo "<td>";
                                                    $assignedAdminId = '';
                                                    $assignQuery = "SELECT admin_id FROM lead_assignments WHERE admission_id = ?";
                                                    $stmt_assign = $db->prepare($assignQuery);
                                                    if ($stmt_assign) {
                                                        $stmt_assign->bind_param("i", $row['admission_id']);
                                                        $stmt_assign->execute();
                                                        $stmt_assign->bind_result($assignedAdminId);
                                                        $stmt_assign->fetch();
                                                        $stmt_assign->close();
                                                    }
                                                    
                                                    echo '<select class="form-control form-control-sm" onchange="saveUserId(this, '.$row['admission_id'].')">';
                                                    echo '<option value="">Select User</option>';
                                                    $query_admin = "SELECT _id, username FROM admin ORDER BY username";
                                                    $admin_result = $db->query($query_admin);
                                                    while ($admin_row = $admin_result->fetch_assoc()) {
                                                        $selected = ($admin_row['_id'] == $assignedAdminId) ? 'selected' : '';
                                                        echo '<option value="'.$admin_row['_id'].'" '.$selected.'>'.htmlspecialchars($admin_row['username']).'</option>';
                                                    }
                                                    echo '</select>';
                                                    echo "</td>";
                                                }
                                                
                                                // ✅ Action buttons - SAME classes
                                                echo "<td>";
                                                echo "<a href='javascript:void(0)' class='btn btn-sm btn-warning btn-action viewLead' data-id='$encoded_id' title='View'><i class='feather icon-eye'></i></a>";
                                                echo "<a href='javascript:void(0)' class='btn btn-sm btn-success btn-action editLead' data-lead-id='$encoded_id' data-status='".htmlspecialchars($row['lead_status'])."' data-remark='".htmlspecialchars($row['remarks'])."' title='Edit'><i class='feather icon-edit'></i></a>";
                                                
                                                if ($isAdmin || in_array('Delete Leads', $permissions)) {
                                                    echo "<a href='javascript:void(0)' class='btn btn-sm btn-danger btn-action delete-btn' data-id='$encoded_id' data-bs-toggle='modal' data-bs-target='#deleteModal' title='Delete'><i class='feather icon-trash'></i></a>";
                                                }
                                                echo "</td>";
                                                echo "</tr>";
                                                
                                                $count++;
                                            }
                                            
                                           
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

    <!-- ✅ Same Modals as main page -->
    <div class="modal fade" id="viewLeadModal" tabindex="-1" aria-labelledby="viewLeadModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewLeadModalLabel">Lead Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">X</button>
                </div>
                <div class="modal-body" id="leadDetails">Loading details...</div>
            </div>
        </div>
    </div>

    <div class="modal" id="editLeadStatusModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Update Lead Status</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">X</button>
                </div>
                <div class="modal-body">
                    <form method="post">
                        <input type="hidden" id="admission_id" name="admission_id">
                        <div class="form-group">
                            <label for="lead_status">Lead Status:</label>
                            <select id="lead_status" name="lead_status" class="form-control">
                                <?php while($s = mysqli_fetch_assoc($status_result)): ?>
                                    <option value="<?php echo htmlspecialchars($s['status_name']); ?>">
                                        <?php echo htmlspecialchars($s['status_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group mt-3">
                            <label for="remarks">Remarks:</label>
                            <textarea id="remarks" name="remarks" class="form-control" rows="3" placeholder="Enter remarks..."></textarea>
                        </div>
                        <button type="submit" name="editleads" class="btn btn-primary">Update</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Delete Leads</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete the selected leads?
                    <input type="hidden" id="deleteId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="alertModal" tabindex="-1" aria-labelledby="alertModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="alertModalLabel">No Selection</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">X</button>
                </div>
                <div class="modal-body">Please select at least one lead to delete.</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-warning" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteSelectedModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal">X</button>
                </div>
                <div class="modal-body">Are you sure you want to delete the selected leads?</div>
                <div class="modal-footer">
                    <button type="button" id="confirmMultiDelete" class="btn btn-danger">Delete</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ✅ Same Scripts as main page -->
    <script src="assets/js/vendor-all.min.js"></script>
    <script src="assets/js/pcoded.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/2.3.0/js/dataTables.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.0/dist/sweetalert2.min.js"></script>
    
    <script>
        let table = new DataTable('#myTable');
    </script>
    
    <script>
        $(document).ready(function() {
            $("#updateuser").delay(5000).slideUp(300);
        });
    </script>
    
    <script>
        function toggleCheckboxes(source) {
            const checkboxes = document.querySelectorAll('.menuCheckbox');
            checkboxes.forEach(checkbox => checkbox.checked = source.checked);
        }
    </script>
    
    <script>
        $(document).on('click', '.viewLead', function() {
            var encodedId = $(this).data('id');
            var modal = new bootstrap.Modal(document.getElementById('viewLeadModal'));
            modal.show();
            $.ajax({
                url: 'fetch-lead-details.php',
                type: 'POST',
                data: { id: encodedId },
                success: function(response) { $('#leadDetails').html(response); },
                error: function() { $('#leadDetails').html('<p>Error fetching details.</p>'); }
            });
        });
    </script>
    
    <script>
        $(document).ready(function() {
            $(document).on("click", ".editLead", function() {
                let admissionId = $(this).data("lead-id");
                let currentStatus = $(this).data("status")?.toLowerCase().trim();
                let remark = $(this).data("remark");
                $("#admission_id").val(admissionId);
                $("#remarks").val(remark);
                $.ajax({
                    url: "fetch_lead_status.php",
                    method: "GET",
                    success: function(data) {
                        $("#lead_status").html(data);
                        $("#lead_status option").each(function() {
                            if ($(this).val().toLowerCase().trim() === currentStatus) {
                                $(this).prop("selected", true);
                                return false;
                            }
                        });
                        $("#editLeadStatusModal").modal("show");
                    },
                    error: function() { alert("Error fetching lead statuses."); }
                });
            });
        });
    </script>
    
    <script>
        document.querySelectorAll('.delete-btn').forEach(function(button) {
            button.addEventListener('click', function () {
                const id = this.getAttribute('data-id');
                document.getElementById('deleteId').value = id;
            });
        });
        document.getElementById('confirmDelete').addEventListener('click', function () {
            const id = document.getElementById('deleteId').value;
            if (id) {
                window.location.href = 'delete-leads.php?id=' + encodeURIComponent(id);
            } else {
                alert('No ID found to delete.');
            }
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
    
    <script>
        function saveUserId(selectElement, admissionId) {
            const adminId = selectElement.value;
            fetch('save-user-id.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `admin_id=${adminId}&admission_id=${admissionId}`
            })
            .then(response => response.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    Swal.fire({
                        icon: data.status === 'success' ? 'success' : 'error',
                        title: data.status === 'success' ? 'Success' : 'Error',
                        text: data.message,
                        confirmButtonText: 'OK'
                    });
                } catch(e) {
                    Swal.fire({ icon: 'error', title: 'Server Error', text: 'Unexpected response.', confirmButtonText: 'OK' });
                }
            })
            .catch(error => {
                Swal.fire({ icon: 'error', title: 'Request Failed', text: 'Network error occurred.', confirmButtonText: 'OK' });
            });
        }
    </script>
    
    <script>
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>