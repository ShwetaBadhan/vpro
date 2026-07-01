<?php


session_start();

if (!isset($_SESSION["login_user"]))

{
	header ("location: index.php");
   
}


error_reporting(0);
include("db/config.php");
// Register user
if(isset($_POST['submit'])){
   $name = strip_tags(($_POST['gold']));

   
   
 $city = strip_tags($_POST['date']);
   
 
	  
mysqli_select_db($db,DB_NAME);

		$query="insert into gold  (price, date) values('$name','$city')";
$quuery=mysqli_query($db,$query);

if($quuery>0)
{
	$msg="add record Successfully";
}


}


?>

<!DOCTYPE html>
<html lang="en">

<meta http-equiv="content-type" content="text/html;charset=UTF-8" />
<head>
<title>Add Gold Price </title>



<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<meta name="description" content="" />
<meta name="keywords" content="">
<meta name="author" content="Codedthemes" />

<link rel="icon" href="assets/images/favicon.ico" type="image/x-icon">

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
<a href="dashboard.php" class="nav-link " style="background:#0e7360; color:#fff;"><span class="pcoded-micon"><i class="feather icon-home"></i></span><span class="">Dashboard</span></a>
</li>


<li class="nav-item pcoded-hasmenu">
						<a href="#!" class="nav-link " style="background:#0e7360; color:#fff;"><span class="pcoded-micon"><i class="feather icon-globe"></i></span><span class="pcoded-mtext">Gold</span></a>
						<ul class="pcoded-submenu">
							<li><a href="gold.php">Add Gold Rate</a></li>
							<li><a href="view_gold.php">View Gold Rate</a></li>
							
</ul>
</li>


<li class="nav-item pcoded-hasmenu">
						<a href="#!" class="nav-link " style="background:#0e7360; color:#fff;"><span class="pcoded-micon"><i class="feather icon-file"></i></span><span class="pcoded-mtext">Menu</span></a>
						<ul class="pcoded-submenu">
							<li><a href="#">Add Menu</a></li>
							<li><a href="#">Add Sub Menu</a></li>
                            <li><a href="#">View Menu</a></li>
							
</ul>
</li>

<li class="nav-item pcoded-hasmenu">
						<a href="#!" class="nav-link " style="background:#0e7360; color:#fff;"><span class="pcoded-micon"><i class="feather icon-edit"></i></span><span class="pcoded-mtext">Pages</span></a>
						<ul class="pcoded-submenu">
							<li><a href="#">Add page</a></li>
							<li><a href="#">view Page</a></li>
                            
							
</ul>
</li>

<li class="nav-item">
<a href="reports.php" class="nav-link " style="background:#0e7360; color:#fff;"><span class="pcoded-micon"><i class="feather icon-image"></i></span><span class="">Reports</span></a>
</li>


<li class="nav-item pcoded-hasmenu">
						<a href="#!" class="nav-link " style="background:#0e7360; color:#fff;"><span class="pcoded-micon"><i class="feather icon-users"></i></span><span class="pcoded-mtext">Membership</span></a>
						<ul class="pcoded-submenu">
                        
                        <li><a href="view_member.php">Register Members</a></li>
                          <li><a href="active_member.php">Active Members</a></li>
						
							
							
</ul>
</li>


<li class="nav-item pcoded-hasmenu">
						<a href="#!" class="nav-link " style="background:#0e7360; color:#fff;"><span class="pcoded-micon"><i class="feather icon-users"></i></span><span class="pcoded-mtext">User</span></a>
						<ul class="pcoded-submenu">
                        
                        <li><a href="add-user.php">Add User</a></li>
                          <li><a href="view-user.php">View User</a></li>
						
							
							
</ul>
</li>


<li class="nav-item pcoded-hasmenu">
						<a href="#!" class="nav-link " style="background:#0e7360; color:#fff;"><span class="pcoded-micon"><i class="feather icon-credit-card"></i></span><span class="pcoded-mtext">Payments</span></a>
						<ul class="pcoded-submenu">
                        
                        <li><a href="#">Success Payment</a></li>
                          <li><a href="#">Failure Payment</a></li>
							
							
</ul>
</li>



<li class="nav-item">
<a href="changepass.php" class="nav-link " style="background:#0e7360; color:#fff;"><span class="pcoded-micon"><i class="feather icon-droplet"></i></span><span class="">Change Password</span></a>
</li>	

<li class="nav-item">
<a href="configuration.php" class="nav-link " style="background:#0e7360; color:#fff;"><span class="pcoded-micon"><i class="feather icon-server"></i></span><span class="">Setting</span></a>
</li>	
<li class="nav-item">
<a href="logout.php" class="nav-link " style="background:#0e7360; color:#fff;"><span class="pcoded-micon"><i class="feather icon-power"></i></span><span class="">Log out</span></a>
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
<h5 class="m-b-10">Add Gold Price
</h5>
</div>
<ul class="breadcrumb">
<li class="breadcrumb-item"><a href="index.php"><i class="feather icon-home"></i></a></li>

</ul>
</div>
</div>
</div>
</div>


<div class="row">

<div class="col-sm-12">
<div class="card">


<div class="card-header table-card-header">

<?php
if($msg)
{
echo " <div class='alert alert-success alert-dismissible fade show' role='alert' style='font-size:16px;' id='goldmessage'>
  <strong><i class='feather icon-check'></i>Thanks!</strong>Gold  Record added Successfully.
  <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
    <span aria-hidden='true'>&times;</span>
  </button>
</div> ";
}
?>

<br/>

 <form class="contact-us" method="post" action="" enctype="multipart/form-data"  autocomplete="off">
                                    <div class=" ">
                                        <!-- Text input-->
                                        <div class="row">
                                            <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
                                                <div class="form-group">Enter Gold Rate*
                                                    <label class="sr-only control-label" for="name">name<span class=" "> </span></label>
                                                    <input id="name" name="gold" type="text" placeholder=" Enter the Gold Rate" class="form-control input-md" required  oninvalid="this.setCustomValidity('Please Gold Rate')"
 oninput="setCustomValidity('')">
                                                </div>
                                            </div>
                                            
                                             
                                            <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
                                                <div class="form-group">Enter date*
                                                    <label class="sr-only control-label" for="name">name<span class=" "> </span></label>
                                                    <input id="name" name="date" type="date"  class="form-control input-md" required  oninvalid="this.setCustomValidity('Please Select date')"
 oninput="setCustomValidity('')">
                                                </div>
                                            </div>
                                            <!-- Text input-->
                                          
                                
                                            
                                            <!-- Button -->
                                            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
                                            
                                          
                                                              <button type="submit" class="btn btn-secondary" name="submit" id="submit">
 <i class="feather icon-save"></i>&nbsp; Add Price
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
$(document).ready(function(){
    $("#goldmessage").delay(5000).slideUp(300);
});
</script>


</body>

</html>
