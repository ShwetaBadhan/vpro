<?php
// ✅ Set timezone FIRST - before anything else
date_default_timezone_set('Asia/Kolkata');
ini_set('date.timezone', 'Asia/Kolkata');

session_start();

if (!isset($_SESSION["login_user"])) {
    header("location: index.php");
    exit();
}
$name = $_SESSION['login_user'];

// ✅ Include database config
include("db/config.php");

// ✅ Set MySQL timezone to match PHP
mysqli_query($db, "SET time_zone = '+05:30'");

// ✅ Now run queries
$query = "SELECT 
    id, 
    username, 
    login_time, 
    logout_time, 
    user_ip, 
    browser, 
    os, 
    device_type, 
    country,
    status,
    last_seen,
    CASE 
        WHEN status = 'logged_out' AND logout_time IS NOT NULL THEN 
            TIMESTAMPDIFF(SECOND, login_time, logout_time)
        WHEN status = 'active' THEN 
            TIMESTAMPDIFF(SECOND, login_time, NOW())
        ELSE 0 
    END AS duration_seconds
FROM user_logs 
ORDER BY id DESC";

$result = mysqli_query($db, $query);

if (!$result) {
    die("Query failed: " . mysqli_error($db));
}

$query = "SELECT * FROM login_settings";
$settingsResult = mysqli_query($db, $query);
$settings = mysqli_fetch_assoc($settingsResult);

$logoPath = $settings['backend_panel_logo'];
$helpdeskNumber = $settings['helpdesk_no'];
$favicon = $settings['favicon'];
?>

<!DOCTYPE html>
<html lang="en">

<meta http-equiv="content-type" content="text/html;charset=UTF-8" />

<head>
    <title>User Logs</title>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="" />
    <meta name="keywords" content="">
    <meta name="author" content="" />

    <link rel="icon" href="<?php echo $favicon; ?>" type="image/x-icon">

    <link rel="stylesheet" href="assets/css/plugins/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="assets/css/style.css">

    <!-- Custom Styles for Better UI -->
    <style>
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
        }

        .status-logged-out {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(245, 87, 108, 0.3);
        }

        .duration-cell {
            font-family: 'Courier New', monospace;
            font-weight: 600;
            color: #2d3748;
            background: #f7fafc;
            padding: 8px 12px;
            border-radius: 6px;
            display: inline-block;
        }

        .logout-time {
            color: #718096;
            font-style: italic;
        }

        .active-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            background: #48bb78;
            border-radius: 50%;
            margin-right: 5px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }
        }

        .table thead th {
            background: #f8f9fa;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
            color: #4a5568;
        }

        .table tbody tr:hover {
            background-color: #f7fafc;
        }

        .device-icon {
            margin-right: 5px;
        }
    </style>
</head>

<body class="">

    <div class="loader-bg">
        <div class="loader-track">
            <div class="loader-fill"></div>
        </div>
    </div>

    <!-- Header -->
    <?php include("header.php"); ?>
    <!-- /Header -->

    <!-- navbar -->
    <?php include("navbar.php"); ?>
    <!-- /navbar -->

    <section class="pcoded-main-container">
        <div class="pcoded-content">

            <div class="page-header">
                <div class="page-block">
                    <div class="row align-items-center">
                        <div class="col-md-12">
                            <div class="page-header-title">
                                <h5 class="m-b-10">User Logs</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header table-card-header">
                            <h5>Login Activity Tracker</h5>
                        </div>
                        <div class="card-body">

                            <form id="deleteMForm" action="delete-logs.php" method="post">
                                <button type="button" id="deleteSelected" class="btn btn-danger">
                                    <i class='feather icon-trash'></i> Delete Selected
                                </button>
                                <div class="dt-responsive table-responsive mt-4">
                                    <?php
                                    if (isset($_GET['status'])) {
                                        $st = $_GET['status'];
                                        $st1 = base64_decode($st);

                                        if ($st1 > 0) {
                                            echo " <div class='alert alert-success alert-dismissible fade show' role='alert' style='font-size:16px;' id='gold'>
                                                <strong><i class='feather icon-check'></i> Success!</strong> Log(s) has been Deleted Successfully.
                                                <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                                                    <span aria-hidden='true'>&times;</span>
                                                </button>
                                            </div> ";
                                        } else {
                                            echo " <div class='alert alert-danger alert-dismissible fade show' role='alert' style='font-size:16px;' id='gold'>
                                                <strong>Error!</strong> Log(s) has been not Deleted.
                                                <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                                                    <span aria-hidden='true'>&times;</span>
                                                </button>
                                            </div> ";
                                        }
                                    }
                                    ?>
                                    <br />

                                    <?php
                                    echo '<table id="basic-btn" class="table table-striped table-bordered nowrap">';
                                    echo "<thead>";
                                    echo "<tr>";
                                    echo "<th><input type='checkbox' id='selectAll'></th>";
                                    echo "<th>SNO</th>";
                                    echo "<th>USERNAME</th>";
                                    echo "<th>LOGIN TIME</th>";
                                    echo "<th>LOGOUT TIME</th>";
                                    echo "<th>DURATION</th>";
                                    echo "<th>STATUS</th>";
                                    echo "<th>SYSTEM IP</th>";
                                    echo "<th>BROWSER</th>";
                                    echo "<th>PLATFORM</th>";
                                    echo "<th>DEVICE TYPE</th>";
                                    echo "<th>COUNTRY</th>";
                                    echo "<th>ACTION</th>";
                                    echo "</tr>";
                                    echo "</thead>";
                                    ?>
                                    <?php
                                    $count = 1;
                                    echo "<tbody>";
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        echo "<tr class='record'>";

                                        $encoded_id = base64_encode($row['id']);

                                        // ✅ Get duration safely
                                        $totalSeconds = 0;
                                        if (isset($row['duration_seconds'])) {
                                            $totalSeconds = intval($row['duration_seconds']);
                                        }

                                        // Handle negative values
                                        if ($totalSeconds < 0) {
                                            $totalSeconds = 0;
                                        }

                                        // Calculate hours, minutes, seconds
                                        $hours = floor($totalSeconds / 3600);
                                        $minutes = floor(($totalSeconds % 3600) / 60);
                                        $seconds = $totalSeconds % 60;

                                        // Show duration
                                        if ($totalSeconds > 0) {
                                            $duration = sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
                                        } else {
                                            $duration = "00:00:00";
                                        }

                                        // ✅ Status badge - check actual status
                                        $statusBadge = '';
                                        if ($row['status'] == 'active') {
                                            $statusBadge = "<span class='badge bg-success'>Active</span>";
                                        } else {
                                            $statusBadge = "<span class='badge bg-secondary'>Logged Out</span>";
                                        }

                                        // ✅ Logout time
                                        $logoutTime = '';
                                        if (!empty($row['logout_time']) && $row['logout_time'] != '0000-00-00 00:00:00') {
                                            $logoutTime = htmlspecialchars($row['logout_time']);
                                        } else {
                                            $logoutTime = "<em class='text-muted'>Still Active</em>";
                                        }

                                        // Device icon
                                        $deviceType = htmlspecialchars($row['device_type'] ?? 'Desktop');
                                        $deviceIcon = '';
                                        if ($deviceType == 'Desktop') {
                                            $deviceIcon = "<i class='feather icon-monitor'></i>";
                                        } elseif ($deviceType == 'Mobile') {
                                            $deviceIcon = "<i class='feather icon-smartphone'></i>";
                                        } else {
                                            $deviceIcon = "<i class='feather icon-tablet'></i>";
                                        }

                                        // Output row
                                        echo "<td><input type='checkbox' name='log_ids[]' value='$encoded_id' class='log-checkbox'></td>";
                                        echo "<td>$count</td>";
                                        echo "<td><strong>" . htmlspecialchars($row['username']) . "</strong></td>";
                                        echo "<td>" . htmlspecialchars($row['login_time']) . "</td>";
                                        echo "<td>$logoutTime</td>";
                                        echo "<td><span class='duration-cell'>$duration</span></td>";
                                        echo "<td>$statusBadge</td>";
                                        echo "<td>" . htmlspecialchars($row['user_ip']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['browser'] ?? 'Unknown') . "</td>";
                                        echo "<td>" . htmlspecialchars($row['os'] ?? 'Unknown') . "</td>";
                                        echo "<td>$deviceIcon " . htmlspecialchars($deviceType) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['country'] ?? 'Unknown') . "</td>";
                                        echo "<td>
                                            <a href='javascript:void(0)' class='btn btn-danger btn-sm delete-btn'
                                            data-id='$encoded_id'
                                            data-bs-toggle='modal'
                                            data-bs-target='#deleteModal'>
                                            <i class='feather icon-trash'></i>
                                            </a>
                                        </td>";

                                        echo "</tr>";
                                        $count++;
                                    }
                                    echo "</tbody>";
                                    echo "</table>";
                                    ?>
                                </div>
                                <br />
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- Delete Single Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Delete Log</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this log entry?
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
                    Please select at least one log to delete.
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
                    Are you sure you want to delete the selected logs?
                </div>
                <div class="modal-footer">
                    <button type="button" id="confirmMultiDelete" class="btn btn-danger">Delete</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/vendor-all.min.js"></script>
    <script src="assets/js/plugins/bootstrap.min.js"></script>
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
    <script src="assets/js/pages/data-export-custom.js"></script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            // Auto-hide success/error alerts
            $("#gold").delay(5000).slideUp(300);

            // Select all checkbox functionality
            $('#selectAll').on('change', function() {
                $('.log-checkbox').prop('checked', this.checked);
            });
        });
    </script>

    <script>
        // Single delete functionality
        document.querySelectorAll('.delete-btn').forEach(function(button) {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                document.getElementById('deleteId').value = id;
            });
        });

        document.getElementById('confirmDelete').addEventListener('click', function() {
            const id = document.getElementById('deleteId').value;
            if (id) {
                window.location.href = 'delete-logs.php?id=' + encodeURIComponent(id);
            } else {
                alert('No ID found to delete.');
            }
        });
    </script>

    <script>
        // Multiple delete functionality
        document.getElementById("deleteSelected").addEventListener("click", function() {
            var form = document.getElementById("deleteMForm");
            var checkboxes = form.querySelectorAll("input[name='log_ids[]']:checked");

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

    <!-- Session Heartbeat - Keeps session alive -->
    <script>
        setInterval(function() {
            fetch('session_heartbeat.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=heartbeat'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'expired') {
                        alert('Your session has expired. Please login again.');
                        window.location.href = 'index.php';
                    }
                })
                .catch(err => console.log('Heartbeat error:', err));
        }, 240000); // 4 minutes
    </script>

</body>

</html>