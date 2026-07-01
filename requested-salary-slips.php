<?php
session_start();
if (!isset($_SESSION["login_user"])) {
    header("location: index.php");
}

include('db/config.php');
$query = "SELECT 
    sr.request_id,
    sr.month_from,
    sr.month_to,
    sr.employee_id,
    sr.status,
    ps.personal_id,
    ps.name AS emp_name
FROM salary_slip_requests AS sr
LEFT JOIN personal_details AS ps ON ps.personal_id = sr.employee_id

        ;
";

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
    // echo $favicon;
    // exit;
?>
<!DOCTYPE html>
<html lang="en">

<meta http-equiv="content-type" content="text/html;charset=UTF-8" />

<head>
    <title>Requested Salary Slip</title>
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
    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"> -->

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
                                <h5 class="m-b-10">Requested Salary Slip
                            </div>
                            <!--                             <ul class="breadcrumb"> -->
                            <!--                                 <li class="breadcrumb-item"><a href="index.php"><i class="feather icon-home"></i></a> -->
                            <!--                                 </li> -->
                            <!--                             </ul> -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-body add-product pb-0">
                    <form id="deleteForm" action="delete-category.php" method="post">
                      
                        <div class="dt-responsive table-responsive">

                            <?php

                            if (isset($_GET['status'])) {
                                $st = $_GET['status'];
                                $st1 = base64_decode($st);

                                if ($st1 > 0) {
                                    echo " <div class='alert alert-success alert-dismissible fade show' role='alert' style='font-size:16px;' id='goldmessage'>
  <strong><i class='feather icon-check'></i>Success!</strong> Service has been deleted successfully.
  <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
    <span aria-hidden='true'>&times;</span>
  </button>
</div> ";
                                } else {

                                    echo " <div class='alert alert-danger alert-dismissible fade show' role='alert' style='font-size:16px;' id='goldmessage'>
  <strong>Error!</strong> Service has been not Updated
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
                            echo "<th>Employee Name</th>";

                            echo "<th>Slip Month</th>";
                            echo "<th>status</th>";
                            echo "<th>Action</th>";
                            echo "</tr>";
                            echo "</thead>";

                            $count = 1;
                            echo "<tbody>";
                            $count = 1;
                            while ($row = mysqli_fetch_assoc($result)) {
                                echo "<tr class='record'>";

                                $encoded_id = base64_encode($row['request_id']); // Encode the request ID
                                echo "<td><input type='checkbox' name='request_id[]' value='$encoded_id'></td>";

                                echo "<td>$count</td>";
                                echo "<td>" . htmlspecialchars($row['emp_name']) . "</td>";
                                if (!function_exists('formatMonth')) {
                                    function formatMonth($dateStr)
                                    {
                                        if (empty($dateStr)) return '';

                                        // Try Y-m-d
                                        $date = DateTime::createFromFormat('Y-m-d', $dateStr);
                                        if ($date && $date->format('Y-m-d') === $dateStr) {
                                            return $date->format('F Y');
                                        }

                                        // Try Y-m (for YYYY-MM input)
                                        $date = DateTime::createFromFormat('Y-m', $dateStr);
                                        if ($date && $date->format('Y-m') === $dateStr) {
                                            return $date->format('F Y');
                                        }

                                        return '';
                                    }
                                }

                                $from = formatMonth($row['month_from'] ?? '');
                                $to = formatMonth($row['month_to'] ?? '');

                                // Output safely
                                if ($from && $to) {
                                    echo "<td>$from - $to</td>";
                                } elseif ($from) {
                                    echo "<td>$from</td>";
                                } elseif ($to) {
                                    echo "<td>$to</td>";
                                } else {
                                    echo "<td>-</td>";
                                }
                                // Show status text based on value
                                switch ($row['status']) {
                                    case 'approved':
                                        echo "<td>Approved</td>";
                                        break;
                                    case 'rejected':
                                        echo "<td>Rejected</td>";
                                        break;
                                    case 'pending':
                                        echo "<td>Pending</td>";
                                        break;
                                    default:
                                        echo "<td>Unknown</td>";
                                }


                                echo "<td>
    <a href='add-salary-slip.php?emp_id={$row['employee_id']}&request_id={$row['request_id']}' class='btn btn-primary'>
    Generate Slip
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
                    <input type="hidden" id="deleteId">
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
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
        tinymce.init({
            selector: 'textarea',
            plugins: 'ai tinycomments mentions anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount checklist mediaembed casechange export formatpainter pageembed permanentpen footnotes advtemplate advtable advcode editimage tableofcontents mergetags powerpaste tinymcespellchecker autocorrect a11ychecker typography inlinecss',
            toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table mergetags | align lineheight | tinycomments | checklist numlist bullist indent outdent | emoticons charmap | removeformat',
            tinycomments_mode: 'embedded',
            tinycomments_author: 'Author name',
            mergetags_list: [{
                    value: 'First.Name',
                    title: 'First Name'
                },
                {
                    value: 'Email',
                    title: 'Email'
                },
            ],
            ai_request: (request, respondWith) => respondWith.string(() => Promise.reject(
                "See docs to implement AI Assistant"))
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
                window.location.href = 'delete-salary-slip.php?id=' + encodeURIComponent(id);
            } else {
                alert('No ID found to delete.');
            }
        });
    </script>
</body>

</html>