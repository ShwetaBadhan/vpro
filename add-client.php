<?php
session_start();
// error_reporting(0);
$upload_directory = "client/";
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
    $clientName = $_POST['name'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $state = $_POST['state'];
    $city = $_POST['city'];
    $status = $_POST['status'];
    $renewal = $_POST['renewal_date'];
    $remarks = $_POST['remarks'];
    $primary_person = $_POST['client_primary_person'];

    // Optional fields
    $services = isset($_POST['services']) && is_array($_POST['services']) ? $_POST['services'] : [];
    $servicesString = implode(', ', $services);
    $servicesString = mysqli_real_escape_string($db, $servicesString);



    date_default_timezone_set('Asia/Kolkata');
    $createdDate = date('Y-m-d H:i:s A');
    // echo '<pre>';
    // print_r($_POST);
    // exit();
    // Escape values (if you're not using prepared statements)
    $clientName = mysqli_real_escape_string($db, $clientName);
    $phone = mysqli_real_escape_string($db, $phone);
    $email = mysqli_real_escape_string($db, $email);
    $address = mysqli_real_escape_string($db, $address);
    $state = mysqli_real_escape_string($db, $state);
    $city = mysqli_real_escape_string($db, $city);
    $status = mysqli_real_escape_string($db, $status);
     $renewal = mysqli_real_escape_string($db, $renewal);
      $remarks = mysqli_real_escape_string($db, $remarks);
      $primary_person = mysqli_real_escape_string($db, $primary_person);


    $createdDate = mysqli_real_escape_string($db, $createdDate);

    // SQL Insert Query
    $query = "INSERT INTO clients (name, phone, email, address, services, state, city, status,renewal_date, remarks,client_primary_person,created_at) 
          VALUES ('$clientName', '$phone', '$email', '$address', '$servicesString', '$state', '$city', '$status','$renewal', '$remarks','$primary_person', '$createdDate')";


    if (mysqli_query($db, $query)) {
        $_SESSION['success_msg'] = "Client added successfully.";

        // Redirect to the same page to prevent resubmission
        header("Location: add-client.php");
        exit();
    } else {
        $msg = "
        <div class='alert alert-danger alert-dismissible fade show' role='alert' style='font-size:16px;' id='goldmessage'>
            <strong><i class='feather icon-alert-circle'></i> Error!</strong> Failed to add client. 
            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                <span aria-hidden='true'>&times;</span>
            </button>
        </div>";
    }
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
    <title>Add Client </title>



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
                                <h5 class="m-b-10">Add Client
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
                                           
                                                <label class="" for="name">Client Name<span class=" ">
                                                    </span></label>
                                                <input name="name" type="text" id="name" placeholder=" Enter Client Name"
                                                    class="form-control input-md"
                                                    required>
                                            
                                        </div>
                                         <div class="col-xl-6 col-lg-6 col-md-4 col-sm-12 col-12 mb-2">
                                           
                                                <label class="" for="name"> Primary Person<span class=" ">
                                                    </span></label>
                                                <input name="client_primary_person" type="text" id="client_primary_person" placeholder=" Enter Primary Person Name"
                                                    class="form-control input-md"
                                                    required>
                                            
                                        </div>
                                        <div class="col-xl-6 col-lg-6 col-md-4 col-sm-12 col-12 mb-2">
                                            
                                                <label class="" for="name">Enter Phone No.<span class=" ">
                                                    </span></label>
                                                <input name="phone" type="number" id="name" placeholder=" Enter Enter Phone No."
                                                    class="form-control input-md"
                                                    required>
                                           
                                        </div>
                                        <div class="col-xl-6 col-lg-6 col-md-4 col-sm-12 col-12">
                                           
                                                <label class="" for="name">Enter Email<span class=" ">
                                                    </span></label>
                                                <input name="email" type="text" id="name" placeholder=" Enter Email"
                                                    class="form-control input-md"
                                                    required>
                                            
                                        </div>
                                        <!-- <div class="col-xl-6 col-lg-6 col-md-4 col-sm-12 col-12">
                                            <div class="form-group">Client Active Date<span class="red-text">*</span>
                                                <label class="sr-only control-label" for="active_date">Client Active Date<span class=" ">
                                                    </span></label>
                                                <input name="active_date" type="date" id="active_date" placeholder=" Enter event date"
                                                    class="form-control input-md"
                                                    required>



                                            </div>
                                        </div> -->

                                        <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
                                            <div class="form-group">
                                                <label for="service" class="form-label">Service Name</label>
                                                <?php
                                                $category_query = "SELECT * FROM service WHERE status='1'";
                                                $result = $db->query($category_query);

                                                if ($result->num_rows > 0) {
                                                    echo " <select id='multiSelect' name='services[]' multiple='multiple' required>";
                                                    echo "<option value='' selected disabled>Choose</option>";

                                                    while ($row = $result->fetch_assoc()) {
                                                        echo "<option value='{$row['name']}'>{$row['name']}</option>";
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
                                                <option value="" selected disabled>Choose</option>
                                                <?php
                                                $state_query = "SELECT * FROM state WHERE status='1'";
                                                $result = $db->query($state_query);
                                                if ($result->num_rows > 0) {
                                                    while ($row = $result->fetch_assoc()) {
                                                        echo "<option value='{$row['state_id']}'>{$row['state_name']}</option>";
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







                                        <div class="col-xl-6 col-lg-6 col-md-4 col-sm-12 col-12 mt-3">
                                            
                                                <label class="" for="name">Status<span class=" ">
                                                   </label>
                                                <select id="" name="status" class="form-control"
                                                    oninvalid="this.setCustomValidity('Please Select Status')"
                                                    oninput="setCustomValidity('')" required>
                                                    <option value="" selected disabled>Choose</option>
                                                    <option value="1">Enable</option>
                                                    <option value="0">Disabe</option>
                                                    <option value="Hold">Hold</option>

                                                </select>
                                           
                                        </div>
                                         <div class="col-xl-6 col-lg-6 col-md-4 col-sm-12 col-12 mt-3">
                                            
                                                <label class="" for="name">Renewal Date<span class=" ">
                                                   </label>
                                                <input type="date" class="form-control" name="renewal_date">
                                           
                                        </div>
                                        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 mt-3">
                                            <div class="form-group">
                                                <label for="address" class="form-label">Address <span class="red-text">*</span></label>
                                                <textarea name="address" id="address" class="form-control" rows="5" required></textarea>
                                            </div>
                                        </div>

                                        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 mt-3">
                                            <div class="form-group">
                                                <label for="remarks" class="form-label">Remarks (If client is hold)</label>
                                                <textarea name="remarks" id="remarks" class="form-control" rows="5"></textarea>
                                            </div>
                                        </div>


                                        <!-- Button -->
                                        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">

                                            <button type="submit" class="btn btn-secondary" name="submit" id="submit">
                                                <i class="feather icon-save lg"></i>&nbsp; Add Client
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
        function InvalidMsg(textbox) {

            if (textbox.value == '') {
                textbox.setCustomValidity('Required email address');
            } else if (textbox.validity.typeMismatch) {
                textbox.setCustomValidity('please enter a valid email address');
            } else {
                textbox.setCustomValidity('');
            }
            return true;
        }
    </script>

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


<script>
    $('#state').on('change', function() {
        var stateId = $(this).val();

        $.ajax({
            url: '', // send to same page
            type: 'POST',
            data: {
                state_id: stateId
            },
            success: function(data) {
                $('#city').html(data);
            },
            error: function() {
                alert('Something went wrong!');
            }
        });
    });
</script>



</html>