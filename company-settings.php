<?php
session_start();
include("db/config.php");

$msg="";

if (!isset($_SESSION["login_user"])) {
    header("location: index.php");
}

$result = mysqli_query($db, "SELECT * FROM company_info");
$row = mysqli_fetch_assoc($result);

$companyName = isset($row['company_name']) ? $row['company_name'] : '';
$companyEmail = isset($row['email']) ? $row['email'] : '';
$companyMobile = isset($row['mobile']) ? $row['mobile'] : '';
$companyFax = isset($row['fax']) ? $row['fax'] : '';
$companyWebsite = isset($row['website']) ? $row['website'] : '';
$companyAboutUs = isset($row['about_us']) ? $row['about_us'] : '';
$c = isset($row['id']) ? $row['id'] : '';


$result1 = mysqli_query($db, "SELECT * FROM company_address");
$row1 = mysqli_fetch_assoc($result1);

$Address = isset($row1['address']) ? $row1['address'] : '';
$City = isset($row1['city']) ? $row1['city'] : '';
$State = isset($row1['state']) ? $row1['state'] : '';
$Country = isset($row1['country']) ? $row1['country'] : '';
$Postal_Code = isset($row1['postal_code']) ? $row1['postal_code'] : '';
$a = isset($row1['id']) ? $row1['id'] : '';


$result2 = mysqli_query($db, "SELECT * FROM social_links");
$row2 = mysqli_fetch_assoc($result2);

$Instagram = isset($row2['instagram']) ? $row2['instagram'] : '';
$Facebook = isset($row2['facebook']) ? $row2['facebook'] : '';
$Twitter = isset($row2['twitter']) ? $row2['twitter'] : '';
$WhatsApp = isset($row2['whatsapp']) ? $row2['whatsapp'] : '';
$Youtube = isset($row2['youtube']) ? $row2['youtube'] : '';
$Linkedin = isset($row2['linkedin']) ? $row2['linkedin'] : '';
$Pinterest = isset($row2['pinterest']) ? $row2['pinterest'] : '';
$s = isset($row2['id']) ? $row2['id'] : '';

$result3 = mysqli_query($db, "SELECT * FROM localization");
$row3 = mysqli_fetch_assoc($result3);

$Language = isset($row3['website_language']) ? $row3['website_language'] : '';
$Timezone = isset($row3['website_timezone']) ? $row3['website_timezone'] : '';
$DateFormat = isset($row3['website_date_format']) ? $row3['website_date_format'] : '';
$TimeFormat = isset($row3['website_time_format']) ? $row3['website_time_format'] : '';
$StartingMonth = isset($row3['website_starting_month']) ? $row3['website_starting_month'] : '';
$FinancialYear = isset($row3['website_financial_year']) ? $row3['website_financial_year'] : '';
$l = isset($row3['id']) ? $row3['id'] : '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['submit1'])) {
        // Handle submit1 button
        $NewCompanyName = isset($_POST['Name']) ? $_POST['Name'] : '';
        $NewCompanyEmail = isset($_POST['Email']) ? $_POST['Email'] : '';
        $NewCompanyMobile = isset($_POST['Mobile']) ? $_POST['Mobile'] : '';
        $NewCompanyFax = isset($_POST['Fax']) ? $_POST['Fax'] : '';
        $NewCompanyWebsite = isset($_POST['Website']) ? $_POST['Website'] : '';
        $NewCompanyAboutUs = isset($_POST['About_us']) ? $_POST['About_us'] : '';
            
            $sql = "INSERT INTO company_info (id, company_name, email, mobile, fax, website, about_us)
                    VALUES ('$c', '$NewCompanyName', '$NewCompanyEmail', '$NewCompanyMobile', '$NewCompanyFax', '$NewCompanyWebsite', '$NewCompanyAboutUs')
                    ON DUPLICATE KEY UPDATE company_name='$NewCompanyName', email='$NewCompanyEmail', mobile='$NewCompanyMobile', fax='$NewCompanyFax', website='$NewCompanyWebsite', about_us='$NewCompanyAboutUs'";
            
            if ($db->query($sql) === TRUE) {
                $msg = "
                    <div class='alert alert-success alert-dismissible fade show' role='alert' style='font-size:16px;' id='goldmessage'>
                    <strong><i class='feather icon-check'></i>Success!</strong> The Company Information has been updated.
                    <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                    <span aria-hidden='true'>&times;</span>
                    </button>
                    </div>
                ";
            } 
            else 
            {
                echo "Error: " . $sql . "<br>" . $db->error;
            }
        
    } 
    elseif (isset($_POST['submit2'])) {
        // Handle submit2 button
        $NewAddress = isset($_POST['Address']) ? $_POST['Address'] : '';
        $NewCity = isset($_POST['City']) ? $_POST['City'] : '';
        $NewState = isset($_POST['State']) ? $_POST['State'] : '';
        $NewCountry = isset($_POST['Country']) ? $_POST['Country'] : '';
        $NewPostalCode = isset($_POST['Postalcode']) ? $_POST['Postalcode'] : '';
        
        $sql = "INSERT INTO company_address (id, address, city, state, country, postal_code)
                VALUES ('$a', '$NewAddress', '$NewCity', '$NewState', '$NewCountry', '$NewPostalCode')
                ON DUPLICATE KEY UPDATE address='$NewAddress', city='$NewCity', state='$NewState', country='$NewCountry', postal_code='$NewPostalCode'";
        
        if ($db->query($sql) === TRUE) {
            $msg = "
                <div class='alert alert-success alert-dismissible fade show' role='alert' style='font-size:16px;' id='goldmessage'>
                <strong><i class='feather icon-check'></i>Success!</strong> The Address has been updated.
                <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                <span aria-hidden='true'>&times;</span>
                </button>
                </div>
            ";
        } else {
            echo "Error: " . $sql . "<br>" . $db->error;
        }
    } 
    elseif (isset($_POST['submit3'])) {
        // Handle submit2 button
        $NewInstagram = isset($_POST['Instagram']) ? $_POST['Instagram'] : '';
        $NewFacebook = isset($_POST['Facebook']) ? $_POST['Facebook'] : '';
        $NewTwitter = isset($_POST['Twitter']) ? $_POST['Twitter'] : '';
        $NewWhatsapp = isset($_POST['Whatsapp']) ? $_POST['Whatsapp'] : '';
        $NewYoutube = isset($_POST['Youtube']) ? $_POST['Youtube'] : '';
        $NewLinkedin = isset($_POST['Linkedin']) ? $_POST['Linkedin'] : '';
        $NewPinterest = isset($_POST['Pinterest']) ? $_POST['Pinterest'] : '';
        
        $sql = "INSERT INTO social_links (id, instagram, facebook, twitter, whatsapp, youtube, linkedin, pinterest)
                VALUES ('$s', '$NewInstagram', '$NewFacebook', '$NewTwitter', '$NewWhatsapp', '$NewYoutube', '$NewLinkedin', '$NewPinterest')
                ON DUPLICATE KEY UPDATE instagram='$NewInstagram', facebook='$NewFacebook', twitter='$NewTwitter', whatsapp='$NewWhatsapp', youtube='$NewYoutube', linkedin='$NewLinkedin', pinterest='$NewPinterest'";
        
        if ($db->query($sql) === TRUE) {
            $msg = "
                <div class='alert alert-success alert-dismissible fade show' role='alert' style='font-size:16px;' id='goldmessage'>
                <strong><i class='feather icon-check'></i>Success!</strong> The Social Links has been updated.
                <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                <span aria-hidden='true'>&times;</span>
                </button>
                </div>
            ";
        }else {
            echo "Error: " . $sql . "<br>" . $db->error;
            }
    }
    elseif (isset($_POST['submit4'])) {

        $Newlanguage = $_POST['language'];
        $NewTimeZone = $_POST['timezone'];
        $NewDateFormat = $_POST['date'];
        $NewTimeFormat = $_POST['timeformat'];
        $NewFinancialYear = $_POST['year'];
        $NewStartingMonth = $_POST['month'];
        
        $sql = "INSERT INTO localization (id, website_language, website_timezone, website_date_format, website_time_format, website_financial_year, website_starting_month)
                VALUES ('$l', '$Newlanguage', '$NewTimeZone', '$NewDateFormat', '$NewTimeFormat', '$NewFinancialYear', '$NewStartingMonth')
                ON DUPLICATE KEY UPDATE website_language='$Newlanguage', website_timezone='$NewTimeZone', website_date_format='$NewDateFormat', website_time_format='$NewTimeFormat', website_financial_year='$NewFinancialYear', website_starting_month='$NewStartingMonth'";
        
        if ($db->query($sql) === TRUE) {
            $msg = "
                <div class='alert alert-success alert-dismissible fade show' role='alert' style='font-size:16px;' id='goldmessage'>
                <strong><i class='feather icon-check'></i>Success!</strong> The localization settings has been updated.
                <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                <span aria-hidden='true'>&times;</span>
                </button>
                </div>
            ";
        }else {
            echo "Error: " . $sql . "<br>" . $db->error;
        }
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
    <title>Company Settings</title>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="" />
    <meta name="keywords" content="">
    <meta name="author" content="Codedthemes" />

    <link rel="icon" href="<?php echo $favicon; ?>" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/plugins/dataTables.bootstrap4.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.css">
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

    <?php include 'header.php'; ?>
    <?php include 'navbar.php'; ?>

    <section class="pcoded-main-container">
        <div class="pcoded-content">

            <div class="page-header">
                <div class="page-block">
                    <div class="row align-items-center">
                        <div class="col-md-12">
                            <div class="page-header-title">
                                <h5 class="m-b-10">Company Settings
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
                            <h6><span data-feather="alert-circle"></span> OUR INFORMATION</h6>
                            <hr />
                            <form method="post" action="">
                                <div class="row">
                                    <div class="col-xl-4 col-lg-4 col-md-4 col-sm-12 col-4">
                                        <div class="form-group">
                                            <label class="form-label">Company Name <span class="red-text">*</span></label>
										    <input type="text" name="Name" class="form-control" placeholder="Enter Company Name" value="<?php echo $companyName; ?>" required>
                                        </div>
                                    </div>


                                    <div class="col-xl-4 col-lg-4 col-md-6 col-sm-12 col-4">
                                        <div class="form-group">
                                            <label class="form-label">Company Email Address <span class="red-text">*</span></label>
											<input type="email" name="Email" class="form-control" placeholder="Enter Email Address" value="<?php echo $companyEmail; ?>" required>
                                        </div>
                                    </div>
                                     <div class="col-xl-4 col-lg-4 col-md-6 col-sm-12 col-4">
                                        <div class="form-group">
                                            <label class="form-label">Phone Number <span class="red-text">*</span></label>
											<input type="text" name="Mobile" class="form-control" placeholder="Enter Phone Number" value="<?php echo $companyMobile; ?>" required>
                                        </div>
                                    </div>
                                    
                                    
                                    <div class="col-xl-8 col-lg-8 col-md-8 col-sm-12 col-8">
                                        <div class="form-group">
                                            <label class="form-label">Fax Number <span class="red-text">*</span></label>
									        <input type="text" name="Fax" class="form-control" placeholder="Enter Fax" value="<?php echo $companyFax; ?>" required>
                                        </div>
                                    </div>
                                    
                                    
                                    <div class="col-xl-4 col-lg-4 col-md-4 col-sm-12 col-4">
                                        <div class="form-group">
                                            <label class="form-label">Website <span class="red-text">*</span></label>
											<input type="text" name="Website" class="form-control" placeholder="Enter Website" value="<?php echo $companyWebsite; ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
                                            <div class="form-group">
                                                <label for="About_us" class="form-label">About Us <span class="red-text">*</span></label>
                                                <textarea class="form-control" rows="2" cols="45" name="About_us" placeholder="Enter about-us details" id="editor1" required><?php echo $companyAboutUs; ?></textarea>
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
          
            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header table-card-header">
                            <h6><span data-feather="map-pin"></span> OUR ADDRESS</h6>
                            <hr/>
                            <form method="post" action="">
                                <div class="row">
                                    <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 col-6">
                                        <div class="form-group">
                                            <label class="form-label">Address <span class="red-text">*</span></label>
										    <input type="text" name="Address" class="form-control" placeholder="Enter Address" value="<?php echo $Address; ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 col-6">
                                        <div class="form-group">
                                            <label class="form-label">City <span class="red-text">*</span></label>
										    <input type="text" name="City" class="form-control" placeholder="Enter City" value="<?php echo $City; ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 col-6">
                                        <div class="form-group">
                                            <label class="form-label">State / Province <span class="red-text">*</span></label>
											<input type="text" name="State" class="form-control" placeholder="Enter Company State" value="<?php echo $State; ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 col-6">
                                        <div class="form-group">
                                            <label class="form-label">Country <span class="red-text">*</span></label>
											<input type="text" name="Country" class="form-control" placeholder="Enter Country" value="<?php echo $Country; ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 col-6">
                                        <div class="form-group">
                                            <label class="form-label">Postal Code <span class="red-text">*</span></label>
											<input type="text" name="Postalcode" class="form-control" placeholder="Enter Postal Code" value="<?php echo $Postal_Code; ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">

                                        <button type="submit" class="btn btn-secondary" name="submit2" id="submit">
                                            <i class="feather icon-save lg"></i>&nbsp; Save
                                        </button>
                                   </div>
                                 </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header table-card-header">
                            <h6><span data-feather="at-sign"></span> SOCIAL LINKS</h6>
                            <hr/>
                            <form method="post" action="">
                                <div class="row">
                                    <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 col-6">
                                        <div class="form-group">
                                            <label class="form-label">Instagram</label>
										    <input type="text" name="Instagram" class="form-control" placeholder="Link Instagram" value="<?php echo $Instagram; ?>">
                                        </div>
                                    </div>
                                    <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 col-6">
                                        <div class="form-group">
                                            <label class="form-label">Facebook</label>
										    <input type="text" name="Facebook" class="form-control" placeholder="Link Facebook" value="<?php echo $Facebook; ?>">
                                        </div>
                                    </div>
                                    <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 col-6">
                                        <div class="form-group">
                                            <label class="form-label">Twitter</label>
											<input type="text" name="Twitter" class="form-control" placeholder="Link Twitter" value="<?php echo $Twitter; ?>">
                                        </div>
                                    </div>
                                    <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 col-6">
                                        <div class="form-group">
                                            <label class="form-label">WhatsApp</label>
											<input type="text" name="Whatsapp" class="form-control" placeholder="Link WhatsApp" value="<?php echo $WhatsApp; ?>">
                                        </div>
                                    </div>
                                    <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 col-6">
                                        <div class="form-group">
                                            <label class="form-label">Youtube</label>
											<input type="text" name="Youtube" class="form-control" placeholder="Link Youtube" value="<?php echo $Youtube; ?>">
                                        </div>
                                    </div>
                                    <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 col-6">
                                        <div class="form-group">
                                            <label class="form-label">Linkedin</label>
											<input type="text" name="Linkedin" class="form-control" placeholder="Link Linkedin" value="<?php echo $Linkedin;?>">
                                        </div>
                                    </div>
                                    <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 col-6">
                                        <div class="form-group">
                                            <label class="form-label">Pinterest</label>
											<input type="text" name="Pinterest" class="form-control" placeholder="Link Pinterest" value="<?php echo $Pinterest;?>">
                                        </div>
                                    </div>
                                    
                                    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">

                                        <button type="submit" class="btn btn-secondary" name="submit3" id="submit">
                                            <i class="feather icon-save lg"></i>&nbsp; Save
                                        </button>
                                   </div>
                                 </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header table-card-header">
                            <h6><span data-feather="clock"></span> LOCALIZATION</h6>
                            <hr/>
                            <form method="post" action="">
                                <div class="row">
                                    <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 col-6">
                                        <div class="form-group">
                                            <label class="form-label">Language <span class="red-text">*</span></label>
										    <input type="text" name="language" class="form-control" placeholder="Enter the Language of the Website" value="<?php echo $Language; ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
    									<div class="form-group">
        									<label for="status" class="form-label">Timezone <span class="red-text">*</span></label>
        									<select id="" name="timezone" class="form-control" required>
            									<option value="" disabled>Choose</option>
            									<option value="UTC+04:00" <?php if ($Timezone == "UTC+04:00") echo "selected"; ?>>UTC+04:00</option>
            									<option value="UTC+05:30" <?php if ($Timezone == "UTC+05:30") echo "selected"; ?>>UTC+05:30</option>
        									</select>
        									<small class="text-muted">Select Time zone in website.</small>
    									</div>
									</div>
									<div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 col-6">
    									<div class="form-group">
        									<label class="form-label">Financial Year <span class="red-text">*</span></label>
        									<input type="text" name="year" class="form-control" placeholder="Enter year for finance" value="<?php echo $FinancialYear; ?>" required>
    									</div>
									</div>
									<div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
    									<div class="form-group">
        									<label for="status" class="form-label">Date format <span class="red-text">*</span></label>
        										<select id="" name="date" class="form-control" required>
           	 										<option value="" disabled>Choose</option>
            										<option value="DD-MM-YYYY" <?php if ($DateFormat == "DD-MM-YYYY") echo "selected"; ?>>DD-MM-YYYY</option>
            										<option value="MM-DD-YYYY" <?php if ($DateFormat == "MM-DD-YYYY") echo "selected"; ?>>MM-DD-YYYY</option>
        										</select>
        										<small class="text-muted">Select date format to display in website.</small>
    									</div>
									</div>
									<div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
    									<div class="form-group">
        									<label for="status" class="form-label">Time Format <span class="red-text">*</span></label>
        										<select id="" name="timeformat" class="form-control" required>
            										<option value="" disabled>Choose</option>
            										<option value="12 Hours" <?php if ($TimeFormat == "12 Hours") echo "selected"; ?>>12 Hours</option>
           	 										<option value="24 Hours" <?php if ($TimeFormat == "24 Hours") echo "selected"; ?>>24 Hours</option>
        										</select>
        										<small class="text-muted">Select time format to display in website.</small>
    									</div>
									</div>
									<div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
    									<div class="form-group">
        									<label for="status" class="form-label">Starting Month <span class="red-text">*</span></label>
        										<select id="" name="month" class="form-control" required>
            										<option value="" disabled>Choose</option>
            										<option value="January" <?php if ($StartingMonth == "January") echo "selected"; ?>>January</option>
            										<option value="February" <?php if ($StartingMonth == "February") echo "selected"; ?>>February</option>
            										<option value="March" <?php if ($StartingMonth == "March") echo "selected"; ?>>March</option>
            										<option value="April" <?php if ($StartingMonth == "April") echo "selected"; ?>>April</option>
        										</select>
        										<small class="text-muted">Select starting month to display.</small>
    									</div>
									</div>                   
                                    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
                                        <button type="submit" class="btn btn-secondary" name="submit4" id="submit">
                                            <i class="feather icon-save lg"></i>&nbsp; Save
                                        </button>
                                   </div>
                                 </div>
                            </form>
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
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <script>
    feather.replace(); // Initialize Feather Icons
    </script>
    <script>
    $(document).ready(function() {
        $("#goldmessage").delay(5000).slideUp(300);
    });
    </script>
</body>

</html>