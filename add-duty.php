<?php
session_start();
error_reporting(E_ALL);

if (!isset($_SESSION["login_user"])) {
    header("location: index.php");
}

include("db/config.php");

// Initialize variables
$msg = "";


// Handle form submission
if (isset($_POST['submit'])) {
    //     echo '<pre>';
    // print_r($_POST);
    // exit();
    $PositionID =  $_POST['position_id'];
    $status = mysqli_real_escape_string($db, $_POST['status']);

    // Check if responsibilities are set
    if (!empty($_POST['responsibilities'])) {
        $all_inserted = true;

       foreach ($_POST['responsibilities'] as $duty) {
    $duty_clean = mysqli_real_escape_string($db, $duty);
    $query = "INSERT INTO `duties` (position_id, status, duty_name) 
              VALUES ('$PositionID', '$status', '$duty_clean')";

    

    if (!mysqli_query($db, $query)) {
        die("Insert failed: " . mysqli_error($db)); // Show error
    }
}


        if ($all_inserted) {
            $msg = "
            <div class='alert alert-success alert-dismissible fade show' role='alert' style='font-size:16px;' id='goldmessage'>
            <strong><i class='feather icon-check'></i> Thanks!</strong> Duties added successfully.
            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
              <span aria-hidden='true'>&times;</span>
            </button>
            </div>";
        } else {
            $msg = "
            <div class='alert alert-danger alert-dismissible fade show' role='alert' style='font-size:16px;' id='goldmessage'>
            <strong><i class='feather icon-check'></i> Error!</strong> Failed to add some or all duties.
            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
              <span aria-hidden='true'>&times;</span>
            </button>
            </div>";
        }
    }
}



// Fetch position from the database
$positionquery = "SELECT position_id, name FROM position WHERE status = 1"; // Assuming your table is `city_type`
$positionresult = mysqli_query($db, $positionquery);
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

<head>
    <title> Add Responsibility</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="icon" href="<?php echo $favicon; ?>" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/plugins/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .red-text {
            color: red;
        }
    </style>
</head>

<body>
    <div class="loader-bg">
        <div class="loader-track">
            <div class="loader-fill"></div>
        </div>
    </div>

    <!-- Header -->
    <?php include("header.php"); ?>
    <!-- /Header -->

    <!-- Navbar -->
    <?php include("navbar.php"); ?>
    <!-- /Navbar -->

    <section class="pcoded-main-container">
        <div class="pcoded-content">
            <div class="page-header">
                <div class="page-block">
                    <div class="row align-items-center">
                        <div class="col-md-12">
                            <div class="page-header-title">
                                <h5 class="m-b-10"> Add Responsibility</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header table-card-header">
                            <?php if ($msg) echo $msg; ?>
                            <br />

                                <form method="post" action="" enctype="multipart/form-data" autocomplete="off">
                                    <div class="row">
                                        <!-- Dynamic Select for Course Types -->
                                            <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
                                                <div class="form-group">
                                                    <label for="city_type_id" class="form-label">Select Designation<span class="red-text">*</span></label>
                                                    <select class="form-control" id="position_id" name="position_id" required>
                                                        <option value="" selected disabled>Choose a Designation</option>
                                                        <?php
                                                        if (mysqli_num_rows($positionresult) > 0) {
                                                            while ($row = mysqli_fetch_assoc($positionresult)) {
                                                                echo "<option value='" . $row['position_id'] . "'>" . $row['name'] . "</option>";
                                                            }
                                                        } else {
                                                            echo "<option value='' disabled>No city types found</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
                                    
    <!-- Responsibilities Column -->
    <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
        <div class="form-group">
            <label class="form-label">Responsibilities <span class="red-text">*</span></label>
            <div id="responsibilities-container">
                <!-- Dynamic responsibility inputs will appear here -->
            </div>
            <button type="button" class="btn btn-sm btn-primary mt-2" onclick="addResponsibility()">+ Add Responsibility</button>
        </div>
    </div>


                                        
                                        <!-- Status -->
                                    <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
        <div class="form-group">
            <label for="status" class="form-label">Status <span class="red-text">*</span></label>
            <select name="status" class="form-control" required>
                <option value="" disabled>Choose</option>
                <option value="1" selected>Enable</option> <!-- Default selected -->
                <option value="0">Disable</option>
            </select>
        </div>
    </div>


                                        <!-- Submit Button -->
                                        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
                                            <button type="submit" class="btn btn-secondary" name="submit">
                                                <i class="feather icon-save"></i>&nbsp;  Add Responsibility
                                            </button>
                                        </div>
                                    </div>
                                </form>
                        </div>
                        <div class="card-body">
                            <div class="dt-responsive table-responsive"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

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
    <script>
        $(document).ready(function() {
            $("#goldmessage").delay(5000).slideUp(300);
        });
    </script>
<script>
    function addResponsibility() {
        const container = document.getElementById("responsibilities-container");

        // Create a wrapper div for each input + remove button
        const wrapper = document.createElement("div");
        wrapper.classList.add("d-flex", "gap-2", "mt-2");

        // Create input field
        const input = document.createElement("input");
        input.type = "text";
        input.name = "responsibilities[]"; // array input
        input.className = "form-control";
        input.placeholder = "Enter responsibility";
        input.required = true;

        // Create remove button
        const removeBtn = document.createElement("button");
        removeBtn.type = "button";
        removeBtn.className = "btn btn-danger btn-sm";
        removeBtn.innerText = "Remove";
        removeBtn.onclick = function () {
            container.removeChild(wrapper);
        };

        wrapper.appendChild(input);
        wrapper.appendChild(removeBtn);
        container.appendChild(wrapper);
    }
</script>
</body>

</html>
