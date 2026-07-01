<?php
session_start();
// error_reporting(0);
$upload_directory = "Event/";
if (!isset($_SESSION["login_user"])) {
    header("location: index.php");
}
include('db/config.php');
$msg = "";
if (isset($_SESSION['success_msg'])) {
    $msg = "
    <div class='alert alert-success alert-dismissible fade show' role='alert' style='font-size:16px;' id='goldmessage'>
        <strong><i class='feather icon-check'></i> Thanks!</strong> " . $_SESSION['success_msg'] . "
        <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
            <span aria-hidden='true'>&times;</span>
        </button>
    </div>";
    unset($_SESSION['success_msg']);
}
if (isset($_POST['submit'])) {
    // echo '<pre>';
    // print_r($_POST);
    // exit();
    // Required fields
    $eventName = $_POST['title'];
    $date = $_POST['event_date'];
    $type = $_POST['type'];
    $status = $_POST['status'];
    $desc = $_POST['description'];




    date_default_timezone_set('Asia/Kolkata');
    $createdDate = date('Y-m-d H:i:s A');
    // echo '<pre>';
    // print_r($_POST);
    // exit();
    // Escape values (if you're not using prepared statements)
    $eventName = mysqli_real_escape_string($db, $eventName);
    $date = mysqli_real_escape_string($db, $date);
    $type = mysqli_real_escape_string($db, $type);
    $status = mysqli_real_escape_string($db, $status);
    $desc = mysqli_real_escape_string($db, $desc);


    $createdDate = mysqli_real_escape_string($db, $createdDate);

    // SQL Insert Query
    $query = "INSERT INTO event_calendar (title, event_date, type, description, status,created_at) 
          VALUES ('$eventName', '$date', '$type', '$desc', '$status' , '$createdDate')";


    if (mysqli_query($db, $query)) {
        $_SESSION['success_msg'] = "Event added successfully.";

        // Redirect to the same page to prevent resubmission
        header("Location: add-event.php");
        exit();
    } else {
        $msg = "
        <div class='alert alert-danger alert-dismissible fade show' role='alert' style='font-size:16px;' id='goldmessage'>
            <strong><i class='feather icon-alert-circle'></i> Error!</strong> Failed to Add Event. 
            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                <span aria-hidden='true'>&times;</span>
            </button>
        </div>";
    }
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
    <title>Add Event </title>



    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="" />
    <meta name="keywords" content="">
    <meta name="author" content="#" />

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
                                <h5 class="m-b-10">Add Event
                                </h5>
                            </div>
                            <!--                              <ul class="breadcrumb"> -->
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

                            echo "<span id='user-availability-status'></span>";
                            if ($msg) {
                                echo $msg;
                            }
                            ?>

                            <br />

                            <form method="post" action="" enctype="multipart/form-data" autocomplete="off">
                                <div class=" ">
                                    <!-- Text input-->
                                    <div class="row">
                                        <div class="col-xl-6 col-lg-6 col-md-4 col-sm-12 col-12 mb-2">
                                           
                                                <label class="" for="name">Event Name<span class=" ">
                                                    </span></label>
                                                <input name="title" type="text" id="name" placeholder=" Enter Event Name"
                                                    class="form-control input-md"
                                                    required>
                                            
                                        </div>
                                         <div class="col-xl-6 col-lg-6 col-md-4 col-sm-12 col-12 mb-2">
                                           
                                                <label class="" for="name"> Event  Date<span class=" ">
                                                    </span></label>
                                                <input name="event_date" type="date" id="date" placeholder=" Enter Primary Person Name"
                                                    class="form-control input-md"
                                                    required>
                                            
                                        </div>
                                      
                                       
                                      


                                        <div class="col-xl-6 col-lg-6 col-md-4 col-sm-12 col-12 mt-3">
                                            
                                                <label class="" for="name"> Type<span class=" ">
                                                   </label>
                                                <select id="" name="type" class="form-control"
                                                    oninvalid="this.setCustomValidity('Please Select Status')"
                                                    oninput="setCustomValidity('')" required>
                                                    <option value="" selected disabled>Choose</option>
                                                    <option value="holiday">Holiday</option>
                                                    <option value="event">Event</option>
                                                    

                                                </select>
                                           
                                        </div>
                                        <div class="col-xl-6 col-lg-6 col-md-4 col-sm-12 col-12 mt-3">
                                            
                                                <label class="" for="name">Status<span class=" ">
                                                   </label>
                                                <select id="" name="status" class="form-control"
                                                    oninvalid="this.setCustomValidity('Please Select Status')"
                                                    oninput="setCustomValidity('')" required>
                                                    <option value="" selected disabled>Choose</option>
                                                    <option value="1">Active</option>
                                                    <option value="0">Inactive</option>
                                                   

                                                </select>
                                           
                                        </div>
                                       
                                        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 mt-3">
                                            <div class="form-group">
                                                <label for="address" class="form-label">Description <span class="red-text">*</span></label>
                                                <textarea name="description" id="description" class="form-control" rows="5" required></textarea>
                                            </div>
                                        </div>

                                      

                                        <!-- Button -->
                                        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">

                                            <button type="submit" class="btn btn-secondary" name="submit" id="submit">
                                                <i class="feather icon-save lg"></i>&nbsp; Add Event
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
    <!-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> -->
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
    
</body>


<script>
    $(document).ready(function() {
        $("#goldmessage").delay(5000).slideUp(300);
    });
</script>

<script>
    $(document).ready(function() {
        $("#successMessage").delay(5000).slideUp(300);
    });
</script>





</html>