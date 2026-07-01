<?php
session_start();
include("db/config.php");

$msg = "";
$upload_directory = "popup/";

if (!isset($_SESSION["login_user"])) {
    header("location: index.php");
}

// Fetch Google reCAPTCHA
$google_result = mysqli_query($db, "SELECT * FROM recaptcha_settings WHERE provider = 'google' LIMIT 1");
$google = mysqli_fetch_assoc($google_result);

$sitekey = isset($google['site_key']) ? $google['site_key'] : '';
$secretKey = isset($google['secret_key']) ? $google['secret_key'] : '';
$google_id = isset($google['id']) ? $google['id'] : '';
$existingGStatus = $google['status'];

// Fetch Cloudflare Turnstile
$cf_result = mysqli_query($db, "SELECT * FROM recaptcha_settings WHERE provider = 'cloudflare' LIMIT 1");
$cf = mysqli_fetch_assoc($cf_result);

$cf_sitekey = isset($cf['site_key']) ? $cf['site_key'] : '';
$cf_secretKey = isset($cf['secret_key']) ? $cf['secret_key'] : '';
$cf_id = isset($cf['id']) ? $cf['id'] : '';
$existingCstatus = $cf['status'];

$result1 = mysqli_query($db, "SELECT * FROM smtp_email");
$row1 = mysqli_fetch_assoc($result1);

$Address_smtp = isset($row1['from_email']) ? $row1['from_email'] : '';
$password_smtp = isset($row1['password']) ? $row1['password'] : '';
$host_smtp = isset($row1['host']) ? $row1['host'] : '';
$port_smtp = isset($row1['port']) ? $row1['port'] : '';
$s = isset($row1['smtp_id']) ? $row1['smtp_id'] : '';

$result2 = mysqli_query($db, "SELECT * FROM login_settings");
$row2 = mysqli_fetch_assoc($result2);

$Number = isset($row2['helpdesk_no']) ? $row2['helpdesk_no'] : '';
$l = isset($row2['id']) ? $row2['id'] : '';

// ✅ NEW: Fetch existing letterhead and certificate paths
$existingLetterhead = isset($row2['letterhead_pdf']) ? $row2['letterhead_pdf'] : '';
$existingCertificate = isset($row2['certificate_pdf']) ? $row2['certificate_pdf'] : '';

$copy = mysqli_query($db, "SELECT * FROM copyright");
$copyres = mysqli_fetch_assoc($copy);
$Name = isset($copyres['name']) ? $copyres['name'] : '';
$status = isset($copyres['status']) ? $copyres['status'] : '';
$link = isset($copyres['link']) ? $copyres['link'] : '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['submit1'])) {
        if (!empty($_POST['key']) && !empty($_POST['secret']) && !empty($_POST['status'])) {
            $key = mysqli_real_escape_string($db, $_POST['key']);
            $secret_key = mysqli_real_escape_string($db, $_POST['secret']);
            $status = $_POST['status'];

            $check_sql = "SELECT id FROM recaptcha_settings WHERE provider = 'google' LIMIT 1";
            $check_result = mysqli_query($db, $check_sql);

            if (mysqli_num_rows($check_result) > 0) {
                $update_sql = "UPDATE recaptcha_settings 
                           SET site_key = '$key', secret_key = '$secret_key', status = '$status', updated_at = NOW()
                           WHERE provider = 'google'";
                $query = mysqli_query($db, $update_sql);
            } else {
                $insert_sql = "INSERT INTO recaptcha_settings (provider, site_key, secret_key, status)
                           VALUES ('google', '$key', '$secret_key', '$status')";
                $query = mysqli_query($db, $insert_sql);
            }

            if ($query) {
                $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible fade show' role='alert'>
                <strong><i class='feather icon-check'></i> Success!</strong> Google reCAPTCHA settings updated.
                <button type='button' class='close' data-dismiss='alert'>&times;</button>
                </div>";
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            } else {
                echo "Error: " . mysqli_error($db);
            }
        }
    }

    // Handle Letterhead Upload (submit5)
    if (isset($_POST['submit5'])) {
        $letterhead_file = $_FILES['letterhead_pdf']['name'];
        
        if (!empty($letterhead_file)) {
            $temp_name = $_FILES['letterhead_pdf']['tmp_name'];
            $file_ext = strtolower(pathinfo($letterhead_file, PATHINFO_EXTENSION));
            $file_size = $_FILES['letterhead_pdf']['size'];
            
            if ($file_ext !== 'pdf') {
                $msg = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                    <strong><i class='feather icon-x'></i> Error!</strong> Only PDF files are allowed.
                    <button type='button' class='close' data-dismiss='alert'>&times;</button>
                </div>";
            } elseif ($file_size > 10 * 1024 * 1024) {
                $msg = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                    <strong><i class='feather icon-x'></i> Error!</strong> File size exceeds 10MB limit.
                    <button type='button' class='close' data-dismiss='alert'>&times;</button>
                </div>";
            } else {
                $letterhead_dir = "pdf/";
                if (!is_dir($letterhead_dir)) {
                    mkdir($letterhead_dir, 0777, true);
                }
                
                $unique_filename = "Letterhead_" . time() . "." . $file_ext;
                $target_path = $letterhead_dir . $unique_filename;
                
                if (move_uploaded_file($temp_name, $target_path)) {
                    // Delete old letterhead if exists and not default
                    if (!empty($existingLetterhead) && file_exists($existingLetterhead) && $existingLetterhead !== 'pdf/Letterhead.pdf') {
                        @unlink($existingLetterhead);
                    }
                    
                    $target_path_escaped = mysqli_real_escape_string($db, $target_path);
                    $update_sql = "UPDATE login_settings SET letterhead_pdf = '$target_path_escaped' WHERE id = $l";
                    if (mysqli_query($db, $update_sql)) {
                        $msg = "<div class='alert alert-success alert-dismissible fade show' role='alert'>
                            <strong><i class='feather icon-check'></i> Success!</strong> Letterhead uploaded successfully.
                            <button type='button' class='close' data-dismiss='alert'>&times;</button>
                        </div>";
                        $existingLetterhead = $target_path;
                    } else {
                        $msg = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                            <strong><i class='feather icon-x'></i> Error!</strong> Database update failed: " . mysqli_error($db) . "
                            <button type='button' class='close' data-dismiss='alert'>&times;</button>
                        </div>";
                    }
                } else {
                    $msg = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                        <strong><i class='feather icon-x'></i> Error!</strong> Failed to upload file.
                        <button type='button' class='close' data-dismiss='alert'>&times;</button>
                    </div>";
                }
            }
        } else {
            $msg = "<div class='alert alert-warning alert-dismissible fade show' role='alert'>
                <strong><i class='feather icon-alert-triangle'></i> Warning!</strong> Please select a PDF file.
                <button type='button' class='close' data-dismiss='alert'>&times;</button>
            </div>";
        }
    }

    // ✅ NEW: Handle Certificate Upload (submit6)
    if (isset($_POST['submit6'])) {
        $certificate_file = $_FILES['certificate_pdf']['name'];
        
        if (!empty($certificate_file)) {
            $temp_name = $_FILES['certificate_pdf']['tmp_name'];
            $file_ext = strtolower(pathinfo($certificate_file, PATHINFO_EXTENSION));
            $file_size = $_FILES['certificate_pdf']['size'];
            
            if ($file_ext !== 'pdf') {
                $msg = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                    <strong><i class='feather icon-x'></i> Error!</strong> Only PDF files are allowed for certificate.
                    <button type='button' class='close' data-dismiss='alert'>&times;</button>
                </div>";
            } elseif ($file_size > 10 * 1024 * 1024) {
                $msg = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                    <strong><i class='feather icon-x'></i> Error!</strong> File size exceeds 10MB limit.
                    <button type='button' class='close' data-dismiss='alert'>&times;</button>
                </div>";
            } else {
                $certificate_dir = "pdf/";
                if (!is_dir($certificate_dir)) {
                    mkdir($certificate_dir, 0777, true);
                }
                
                $unique_filename = "Certificate_" . time() . "." . $file_ext;
                $target_path = $certificate_dir . $unique_filename;
                
                if (move_uploaded_file($temp_name, $target_path)) {
                    // Delete old certificate if exists
                    if (!empty($existingCertificate) && file_exists($existingCertificate) && $existingCertificate !== 'pdf/Certificate.pdf') {
                        @unlink($existingCertificate);
                    }
                    
                    $target_path_escaped = mysqli_real_escape_string($db, $target_path);
                    $update_sql = "UPDATE login_settings SET certificate_pdf = '$target_path_escaped' WHERE id = $l";
                    if (mysqli_query($db, $update_sql)) {
                        $msg = "<div class='alert alert-success alert-dismissible fade show' role='alert'>
                            <strong><i class='feather icon-check'></i> Success!</strong> Certificate template uploaded successfully. This will be used globally for all certificates.
                            <button type='button' class='close' data-dismiss='alert'>&times;</button>
                        </div>";
                        $existingCertificate = $target_path;
                    } else {
                        $msg = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                            <strong><i class='feather icon-x'></i> Error!</strong> Database update failed: " . mysqli_error($db) . "
                            <button type='button' class='close' data-dismiss='alert'>&times;</button>
                        </div>";
                    }
                } else {
                    $msg = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                        <strong><i class='feather icon-x'></i> Error!</strong> Failed to upload certificate file.
                        <button type='button' class='close' data-dismiss='alert'>&times;</button>
                    </div>";
                }
            }
        } else {
            $msg = "<div class='alert alert-warning alert-dismissible fade show' role='alert'>
                <strong><i class='feather icon-alert-triangle'></i> Warning!</strong> Please select a PDF file for certificate.
                <button type='button' class='close' data-dismiss='alert'>&times;</button>
            </div>";
        }
    }

    if (isset($_POST['submit_cf'])) {
        if (!empty($_POST['cf_key']) && !empty($_POST['cf_secret']) && !empty($_POST['status'])) {
            $cf_key = mysqli_real_escape_string($db, $_POST['cf_key']);
            $cf_secret = mysqli_real_escape_string($db, $_POST['cf_secret']);
            $cf_status = $_POST['status'];

            $check_cf_sql = "SELECT id FROM recaptcha_settings WHERE provider = 'cloudflare' LIMIT 1";
            $cf_check_result = mysqli_query($db, $check_cf_sql);

            if (mysqli_num_rows($cf_check_result) > 0) {
                $update_cf_sql = "UPDATE recaptcha_settings 
                              SET site_key = '$cf_key', secret_key = '$cf_secret', status = '$cf_status', updated_at = NOW()
                              WHERE provider = 'cloudflare'";
                $cf_query = mysqli_query($db, $update_cf_sql);
            } else {
                $insert_cf_sql = "INSERT INTO recaptcha_settings (provider, site_key, secret_key, status)
                              VALUES ('cloudflare', '$cf_key', '$cf_secret', '$cf_status')";
                $cf_query = mysqli_query($db, $insert_cf_sql);
            }

            if ($cf_query) {
                $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible fade show' role='alert'>
                <strong><i class='feather icon-check'></i> Success!</strong> Cloudflare Turnstile settings updated.
                <button type='button' class='close' data-dismiss='alert'>&times;</button>
                </div>";
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            } else {
                echo "Error: " . mysqli_error($db);
            }
        }
    } elseif (isset($_POST['submit2'])) {
        $email = $_POST['from_email'];
        $password = $_POST['password'];
        $host = isset($_POST['host']) ? $_POST['host'] : '';
        $port = isset($_POST['port']) ? $_POST['port'] : '';

        $sql = "INSERT INTO smtp_email (smtp_id, from_email, password, host, port)
                VALUES ('$s', '$email', '$password', '$host', '$port')
                ON DUPLICATE KEY UPDATE from_email='$email', password='$password', host='$host', port='$port'";

        if ($db->query($sql) === TRUE) {
            $msg = "
                <div class='alert alert-success alert-dismissible fade show' role='alert' style='font-size:16px;' id='goldmessage'>
                <strong><i class='feather icon-check'></i>Success!</strong> The SMTP setting has been updated.
                <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                <span aria-hidden='true'>&times;</span>
                </button>
                </div>
            ";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            echo "Error: " . $sql . "<br>" . $db->error;
        }
    } elseif (isset($_POST['submit3'])) {
        $helpdeskNumber = $_POST["helpdesk_number"];

        $backendPanelLogoDirectory = "logo/backend_panel_logo/";
        $faviconDirectory = "logo/favicon/";
        $landingPageLogoBlackDirectory = "logo/landing_page_logo_black/";
        $landingPageLogoWhiteDirectory = "logo/landing_page_logo_white/";

        if ($_FILES["logo"]["name"]) {
            $logoPath = $_FILES["logo"]["name"];
            $targetPath = $backendPanelLogoDirectory . $logoPath;
            move_uploaded_file($_FILES["logo"]["tmp_name"], $targetPath);
            $backendPanelLogoPath = $backendPanelLogoDirectory . $logoPath;

            if (!empty($row2['backend_panel_logo'])) {
                unlink($row2['backend_panel_logo']);
            }
        } else {
            $backendPanelLogoPath = $row2['backend_panel_logo'];
        }

        if ($_FILES["favicon"]["name"]) {
            $faviconPath = $_FILES["favicon"]["name"];
            $targetPath = $faviconDirectory . $faviconPath;
            move_uploaded_file($_FILES["favicon"]["tmp_name"], $targetPath);
            $faviconFilePath = $faviconDirectory . $faviconPath;

            if (!empty($row2['favicon'])) {
                unlink($row2['favicon']);
            }
        } else {
            $faviconFilePath = $row2['favicon'];
        }

        if ($_FILES["logo1"]["name"]) {
            $landingPageLogoPathBlack = $_FILES["logo1"]["name"];
            $targetPath = $landingPageLogoBlackDirectory . $landingPageLogoPathBlack;
            move_uploaded_file($_FILES["logo1"]["tmp_name"], $targetPath);
            $landingPageLogoFilePathBlack = $landingPageLogoBlackDirectory . $landingPageLogoPathBlack;

            if (!empty($row2['landing_page_logo_black'])) {
                unlink($row2['landing_page_logo_black']);
            }
        } else {
            $landingPageLogoFilePathBlack = $row2['landing_page_logo_black'];
        }

        if ($_FILES["logo2"]["name"]) {
            $landingPageLogoPathWhite = $_FILES["logo2"]["name"];
            $targetPath = $landingPageLogoWhiteDirectory . $landingPageLogoPathWhite;
            move_uploaded_file($_FILES["logo2"]["tmp_name"], $targetPath);
            $landingPageLogoFilePathWhite = $landingPageLogoWhiteDirectory . $landingPageLogoPathWhite;

            if (!empty($row2['landing_page_logo_white'])) {
                unlink($row2['landing_page_logo_white']);
            }
        } else {
            $landingPageLogoFilePathWhite = $row2['landing_page_logo_white'];
        }

        $sql = "INSERT INTO login_settings (id, backend_panel_logo, favicon, landing_page_logo_black, landing_page_logo_white, helpdesk_no)
                VALUES ('$l', '$backendPanelLogoPath', '$faviconFilePath', '$landingPageLogoFilePathBlack', '$landingPageLogoFilePathWhite', '$helpdeskNumber')
                ON DUPLICATE KEY UPDATE backend_panel_logo='$backendPanelLogoPath', favicon='$faviconFilePath', landing_page_logo_black='$landingPageLogoFilePathBlack', landing_page_logo_white='$landingPageLogoFilePathWhite', helpdesk_no='$helpdeskNumber'";

        if ($db->query($sql) === TRUE) {
            $msg = "
        <div class='alert alert-success alert-dismissible fade show' role='alert' style='font-size:16px;' id='goldmessage'>
        <strong><i class='feather icon-check'></i>Success!</strong> The Logo and Helpdesk Settings have been updated.
        <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
        <span aria-hidden='true'>&times;</span>
        </button>
        </div>
    ";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            echo "Error: " . $sql . "<br>" . $db->error;
        }
    } elseif (isset($_POST['submit4'])) {
        $map_key = $_POST['map_key'];

        $sql = "INSERT INTO map (map_api_key)
                    VALUES ('$map_key')
                    ON DUPLICATE KEY UPDATE map_api_key='$map_key'";

        if ($db->query($sql) === TRUE) {
            $msg = "
                    <div class='alert alert-success alert-dismissible fade show' role='alert' style='font-size:16px;' id='goldmessage'>
                    <strong><i class='feather icon-check'></i>Success!</strong> The Google Maps Api key has been updated.
                    <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                    <span aria-hidden='true'>&times;</span>
                    </button>
                    </div>
                ";
        } else {
            echo "Error: " . $sql . "<br>" . $db->error;
        }
    } elseif (isset($_POST['copysubmit'])) {
        $Name = $_POST['name'] ?? '';
        $status = $_POST['status'] ?? '';
        $link = $_POST['link'] ?? '';

        $check = $db->query("SELECT id FROM copyright LIMIT 1");
        if ($check->num_rows == 0) {
            $stmt = $db->prepare("INSERT INTO copyright (name, status, link) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $Name, $status, $link);
        } else {
            $stmt = $db->prepare("UPDATE copyright SET name = ?, status = ?, link = ? LIMIT 1");
            $stmt->bind_param("sss", $Name, $status, $link);
        }

        if ($stmt->execute()) {
            $msg = "
        <div class='alert alert-success alert-dismissible fade show' role='alert' style='font-size:16px;' id='goldmessage'>
            <strong><i class='feather icon-check'></i> Success!</strong> Copyright information has been saved.
            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                <span aria-hidden='true'>&times;</span>
            </button>
        </div>";
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
    }
}

$upload_directory = "popup/";

if (isset($_POST['submit'])) {
    $status = $_POST['status'];
    $temp_name = $_FILES["uploaded_file"]["tmp_name"];
    $original_name = $_FILES["uploaded_file"]["name"];
    $file_size = $_FILES["uploaded_file"]["size"];
    $msg = '';

    if ($status == '0') {
        $updateStatusQuery = "UPDATE cover_photo SET status = '$status' LIMIT 1";
        if (mysqli_query($db, $updateStatusQuery)) {
            $msg = "
                <div class='alert alert-success alert-dismissible fade show' role='alert' style='font-size:16px;' id='goldmessage'>
                    <strong><i class='feather icon-check'></i>Success!</strong> Image disabled successfully.
                    <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                        <span aria-hidden='true'>&times;</span>
                    </button>
                </div>";
        } else {
            $msg = "<div class='alert alert-danger alert-dismissible fade show' role='alert' style='font-size:16px;' id='goldmessage'>
                    <strong><i class='feather icon-check'></i>Error!</strong> Unable to disable the image.
                    <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                        <span aria-hidden='true'>&times;</span>
                    </button>
                </div>";
        }
    } else {
        if ($temp_name) {
            $allowed_types = ["image/jpeg", "image/png", "image/gif", "image/svg+xml", "image/webp"];
            $file_type = mime_content_type($temp_name);

            if (!in_array($file_type, $allowed_types)) {
                $msg = "<div class='alert alert-danger alert-dismissible fade show' role='alert' style='font-size:16px;' id='goldmessage'>
                            <strong><i class='feather icon-check'></i>Error!</strong> Please upload a valid image file.
                            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                                <span aria-hidden='true'>&times;</span>
                            </button>
                        </div>";
            } else if ($file_size < 2 * 1024 * 1024) {
                $unique_filename = uniqid() . '_' . $original_name;
                move_uploaded_file($temp_name, $upload_directory . $unique_filename);

                $updateQuery = "UPDATE cover_photo SET imgurl = '$unique_filename', status = '$status' LIMIT 1";
                if (mysqli_query($db, $updateQuery)) {
                    $msg = "
                        <div class='alert alert-success alert-dismissible fade show' role='alert' style='font-size:16px;' id='goldmessage'>
                            <strong><i class='feather icon-check'></i>Success!</strong> Image updated successfully.
                            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                                <span aria-hidden='true'>&times;</span>
                            </button>
                        </div>";
                } else {
                    $msg = "<div class='alert alert-danger alert-dismissible fade show' role='alert' style='font-size:16px;' id='goldmessage'>
                            <strong><i class='feather icon-check'></i>Error!</strong> Unable to update the image.
                            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                                <span aria-hidden='true'>&times;</span>
                            </button>
                        </div>";
                }
            } else {
                $msg = "<div class='alert alert-danger alert-dismissible fade show' role='alert' style='font-size:16px;' id='goldmessage'>
                            <strong><i class='feather icon-check'></i>Error!</strong> File size exceeds the limit of 2MB.
                            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                                <span aria-hidden='true'>&times;</span>
                            </button>
                        </div>";
            }
        } else {
            $updateQuery = "UPDATE cover_photo SET status = '$status' LIMIT 1";
            if (mysqli_query($db, $updateQuery)) {
                $msg = "
                    <div class='alert alert-success alert-dismissible fade show' role='alert' style='font-size:16px;' id='goldmessage'>
                        <strong><i class='feather icon-check'></i>Success!</strong> Image enabled successfully.
                        <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                            <span aria-hidden='true'>&times;</span>
                        </button>
                    </div>";
            } else {
                $msg = "<div class='alert alert-danger alert-dismissible fade show' role='alert' style='font-size:16px;' id='goldmessage'>
                        <strong><i class='feather icon-check'></i>Error!</strong> Unable to enable the image.
                        <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                            <span aria-hidden='true'>&times;</span>
                        </button>
                    </div>";
            }
        }
    }
}

$query = "SELECT * FROM login_settings";
$settingsResult = mysqli_query($db, $query);
$settings = mysqli_fetch_assoc($settingsResult);

$logoPath = $settings['backend_panel_logo'];
$helpdeskNumber = $settings['helpdesk_no'];
$favicon = $settings['favicon'];
?>

<!DOCTYPE html>
<html lang="en">

<meta http-equiv="content-type" content="text/html;charset=UTF-8" />

<head>
    <title>System Settings </title>

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
        .letterhead-preview, .certificate-preview {
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 5px;
            background: #f9f9f9;
        }
        .letterhead-preview iframe, .certificate-preview iframe {
            width: 100%;
            height: 250px;
            border: 1px solid #ccc;
            margin-top: 10px;
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
                                <h5 class="m-b-10">System Settings</h5>
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
                            if ($msg) {
                                echo $msg;
                            }
                            ?>
                            <br />
                            <h6><span data-feather="airplay"></span> DISPLAY RECAPTCHA</h6>
                            <hr />

                            <div class="mb-3">
                                <button type="button" class="btn btn-outline-primary me-2" id="googleBtn">Google</button>
                                <button type="button" class="btn btn-outline-primary" id="cloudflareBtn">Cloudflare</button>
                            </div>

                            <!-- Google Form -->
                            <div id="googleForm">
                                <form method="post" action="">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-label">Google reCAPTCHA Site Key <span class="red-text">*</span></label>
                                                <input type="text" name="key" class="form-control" placeholder="Enter the site key" value="<?php echo $sitekey; ?>" required>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-label">Google reCAPTCHA Secret Key <span class="red-text">*</span></label>
                                                <input type="text" name="secret" class="form-control" placeholder="Enter the secret key" value="<?php echo $secretKey; ?>" required>
                                            </div>
                                        </div>

                                        <div class="col-xl-4 col-lg-4 col-md-4 col-sm-12 col-12" style="margin-top: 6px">
                                            <div class="form-group">Status <span class="red-text">*</span>
                                                <label class="sr-only control-label" for="name">Status<span class=" "></span></label>
                                                <select id="status" name="status" class="form-control"
                                                    oninvalid="this.setCustomValidity('Please Select Status')"
                                                    oninput="setCustomValidity('')" required>
                                                    <option value="">Choose</option>
                                                    <option value="active" <?= $existingGStatus === 'active' ? 'selected' : '' ?>>Active</option>
                                                    <option value="inactive" <?= $existingGStatus === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <button type="submit" class="btn btn-secondary" name="submit1">
                                                <i class="feather icon-save lg"></i>&nbsp; Save
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <!-- Cloudflare Form -->
                            <div id="cloudflareForm" style="display:none;">
                                <form method="post" action="">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-label">Cloudflare Turnstile Site Key <span class="red-text">*</span></label>
                                                <input type="text" name="cf_key" class="form-control" placeholder="Enter Cloudflare site key" value="<?php echo htmlspecialchars($cf_sitekey); ?>">
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-label">Cloudflare Secret Key <span class="red-text">*</span></label>
                                                <input type="text" name="cf_secret" class="form-control" placeholder="Enter Cloudflare secret key" value="<?php echo htmlspecialchars($cf_secretKey); ?>">
                                            </div>
                                        </div>

                                        <div class="col-xl-4 col-lg-4 col-md-4 col-sm-12 col-12" style="margin-top: 6px">
                                            <div class="form-group">Status <span class="red-text">*</span>
                                                <label class="sr-only control-label" for="name">Status<span class=" "></span></label>
                                                <select id="status" name="status" class="form-control"
                                                    oninvalid="this.setCustomValidity('Please Select Status')"
                                                    oninput="setCustomValidity('')" required>
                                                    <option value="">Choose</option>
                                                    <option value="active" <?= $existingCstatus === 'active' ? 'selected' : '' ?>>Active</option>
                                                    <option value="inactive" <?= $existingCstatus === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-secondary" name="submit_cf">
                                                <i class="feather icon-save lg"></i>&nbsp; Save
                                            </button>
                                        </div>
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
                            <h6><span data-feather="inbox"></span> SMTP</h6>
                            <hr />
                            <form method="post" action="">
                                <div class="row">
                                    <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 col-6">
                                        <div class="form-group">
                                            <label class="form-label">Email From Address <span class="red-text">*</span></label>
                                            <input type="text" name="from_email" class="form-control" placeholder="Enter the email from address" value="<?php echo $Address_smtp; ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 col-6">
                                        <div class="form-group">
                                            <label class="form-label">Email Password <span class="red-text">*</span></label>
                                            <input type="text" name="password" class="form-control" placeholder="Enter the email password" value="<?php echo $password_smtp; ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 col-6">
                                        <div class="form-group">
                                            <label class="form-label">Email Host <span class="red-text">*</span></label>
                                            <input type="text" name="host" class="form-control" placeholder="Enter the email host" value="<?php echo $host_smtp; ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 col-6">
                                        <div class="form-group">
                                            <label class="form-label">Email Port <span class="red-text">*</span></label>
                                            <input type="text" name="port" class="form-control" placeholder="Enter the email port" value="<?php echo $port_smtp; ?>" required>
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
                            <h6><span data-feather="image"></span> LOGO AND HELPDESK</h6>
                            <hr />
                            <form method="post" action="" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 col-6">
                                        <div class="form-group">
                                            <label class="form-label">Backend Panel Logo <span class="red-text">*</span></label>
                                            <input type="file" class="form-control" name="logo">
                                            <small class="text-muted">Leave it blank if you don't want to change the image.</small>
                                        </div>

                                        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
                                            <div class="form-group">
                                                <label class="form-label"><span class="red-text">*</span> Recent Backend Panel Logo</label>
                                                <?php if (!empty($row2['backend_panel_logo'])): ?>
                                                    <img src="<?php echo $row2['backend_panel_logo']; ?>" alt="Current Logo" style="max-width: 100px; max-height: 100px;">
                                                <?php else: ?>
                                                    <span>No recent logo available</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <br />
                                        <br />
                                    </div>

                                    <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 col-6">
                                        <div class="form-group">
                                            <label class="form-label">Favicon <span class="red-text">*</span></label>
                                            <input type="file" class="form-control" name="favicon">
                                            <small class="text-muted">Leave it blank if you don't want to change the favicon.</small>
                                        </div>

                                        <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 col-6">
                                            <div class="form-group">
                                                <label class="form-label"><span class="red-text">*</span> Recent Favicon</label>
                                                <?php if (!empty($row2['favicon'])): ?>
                                                    <img src="<?php echo $row2['favicon']; ?>" alt="Current Logo" style="max-width: 50px; max-height: 50px;">
                                                <?php else: ?>
                                                    <span>No recent logo available</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 col-6">
                                        <div class="form-group">
                                            <label class="form-label">Landing Page Logo Black <span class="red-text">*</span></label>
                                            <input type="file" class="form-control" name="logo1">
                                            <small class="text-muted">Leave it blank if you don't want to change the image.</small>
                                        </div>

                                        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
                                            <div class="form-group">
                                                <label class="form-label"><span class="red-text">*</span> Recent Landing Page Logo Black</label>
                                                <?php if (!empty($row2['landing_page_logo_black'])): ?>
                                                    <img src="<?php echo $row2['landing_page_logo_black']; ?>" alt="Current Logo" style="max-width: 100px; max-height: 100px;">
                                                <?php else: ?>
                                                    <span>No recent logo available</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <br />
                                        <br />
                                    </div>

                                    <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 col-6">
                                        <div class="form-group">
                                            <label class="form-label">Landing Page Logo White <span class="red-text">*</span></label>
                                            <input type="file" class="form-control" name="logo2">
                                            <small class="text-muted">Leave it blank if you don't want to change the image.</small>
                                        </div>

                                        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
                                            <div class="form-group">
                                                <label class="form-label"><span class="red-text">*</span> Recent Landing Page Logo White</label>
                                                <?php if (!empty($row2['landing_page_logo_white'])): ?>
                                                    <img src="<?php echo $row2['landing_page_logo_white']; ?>" alt="Current Logo" style="max-width: 100px; max-height: 100px;">
                                                <?php else: ?>
                                                    <span>No recent logo available</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 col-6">
                                        <div class="form-group">
                                            <label class="form-label">Helpdesk Number <span class="red-text">*</span></label>
                                            <input type="text" class="form-control" name="helpdesk_number" placeholder="Enter the helpdesk Number" value="<?php echo $Number; ?>" required>
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

            <!-- ✅ LETTERHEAD UPLOAD SECTION -->
            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header table-card-header">
                            <h6><span data-feather="file-text"></span> GLOBAL LETTERHEAD (For All Offer Letters)</h6>
                            <hr />
                            <div class="alert alert-info">
                                <strong><i class="feather icon-info"></i> Info:</strong> 
                               This letterhead will automatically be used in all offer letters (Intern & Employee). Upload once, it will apply everywhere!
                            </div>
                            
                            <form method="post" action="" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">Upload Letterhead PDF <span class="red-text">*</span></label>
                                            <input type="file" name="letterhead_pdf" class="form-control" accept=".pdf" required>
                                            <small class="text-muted">
                                                <i class="feather icon-alert-circle"></i> 
                                                Only PDF files allowed (Max 10MB). Recommended size: A4 (210mm x 297mm)
                                            </small>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">Current Letterhead</label>
                                            <?php if (!empty($existingLetterhead) && file_exists($existingLetterhead)): ?>
                                                <div class="letterhead-preview">
                                                    <p><strong>File:</strong> <?php echo basename($existingLetterhead); ?></p>
                                                    <p><strong>Path:</strong> <code><?php echo $existingLetterhead; ?></code></p>
                                                    <iframe src="<?php echo $existingLetterhead; ?>#toolbar=0&navpanes=0&scrollbar=0" frameborder="0"></iframe>
                                                </div>
                                            <?php else: ?>
                                                <div class="alert alert-warning">No letterhead uploaded yet. Default will be used.</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="col-xl-12">
                                        <button type="submit" class="btn btn-primary" name="submit5">
                                            <i class="feather icon-upload"></i>&nbsp; Upload Letterhead
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ✅ NEW: CERTIFICATE UPLOAD SECTION -->
            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header table-card-header">
                            <h6><span data-feather="award"></span> GLOBAL CERTIFICATE TEMPLATE (For All Certificates)</h6>
                            <hr />
                            <div class="alert alert-info">
                                <strong><i class="feather icon-info"></i> Info:</strong> 
                               This certificate template will automatically be used in all certificates (Internship, Completion, etc.). Upload once, it will apply everywhere!
                            </div>
                            
                            <form method="post" action="" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">Upload Certificate PDF <span class="red-text">*</span></label>
                                            <input type="file" name="certificate_pdf" class="form-control" accept=".pdf" required>
                                            <small class="text-muted">
                                                <i class="feather icon-alert-circle"></i> 
                                                Only PDF files allowed (Max 10MB). Recommended size: A4 Landscape (297mm x 210mm)
                                            </small>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">Current Certificate Template</label>
                                            <?php if (!empty($existingCertificate) && file_exists($existingCertificate)): ?>
                                                <div class="certificate-preview">
                                                    <p><strong>File:</strong> <?php echo basename($existingCertificate); ?></p>
                                                    <p><strong>Path:</strong> <code><?php echo $existingCertificate; ?></code></p>
                                                    <iframe src="<?php echo $existingCertificate; ?>#toolbar=0&navpanes=0&scrollbar=0" frameborder="0"></iframe>
                                                </div>
                                            <?php else: ?>
                                                <div class="alert alert-warning">No certificate template uploaded yet. Default will be used.</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="col-xl-12">
                                        <button type="submit" class="btn btn-primary" name="submit6">
                                            <i class="feather icon-upload"></i>&nbsp; Upload Certificate Template
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
                            <h6><span data-feather="map"></span> Cover Photo</h6>
                            <hr />
                            <?php
                            $sliderId = 1;
                            $fetchStatusQuery = "SELECT status FROM cover_photo WHERE id = $sliderId LIMIT 1";
                            $statusResult = mysqli_query($db, $fetchStatusQuery);

                            $existingStatus = null;
                            if ($statusResult && mysqli_num_rows($statusResult) > 0) {
                                $row = mysqli_fetch_assoc($statusResult);
                                $existingStatus = $row['status'];
                            }
                            ?>

                            <form class="contact-us" method="post" action="" enctype="multipart/form-data" autocomplete="off">
                                <div class="">
                                    <div class="row">
                                        <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
                                            <div class="form-group">
                                                <label for="name" class="form-label">Image (924px x 601px)*</label>
                                                <div class="input-group">
                                                    <input type="file"
                                                        name="uploaded_file"
                                                        id="imageInput"
                                                        class="form-control input-md mr-2">
                                                    <div class="input-group-append" id="previewButtonContainer" style="display: none;">
                                                        <button type="button" id="previewBtn" class="btn btn-secondary" onclick="showPreviewModal()">
                                                            <i class="far fa-eye"></i> Preview *
                                                        </button>
                                                    </div>
                                                </div>
                                                <small class="text-muted">Leave it blank if you don't want to change the image.</small>
                                            </div>
                                        </div>
                                        <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
                                            <?php
                                            $upload_directory = "popup/";
                                            $selectExistingImagesQuery = "SELECT imgurl FROM cover_photo LIMIT 1";
                                            $existingImagesResult = mysqli_query($db, $selectExistingImagesQuery);

                                            if ($existingImagesResult && mysqli_num_rows($existingImagesResult) > 0) {
                                                echo '<div class="form-group">';
                                                echo '<label for="current_images" class="form-label">Recent Image *</label>';
                                                echo '<div class="row">';

                                                while ($row = mysqli_fetch_assoc($existingImagesResult)) {
                                                    $imageFilename = $row['imgurl'];
                                                    $imagePath = $upload_directory . $imageFilename;

                                                    echo '<div class="col-xl-3 col-lg-3 col-md-4 col-sm-6 col-12 mb-3">';
                                                    echo '<img src="' . $imagePath . '" class="img-fluid" alt="Uploaded Image" style="height: 113px; margin-top: -2px; width: 475px;">';
                                                    echo '</div>';
                                                }

                                                echo '</div>';
                                                echo '</div>';
                                            } else {
                                                echo '<p>No images found.</p>';
                                            }
                                            ?>
                                        </div>

                                        <div class="col-xl-6 col-lg-6 col-md-4 col-sm-12 col-12" style="margin-top: -68px">
                                            <div class="form-group">Status <span class="red-text">*</span>
                                                <label class="sr-only control-label" for="name">Status<span class=" "></span></label>
                                                <select id="status" name="status" class="form-control"
                                                    oninvalid="this.setCustomValidity('Please Select Status')"
                                                    oninput="setCustomValidity('')" required>
                                                    <option value="">Choose</option>
                                                    <option value="1" <?= $existingStatus === '1' ? 'selected' : '' ?>>Enable</option>
                                                    <option value="0" <?= $existingStatus === '0' ? 'selected' : '' ?>>Disable</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
                                            <button type="submit" class="btn btn-secondary" name="submit" id="submit">
                                                <i class="feather icon-save"></i>&nbsp; Save
                                            </button>
                                        </div>
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
                            <h6><span data-feather="edit"></span> Copyright</h6>
                            <hr />

                            <form class="contact-us" method="post" action="" enctype="multipart/form-data" autocomplete="off">
                                <div class="">
                                    <div class="row">
                                        <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
                                            <div class="form-group">
                                                <label for="name" class="form-label">Developed By</label>
                                                <div class="input-group">
                                                    <input type="text" name="name" class="form-control" placeholder="" value="<?php echo htmlspecialchars($Name); ?>">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
                                            <div class="form-group">
                                                <label for="name" class="form-label">URL</label>
                                                <div class="input-group">
                                                    <input type="text" name="link" class="form-control" placeholder="" value="<?php echo htmlspecialchars($link); ?>">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-xl-6 col-lg-6 col-md-4 col-sm-12 col-12" style="margin-top: 8px">
                                            <div class="form-group">Status <span class="red-text">*</span>
                                                <label class="sr-only control-label" for="name">Status<span class=" "></span></label>
                                                <select id="status" name="status" class="form-control"
                                                    oninvalid="this.setCustomValidity('Please Select Status')"
                                                    oninput="setCustomValidity('')" required>
                                                    <option value="">Choose</option>
                                                    <option value="1" <?= $existingStatus === '1' ? 'selected' : '' ?>>Enable</option>
                                                    <option value="0" <?= $existingStatus === '0' ? 'selected' : '' ?>>Disable</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
                                            <button type="submit" class="btn btn-secondary" name="copysubmit" id="submit">
                                                <i class="feather icon-save"></i>&nbsp; Save
                                            </button>
                                        </div>
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
        feather.replace();
    </script>
    <script>
        $(document).ready(function() {
            $("#goldmessage").delay(5000).slideUp(300);
        });
    </script>
    <script>
        const googleBtn = document.getElementById('googleBtn');
        const cloudflareBtn = document.getElementById('cloudflareBtn');
        const googleForm = document.getElementById('googleForm');
        const cloudflareForm = document.getElementById('cloudflareForm');

        googleBtn.addEventListener('click', () => {
            googleForm.style.display = 'block';
            cloudflareForm.style.display = 'none';
            googleBtn.classList.add('btn-primary');
            googleBtn.classList.remove('btn-outline-primary');
            cloudflareBtn.classList.remove('btn-primary');
            cloudflareBtn.classList.add('btn-outline-primary');
        });

        cloudflareBtn.addEventListener('click', () => {
            googleForm.style.display = 'none';
            cloudflareForm.style.display = 'block';
            cloudflareBtn.classList.add('btn-primary');
            cloudflareBtn.classList.remove('btn-outline-primary');
            googleBtn.classList.remove('btn-primary');
            googleBtn.classList.add('btn-outline-primary');
        });
    </script>
</body>

</html>