<?php

session_start();


if (!isset($_SESSION["login_user"])) {
    header("location: index.php");
}
$name = $_SESSION['login_user'];

include("db/config.php");

$query = "SELECT 
            position.position_id,
            position.name AS position_name,
            duties.status,
            duties.duty_id,
            GROUP_CONCAT(duties.duty_name SEPARATOR ', ') AS all_duties
          FROM duties
          LEFT JOIN position ON duties.position_id = position.position_id
          GROUP BY duties.position_id, duties.status";

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
    <title>All Responsibilities</title>



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
                                <h5 class="m-b-10">All Responsibilities
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

                            <form id="" action="" method="post">
                                <!-- <button type="button" id="deleteSelected" class="btn btn-danger mb-2">Delete
                                    Selected</button> -->

                                <div class="dt-responsive table-responsive">
                                    <?php

                                if (isset($_GET['status'])) {
                                    $st = $_GET['status'];
                                    $st1 = base64_decode($st);

                                    if ($st1 > 0) {
                                        echo " <div class='alert alert-success alert-dismissible fade show' role='alert' style='font-size:16px;' id='gold'>
  <strong><i class='feather icon-check'></i>Success!</strong> Position has been Updated Successfully.
  <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
    <span aria-hidden='true'>&times;</span>
  </button>
</div> ";
                                    } else {

                                        echo " <div class='alert alert-danger alert-dismissible fade show' role='alert' style='font-size:16px;' id='gold'>
  <strong>Error!</strong> Positionhas been not Updated
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
                                echo "<th> POSITION NAME</th>";
                                 echo "<th> DUTIES</th>";
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
    
    $encoded_id = base64_encode($row['position_id']); // Replace with the correct ID column from your query
    
    echo "<td><input type='checkbox' name='duty_ids[]' value='$encoded_id'></td>";
    
    echo "<td> $count </td>";
    echo "<td>" . htmlspecialchars($row['position_name']) . "</td>";
$wrappedDuties = wordwrap($row['all_duties'], 40, '<br>', true);

    echo "<td>{$wrappedDuties}</td>";
  
    
    echo "<td>";
    if ($row['status'] == 1) {
        echo "Enable";
    } else {
        echo "Disable";
    }
    echo "</td>";
    
    echo "<td>
        <a href='edit-duty.php?id=$encoded_id' class='btn btn-warning'>
            <i class='feather icon-edit'></i>
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
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Delete Selected Duties</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    Are you sure you want to delete the duties?
                    <input type="hidden" id="deleteId">
                </div>

                <div class="modal-footer">
                                        <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
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


    <script type="text/javascript">
    $(function() {
        $(".delbutton").click(function() {

            var element = $(this);

            var del_id = element.attr("id");

            var info = 'id=' + del_id;
            if (confirm("Are you sure you want to delete this Record?")) {
                $.ajax({
                    type: "GET",
                    url: "deletegold.php",
                    data: info,
                    success: function() {}
                });
                $(this).parents(".record").animate({
                        backgroundColor: "#FF3"
                    }, "fast")
                    .animate({
                        opacity: "hide"
                    }, "slow");
            }
            return false;
        });
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
            window.location.href = 'delete-duties.php?id=' + encodeURIComponent(id);
        } else {
            alert('No ID found to delete.');
        }
    });
    </script>



</body>

</html>