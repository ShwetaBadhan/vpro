<?php
session_start();
if (!isset($_SESSION["login_user"])) {
    header("location: index.php");
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


$query = "SELECT  
    clients.client_id,
    clients.name, 
    clients.phone, 
    clients.email, 
    clients.address, 
    clients.services, 
    clients.client_primary_person, 
    clients.created_at, 
    clients.status,

    state.state_name,
    city.city_name
FROM clients
LEFT JOIN state ON state.state_id = clients.state
LEFT JOIN city ON city.city_id = clients.city
WHERE is_deleted = 0;

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
    <link href="https://cdn.datatables.net/2.3.2/css/dataTables.dataTables.min.css" rel="stylesheet">

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
                    <form id="deleteMForm" action="delete-client.php" method="post">
                        <input type="hidden" name="delete" value="1"> <!-- this tells PHP it's a delete request -->

                        <button type="button" id="deleteSelected" class="btn btn-danger">
                            <i class='feather icon-trash'></i> Delete Selected
                        </button>

                        <div class="dt-responsive table-responsive mt-4">

                            <?php
                            if (isset($_GET['status'])) {
                                $st = $_GET['status'];
                                $st1 = base64_decode($st);

                                // Detect if this came from delete or edit
                                $action = isset($_GET['action']) && $_GET['action'] === 'delete' ? 'deleted' : 'updated';

                                if ($st1 > 0) {
                                    echo "
        <div class='alert alert-success alert-dismissible fade show' role='alert' style='font-size:16px;' id='goldmessage'>
            <strong><i class='feather icon-check'></i>Success!</strong> Client has been {$action} successfully.
            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                <span aria-hidden='true'>&times;</span>
            </button>
        </div>";
                                } else {
                                    echo "
        <div class='alert alert-danger alert-dismissible fade show' role='alert' style='font-size:16px;' id='goldmessage'>
            <strong>Error!</strong> Client has not been {$action}.
            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                <span aria-hidden='true'>&times;</span>
            </button>
        </div>";
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
                            echo "<th> PRIMARY PERSON</th>";
                            echo "<th> PHONE</th>";
                            echo "<th> EMAIL</th>";
                            echo "<th> SERVICES</th>";
                            echo "<th> STATE</th>";
                            echo "<th> CITY</th>";

                            // echo "<th> ADDRESS</th>";


                            echo "<th>STATUS</th>";
                            echo "<th>EDIT</th>";
                            echo "</tr>";
                            echo "</thead>";

                            $count = 1;
                            echo "<tbody>";
                            while ($row = mysqli_fetch_assoc($result)) {
                                echo "<tr class='record'>";
                                $encoded_id = base64_encode($row['client_id']); // Encode the category ID

                                echo "<td><input type='checkbox' name='client_ids[]' value='$encoded_id'></td>";
                                echo "<td>$count</td>";
                                echo "<td>" . $row['name'] . "</td>";
                                echo "<td>" . $row['client_primary_person'] . "</td>";
                                echo "<td>" . $row['phone'] . "</td>";
                                echo "<td>" . $row['email'] . "</td>";

                                $wrappedServices = wordwrap($row['services'], 40, '<br>', true);

                                echo "<td>{$wrappedServices}</td>";
                                echo "<td>" . $row['state_name'] . "</td>";
                                echo "<td>" . $row['city_name'] . "</td>";
                                //  $address = $row['address'];
                                // $shortAddress = strlen($address) > 180 ? substr($remarks, 0,180) . '..' : $address;
                                // echo "<td>{$shortAddress}</td>";



                                echo "<td>" . ($row['status'] == 1 ? "Enable" : ($row['status'] == 0 ? "Disable" : "Hold")) . "</td>";

                                echo "<td>
                                <a href='javascript:void(0)' class='btn btn-info viewLead' data-id='$encoded_id'>
    <i class='feather icon-eye'></i> 
    </a>
                                        <a href='edit-client.php?id=$encoded_id' class='btn btn-warning'>
                                            <i class='feather icon-edit'></i> 
                                        </a>

<a href='javascript:void(0)' class='btn btn-danger delete-btn'
        data-id='" . base64_encode($row['client_id']) . "'
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
    <!-- Delete Client Modal -->
    <!-- <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="delete-client.php" id="deleteForm">
                <input type="hidden" name="client_id" id="deleteClientId" value="<?php echo $row['client_id']; ?>">
                <input type="hidden" name="delete" value="1">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Delete</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">X</button>
                    </div>
                    <div class="modal-body">
                        Are you sure you want to delete this client?
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-danger">Delete</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </div>
            </form>
        </div>
    </div> -->
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
        Are you sure you want to delete the client?
        <input type="hidden" id="deleteId">
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      </div>

    </div>
  </div>
</div>

    <!-- Modal -->
    <div class="modal fade" id="viewLeadModal" tabindex="-1" aria-labelledby="viewLeadModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewLeadModalLabel">Client Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">X</button>
                </div>
                <div class="modal-body" id="leadDetails">
                    Loading details...
                </div>
            </div>
        </div>
    </div>
    <!-- Edit Lead Status Modal -->
    <!-- Alert Modal -->
    <div class="modal fade" id="alertModal" tabindex="-1" aria-labelledby="alertModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="alertModalLabel">No Selection</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">X</button>
                </div>
                <div class="modal-body">
                    Please select at least one client to delete.
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
                    Are you sure you want to delete the selected clients?
                </div>
                <div class="modal-footer">
                    <button type="button" id="confirmMultiDelete" class="btn btn-danger">Delete</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>


    <!-- jQuery -->

    <!-- jQuery -->
    <!--<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>-->
    <script src="assets/js/vendor-all.min.js"></script>
    <script src="assets/js/plugins/bootstrap.min.js"></script>
    <script src="assets/js/pcoded.min.js"></script>
    <script src="https://cdn.datatables.net/2.3.2/js/dataTables.min.js"></script>
    <script>
        let table = new DataTable('#myTable');
    </script>

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
    <!-- <script>
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                const clientId = this.getAttribute('data-id');
                const encodedId = btoa(clientId); // base64 encode
                document.getElementById('deleteClientId').value = encodedId;
                new bootstrap.Modal(document.getElementById('deleteModal')).show();
                console.log(encodedId);
            });
        });
    </script> -->





    <script>
        $(document).ready(function() {
            $("#goldmessage").delay(5000).slideUp(300);
        });
    </script>


<script>
      // Trash button click → set hidden input value
document.querySelectorAll('.delete-btn').forEach(function(button) {
  button.addEventListener('click', function() {
    const clientId = this.getAttribute('data-id');
    document.getElementById('deleteId').value = clientId;
    console.log("Selected ID:", clientId); // Debugging
  });
});

// Confirm delete → redirect with GET param
document.getElementById('confirmDelete').addEventListener('click', function() {
  const id = document.getElementById('deleteId').value;
  if (id) {
    console.log("Deleting ID:", id); // Debugging
    window.location.href = 'delete-client.php?id=' + encodeURIComponent(id);
  } else {
    alert('No ID found to delete.');
  }
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
                url: 'fetch-client-details.php', // Backend script
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
        document.getElementById("deleteSelected").addEventListener("click", function() {
            var form = document.getElementById("deleteMForm");
            var checkboxes = form.querySelectorAll("input[name='client_ids[]']:checked");

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