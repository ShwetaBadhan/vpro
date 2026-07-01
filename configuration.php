<?php
session_start();
error_reporting(0);
include("db/config.php");

if (!isset($_SESSION["login_user"])) {
    header("location: index.php");
}

$name = $_SESSION['login_user'];
// Register user
$result = mysqli_query($db, "SELECT * FROM  google_captcha ");
$row = mysqli_fetch_row($result);

$result1 = mysqli_query($db, "SELECT * FROM  smtp_email ");
$row1 = mysqli_fetch_row($result1);


$result22 = mysqli_query($db, "SELECT * FROM  admin ");
$row22 = mysqli_fetch_row($result22);
$d = $row[0];
$e = $row1[0];

$dc = $row22[0];

if (isset($_POST['submit123'])) {
    $name = $_POST['name'];
    $mobile = $_POST['mobile'];
    $email = $_POST['email'];
    $staff = $_POST['staff'];
    $password = $_POST['password'];
    $query11 = mysqli_query($db, "UPDATE  admin set username ='$name', email ='$email' , Staff_Email='$staff', mobile='$mobile' WHERE username='"  . $dc . "'");

    if ($query11 > 0) {
        $msg = "
            <div class='alert alert-success alert-dismissible fade show' role='alert' style='font-size:16px;' id='goldmessage'>
            <strong><i class='feather icon-check'></i>Thanks!</strong>The setting has been saved
            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
              <span aria-hidden='true'>&times;</span>
            </button>
          </div>
            ";
    }
}




if (isset($_POST['submit'])) {
    $key = $_POST['key'];
    $secret_key = $_POST['secret'];

    $query = mysqli_query($db, "UPDATE  google_captcha set site_key ='$key', secret_key ='$secret_key' WHERE captcha_id='"  . $d . "'");

    if ($query > 0) {
        $msg = "
            <div class='alert alert-success alert-dismissible fade show' role='alert' style='font-size:16px;' id='goldmessage'>
            <strong><i class='feather icon-check'></i>Thanks!</strong>The setting has been saved
            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
              <span aria-hidden='true'>&times;</span>
            </button>
          </div>
            ";
    }
}
if (isset($_POST['submit1'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $host = $_POST['host'];
    $port = $_POST['port'];
    $query1 = mysqli_query($db, "UPDATE  smtp_email set from_email	 ='$email', password ='$password' , hosts ='$host',  ports ='$port' WHERE smtp_id ='"  . $e . "'");
    if ($query1 > 0) {
        $msg = "
        <div class='alert alert-success alert-dismissible fade show' role='alert' style='font-size:16px;' id='goldmessage'>
        <strong><i class='feather icon-check'></i>Thanks!</strong>The setting has been saved
        <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
          <span aria-hidden='true'>&times;</span>
        </button>
      </div>
        ";
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
    <title>Admin Setting </title>



    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="" />
    <meta name="keywords" content="">
    <meta name="author" content="Codedthemes" />

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


    <nav class="pcoded-navbar menupos-fixed menu-light ">
        <div class="navbar-wrapper  ">
            <div class="navbar-content scroll-div ">
                <ul class="nav pcoded-inner-navbar ">
                    <li class="nav-item pcoded-menu-caption">
                        <label>Navigation</label>
                    </li>
                    <li class="nav-item">
                        <a href="dashboard.php" class="nav-link " style="background:#33cc33; color:#fff;"><span class="pcoded-micon"><i class="feather icon-home"></i></span><span class="">Dashboard</span></a>
                    </li>


                    <li class="nav-item pcoded-hasmenu">
                        <a href="#!" class="nav-link "><span class="pcoded-micon"><i class="feather icon-edit"></i></span><span class="pcoded-mtext">Category</span></a>
                        <ul class="pcoded-submenu">
                            <li><a href="add-category.php">Add Category</a></li>
                            <li><a href="view-category.php">Manage Category</a></li>

                        </ul>
                    </li>


                    <li class="nav-item pcoded-hasmenu">
                        <a href="#!" class="nav-link "><span class="pcoded-micon"><i class="feather icon-file"></i></span><span class="pcoded-mtext">Brands</span></a>
                        <ul class="pcoded-submenu">
                            <li><a href="add-brand.php">Add Brands</a></li>
                            <li><a href="manage-brand.php">Manage Brands</a></li>

                        </ul>
                    </li>

                    <li class="nav-item pcoded-hasmenu">
                        <a href="#!" class="nav-link "><span class="pcoded-micon"><i class="feather icon-home"></i></span><span class="pcoded-mtext">Departments</span></a>
                        <ul class="pcoded-submenu">
                            <li><a href="add-department.php">Add Departments</a></li>
                            <li><a href="manage-department.php">Manage Departments</a></li>


                        </ul>
                    </li>



                    <li class="nav-item pcoded-hasmenu">
                        <a href="#!" class="nav-link "><span class="pcoded-micon"><i class="feather icon-credit-card"></i></span><span class="pcoded-mtext">Price
                                List</span></a>
                        <ul class="pcoded-submenu">

                            <li><a href="add-price.php">Add Price List</a></li>
                            <li><a href="manage-price.php">Manage Price List</a></li>



                        </ul>
                    </li>


                    <li class="nav-item pcoded-hasmenu">
                        <a href="#!" class="nav-link "><span class="pcoded-micon"><i class="feather icon-users"></i></span><span class="pcoded-mtext">Users</span></a>
                        <ul class="pcoded-submenu">

                            <li><a href="add-user.php">Add User</a></li>
                            <li><a href="view-user.php">Manage User</a></li>



                        </ul>
                    </li>


                    <li class="nav-item pcoded-hasmenu">
                        <a href="#!" class="nav-link "><span class="pcoded-micon"><i class="feather icon-briefcase"></i></span><span class="pcoded-mtext">Tenders
                            </span></a>
                        <ul class="pcoded-submenu">

                            <li><a href="tender-request.php">Tender Request</a></li>
                            <li><a href="sent-tender.php"> Sent Tender</a></li>
                            <li><a href="alot-tender.php"> Alot Tender</a></li>


                        </ul>
                    </li>

                    <li class="nav-item">
                        <a href="registered-users.php" class="nav-link "><span class="pcoded-micon"><i class="feather icon-globe"></i></span><span class="">Registered users</span></a>
                    </li>

                    <li class="nav-item pcoded-hasmenu">
                        <a href="#!" class="nav-link "><span class="pcoded-micon"><i class="feather icon-credit-card"></i></span><span class="pcoded-mtext">Website
                            </span></a>
                        <ul class="pcoded-submenu">

                            <li><a href="add-banner.php">Add Banner</a></li>
                            <li><a href="manage-banner.php"> Manage Banner</a></li>

                            <li><a href="add-content.php">Add Content</a></li>
                            <li><a href="manage-content.php"> Manage Content</a></li>



                        </ul>
                    </li>

                    <li class="nav-item">
                        <a href="configuration.php" class="nav-link "><span class="pcoded-micon"><i class="feather icon-server"></i></span><span class="">Setting</span></a>
                    </li>

                    <li class="nav-item">
                        <a href="changepass.php" class="nav-link"><span class="pcoded-micon"><i class="feather icon-command"></i></span><span class="">Change
                                Password</span></a>
                    </li>

                    <li class="nav-item">
                        <a href="reports.php" class="nav-link "><span class="pcoded-micon"><i class="feather icon-file-plus"></i></span><span class="">Logs Report</span></a>
                    </li>

                    <li class="nav-item">
                        <a href="logout.php" class="nav-link " style="background:#33cc33; color:#fff;"><span class="pcoded-micon"><i class="feather icon-power"></i></span><span class="">Log
                                out</span></a>
                    </li>

                </ul>

            </div>
        </div>
    </nav>




    <header class="navbar pcoded-header navbar-expand-lg navbar-light headerpos-fixed header-blue">
        <div class="m-header">
            <a class="mobile-menu" id="mobile-collapse" href="#!"><span></span></a>
            <a href="#!" class="b-brand" style="font-size:24px;">
                ADMIN PANEL

            </a>
            <a href="#!" class="mob-toggler">
                <i class="feather icon-more-vertical"></i>
            </a>
        </div>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav mr-auto">
                <li class="nav-item">

                    <div class="search-bar">

                        <button type="button" class="close" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                </li>
                <li class="nav-item">
                    <a href="#!" class="full-screen" onClick="javascript:toggleFullScreen()"><i class="feather icon-maximize"></i></a>
                </li>
            </ul>


        </div>
        </div>
        </li>

        <div class="dropdown drp-user">
            <a href="#!" class="dropdown-toggle" data-toggle="dropdown">
                <img src="assets/images/user.png" class="img-radius wid-40" alt="User-Profile-Image">
            </a>
            <div class="dropdown-menu dropdown-menu-right profile-notification">
                <div class="pro-head">
                    <img src="assets/images/user.png" class="img-radius" alt="User-Profile-Image">
                    <span><?php echo $name ?></span>
                    <a href="logout.php" class="dud-logout" title="Logout">
                        <i class="feather icon-log-out"></i>
                    </a>
                </div>
                <ul class="pro-body">
                    <li><a href="logout.php" class="dropdown-item"><i class="feather icon-lock"></i> Log out</a></li>
                </ul>
            </div>
        </div>
        </li>
        </ul>
        </div>
    </header>



    <section class="pcoded-main-container">
        <div class="pcoded-content">

            <div class="page-header">
                <div class="page-block">
                    <div class="row align-items-center">
                        <div class="col-md-12">
                            <div class="page-header-title">
                                <h5 class="m-b-10">Server Setting


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


                            <?php

                            echo $msg;
                            ?>

                            <h4>Admin Setting</h4>
                            <hr />
                            <form method="post" action="">
                                <div class="row">
                                    <div class="col-xl-4 col-lg-4 col-md-4 col-sm-12 col-4">
                                        <div class="form-group">User Name
                                            <label class="sr-only control-label" for="name">User Name
                                                *<span class=" ">
                                                </span></label>
                                            <input id="name" name="name" type="text" placeholder=" Enter Google Rechaptcha Site Key  *" class="form-control input-md" required oninvalid="this.setCustomValidity('Please Enter Google Rechaptcha Site Key *')" oninput="setCustomValidity('')" value="<?php echo $row22[0]; ?>">
                                        </div>
                                    </div>


                                    <div class="col-xl-4 col-lg-4 col-md-6 col-sm-12 col-4">
                                        <div class="form-group">Mobile
                                            <label class="sr-only control-label" for="name">Mobile
                                                *<span class=" ">
                                                </span></label>
                                            <input id="name" name="mobile" type="text" placeholder=" Enter Google Rechaptcha Secret Key * *" class="form-control input-md" required oninvalid="this.setCustomValidity('Please Enter Google Rechaptcha Secret Key *')" oninput="setCustomValidity('')" value="<?php echo $row22[6]; ?>">
                                        </div>
                                    </div>


                                    <div class="col-xl-4 col-lg-4 col-md-6 col-sm-12 col-4">
                                        <div class="form-group">Admin Email
                                            <label class="sr-only control-label" for="name">Admin Email
                                                *<span class=" ">
                                                </span></label>
                                            <input id="name" name="email" type="text" placeholder=" Enter Google Rechaptcha Secret Key * *" class="form-control input-md" required oninvalid="this.setCustomValidity('Please Enter Google Rechaptcha Secret Key *')" oninput="setCustomValidity('')" value="<?php echo $row22[2]; ?>">
                                        </div>
                                    </div>


                                    <div class="col-xl-8 col-lg-8 col-md-8 col-sm-12 col-8">
                                        <div class="form-group">Staff Email
                                            <label class="sr-only control-label" for="name">Staff Email
                                                *<span class=" ">
                                                </span></label>
                                            <input id="name" name="staff" type="text" placeholder=" Enter Google Rechaptcha Secret Key * *" class="form-control input-md" required oninvalid="this.setCustomValidity('Please Enter Google Rechaptcha Secret Key *')" oninput="setCustomValidity('')" value="<?php echo $row22[7]; ?>">
                                        </div>
                                    </div>




                                    <div class="col-xl-4 col-lg-4 col-md-4 col-sm-12 col-4">
                                        <div class="form-group">New Password
                                            <label class="sr-only control-label" for="name">Google Rechaptcha Secret Key
                                                *<span class=" ">
                                                </span></label>
                                            <input id="name" name="password" type="text" placeholder="Enter your new password if you want to change.." class="form-control input-md" oninvalid="this.setCustomValidity('Please Enter Google Rechaptcha Secret Key *')" oninput="setCustomValidity('')">
                                        </div>
                                    </div>


                                    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">

                                        <button type="submit" class="btn btn-secondary" name="submit123" id="submit">
                                            <i class="feather icon-save lg"></i>&nbsp; Save
                                        </button>


                                    </div>

                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>





















        <div class="row">


            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header table-card-header">




                        <h4>Display Google Recaptcha</h4>
                        <hr />
                        <form method="post" action="">
                            <div class="row">
                                <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 col-6">
                                    <div class="form-group">Google Rechaptcha Site Key *
                                        <label class="sr-only control-label" for="name">Google Rechaptcha Site Key
                                            *<span class=" ">
                                            </span></label>
                                        <input id="name" name="key" type="text" placeholder=" Enter Google Rechaptcha Site Key  *" class="form-control input-md" required oninvalid="this.setCustomValidity('Please Enter Google Rechaptcha Site Key *')" oninput="setCustomValidity('')" value="<?php echo $row[1]; ?>">
                                    </div>
                                </div>

                                <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 col-6">
                                    <div class="form-group">Google Rechaptcha Secret Key *
                                        <label class="sr-only control-label" for="name">Google Rechaptcha Secret Key
                                            *<span class=" ">
                                            </span></label>
                                        <input id="name" name="secret" type="text" placeholder=" Enter Google Rechaptcha Secret Key * *" class="form-control input-md" required oninvalid="this.setCustomValidity('Please Enter Google Rechaptcha Secret Key *')" oninput="setCustomValidity('')" value="<?php echo $row[2]; ?>">
                                    </div>
                                </div>

                                <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">

                                    <button type="submit" class="btn btn-secondary" name="submit" id="submit">
                                        <i class="feather icon-save lg"></i>&nbsp; Save
                                    </button>


                                </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>

        </div>

        <div class="row">


            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header table-card-header">

                        <h4>SMTP</h4>
                        <hr />
                        <form action="" method="post">
                            <div class="row">
                                <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 col-6">
                                    <div class="form-group">Email From Address *
                                        <label class="sr-only control-label" for="name">Email From Address *
                                            *<span class=" ">
                                            </span></label>
                                        <input id="name" name="email" type="email" placeholder=" EnterEmail From Address   *" class="form-control input-md" required oninvalid="this.setCustomValidity('Please Enter Email From Address *')" oninput="setCustomValidity('')" value="<?php echo $row1[1]; ?>">
                                    </div>
                                </div>

                                <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 col-6">
                                    <div class="form-group">Email Password *
                                        <label class="sr-only control-label" for="name">Email Password *
                                            *<span class=" ">
                                            </span></label>
                                        <input id="name" name="password" type="text" placeholder=" Enter Email Password  *" class="form-control input-md" required oninvalid="this.setCustomValidity('Please EnterEmail Password *')" oninput="setCustomValidity('')" value="<?php echo $row1[2]; ?>">
                                    </div>
                                </div>

                                <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 col-6">
                                    <div class="form-group">Email Hosts*
                                        <label class="sr-only control-label" for="name">Email Hosts *
                                            *<span class=" ">
                                            </span></label>
                                        <input id="name" name="host" type="text" placeholder=" Enter Email Hosts  *" class="form-control input-md" required oninvalid="this.setCustomValidity('Please Enter Email Hosts *')" oninput="setCustomValidity('')" value="<?php echo $row1[3]; ?>">
                                    </div>
                                </div>

                                <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 col-6">
                                    <div class="form-group">Email Ports*
                                        <label class="sr-only control-label" for="name">Email Ports *
                                            *<span class=" ">
                                            </span></label>
                                        <input id="name" name="port" type="text" placeholder=" Enter Email Ports  *" class="form-control input-md" required oninvalid="this.setCustomValidity('Please Enter Email Ports *')" oninput="setCustomValidity('')" value="<?php echo $row1[4]; ?>">
                                    </div>
                                </div>

                                <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">

                                    <button type="submit" class="btn btn-secondary" name="submit1" id="submit">
                                        <i class="feather icon-save lg"></i>&nbsp; Save
                                    </button>


                                </div>

                        </form>
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
        $(document).ready(function() {
            $("#goldmessage").delay(5000).slideUp(300);
        });
    </script>
</body>

</html>