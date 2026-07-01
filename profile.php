<?php
session_start(); 
include 'db/config.php';
$msg="";

if (!isset($_SESSION["login_user"]))
{
    header("location: index.php");
}

$userid = $_SESSION['login_user_id'];

function get_user_details($userid)
{
    include 'db/config.php';
    $sql = "SELECT * FROM admin WHERE _id = '$userid'";
    $result = $db->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $userDetails = $result->fetch_assoc();
        $db->close();
        return $userDetails;
    } else {
        $db->close();
        return false;
    }
}

$userDetails = get_user_details($userid);

if (!$userDetails) {
    echo "User details not found!";
    exit();
}

$Username = $userDetails['username'];
$email = $userDetails['email'];
$phone = $userDetails['mobile'];
$status = $userDetails['status'];

if (isset($_POST['submit']))
{    
    $NewEmail = isset($_POST['Email']) ? $_POST['Email'] : '';
    $NewPhone = isset($_POST['Mobile']) ? $_POST['Mobile'] : ''; 
    $NewStatus = isset($_POST['Status']) ? $_POST['Status'] : '';
    $newPassword = isset($_POST['Password']) ? $_POST['Password'] : '';
    
    if (!empty($newPassword))
    {
        $hashedPassword = md5($newPassword);
        $sql = "UPDATE admin SET password = '$hashedPassword', mobile = '$NewPhone', email = '$NewEmail', status = '$NewStatus' WHERE _id = '$userid'";
    }
    else
    {
        $sql = "UPDATE admin SET mobile = '$NewPhone', email = '$NewEmail', status = '$NewStatus' WHERE _id = '$userid'";
    }
    
    if ($db->query($sql) === TRUE)
    {
        $msg = "
            <div class='alert alert-success alert-dismissible fade show' role='alert' style='font-size:16px;' id='goldmessage'>
            <strong><i class='feather icon-check'></i>Success!</strong> the User details Updated Successfully
            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
              <span aria-hidden='true'>&times;</span>
            </button>
          </div>
            ";
    }
    header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            echo "Error: " . $sql . "<br>" . $db->error;
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
    <title>User Profile</title>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="" />
    <meta name="keywords" content="">
    <meta name="author" content="#" />

    <link rel="icon" href="<?php echo $favicon; ?>" type="image/x-icon">

    <link rel="stylesheet" href="assets/css/plugins/dataTables.bootstrap4.min.css">

    <link rel="stylesheet" href="assets/css/style.css">
    
    <style>
        .red-text {
            color: red;
    }     
    </style>
    
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
                                <h5 class="m-b-10">User Profile
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
                            if ($msg) {
                                echo $msg;
                            }
                            ?>
                            <form method="post" action="">
                                <div class=" ">
                                    <!-- Text input-->
                                    <div class="row">
                                        
                                        <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
                                            <div class="form-group">
                                               <label for="name" class="form-label">Enter Username <span class="red-text">*</span></label>
                    						   <input type="text" name="Username" class="form-control" placeholder="Enter Username" value="<?php echo $Username ?>" readonly>
                                            </div>
                                        </div>
                                        
  										<div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
                                            <div class="form-group">
                                               <label for="password" class="form-label">Enter Password <span class="red-text">*</span></label>
                    						   <input type="password" name="Password" class="form-control" placeholder="Enter new password,if you want to change current password">
                                            </div>
                                        </div>
  
										<div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
                                            <div class="form-group">
                                               <label for="mobile" class="form-label">Mobile No <span class="red-text">*</span></label>
                    						   <input type="text" name="Mobile" class="form-control" placeholder="Enter Mobile Number" value="<?php echo $phone ?>" required>
                                            </div>
                                        </div>

                                        <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
                                            <div class="form-group">
                                               <label for="email" class="form-label">Email <span class="red-text">*</span></label>
                    						   <input type="email" name="Email" class="form-control" placeholder="Enter Email" value="<?php echo $email ?>" required>
                                            </div>
                                        </div>
                                        
                                        <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
                                           		<div class="form-group">
                                               		<label for="status" class="form-label">Status <span class="red-text">*</span></label>
                                                		<select id="" name="Status" class="form-control" required>
                                                			<option value="" selected disabled>Choose</option>
                                                    		<option value="1" <?php echo ($status == 1) ? 'selected' : ''; ?>>Enable</option>
                                                    		<option value="0" <?php echo ($status != 1) ? 'selected' : ''; ?>>Disable</option>
                                                	</select>
                                            	</div>
                                        </div>
                                        
                                        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
                                        	<button type="submit" class="btn btn-secondary" name="submit" id="submit">
                                            	<i class="feather icon-save lg"></i>&nbsp; Save
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
function checkUserAvailability() {
    $("#loader").show();
    jQuery.ajax({
        url: "check.php",
        data: 'username=' + $("#username").val(),
        type: "POST",
        success: function(data) {
            if (data == 1) {
                $("#user-availability-status").html(
                    "<div class='alert alert-danger'> <i class=' feather  icon icon-info'></i> &nbsp;Username already exists in our record.</div>"
                );
                $("#user-availability-status").removeClass('available');
                $("#user-availability-status").addClass('not-available');
                $("#submit").attr('disabled', true);
            } else {
                $("#user-availability-status").html(
                    "<div class='alert alert-success' ><i class='feather icon-check'></i> &nbsp;Username is Available.</div>"
                );
                $("#user-availability-status").removeClass('not-available');
                $("#user-availability-status").addClass('available');
                $("#submit").attr('disabled', false);
            }
            $("#loader").hide();
        },
        error: function() {}
    });
}
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
</html>