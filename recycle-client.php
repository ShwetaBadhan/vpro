<?php
session_start();
if (!isset($_SESSION["login_user"])) {
    header("location: index.php");
}

include('db/config.php');
$query = "SELECT  
    clients.client_id,
    clients.name, 
    clients.phone, 
    clients.email, 
    clients.address, 
    clients.services, 
    clients.active_date, 
    clients.deactive_date, 
    clients.status,
    state.state_name,
    city.city_name
FROM clients
LEFT JOIN state ON state.state_id = clients.state
LEFT JOIN city ON city.city_id = clients.city
WHERE is_deleted = 1;

";

$result = mysqli_query($db, $query);

if (!$result) {
    die("Query failed: " . mysqli_error($db));
}
if (isset($_POST['recycle']) && isset($_POST['client_id'])) {
    $encoded_id = $_POST['client_id'];
    $client_id = base64_decode($encoded_id);

    $query = "UPDATE clients SET status = 1,  is_deleted = 0 WHERE client_id = $client_id";
    if (mysqli_query($db, $query)) {
        header("Location: recycle-client.php?status=" . base64_encode("restored"));
        exit;
    } else {
        echo "Error: " . mysqli_error($db);
    }
} else {
    echo "Invalid request.";
}


// delwete permanent
if (isset($_POST['delete_permanent']) && isset($_POST['client_id'])) {
    $encoded_id = $_POST['client_id'];
    $client_id = base64_decode($encoded_id);

    $query = "DELETE FROM clients WHERE client_id = $client_id";
    if (mysqli_query($db, $query)) {
        header("Location: recycle-client.php?status=" . base64_encode("deleted"));
        exit;
    } else {
        echo "Error deleting client: " . mysqli_error($db);
    }
} else {
    echo "Invalid request.";
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
    <title>All Clients</title>
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
                                <h5 class="m-b-10">All Clients
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
                        <!-- <button type="button" id="deleteSelected" class="btn btn-danger mb-2">Delete Selected</button> -->
                        <div class="dt-responsive table-responsive">

                            <?php

                            if (isset($_GET['status'])) {
                                $st = $_GET['status'];
                                $st1 = base64_decode($st);

                                if ($st1 > 0) {
                                    echo " <div class='alert alert-success alert-dismissible fade show' role='alert' style='font-size:16px;' id='goldmessage'>
  <strong><i class='feather icon-check'></i>Success!</strong> Client has been deleted successfully.
  <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
    <span aria-hidden='true'>&times;</span>
  </button>
</div> ";
                                } else {

                                    echo " <div class='alert alert-danger alert-dismissible fade show' role='alert' style='font-size:16px;' id='goldmessage'>
  <strong>Error!</strong> Client has been not Updated
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
                            echo "<th> NAME</th>";
                            echo "<th> PHONE</th>";
                            echo "<th> EMAIL</th>";
                            echo "<th> SERVICES</th>";
                            echo "<th> STATE</th>";
                            echo "<th> CITY</th>";
                            echo "<th> ACTIVE DATE</th>";
                            echo "<th> DEACTIVE DATE</th>";
                            echo "<th> ADDRESS</th>";

                            echo "<th>STATUS</th>";
                            echo "<th>EDIT</th>";
                            echo "</tr>";
                            echo "</thead>";

                            $count = 1;
                            echo "<tbody>";
                            while ($row = mysqli_fetch_assoc($result)) {
                                echo "<tr class='record'>";
                                $encoded_id = base64_encode($row['client_id']); // Encode the category ID

                                echo "<td><input type='checkbox' name='category_ids[]' value='$encoded_id'></td>";
                                echo "<td>$count</td>";
                                echo "<td>" . $row['name'] . "</td>";
                                echo "<td>" . $row['phone'] . "</td>";
                                echo "<td>" . $row['email'] . "</td>";
                                echo "<td>" . $row['services'] . "</td>";
                                echo "<td>" . $row['state_name'] . "</td>";
                                echo "<td>" . $row['city_name'] . "</td>";
                                echo "<td>" . $row['active_date'] . "</td>";
                                echo "<td>" . $row['deactive_date'] . "</td>";
                                echo "<td>" . $row['address'] . "</td>";
                                echo "<td>" . ($row['status'] == 1 ? "Enable" : "Disable") . "</td>";
                                echo "<td>
                                        <a href='javascript:void(0)' class='btn btn-success recycle-btn' 
  data-id='<?= $encoded_id ?>' 
   data-bs-toggle='modal'
   data-bs-target='#recycleModal'>
   <i class='feather icon-rotate-ccw'></i> Recycle
</a>

                                     <a href='javascript:void(0)' class='btn btn-danger delete-btn'
   data-id='<?= $encoded_id ?>' 
   data-bs-toggle='modal'
   data-bs-target='#deleteModal'>
   <i class='feather icon-trash'></i> Delete
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
    <!-- Recycle Confirmation Modal -->
    <div class="modal fade" id="recycleModal" tabindex="-1" aria-labelledby="recycleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="recycle-client.php">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirm Restore</h5>
                         <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    </div>
                    <div class="modal-body">
                        Are you sure you want to restore this client?
                    </div>
                    <div class="modal-footer">
                        <input type="hidden" name="client_id" id="recycleClientId">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="recycle" class="btn btn-success">Restore</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" action="recycle-client.php">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Confirm Permanent Deletion</h5>
           <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
        </div>
        <div class="modal-body">
          Are you sure you want to permanently delete this client? This action cannot be undone.
        </div>
        <div class="modal-footer">
          <input type="hidden" name="client_id" id="deleteClientId">
          <button type="submit" name="delete_permanent" class="btn btn-danger">Delete</button>

          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </div>
    </form>
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
        document.querySelectorAll('.recycle-btn').forEach(button => {
            button.addEventListener('click', function() {
                const clientId = this.getAttribute('data-id');
                document.getElementById('recycleClientId').value = clientId;
            });
        });
    </script>

<script>
  document.querySelectorAll('.delete-btn').forEach(button => {
    button.addEventListener('click', function () {
      const clientId = this.getAttribute('data-id');
      document.getElementById('deleteClientId').value = clientId;
    });
  });
</script>

</body>

</html>