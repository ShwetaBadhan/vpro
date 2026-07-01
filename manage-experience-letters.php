<?php

session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


if (!isset($_SESSION["login_user"])) {
    header("location: index.php");
}
$name = $_SESSION['login_user'];

include("db/config.php");

$query = "SELECT 
el.experience_id,
el.employee_id,
el.designation,
el.status,
el.date_to,
el.date_from,
position.name AS position_name,
personal_details.name AS emp_name
FROM experience_letter as el
LEFT JOIN position on position.position_id = el.designation
LEFT JOIN personal_details on personal_details.personal_id = el.employee_id;

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

<meta http-equiv="content-type" content="text/html;charset=UTF-8" />

<head>
    <title>All Experience Letters</title>



    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="" />
    <meta name="keywords" content="">
    <meta name="author" content="" />

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
                                <h5 class="m-b-10">All Experience Letters
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


                            <form id="deleteMForm" action="delete-experience-letter.php" method="post">
                                <button type="button" id="deleteSelected" class="btn btn-danger">
                                    <i class='feather icon-trash'></i> Delete Selected
                                </button>
                                <div class="dt-responsive table-responsive mt-4">
                                    <?php
                                    if (isset($_SESSION['msg'])) {
                                        echo $_SESSION['msg'];
                                        unset($_SESSION['msg']);
                                    }
                                    if (isset($_GET['status'])) {
                                        $st = $_GET['status'];
                                        $st1 = base64_decode($st);

                                        if ($st1 > 0) {
                                            echo " <div class='alert alert-success alert-dismissible fade show' role='alert' style='font-size:16px;' id='gold'>
  <strong><i class='feather icon-check'></i>Success!</strong> Experience Letters has been Updated Successfully.
  <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
    <span aria-hidden='true'>&times;</span>
  </button>
</div> ";
                                        } else {

                                            echo " <div class='alert alert-danger alert-dismissible fade show' role='alert' style='font-size:16px;' id='gold'>
  <strong>Error!</strong> Experience Letters has been not Updated
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
                                    echo "<th>SELECT</th>";

                                    echo "<th>SNO</th>";
                                    echo "<th>EMPLOYEE NAME</th>";
                                    echo "<th>DESIGNATION</th>";
                                    echo "<th>DATE FROM</th>";
                                    echo "<th>DATE TO</th>";

                                    echo "<th>STATUS</th>";

                                    echo "<th>EDIT</th>";


                                    echo "</tr>";
                                    echo "</thead>";


                                    ?>
                                    <?php
                                    $count = 1;
                                    echo "<tbody>";
                                    while ($row = mysqli_fetch_assoc($result)) { // Use `mysqli_fetch_assoc` for associative array
                                        echo "<tr class='record'>";

                                        $encoded_id = base64_encode($row['experience_id']); // Replace with the correct ID column from your query

                                        echo "<td><input type='checkbox' name='experience_ids[]' value='$encoded_id'></td>";

                                        echo "<td> $count </td>";
                                        echo "<td>" . htmlspecialchars($row['emp_name']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['position_name']) . "</td>";
                                        echo "<td>" . date("d F, Y", strtotime($row['date_from'])) . "</td>";

                                        echo "<td>" .  date("d F, Y", strtotime($row['date_to'])) . "</td>";




                                        echo "<td>";
                                        if ($row['status'] == 1) {
                                            echo "Enable";
                                        } else {
                                            echo "Disable";
                                        }
                                        echo "</td>";

                                        echo "<td>
        <a href='edit-experience-letter.php?id=$encoded_id' class='btn btn-warning'>
            <i class='feather icon-edit'></i>
        </a>
        <a href='generate-experience-letter.php?id=$encoded_id' class='btn btn-success' target='_blank'>
            <i class='feather icon-users'></i>Experience Letters
        </a>
       <a href='javascript:void(0)' class='btn btn-danger delete-btn' data-id='$encoded_id' data-bs-toggle='modal' data-bs-target='#deleteModal'>
        <i class='feather icon-trash'></i>
    </a>
   
    </td>";

                                        echo "</tr>";
                                        $count++;
                                    }
                                    echo "</tbody>";
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
                    Are you sure you want to delete the Experience Letter?
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
                    Please select at least one Experience Letter to delete.
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
                    Are you sure you want to delete the selected Experience Letters?
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
    <!-- Bootstrap JS Bundle (with Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            $("#gold").delay(5000).slideUp(300);
        });
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
                window.location.href = 'delete-experience-letter.php?id=' + encodeURIComponent(id);
            } else {
                alert('No ID found to delete.');
            }
        });
    </script>

    <script>
        document.getElementById("deleteSelected").addEventListener("click", function() {
            var form = document.getElementById("deleteMForm");
            var checkboxes = form.querySelectorAll("input[name='experience_ids[]']:checked");

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