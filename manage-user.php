<?php
session_start();

// Redirect to login if user not logged in
if (!isset($_SESSION["login_user"])) {
    header("Location: index.php");
    exit();
}

include("db/config.php");

// Get current username from session
$username = $_SESSION['login_user'];

// 🔹 Fetch the user details with their role
$userQuery = "
    SELECT a.*, r.role_name 
    FROM admin AS a
    LEFT JOIN roles AS r ON a.admin_role = r.role_id
    WHERE a.username = '$username'
    LIMIT 1
";
$userResult = mysqli_query($db, $userQuery);
$user = mysqli_fetch_assoc($userResult);

// 🔹 Check role access (allow Admin or HR)
if (!$user || !in_array(strtolower($user['role_name']), ['admin', 'hr'])) {
    header("Location: unauthorized.php");
    exit();
}

// If admin, continue with page logic
$query = "
    SELECT 
        admin._id,
        admin.username,
        admin.email,
        admin.mobile,
        admin.status,
        admin.admin_role,
        roles.role_name
    FROM admin
    LEFT JOIN roles ON admin.admin_role = roles.role_id
";
$result = mysqli_query($db, $query);

// Fetch login settings
$settingsQuery = "SELECT * FROM login_settings";
$settingsResult = mysqli_query($db, $settingsQuery);
$settings = mysqli_fetch_assoc($settingsResult);

$logoPath = $settings['backend_panel_logo'];
$helpdeskNumber = $settings['helpdesk_no'];
$favicon = $settings['favicon'];
?>



<!DOCTYPE html>
<html lang="en">

<meta http-equiv="content-type" content="text/html;charset=UTF-8" />

<head>
    <title>All Admin Users</title>



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
                                <h5 class="m-b-10">All Admin Users
                                </h5>
                            </div>
                            <!-- 							<ul class="breadcrumb"> -->
                            <!--                                 <li class="breadcrumb-item"><a href="index.php"><i class="feather icon-home"></i></a> -->
                            <!--                                 </li> -->
                            <!--                             </ul> -->
                        </div>
                    </div>
                </div>
            </div>


            <div class="row">

                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header table-card-header">
                        </div>
                        <div class="card-body">
                            <form id="deleteMForm" action="delete-user.php" method="post">
                                <button type="button" id="deleteSelected" class="btn btn-danger">
                                    <i class='feather icon-trash'></i> Delete Selected
                                </button>
                                <div class="dt-responsive table-responsive mt-4">
                                    <?php

                                    if (isset($_SESSION['msg'])) {
                                        echo $_SESSION['msg'];
                                        unset($_SESSION['msg']); // remove after showing
                                    }
                                    ?>

                                    <?php

                                    if (isset($_GET['status'])) {
                                        $st = $_GET['status'];
                                        $st1 = base64_decode($st);

                                        if ($st1 > 0) {
                                            echo " <div class='alert alert-success alert-dismissible fade show' role='alert' style='font-size:16px;' id='updateuser'>
  <strong><i class='feather icon-check'></i>Success!</strong> User has been Updated Successfully.
  <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
    <span aria-hidden='true'>&times;</span>
  </button>
</div> ";
                                        } else {

                                            echo " <div class='alert alert-danger alert-dismissible fade show' role='alert' style='font-size:16px;' id='updateuser'>
  <strong>Error!</strong> User has been not Updated
  <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
    <span aria-hidden='true'>&times;</span>
  </button>
</div> ";
                                        }
                                    }

                                    ?>
                                    <br />


                                    <?php

                                    // Display the table
                                    echo '<table id="basic-btn" class="table table-striped table-bordered nowrap ">';
                                    echo "<thead>";
                                    echo "<tr>";
                                    echo "<th>SELECT</th>";
                                    echo "<th>SNO</th>";
                                    echo "<th>USERNAME</th>";
                                    echo "<th>EMAIL</th>";
                                    echo "<th>MOBILE NO</th>";
                                    echo "<th>STATUS</th>";
                                    echo "<th>ADMIN ROLE</th>";
                                    echo "<th>ACTIONS</th>";
                                    echo "</th>";
                                    echo "</thead>";


                                    ?>
                                    <?php



                                    $count = 1;
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        echo "<tr class='record'>";
                                        $encoded_id = base64_encode($row['_id']);
                                        echo "<td><input type='checkbox' name='admin_ids[]' value='$encoded_id'></td>";
                                        echo "<td>{$count}</td>";

                                        echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['mobile']) . "</td>";

                                        echo "<td>" . ($row['status'] == 1 ? "Enable" : "Disable") . "</td>";
                                        echo "<td>" . ($row['role_name']) . "</td>";

                                        $encoded_email = base64_encode($row['email']);
                                        echo "<td>
            <a href='user-edit.php?id={$encoded_email}'>
                <button type='button' class='btn btn-warning'>
                    <i class='feather icon-edit'></i> &nbsp;Edit
                </button>
            </a> &nbsp;
          <a href='#' 
        class='btn btn-danger delete-btn' 
        data-id='" .  $encoded_id . "' 
        data-toggle='modal' 
        data-target='#deleteModal'>
        <i class='feather icon-trash'></i> &nbsp;Delete
      </a>
          </td>";
                                        echo "</tr>";

                                        $count++;
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
    <!-- Delete Confirmation Modal -->
    <!-- Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Delete</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete User?
                    <input type="hidden" id="deleteId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
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
    <!-- Multi Delete Confirmation Modal -->
    <div class="modal fade" id="deleteSelectedModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal">X</button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete the selected users?
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
    <!--<script src="assets/js/menu-setting.min.js"></script>-->

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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            $("#updateuser").delay(5000).slideUp(300);
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // When trash icon is clicked
            document.querySelectorAll('.delete-btn').forEach(function(button) {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    console.log(id);

                    document.getElementById('deleteId').value = id;
                });
            });

            // When Delete button in modal is clicked
            document.getElementById('confirmDelete').addEventListener('click', function() {
                const id = document.getElementById('deleteId').value;
                if (id) {
                    window.location.href = 'delete-user.php?id=' + encodeURIComponent(id);
                } else {
                    alert('No ID found to delete.');
                }
            });
        });
    </script>

    <script>
        document.getElementById("deleteSelected").addEventListener("click", function() {
            var form = document.getElementById("deleteMForm");
            var checkboxes = form.querySelectorAll("input[name='admin_ids[]']:checked");

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
</body>

</html>