<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Redirect if not logged in
if (!isset($_SESSION["login_user"])) {
    header("Location: index.php");
    exit();
}

include('db/config.php');

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

// 🔹 Now allow only admin to execute the rest of the page
$query = "
  SELECT  
    pd.name,  
    pd.dob,
    pd.personal_id,  
    cd.employement_type,  
    cd.employee_status,  
    cd.doj,
    p.name AS position_name
FROM personal_details pd
LEFT JOIN company_details cd
    ON cd.user_id = pd.personal_id
LEFT JOIN position p
    ON p.position_id = cd.designation
WHERE 
    cd.employement_type IS NULL
    OR cd.employement_type != 'trainee';

";
$result = mysqli_query($db, $query);

if (!$result) {
    die("Query failed: " . mysqli_error($db));
}

// 🔹 Fetch settings
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
    <title>All Employees</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="" />
    <meta name="keywords" content="">
    <meta name="author" content="" />

    <link rel="icon" href="<?php echo $favicon; ?>" type="image/x-icon">

    <link rel="stylesheet" href="assets/css/plugins/dataTables.bootstrap4.min.css">

    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.tiny.cloud/1/l0jt1pl0jxgk8lnq5hkx6x384hqvgjse7l8c3mnanxhhzju3/tinymce/6/tinymce.min.js"
        referrerpolicy="origin"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <!-- Bootstrap CSS -->


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
                                <h5 class="m-b-10">All Employees
                            </div>

                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-body add-product pb-0">
                    <form id="deleteForm" action="delete-employees.php" method="post">
                        <button type="button" id="deleteSelected" class="btn btn-danger">
                            <i class='feather icon-trash'></i> Delete Selected
                        </button>
                        <button type="submit" id="submitDeleteForm" style="display: none;"></button>
                        <div class="dt-responsive table-responsive">

                            <?php

                            if (isset($_GET['status'])) {
                                $st = $_GET['status'];
                                $st1 = base64_decode($st);

                                if ($st1 > 0) {
                                    echo " <div class='alert alert-success alert-dismissible fade show' role='alert' style='font-size:16px;' id='goldmessage'>
                                    <strong><i class='feather icon-check'></i>Success!</strong> Employee(s) has been deleted successfully.
                                    <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                                        <span aria-hidden='true'>&times;</span>
                                    </button>
                                    </div> ";
                                } else {

                                    echo " <div class='alert alert-danger alert-dismissible fade show' role='alert' style='font-size:16px;' id='goldmessage'>
                                <strong>Error!</strong> Employee(s) has been not Updated
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
                            echo "<th>Select</th>";
                            echo "<th>SNO</th>";
                            echo "<th>NAME</th>";
                            echo "<th>DESIGNATION</th>";

                            echo "<th>EMPLOYEE STATUS</th>";

                            echo "<th>EDIT</th>";
                            echo "</tr>";
                            echo "</thead>";

                            $count = 1;
                            echo "<tbody>";
                            while ($row = mysqli_fetch_assoc($result)) {
                                echo "<tr class='record'>";
                                $encoded_id = base64_encode($row['personal_id']); // Encode the category ID

                                echo "<td><input type='checkbox' class='row-checkbox' name='personal_ids[]' value='$encoded_id'></td>";
                                echo "<td>$count</td>";
                                echo "<td>" . $row['name'] . "</td>";
                                echo "<td>" . $row['position_name'] . "</td>";

                                echo "<td>" . ($row['employee_status'] == 1 ? "Enable" : "Disable") . "</td>";

                                echo "<td>
                                <a href='javascript:void(0)' class='btn btn-info viewLead' data-id='$encoded_id'>
    <i class='feather icon-eye'></i> 
    </a>
                                        <a href='edit-employee.php?id=$encoded_id' class='btn btn-warning'>
                                            <i class='feather icon-edit'></i> 
                                        </a>
                                     <a href='javascript:void(0)' class='btn btn-danger delete-btn'
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
    </section>
    <!-- Modal -->
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="delete-employees.php">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Delete</h5>
                        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        Are you sure you want to delete this Employee Details?
                    </div>
                    <div class="modal-footer">
                        <input type="hidden" name="employee_id" id="deleteEmployeeId">

                        <button type="submit" class="btn btn-danger">Delete</button>

                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>

                    </div>
                </div>
            </form>
        </div>
    </div>
    <!-- Modal -->
    <div class="modal fade" id="viewLeadModal" tabindex="-1" aria-labelledby="viewLeadModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl"> <!-- Changed from default to modal-lg -->
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewLeadModalLabel">Employee Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">X</button>
                </div>
                <div class="modal-body" id="leadDetails">
                    Loading details...
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
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
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
    <!-- Multi Delete Confirmation Modal -->
    <div class="modal fade" id="deleteSelectedModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Deletion</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete the selected employees?
                </div>
                <div class="modal-footer">
                    <button type="button" id="confirmMultiDelete" class="btn btn-danger">Delete</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>


    <!-- Edit Lead Status Modal -->
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <!-- Bootstrap JS (Popper included) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(".multiple-select").select2({
            //   maximumSelectionLength: 2
        });
    </script>
    <script>
        $(document).ready(function() {
            $("#goldmessage").delay(5000).slideUp(300);
        });
    </script>




    <script>
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                const clientId = this.getAttribute('data-id');
                console.log(clientId);

                document.getElementById('deleteEmployeeId').value = clientId;
            });
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
                url: 'fetch-employee-details.php', // Backend script
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
        // When "Delete Selected" is clicked
        document.getElementById("deleteSelected").addEventListener("click", function() {
            var form = document.getElementById("deleteForm");
            var checkboxes = form.querySelectorAll("input[type=checkbox]:checked");

            if (checkboxes.length === 0) {
                var alertModal = new bootstrap.Modal(document.getElementById('alertModal'));
                alertModal.show();
            } else {
                var deleteSelectedModal = new bootstrap.Modal(document.getElementById('deleteSelectedModal'));
                deleteSelectedModal.show();
            }
        });

        document.getElementById("confirmMultiDelete").addEventListener("click", function() {
            document.getElementById("deleteForm").submit(); // ✅ Direct form submission
        });
    </script>

    ✅
</body>

</html>