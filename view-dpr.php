<?php
session_start();

if (!isset($_SESSION["login_user"])) {
    header("location: index.php");
    exit();
}

include("db/config.php");

// Date filtering
$filter_date = $_GET['date'] ?? '';
$filter_from = $_GET['from_date'] ?? '';
$filter_to = $_GET['to_date'] ?? '';
$filter_user = $_GET['user'] ?? '';

// Build query with filters
$where_conditions = [];

if (!empty($filter_date)) {
    $filter_date = mysqli_real_escape_string($db, $filter_date);
    $where_conditions[] = "dpr.report_date = '$filter_date'";
}

if (!empty($filter_from) && !empty($filter_to)) {
    $filter_from = mysqli_real_escape_string($db, $filter_from);
    $filter_to = mysqli_real_escape_string($db, $filter_to);
    $where_conditions[] = "dpr.report_date BETWEEN '$filter_from' AND '$filter_to'";
} elseif (!empty($filter_from)) {
    $filter_from = mysqli_real_escape_string($db, $filter_from);
    $where_conditions[] = "dpr.report_date >= '$filter_from'";
} elseif (!empty($filter_to)) {
    $filter_to = mysqli_real_escape_string($db, $filter_to);
    $where_conditions[] = "dpr.report_date <= '$filter_to'";
}

if (!empty($filter_user)) {
    $filter_user = mysqli_real_escape_string($db, $filter_user);
    $where_conditions[] = "dpr.username = '$filter_user'";
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Fetch all DPRs with filters
$query = "SELECT dpr.*, 
                 (SELECT COUNT(*) FROM dpr_screenshots WHERE dpr_id = dpr.id) as screenshot_count
          FROM daily_progress_reports dpr 
          $where_clause
          ORDER BY dpr.report_date DESC, dpr.created_at DESC";
$result = mysqli_query($db, $query);

// Get unique users for filter dropdown
$users_query = "SELECT DISTINCT username FROM daily_progress_reports ORDER BY username ASC";
$users_result = mysqli_query($db, $users_query);
$users = [];
while ($user = mysqli_fetch_assoc($users_result)) {
    $users[] = $user['username'];
}

// Statistics
$total_dprs = mysqli_num_rows($result);

// Fetch login settings
$settings_query = "SELECT * FROM login_settings";
$settingsResult = mysqli_query($db, $settings_query);
$settings = mysqli_fetch_assoc($settingsResult);
$favicon = $settings['favicon'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Daily Progress Reports</title>
    <link rel="icon" href="<?php echo $favicon; ?>" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/plugins/dataTables.bootstrap4.min.css">
    
    <style>
        .filter-section {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
            margin-bottom: 15px;
        }
        
        .filter-group label {
            display: block;
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 5px;
            font-size: 13px;
        }
        
        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 8px 12px;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .filter-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        .btn-filter {
            padding: 8px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        /* .btn-apply {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        } */
        
        /* .btn-apply:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        } */
        
        .btn-clear {
            background: #e2e8f0;
            color: #4a5568;
        }
        
        .stats-row {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        
        .stat-badge {
            background: white;
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .stat-badge i {
            font-size: 20px;
            color: #667eea;
        }
        
        .stat-badge .stat-num {
            font-size: 20px;
            font-weight: 700;
            color: #2d3748;
        }
        
        .stat-badge .stat-text {
            font-size: 13px;
            color: #718096;
        }
        
        .table-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        
        .table-header {
            
            color: white;
            padding: 15px 20px;
            font-size: 16px;
            font-weight: 600;
        }
        
        .table-responsive {
            padding: 0;
        }
        
        .table {
            margin: 0;
        }
        
        .table thead th {
            background: #f8f9fa;
            border-bottom: 2px solid #e2e8f0;
            padding: 12px 15px;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            color: #4a5568;
            white-space: nowrap;
        }
        
        .table tbody td {
            padding: 12px 15px;
            vertical-align: middle;
            border-bottom: 1px solid #e2e8f0;
            font-size: 14px;
        }
        
        .table tbody tr:hover {
            background: #f7fafc;
        }
        
        .user-cell {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            /* background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); */
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 13px;
        }
        
        .summary-preview {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #718096;
        }
        
        .screenshot-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #edf2f7;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
            color: #4a5568;
        }
        
        .btn-view {
            /* background: #667eea; */
            color: white;
            padding: 6px 15px;
            border-radius: 6px;
            border: none;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-view:hover {
            background: #5568d3;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);
        }
        
        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px 8px 0 0;
        }
        
        .modal-title {
            font-weight: 600;
        }
        
        .modal-body {
            padding: 25px;
        }
        
        .detail-row {
            margin-bottom: 20px;
        }
        
        .detail-label {
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 8px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .detail-value {
            background: #f7fafc;
            padding: 15px;
            border-radius: 8px;
            color: #2d3748;
            line-height: 1.6;
        }
        
        .screenshots-modal-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 12px;
            margin-top: 10px;
        }
        
        .screenshot-thumb {
            border-radius: 8px;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.3s;
            aspect-ratio: 4/3;
            border: 2px solid #e2e8f0;
        }
        
        .screenshot-thumb:hover {
            transform: scale(1.05);
            border-color: #667eea;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        
        .screenshot-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .modal-screenshot-full {
            max-width: 100%;
            max-height: 80vh;
            object-fit: contain;
            border-radius: 8px;
        }
        
        .no-data {
            text-align: center;
            padding: 40px 20px;
            color: #718096;
        }
        
        .no-data i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #cbd5e0;
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
                                <h5 class="m-b-10">Daily Progress Reports</h5>
                            </div>
                        </div>
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
                        <div class="filter-group">
                            <label>User</label>
                            <select name="user">
                                <option value="">All Users</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo htmlspecialchars($user); ?>" 
                                            <?php echo $filter_user == $user ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="filter-buttons">
                        <button type="button" class="btn-filter btn-clear" onclick="clearFilters()">
                            <i class="feather icon-x"></i> Clear
                        </button>
                        <button type="submit" class="btn-filter btn-primary">
                            <i class="feather icon-check"></i> Apply Filters
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Statistics -->
            <div class="stats-row">
                <div class="stat-badge">
                    <i class="feather icon-file-text"></i>
                    <div>
                        <div class="stat-num"><?php echo $total_dprs; ?></div>
                        <div class="stat-text">Total Reports</div>
                    </div>
                </div>
                <div class="stat-badge">
                    <i class="feather icon-users"></i>
                    <div>
                        <div class="stat-num"><?php echo count($users); ?></div>
                        <div class="stat-text">Total Users</div>
                    </div>
                </div>
            </div>
            
            <!-- Table -->
            <div class="table-card">
                <div class="table-header bg-primary">
                    <i class="feather icon-list"></i> All Reports
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>S.No</th>
                                <th>Date</th>
                                <th>User</th>
                                <th>Time</th>
                                <th>Screenshots</th>
                                <th>Summary</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if (mysqli_num_rows($result) > 0):
                                $count = 1;
                                while ($dpr = mysqli_fetch_assoc($result)): 
                                    $user_initials = strtoupper(substr($dpr['username'], 0, 2));
                                    $summary_preview = substr($dpr['summary_notes'], 0, 60);
                                    if (strlen($dpr['summary_notes']) > 60) {
                                        $summary_preview .= '...';
                                    }
                            ?>
                            <tr>
                                <td><?php echo $count++; ?></td>
                                <td>
                                    <strong><?php echo date('d M Y', strtotime($dpr['report_date'])); ?></strong><br>
                                    <small class="text-muted"><?php echo date('l', strtotime($dpr['report_date'])); ?></small>
                                </td>
                                <td>
                                    <div class="user-cell">
                                        <div class="user-avatar bg-warning"><?php echo $user_initials; ?></div>
                                        <span><?php echo htmlspecialchars($dpr['username']); ?></span>
                                    </div>
                                </td>
                                <td><?php echo date('h:i A', strtotime($dpr['created_at'])); ?></td>
                                <td>
                                    <span class="screenshot-badge">
                                        <i class="feather icon-image"></i>
                                        <?php echo $dpr['screenshot_count']; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="summary-preview" title="<?php echo htmlspecialchars($dpr['summary_notes']); ?>">
                                        <?php echo htmlspecialchars($summary_preview); ?>
                                    </div>
                                </td>
                                <td>
                                    <button class="btn-view btn-danger" 
                                            onclick="viewDPR(<?php echo $dpr['id']; ?>)"
                                            data-id="<?php echo $dpr['id']; ?>"
                                            data-user="<?php echo htmlspecialchars($dpr['username']); ?>"
                                            data-date="<?php echo date('d M Y', strtotime($dpr['report_date'])); ?>"
                                            data-day="<?php echo date('l', strtotime($dpr['report_date'])); ?>"
                                            data-time="<?php echo date('h:i A', strtotime($dpr['created_at'])); ?>"
                                            data-summary="<?php echo htmlspecialchars($dpr['summary_notes']); ?>"
                                            data-screenshots='<?php 
                                                $screenshot_query = "SELECT * FROM dpr_screenshots WHERE dpr_id = {$dpr['id']} ORDER BY uploaded_at ASC";
                                                $screenshot_result = mysqli_query($db, $screenshot_query);
                                                $screenshots = [];
                                                while ($screenshot = mysqli_fetch_assoc($screenshot_result)) {
                                                    $screenshots[] = $screenshot;
                                                }
                                                echo json_encode($screenshots);
                                            ?>'>
                                        <i class="feather icon-eye"></i> View
                                    </button>
                                </td>
                            </tr>
                            <?php 
                                endwhile;
                            else: 
                            ?>
                            <tr>
                                <td colspan="7" class="no-data">
                                    <i class="feather icon-inbox"></i>
                                    <p>No reports found. Try adjusting your filters.</p>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
    
    <!-- View DPR Modal -->
    <div class="modal fade" id="viewDPRModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="feather icon-file-text"></i> DPR Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter: brightness(0) invert(1);"></button>
                </div>
                <div class="modal-body">
                    <div class="detail-row">
                        <div class="detail-label">User</div>
                        <div class="detail-value" id="modalUser"></div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Date & Day</div>
                        <div class="detail-value" id="modalDate"></div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Submitted At</div>
                        <div class="detail-value" id="modalTime"></div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Summary Notes</div>
                        <div class="detail-value" id="modalSummary" style="white-space: pre-wrap;"></div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Screenshots (<span id="screenshotCount">0</span>)</div>
                        <div class="screenshots-modal-grid" id="modalScreenshots"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Full Image Modal -->
    <div class="modal fade" id="fullImageModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Screenshot Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center" style="background: #1a202c;">
                    <img id="fullImage" src="" class="modal-screenshot-full" alt="Screenshot">
                </div>
            </div>
        </div>
    </div>
    
    <script src="assets/js/vendor-all.min.js"></script>
    <script src="assets/js/plugins/bootstrap.min.js"></script>
    <script src="assets/js/pcoded.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function viewDPR(id) {
            const btn = document.querySelector(`button[data-id="${id}"]`);
            const user = btn.getAttribute('data-user');
            const date = btn.getAttribute('data-date');
            const day = btn.getAttribute('data-day');
            const time = btn.getAttribute('data-time');
            const summary = btn.getAttribute('data-summary');
            const screenshots = JSON.parse(btn.getAttribute('data-screenshots'));
            
            document.getElementById('modalUser').textContent = user;
            document.getElementById('modalDate').textContent = `${date} (${day})`;
            document.getElementById('modalTime').textContent = time;
            document.getElementById('modalSummary').textContent = summary;
            
            const screenshotsContainer = document.getElementById('modalScreenshots');
            document.getElementById('screenshotCount').textContent = screenshots.length;
            screenshotsContainer.innerHTML = '';
            
            if (screenshots.length > 0) {
                screenshots.forEach(screenshot => {
                    const thumb = document.createElement('div');
                    thumb.className = 'screenshot-thumb';
                    thumb.onclick = () => showFullImage(screenshot.screenshot_path);
                    thumb.innerHTML = `<img src="${screenshot.screenshot_path}" alt="Screenshot">`;
                    screenshotsContainer.appendChild(thumb);
                });
            } else {
                screenshotsContainer.innerHTML = '<p class="text-muted">No screenshots attached</p>';
            }
            
            new bootstrap.Modal(document.getElementById('viewDPRModal')).show();
        }
        
        function showFullImage(path) {
            document.getElementById('fullImage').src = path;
            new bootstrap.Modal(document.getElementById('fullImageModal')).show();
        }
        
        function clearFilters() {
            window.location.href = 'view-dpr.php';
        }
    </script>
</body>
</html>