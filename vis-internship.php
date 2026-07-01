<?php
session_start();
if (!isset($_SESSION["login_user"]))
{
    header("location: index.php");
}

include('db/config1.php');
$query = "SELECT * FROM internship ORDER BY internship_id DESC";

$result = mysqli_query($db_training, $query);

if (!$result) {
    die("Query failed: " . mysqli_error($db));
}
?>
<!DOCTYPE html>
<html lang="en">

<meta http-equiv="content-type" content="text/html;charset=UTF-8" />

<head>
    <title>Internship</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="" />
    <meta name="keywords" content="">
    <meta name="author" content="" />

    <link rel="icon" href="assets/images/favicon.ico" type="image/x-icon">

    <link rel="stylesheet" href="assets/css/plugins/dataTables.bootstrap4.min.css">

    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.tiny.cloud/1/l0jt1pl0jxgk8lnq5hkx6x384hqvgjse7l8c3mnanxhhzju3/tinymce/6/tinymce.min.js"
        referrerpolicy="origin"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />


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
                                <h5 class="m-b-10">Internship
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
                       <form id="deleteForm" action="delete-contact-leads.php" method="post">
                         <button type="button" id="deleteSelected" class="btn btn-danger mb-2">Delete Selected</button>
                        <div class="dt-responsive table-responsive">
                            
                                      <?php

                                if (isset($_GET['status'])) {
                                    $st = $_GET['status'];
                                    $st1 = base64_decode($st);

                                    if ($st1 > 0) {
                                        echo " <div class='alert alert-success alert-dismissible fade show' role='alert' style='font-size:16px;' id='goldmessage'>
  <strong><i class='feather icon-check'></i>Success!</strong> Records has been Deleted Successfully.
  <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
    <span aria-hidden='true'>&times;</span>
  </button>
</div> ";
                                    } else {

                                        echo " <div class='alert alert-danger alert-dismissible fade show' role='alert' style='font-size:16px;' id='goldmessage'>
  <strong>Error!</strong> Records has been not Updated
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
                                 echo "<th><input  type='checkbox' id='selectAll'> SELECT</th>";;
                         
                            echo "<th>SNO</th>";
                            echo "<th>NAME</th>";
                            echo "<th>EMAIL ID</th>";
                            echo "<th>MOBILE NUMBER</th>";
                            echo "<th>STATE</th>";
                            echo "<th>COURSES</th>";
                           
                         
                            echo "</tr>";
                            echo "</thead>";

                            $count = 1;
                            echo "<tbody>";
                            while ($row = mysqli_fetch_row($result)) {
                                echo "<tr class='record'>";
                                
                                $encoded_id = base64_encode($row[0]); // Encode the category ID
                                
                                echo "<td><input  class='selectItem' type='checkbox' name='advt_ids[]' value='$encoded_id'></td>";
                                
                                echo "<td>$count</td>";
                                echo "<td>" . $row['1'] . "</td>";
                                echo "<td>" . $row['2'] . "</td>";
                                echo "<td>" . $row['3'] . "</td>";
                                echo "<td>" . $row['4'] . "</td>";
                                echo "<td>" . $row['5'] . "</td>";
                             
                                
                                // echo "<td>";
                                // if ($row['5'] == 1) {
                                //     echo "Enable";
                                // } else {
                                //     echo "Disable";
                                // }
                                echo "</td>";
                                        
                               

                                $count++;
                            }

                            echo "</tbody>";
                            echo "</table>";
                            ?>
                        </div>
                       <br/>
                     </form>
                    </div>
               </div>
        </div>
    </section>
      <!-- Alert Modal -->
    <div class="modal fade" id="alertModal" tabindex="-1" aria-labelledby="alertModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="alertModalLabel">No Selection</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">X</button>
                </div>
                <div class="modal-body">
                    Please select at least a record to delete.
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
                    Are you sure you want to delete the selected records?
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <!-- Bootstrap JS Bundle (with Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>$(".multiple-select").select2({
            //   maximumSelectionLength: 2
        });</script>
    <script>
        $(document).ready(function () {
            $("#goldmessage").delay(5000).slideUp(300);
        });
    </script>

  

     <script>
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
            document.getElementById("deleteForm").submit();
        });
    </script>
    
</body>
 <script>
        // ✅ Select All / Deselect All feature
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.selectItem');
            checkboxes.forEach(cb => cb.checked = this.checked);
        });
    </script>
</html>