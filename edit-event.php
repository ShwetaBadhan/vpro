<?php
session_start();
include('db/config.php');
$msg = "";

if (isset($_GET['id'])) {
    $encodedEventId = $_GET['id'];
    $EventId = intval(base64_decode($encodedEventId)); // Safely decode and cast to int

    // Fetch Event data
    $query = "SELECT * FROM event_calendar WHERE event_id = $EventId";
    $result = mysqli_query($db, $query);

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $eventName = $row['title'];
        $date = $row['event_date'];
        $type = $row['type'];
        $status = $row['status'];
        $description = $row['description'];

        // echo $description;
        // exit;
       


        // Update process
        if (isset($_POST['submit'])) {
            // echo '<pre>';
            // print_r($_POST);
            // exit();
            $updatedeventName = mysqli_real_escape_string($db, $_POST['title']);
            $updateddate = mysqli_real_escape_string($db, $_POST['event_date']);
            $updatedtype = mysqli_real_escape_string($db, $_POST['type']);
            $updatedStatus = mysqli_real_escape_string($db, $_POST['status']);
            $updateddesc = mysqli_real_escape_string($db, $_POST['description']);

        
            $updateQuery = "Update event_calendar SET 
        title = '$updatedeventName',
        event_date = '$updateddate',
        type = '$updatedtype',
        status = '$updatedStatus',
        description = '$updateddesc'
        
        WHERE event_id = $EventId";
            //  echo '<pre>';
            //             print_r($updateQuery);
            //             exit();
            if ($db->query($updateQuery) === TRUE) {
                $status = base64_encode(1);
                echo ("<script>window.location.href='manage-events.php?status=$status';</script>");
            } else {
                echo "Error updating Event: " . $db->error;
            }
        }
    } else {
        echo "Event not found!";
        exit;
    }
} else {
    echo "Invalid request!";
    exit;
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
    <title>Update Event</title>

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
                                <h5 class="m-b-10">Update Event
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
                                        <div class="col-xl-6 col-lg-6 col-md-4 col-sm-12 col-12 mb-2">
                                           
                                                <label class="" for="name">Event Name<span class=" ">
                                                    </span></label>
                                                <input name="title" type="text" id="name" placeholder="Enter Event Name"
                                                    class="form-control input-md" value="<?php echo $eventName?>"
                                                    required>
                                            
                                        </div>
                                         <div class="col-xl-6 col-lg-6 col-md-4 col-sm-12 col-12 mb-2">
                                           
                                                <label class="" for="name"> Event  Date<span class=" ">
                                                    </span></label>
                                                <input name="event_date" type="date" id="date" placeholder=" Enter Primary Person Name"
                                                    class="form-control input-md" value="<?php echo $date?>"
                                                    required>
                                            
                                        </div>
                                      
                                       
                                      


                                        <div class="col-xl-6 col-lg-6 col-md-4 col-sm-12 col-12 mt-3">
                                            
                                                <label class="" for="name"> Type<span class=" ">
                                                   </label>
                                                <select id="" name="type" class="form-control"
                                                    oninvalid="this.setCustomValidity('Please Select Status')"
                                                    oninput="setCustomValidity('')" required>
                                                    <option value="" selected disabled>Choose</option>
                                                    <option value="holiday" <?php echo ($type == 'holiday') ? 'selected' : ''; ?> >Holiday</option>
                                                    <option value="event" <?php echo ($type == 'event') ? 'selected' : ''; ?> >Event</option>
                                                    

                                                </select>
                                           
                                        </div>
                                       <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12 mt-3">
                                            <div class="form-group">
                                                <label for="name" class="form-label">Status <span class="red-text">*</span></label>
                                                <select id="" name="status" class="form-control">
                                                    <option value="" selected disabled>Choose</option>
                                                    <option value="1" <?php echo ($status == 1) ? 'selected' : ''; ?>>Active</option>
                                                    <option value="0" <?php echo ($status != 1) ? 'selected' : ''; ?>>Inactive</option>
                                                   
                                                </select>
                                            </div>
                                        </div>
                                       
                                        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 mt-3">
                                            <div class="form-group">
                                                <label for="address" class="form-label">Description <span class="red-text">*</span></label>
                                                <textarea name="description" id="description" class="form-control" rows="5" value=""><?php echo $description?></textarea>
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