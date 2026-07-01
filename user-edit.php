<?php
session_start();
if (!isset($_SESSION["login_user"])) {
    header("location: index.php");
}

$msg="";
$name = $_SESSION['login_user'];

include("db/config.php");

$en = $_GET["id"];

$de = base64_decode($en);

$selected_role_id = ""; // Will be set from the database

// Fetch existing admin details (to pre-fill form)
$adminQuery = mysqli_query($db, "SELECT * FROM admin WHERE email = '$de'");
$admin = mysqli_fetch_assoc($adminQuery);
if ($admin) {
    $selected_role_id = $admin['admin_role'];
}

if (isset($_POST['submit'])) {
    $username = mysqli_real_escape_string($db, $_POST["username"]);
    $email = mysqli_real_escape_string($db, $_POST["email"]);
    $mobile = mysqli_real_escape_string($db, $_POST["mobile"]);
    $status = mysqli_real_escape_string($db, $_POST["status"]);
    $role_id = mysqli_real_escape_string($db, $_POST["admin_role"]);
    $password = $_POST["password"];

    if (!empty($password)) {
        $hashed_password = md5($password);
        $sql = "UPDATE admin SET 
                    username = '$username',
                    password = '$hashed_password',
                    email = '$email',
                    status = '$status',
                    mobile = '$mobile',
                    admin_role = '$role_id'
                WHERE email = '$de'";
    } else {
        $sql = "UPDATE admin SET 
                    username = '$username',
                    email = '$email',
                    status = '$status',
                    mobile = '$mobile',
                    admin_role = '$role_id'
                WHERE email = '$de'";
    }

    if (mysqli_query($db, $sql)) {
        $status_code = base64_encode(1);
        echo "<script>window.location.href='manage-user.php?status=$status_code';</script>";
        exit();
    } else {
        echo "<div class='alert alert-danger'>Error updating admin: " . mysqli_error($db) . "</div>";
    }
}


// Fetch roles from the database
$rolequery = "SELECT role_id,role_name FROM roles"; 
$roleresult = mysqli_query($db, $rolequery);

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
    <title>Update Admin User </title>



    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="" />
    <meta name="keywords" content="">
    <meta name="author" content="Codedthemes" />

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

    <?php include 'header.php'; ?>
    <?php include 'navbar.php'; ?>
	
    <section class="pcoded-main-container">
        <div class="pcoded-content">

            <div class="page-header">
                <div class="page-block">
                    <div class="row align-items-center">
                        <div class="col-md-12">
                            <div class="page-header-title">
                                <h5 class="m-b-10">Update Admin User
                                </h5>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header table-card-header">
                            <form class="contact-us" method="post" action="" enctype="multipart/form-data" autocomplete="off">
                                <div class=" ">
                                    <!-- Text input-->
                                    <div class="row">
                                        <div class="col-xl-6 col-lg-6 col-md-4 col-sm-12 col-12">
                                            <div class="form-group">Enter Username <span class="red-text">*</span>
                                                <label class="sr-only control-label" for="name">Username<span class=" ">
                                                    </span></label>
                                                <input id="name" name="username" type="text" placeholder=" Username" class="form-control input-md"  value="<?php echo $admin['username']; ?>" readonly>
                                            </div>
                                        </div>
                                        <div class="col-xl-6 col-lg-6 col-md-4 col-sm-12 col-12">
                                            <div class="form-group">Enter Password <span class="red-text">*</span>
                                                <label class="sr-only control-label" for="name">Password<span class=" ">
                                                    </span></label>
                                                <input id="name" name="password" type="password" placeholder="Enter new password,if you want to change current password" class="form-control input-md" value="">
                                            </div>
                                        </div>
                                        <div class="col-xl-6 col-lg-6 col-md-4 col-sm-12 col-12">
                                            <div class="form-group">Mobile No <span class="red-text">*</span>
                                                <label class="sr-only control-label" for="name">Mobile No<span class=" ">
                                                    </span></label>
                                                <input id="name" name="mobile" type="number" placeholder=" Enter Mobile No *" class="form-control input-md" required oninvalid="this.setCustomValidity('Please Enter Mobile Number')" oninput="setCustomValidity('')" value="<?php echo $admin['mobile']; ?>">
                                            </div>
                                        </div>


                                        <div class="col-xl-6 col-lg-6 col-md-4 col-sm-12 col-12">
                                            <div class="form-group">Email <span class="red-text">*</span>
                                                <label class="sr-only control-label" for="name">Email<span class=" ">
                                                    </span></label>
                                                <input id="name" name="email" type="email" class="form-control input-md" required placeholder="Enter Email" value="<?php echo $admin['email']; ?>">
                                            </div>
                                        </div>
                                        <div class="col-xl-6 col-lg-6 col-md-4 col-sm-12 col-12">
                                            <div class="form-group">Status <span class="red-text">*</span>
                                                <label class="sr-only control-label" for="name">Status<span class=" ">
                                                    </span></label>
                                                <select id="" name="status" class="form-control" required>
                                                    <option value="" selected disabled>Choose</option>
                                                    <option value="1" <?php echo (isset($admin['status']) && $admin['status'] == 1) ? 'selected' : ''; ?>>
                                                        Enable
                                                    </option>
                                                    <option value="0" <?php echo (isset($admin['status']) && $admin['status'] == 0) ? 'selected' : ''; ?>>
                                                        Disable
                                                </select>
                                            </div>
                                        </div>

                                        

                                        <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
    <div class="form-group">
        <label for="role_id" class="form-label">Select Role<span class="red-text">*</span></label>
        <select class="form-control" id="role_id" name="admin_role" required>
    <option value="" disabled>Choose a Role</option>
    <?php
    if (mysqli_num_rows($roleresult) > 0) {
        while ($admin = mysqli_fetch_assoc($roleresult)) {
            $selected = ($admin['role_id'] == $selected_role_id) ? "selected" : "";
            echo "<option value='" . $admin['role_id'] . "' $selected>" . $admin['role_name'] . "</option>";
        }
    } else {
        echo "<option value='' disabled>No role found</option>";
    }
    ?>
</select>

    </div>
</div>

                                    </div>

                                        <!-- Button -->
                                        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">


                                            <button type="submit" class="btn btn-secondary" name="submit" id="submit">
                                                <i class="feather icon-save lg"></i>&nbsp; Update Admin User
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

            // Handle removal of selected items
            $('#multiSelect').on('click', '.remove-item', function() {
                var valueToRemove = $(this).data('value');
                var $select = $('#multiSelect');
                $select.find('option[value="' + valueToRemove + '"]').prop('selected', false);
                $select.trigger('change.select2');
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
    		$("#successMessage").delay(5000).slideUp(300);
	});
	</script>
</body>

</html>