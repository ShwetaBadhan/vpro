<?php
require("db/config.php");
error_reporting(0);
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $sql = "SELECT username FROM  admin WHERE activation_token = '"  . $token . "'";
    $re = mysqli_query($db, $sql);

    //$count=mysqli_num_rows($result);
    $row1 = mysqli_fetch_row($re);
    $id = $row1[0];

    if ($row1 > 0) {
        $updateSql = "UPDATE admin SET status = 1 WHERE username = '"  . $id . "'";
        mysqli_query($db, $updateSql);
        $msg = "Account activated successfully!";

        $sql = "UPDATE admin SET activation_token = 0 WHERE username = '"  . $id . "'";
        mysqli_query($db, $sql);
        header("refresh:5;url=index.php");
    } else {

        $msg = "Invalid or expired token.";
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Activation</title>
    <!-- Include Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-6 offset-md-3">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title" style="color:#000;"> Quote Tender Account Activation</h4>
                        <?php
                        if ($id) {

                            echo " <div class='alert alert-success alert-dismissible fade show' role='alert' style='font-size:16px;' id='successMessage'>
                            <strong><i class=' feather  icon icon-info'></i>Success! </strong>$msg.
                            
                          </div> ";
                        } else {
                            echo " <div class='alert alert-danger alert-dismissible fade show' role='alert' style='font-size:16px;' id='successMessage'>
                            <strong><i class=' feather  icon icon-info'></i>Error! </strong>$msg.
                            
                          </div> ";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Include Bootstrap JS (Optional) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>