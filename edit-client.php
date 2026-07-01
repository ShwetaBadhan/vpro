<?php
session_start();
include('db/config.php');
$msg = "";

if (isset($_GET['id'])) {
    $encodedClientId = $_GET['id'];
    $ClientId = intval(base64_decode($encodedClientId)); // Safely decode and cast to int

    // Fetch client data
    $query = "SELECT * FROM clients WHERE client_id = $ClientId";
    $result = mysqli_query($db, $query);

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $clientName = $row['name'];
        $phone = $row['phone'];
        $email = $row['email'];
        $address = $row['address'];
        $state = $row['state'];
        $city = $row['city'];
        $services = $row['services']; // comma-separated string
        $active_date = $row['active_date'];
        $deactive_date = $row['deactive_date'];
        $categoryStatus = $row['status'];
        $renewal = $row['renewal_date'];
        $remarks = $row['remarks'];
        $primary_person = $row['client_primary_person'];


        // Update process
        if (isset($_POST['submit'])) {
            // echo '<pre>';
            // print_r($_POST);
            // exit();
            $updatedClientName = mysqli_real_escape_string($db, $_POST['name']);
            $updatedPhone = mysqli_real_escape_string($db, $_POST['phone']);
            $updatedEmail = mysqli_real_escape_string($db, $_POST['email']);
            $updatedAddress = mysqli_real_escape_string($db, $_POST['address']);
            $updatedState = intval($_POST['state']);
            $updatedCity = intval($_POST['city']);
            $updatedStatus = mysqli_real_escape_string($db, $_POST['status']);
            $updatedServices = isset($_POST['services']) ? implode(',', $_POST['services']) : '';
            $updatedRenewal = mysqli_real_escape_string($db, $_POST['renewal_date']);
            $updatedRemarks = mysqli_real_escape_string($db, $_POST['remarks']);
            $updatedPrimary =mysqli_real_escape_string($db, $_POST['client_primary_person']);
            // Handle dates: allow empty/null values, otherwise sanitize
            $updatedActiveDate = !empty($_POST['active_date']) ? "'" . mysqli_real_escape_string($db, $_POST['active_date']) . "'" : "NULL";
            $updatedDeactiveDate = !empty($_POST['deactive_date']) ? "'" . mysqli_real_escape_string($db, $_POST['deactive_date']) . "'" : "NULL";

            $updateQuery = "UPDATE clients SET 
        name = '$updatedClientName',
        phone = '$updatedPhone',
        email = '$updatedEmail',
        address = '$updatedAddress',
        state = $updatedState,
        city = $updatedCity,
        services = '$updatedServices',
        active_date = $updatedActiveDate,
        deactive_date = $updatedDeactiveDate,
        status = '$updatedStatus',
        renewal_date = '$updatedRenewal',
        remarks = '$updatedRemarks',
        client_primary_person = '$updatedPrimary'
        WHERE client_id = $ClientId";
            //  echo '<pre>';
            //             print_r($updateQuery);
            //             exit();
            if ($db->query($updateQuery) === TRUE) {
                $status = base64_encode(1);
                echo ("<script>window.location.href='manage-client.php?status=$status';</script>");
            } else {
                echo "Error updating client: " . $db->error;
            }
        }
    } else {
        echo "Client not found!";
        exit;
    }
} else {
    echo "Invalid request!";
    exit;
}

if (isset($_POST['state_id'])) {
    $state_id = mysqli_real_escape_string($db, $_POST['state_id']);

    $query = "SELECT * FROM city WHERE state_id = '$state_id' AND status='1'";
    $result = mysqli_query($db, $query);

    echo "<option value='' selected disabled>Choose</option>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<option value='{$row['city_id']}'>{$row['city_name']}</option>";
    }

    exit(); // stop further HTML output
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
    <title>Update Client</title>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="" />
    <meta name="keywords" content="">
    <meta name="author" content="" />

    <link rel="icon" href="<?php echo $favicon; ?>" type="image/x-icon">

    <link rel="stylesheet" href="assets/css/plugins/dataTables.bootstrap4.min.css">

    <link rel="stylesheet" href="assets/css/style.css">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet">

    <Style>
        .select2-container--default .select2-selection--multiple .select2-selection__rendered li {
            list-style: none;
            color: #003399;
            ;
            background: #fff;
        }

        .red-text {
            color: red;
        }
    </Style>
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
                                <h5 class="m-b-10">Update Client
                                </h5>
                            </div>
                            <!--                             <ul class="breadcrumb"> -->
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
                            <?php
                            if ($msg) {
                                echo $msg;
                            }
                            ?>
                            <br />
                            <form method="post" action="" enctype="multipart/form-data" autocomplete="off">
                                <div class=" ">
                                    <!-- Text input-->
                                    <div class="row">
                                        <div class="col-xl-6 col-lg-6 col-md-4 col-sm-12 col-12">
                                            <div class="form-group"> Client Name <span class="red-text">*</span>
                                                <label class="sr-only control-label" for="name">Client Name<span class=" ">
                                                    </span></label>
                                                <input name="name" type="text" id="name" value="<?php echo $clientName; ?>"
                                                    class="form-control input-md">
                                            </div>
                                        </div>
                                        <div  class="col-xl-6 col-lg-6 col-md-4 col-sm-12 col-12">
                                            <div class="form-group">Primary Person<span class="red-text">*</span>
                                                <label class="sr-only control-label" for="client_primary_person">Primary Person<span class=" ">
                                                    </span></label>
                                                <input name="client_primary_person" type="text" id="client_primary_person" value="<?php echo $primary_person; ?>"
                                                    class="form-control input-md">
                                            </div>
                                            </div>
                                        <div class="col-xl-6 col-lg-6 col-md-4 col-sm-12 col-12">
                                            <div class="form-group">Enter Phone No.<span class="red-text">*</span>
                                                <label class="sr-only control-label" for="name">Enter Phone No.<span class=" ">
                                                    </span></label>
                                                <input name="phone" type="text" id="name" value="<?php echo $phone; ?>"
                                                    class="form-control input-md">
                                            </div>
                                        </div>
                                        <div class="col-xl-6 col-lg-6 col-md-4 col-sm-12 col-12">
                                            <div class="form-group">Enter Email<span class="red-text">*</span>
                                                <label class="sr-only control-label" for="name">Enter Email<span class=" ">
                                                    </span></label>
                                                <input name="email" type="text" id="name" value="<?php echo $email; ?>"
                                                    class="form-control input-md">
                                            </div>
                                        </div>

                                        <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
                                            <div class="form-group">
                                                <label for="service" class="form-label">Service Name</label>
                                                <?php
                                                // Agar $services mein comma separated string hai, toh ise array mein convert karen with trimming
                                                $selectedServices = [];

                                                if (isset($services) && !empty($services)) {
                                                    // Split by comma and trim each value to remove spaces
                                                    $selectedServices = array_map('trim', explode(',', $services));
                                                }

                                                $category_query = "SELECT * FROM service WHERE status='1'";
                                                $result = $db->query($category_query);

                                                if ($result->num_rows > 0) {
                                                    echo "<select id='multiSelect' name='services[]' multiple='multiple' required class='form-control'>";
                                                    echo "<option value='' disabled>Choose</option>";

                                                    while ($row = $result->fetch_assoc()) {
                                                        // Trim service name here too just to be safe
                                                        $serviceName = trim($row['name']);
                                                        $isSelected = in_array($serviceName, $selectedServices) ? "selected" : "";
                                                        echo "<option value='{$serviceName}' $isSelected>{$serviceName}</option>";
                                                    }

                                                    echo "</select>";
                                                } else {
                                                    echo "No Services found.";
                                                }
                                                ?>

                                            </div>
                                        </div>


                                        <div class="col-xl-6 col-lg-6 col-md-4 col-sm-12 col-12">
                                            <label for="state" class="form-label">State</label>
                                            <select name="state" class="form-control select" id="state">
                                                <option value="" disabled>Choose</option>
                                                <?php
                                                $state_query = "SELECT * FROM state WHERE status='1'";
                                                $result = $db->query($state_query);
                                                if ($result->num_rows > 0) {
                                                    while ($row = $result->fetch_assoc()) {
                                                        $selected = ($row['state_id'] == $state) ? 'selected' : '';
                                                        echo "<option value='{$row['state_id']}' $selected>{$row['state_name']}</option>";
                                                    }
                                                }
                                                ?>
                                            </select>


                                        </div>

                                        <div class="col-xl-6 col-lg-6 col-md-4 col-sm-12 col-12">
                                            <label for="city" class="form-label">City</label>
                                            <select name="city" class="form-control select" id="city">
                                                <option value="" selected disabled>Select State First</option>
                                            </select>

                                        </div>
                                        <div class="col-xl-6 col-lg-6 col-md-4 col-sm-12 col-12 mt-4">
                                            <label for="active_date">Active Date</label>
                                            <input type="date" name="active_date" id="active_date" class="form-control"
                                                value="<?php echo isset($active_date) ? htmlspecialchars($active_date) : ''; ?>">
                                        </div>
                                        <div class="col-xl-6 col-lg-6 col-md-4 col-sm-12 col-12 mt-4">
                                            <label for="deactive_date">Deactive Date</label>
                                            <input type="date" name="deactive_date" id="deactive_date" class="form-control"
                                                value="<?php echo isset($deactive_date) ? htmlspecialchars($deactive_date) : ''; ?>">
                                        </div>






                                        <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12 mt-3">
                                            <div class="form-group">
                                                <label for="name" class="form-label">Status <span class="red-text">*</span></label>
                                                <select id="" name="status" class="form-control">
                                                    <option value="" selected disabled>Choose</option>
                                                    <option value="1" <?php echo ($categoryStatus == 1) ? 'selected' : ''; ?>>Enable</option>
                                                    <option value="0" <?php echo ($categoryStatus != 1) ? 'selected' : ''; ?>>Disable</option>
                                                    <option value="Hold" <?php echo ($categoryStatus == 'Hold' ) ? 'selected' : ''; ?>>Hold</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-xl-6 col-lg-6 col-md-4 col-sm-12 col-12 mt-3">
                                            
                                                <label class="" for="name">Renewal Date<span class=" ">
                                                   </label>
                                                <input type="date" class="form-control" name="renewal_date" value="<?php echo $renewal; ?>">
                                           
                                        </div>
                                        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
                                            <div class="form-group">
                                                <label for="address" class="form-label">Address <span class="red-text">*</span></label>
                                                <textarea name="address" id="address" class="form-control" rows="5"><?php echo $address; ?></textarea>
                                            </div>
                                        </div>

                                        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 mt-3">
                                            <div class="form-group">
                                                <label for="remarks" class="form-label">Remarks (If client is hold)</label>
                                                <textarea name="remarks" id="remarks" class="form-control" rows="5"><?php echo $remarks; ?></textarea>
                                            </div>
                                        </div>

                                        <!-- Button -->
                                        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">

                                            <button type="submit" class="btn btn-secondary" name="submit" id="submit">
                                                <i class="feather icon-save lg"></i>&nbsp; Update Client
                                            </button>


                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="card-body">
                            <div class="dt-responsive table-responsive">


                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </section>





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
    <script>
        $(document).ready(function() {
            $('#multiSelect').select2({
                width: '100%',
                closeOnSelect: false,
                templateSelection: function(data, container) {
                    // Add a cross icon to selected items    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet">

                    var $option = $(data.element);
                    var text = $option.text();
                    container.text(text);
                    container.append('<span class="remove-item" data-value="' + data.id + '">&times;</span>');
                }
            });

            $('#multiSelect').on('click', '.remove-item', function(e) {
                e.stopPropagation(); // Prevent Select2 dropdown from opening
                var valueToRemove = $(this).data('value');
                var $select = $('#multiSelect');

                // Deselect the option
                var selectedValues = $select.val() || [];
                selectedValues = selectedValues.filter(function(value) {
                    return value !== valueToRemove;
                });

                $select.val(selectedValues).trigger('change');
            });

        });
    </script>
    <script>
        $(document).ready(function() {
            $("#goldmessage").delay(5000).slideUp(300);
        });
    </script>
    <script>
        $(document).ready(function() {
            var stateId = "<?php echo $state; ?>";
            var selectedCityId = "<?php echo $city; ?>";

            if (stateId) {
                $.ajax({
                    type: 'POST',
                    url: '',
                    data: {
                        state_id: stateId
                    },
                    success: function(response) {
                        $('#city').html(response); // fill city dropdown
                        $('#city').val(selectedCityId); // select the correct city
                    }
                });
            }
        });
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const selectedCityId = "<?php echo $city; ?>"; // your existing city ID

            // Only trigger if there's a selected state
            if (document.getElementById('state').value !== "") {
                // Trigger change event to load cities via AJAX
                const stateSelect = document.getElementById('state');
                const event = new Event('change');
                stateSelect.dispatchEvent(event);

                // Wait for AJAX to populate cities, then select the correct city
                setTimeout(function() {
                    const citySelect = document.getElementById('city');
                    for (let i = 0; i < citySelect.options.length; i++) {
                        if (citySelect.options[i].value == selectedCityId) {
                            citySelect.options[i].selected = true;
                            break;
                        }
                    }
                }, 500); // Adjust this delay if your AJAX is slower/faster
            }
        });
    </script>

</body>

</html>