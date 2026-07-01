<?php

session_start();


if (!isset($_SESSION["login_user"])) {
    header("location: index.php");
}
$name = $_SESSION['login_user'];

include("db/config.php");

// Fetch ID card records with employee details
$query = "
    SELECT 
        i.id_card_id,
        i.employee_id,
        i.template_front,
        i.template_back,
        i.status,
        p.name AS employee_name,
        p.photo AS employee_photo,
        d.name AS designation_name
    FROM id_cards i
    LEFT JOIN personal_details p ON i.employee_id = p.personal_id
    LEFT JOIN company_details c ON p.personal_id = c.user_id
    LEFT JOIN position d ON c.designation = d.position_id
    ORDER BY i.id_card_id DESC
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
    <title>All ID Cards</title>



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
                                <h5 class="m-b-10">All ID Cards
                                </h5>
                            </div>

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

                            <form id="deleteMForm" action="delete-idcard.php" method="post">
                                <button type="button" id="deleteSelected" class="btn btn-danger">
                                    <i class='feather icon-trash'></i> Delete Selected
                                </button>
                                <div class="dt-responsive table-responsive mt-4">
                                    <?php
                                    if (isset($_SESSION['success'])) {
                                        echo "<div class='alert alert-success alert-dismissible fade show' role='alert'>
            <strong>Success!</strong> {$_SESSION['success']}
            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                <span aria-hidden='true'>&times;</span>
            </button>
          </div>";
                                        unset($_SESSION['success']);
                                    }

                                    if (isset($_SESSION['error'])) {
                                        echo "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
            <strong>Error!</strong> {$_SESSION['error']}
            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                <span aria-hidden='true'>&times;</span>
            </button>
          </div>";
                                        unset($_SESSION['error']);
                                    }
                                    ?>

                                    <?php

                                    if (isset($_GET['status'])) {
                                        $st = $_GET['status'];
                                        $st1 = base64_decode($st);

                                        if ($st1 > 0) {
                                            echo " <div class='alert alert-success alert-dismissible fade show' role='alert' style='font-size:16px;' id='gold'>
  <strong><i class='feather icon-check'></i>Success!</strong> Id Card has been Updated Successfully.
  <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
    <span aria-hidden='true'>&times;</span>
  </button>
</div> ";
                                        } else {

                                            echo " <div class='alert alert-danger alert-dismissible fade show' role='alert' style='font-size:16px;' id='gold'>
  <strong>Error!</strong> Id Card has been not Updated
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
                                    echo "<th><input type='checkbox' id='selectAll'> SELECT</th>";
                                    echo "<th>SNO</th>";
                                    echo "<th>EMPLOYEE NAME</th>";
                                    echo "<th>DESIGNATION</th>";
                                    echo "<th>TEMPLATE IMAGE</th>";
                                    echo "<th>EMPLOYEE IMAGE</th>";

                                    echo "<th>STATUS</th>";

                                    echo "<th>EDIT</th>";


                                    echo "</tr>";
                                    echo "</thead>";


                                    ?>
                                    <?php
                                    $sno = 1;

                                   if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $encoded_id = base64_encode($row['id_card_id']); // Replace with correct ID column

        // Fallback if images are missing
        $templateImg = !empty($row['template_front']) && file_exists($row['template_front'])
            ? $row['template_front']
            : 'assets/images/template/idcard/default_front.png';

        $templateImgBack = !empty($row['template_back']) && file_exists($row['template_back'])
            ? $row['template_back']
            : 'assets/images/template/idcard/default_back.png';

        $empPhoto = !empty($row['employee_photo']) && file_exists($row['employee_photo'])
            ? $row['employee_photo']
            : 'assets/images/default-user.png';

        $statusBadge = $row['status'] == 1
            ? '<span class="badge bg-success">Enabled</span>'
            : '<span class="badge bg-danger">Disabled</span>';

        echo "<tr>";
        echo "<td><input class='selectItem' type='checkbox' name='cards_ids[]' value='" . base64_encode($row['id_card_id']) . "'></td>";

        echo "<td>" . $sno++ . "</td>";
        echo "<td>" . htmlspecialchars($row['employee_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['designation_name'] ?? 'N/A') . "</td>";

        // 🟢 Show Front and Back templates side by side
        echo "<td style='display:flex; align-items:center; gap:8px;'>
                <div>
                    <img src='" . $templateImg . "' width='70' height='70' style='object-fit:cover; border-radius:6px; border:1px solid #ddd;'>
                    <div style='font-size:12px; text-align:center; margin-top:3px;'>Front</div>
                </div>
                <div>
                    <img src='" . $templateImgBack . "' width='70' height='70' style='object-fit:cover; border-radius:6px; border:1px solid #ddd;'>
                    <div style='font-size:12px; text-align:center; margin-top:3px;'>Back</div>
                </div>
              </td>";

        echo "<td><img src='" . $empPhoto . "' width='80' height='80' style='object-fit:cover; border-radius:6px; border:1px solid #ddd;'></td>";
        echo "<td>" . $statusBadge . "</td>";

        echo "<td>
                <a href='edit-idcard.php?id=$encoded_id' class='btn btn-warning'>
                    <i class='feather icon-edit'></i>
                </a>
                <a href='generate-id-card.php?id=$encoded_id' class='btn btn-success' target='_blank'>
                    <i class='feather icon-users'></i> ID Card
                </a>
                <a href='javascript:void(0)' class='btn btn-danger delete-btn' data-id='$encoded_id' data-bs-toggle='modal' data-bs-target='#deleteModal'>
                    <i class='feather icon-trash'></i>
                </a>
              </td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='8' class='text-center text-muted'>No ID Card Records Found</td></tr>";
}

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
                    Are you sure you want to delete the Id Card?
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
                    Please select at least one id card to delete.
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
                    Are you sure you want to delete the selected Id Card?
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
                window.location.href = 'delete-idcard.php?id=' + encodeURIComponent(id);
            } else {
                alert('No ID found to delete.');
            }
        });
    </script>

    <script>
        document.getElementById("deleteSelected").addEventListener("click", function() {
            var form = document.getElementById("deleteMForm");
            var checkboxes = form.querySelectorAll("input[name='cards_ids[]']:checked");

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
        // ✅ Select All / Deselect All feature
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.selectItem');
            checkboxes.forEach(cb => cb.checked = this.checked);
        });
    </script>
</body>

</html>