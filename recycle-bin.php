<?php

session_start();

if (!isset($_SESSION["login_user"])) {
    header("location: index.php");
}
$name = $_SESSION['login_user'];

include("db/config.php");

$adminID = $_SESSION['login_user_id'];
$adminPermissionQuery = "SELECT nm.title FROM admin_permissions ap 
INNER JOIN navigation_menus nm ON ap.navigation_menu_id = nm.id WHERE ap.admin_id='" . $adminID . "'";
$adminPermissionResult = mysqli_query($db, $adminPermissionQuery);

while ($row = mysqli_fetch_row($adminPermissionResult)) {
    $userPermissions[] = $row[0];
}
$allowedAction = !in_array('All', $userPermissions) && in_array('Recycle Bin', $userPermissions);

$query = "SELECT * FROM recycle_bin;
";
$result = mysqli_query($db, $query);
$query = "SELECT * FROM login_settings";
    $settingsResult = mysqli_query($db, $query);
    $settings = mysqli_fetch_assoc($settingsResult);

    $logoPath = $settings['backend_panel_logo'];
    $helpdeskNumber = $settings['helpdesk_no'];
    $favicon = $settings['favicon'];
    // echo $favicon;
    // exit;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="content-type" content="text/html;charset=UTF-8" />
    <title>Recycle Bin</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="" />
    <meta name="keywords" content="">
    <meta name="author" content="Codedthemes" />
    <link rel="icon" href="<?php echo $favicon; ?>" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/plugins/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

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
                                <h5 class="m-b-10">Recycle Bin</h5>
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
                             <form id="deleteMForm" action="delete-permanent.php" method="post">
                         	<button type="button" id="deleteSelected" class="btn btn-danger">
        <i class='feather icon-trash'></i> Delete Selected
    </button>
                                <div class="d-flex align-items-center mb-2">
                                  


                                </div>
                                <div class="dt-responsive table-responsive">

                                    <?php
                                    // Status message
                                    if (isset($_GET['status'])) {
                                        $st = $_GET['status'];
                                        $st1 = base64_decode($st);

                                        if ($st1 > 0) {
                                            echo "<div class='alert alert-success alert-dismissible fade show' role='alert' style='font-size:16px;' id='updateuser'>
                                                    <strong><i class='feather icon-check'></i>Success!</strong> Leads has been Deleted Successfully.
                                                    <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                                                        <span aria-hidden='true'>&times;</span>
                                                    </button>
                                                </div>";
                                        } else {
                                            echo "<div class='alert alert-danger alert-dismissible fade show' role='alert' style='font-size:16px;' id='updateuser'>
                                                    <strong>Error!</strong> Recycle bin has not been Updated.
                                                    <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                                                        <span aria-hidden='true'>&times;</span>
                                                    </button>
                                                </div>";
                                        }
                                    }
                                    ?>

                                    <?php
                                    echo '<table id="basic-btn" class="table table-striped table-bordered nowrap">';
                                    echo "<thead>";
                                    echo "<tr>";
                                    echo "<th><input type='checkbox' id='selectAll' onclick='toggleCheckboxes(this)'> SELECT</th>";
                                    echo "<th>S.No.</th>";
                                    echo "<th>Name</th>";
                                    echo "<th>Email</th>";
                                    echo "<th>Mobile</th>";
                                    echo "<th>Course Name</th>";
                                    echo "<th>State</th>";
                                    echo "<th>City</th>";
                                    echo "<th>Remarks</th>";
                                    echo "<th>Lead Status</th>";
                                    echo "<th>Date</th>";
                                    echo "<th>Deleted At</th>";
                                    echo "<th>Actions</th>";
                                    echo "</tr></thead>";

                                    echo "<tbody>";
                                    $count = 1;
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        echo "<tr class='record'>";

                                        $encoded_id = base64_encode($row['recycle_id']); // Assuming recycle_id is the unique identifier
                                        echo "<td><input type='checkbox' class='menuCheckbox' name='recycle_ids[]' value='$encoded_id'></td>";
                                        echo "<td>" . $count++ . "</td>";
                                        echo "<td>" . $row['name'] . "</td>"; // Name
                                        echo "<td>" . $row['email'] . "</td>"; // Email
                                        echo "<td>" . $row['mobile'] . "</td>"; // Mobile
                                        echo "<td>" . $row['course_name'] . "</td>"; // Course Name
                                        echo "<td>" . $row['state'] . "</td>"; // state Name
                                        echo "<td>" . $row['city'] . "</td>"; // city Name
                                        echo "<td>" . $row['remarks'] . "</td>"; // city Name
                                        $status = strtolower($row['lead_status']); // Convert to lowercase for easier comparison
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


                                        echo "<td><span class='badge' style='font-size:14px; background-color: $badgeColor; color: $textColor;'>" . ucfirst($status) . "</span></td>";

                                          echo "<td>" . date("d-m-Y", strtotime( $row['date'] )). "</td>";
                                        echo "<td>" . $row['deleted_at'] . "</td>"; // Deleted At

                                        // Display action buttons only if the user has the required permissions
                                        echo "<td>";

                                        echo "<a href='javascript:void(0);'
   class='btn btn-success open-restore-modal'
   data-id='<?= $encoded_id ?>''>
   <i class='fas fa-undo'></i>
</a>

                                               <a href='javascript:void(0);' 
   class='btn btn-danger open-delete-modal'
   data-id='<?= $encoded_id ?>''>
   <i class='fas fa-trash-alt'></i>
</a>";

                                        echo "</td>";

                                        echo "</tr>";
                                    }
                                    echo "</tbody>";
                                    echo "</table>";
                                    ?>

                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Delete Selected Leads</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete the selected leads?
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
        Please select at least one user to delete.
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-warning" data-bs-dismiss="modal">OK</button>
      </div>
    </div>
  </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header  text-white">
        <h5 class="modal-title" id="deleteConfirmModalLabel">Confirm Deletion</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">X</button>
      </div>
      <div class="modal-body">
        Are you sure you want to permanently delete this lead?
      </div>
      <div class="modal-footer">
        <a href="#" id="confirmPermanentDelete" class="btn btn-danger">Yes, Delete</a>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      </div>
    </div>
  </div>
</div>
<!-- Restore Confirmation Modal -->
<div class="modal fade" id="restoreConfirmModal" tabindex="-1" aria-labelledby="restoreConfirmModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="restoreConfirmModalLabel">Confirm Restore</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">X</button>
      </div>
      <div class="modal-body">
        Are you sure you want to restore this lead?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <a href="#" id="confirmRestore" class="btn btn-success">Yes, Restore</a>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            $("#updateuser").delay(5000).slideUp(300);
        });
    </script>
    <script>
        function toggleCheckboxes(source) {
            const checkboxes = document.querySelectorAll('.menuCheckbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = source.checked;
            });
        }
    </script>
  <script>
        document.getElementById("deleteSelected").addEventListener("click", function() {
            var form = document.getElementById("deleteMForm");
            var checkboxes = form.querySelectorAll("input[name='recycle_ids[]']:checked");

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
  document.addEventListener("DOMContentLoaded", function () {
    var deleteButtons = document.querySelectorAll(".open-delete-modal");
    var confirmDeleteBtn = document.getElementById("confirmPermanentDelete");

    deleteButtons.forEach(function (btn) {
      btn.addEventListener("click", function () {
        var id = this.getAttribute("data-id");
        // Update modal confirm button link
        confirmDeleteBtn.setAttribute("href", "delete-permanent.php?id=" + encodeURIComponent(id));

        // Show the modal
        var deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
        deleteModal.show();
      });
    });
  });
</script>
<script>
  document.addEventListener("DOMContentLoaded", function () {
    const restoreButtons = document.querySelectorAll(".open-restore-modal");
    const confirmRestoreBtn = document.getElementById("confirmRestore");

    restoreButtons.forEach(function (btn) {
      btn.addEventListener("click", function () {
        const id = this.getAttribute("data-id");
        confirmRestoreBtn.setAttribute("href", "restore.php?id=" + encodeURIComponent(id));
        
        const restoreModal = new bootstrap.Modal(document.getElementById('restoreConfirmModal'));
        restoreModal.show();
      });
    });
  });
</script>

</body>

</html>