<?php
session_start();
include("db/config.php");

// Function to delete an admin record
function deleteAdmin($db, $admin_id) {
    $delete_query = "DELETE FROM admin WHERE _id = '$admin_id'";
    return mysqli_query($db, $delete_query);
}

// ✅ Single delete
if (isset($_GET['id'])) {
    $admin_id = base64_decode($_GET['id']);
    $admin_id = mysqli_real_escape_string($db, $admin_id);

    if (deleteAdmin($db, $admin_id)) {
        $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible fade show' role='alert' style='font-size:16px;' id='gold'>
            <strong><i class='feather icon-check'></i> Deleted!</strong> User deleted successfully.
            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                <span aria-hidden='true'>&times;</span>
            </button>
        </div>";
    } else {
        $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible fade show' role='alert' style='font-size:16px;' id='gold'>
            <strong>Error!</strong> Failed to delete admin.
            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                <span aria-hidden='true'>&times;</span>
            </button>
        </div>";
    }

    header("Location: manage-user.php");
    exit();
}

// ✅ Multiple delete
if (isset($_POST['admin_ids'])) {
    $success = 0;
    foreach ($_POST['admin_ids'] as $encoded_id) {
        $admin_id = base64_decode($encoded_id);
        $admin_id = mysqli_real_escape_string($db, $admin_id);

        if (deleteAdmin($db, $admin_id)) {
            $success++;
        }
    }

    if ($success > 0) {
        $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible fade show' role='alert' style='font-size:16px;' id='gold'>
            <strong>Success!</strong> $success Admin(s) deleted successfully.
            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                <span aria-hidden='true'>&times;</span>
            </button>
        </div>";
    } else {
        $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible fade show' role='alert' style='font-size:16px;' id='gold'>
            <strong>Error!</strong> No admin records deleted.
            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                <span aria-hidden='true'>&times;</span>
            </button>
        </div>";
    }

    header("Location: manage-user.php");
    exit();
}

// Redirect if no ID provided
header("Location: manage-user.php");
exit();
?>
