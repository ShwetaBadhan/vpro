<?php
session_start(); // ✅ Needed to use $_SESSION
include("db/config.php");

// Function to delete role and return success status
function deleteRole($db, $role_id) {
    $delete_query = "DELETE FROM roles WHERE role_id = '$role_id'";
    return mysqli_query($db, $delete_query); // ✅ Return true/false
}

// ✅ Single delete
if (isset($_GET['id'])) {
    $role_id = base64_decode($_GET['id']);
    $role_id = mysqli_real_escape_string($db, $role_id);
    
    if (deleteRole($db, $role_id)) {
        $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible fade show' role='alert' style='font-size:16px;' id='gold'>
            <strong><i class='feather icon-check'></i> Deleted!</strong> Role deleted successfully.
            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                <span aria-hidden='true'>&times;</span>
            </button>
        </div>";
    } else {
        $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible fade show' role='alert' style='font-size:16px;' id='gold'>
            <strong>Error!</strong> Failed to delete role.
            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                <span aria-hidden='true'>&times;</span>
            </button>
        </div>";
    }

    header("Location: manage-role.php");
    exit();
}

// ✅ Multiple delete
if (isset($_POST['role_ids'])) {
    $success = 0;

    foreach ($_POST['role_ids'] as $encoded_id) {
        $role_id = base64_decode($encoded_id);
        $role_id = mysqli_real_escape_string($db, $role_id);

        if (deleteRole($db, $role_id)) {
            $success++;
        }
    }

    if ($success > 0) {
        $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible fade show' role='alert' style='font-size:16px;' id='gold'>
            <strong>Success!</strong> $success User(s) deleted successfully.
            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                <span aria-hidden='true'>&times;</span>
            </button>
        </div>";
    } else {
        $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible fade show' role='alert' style='font-size:16px;' id='gold'>
            <strong>Error!</strong> No user records deleted.
            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                <span aria-hidden='true'>&times;</span>
            </button>
        </div>";
    }

    header("Location: manage-role.php");
    exit();
}

// If no action, just redirect
header("Location: manage-role.php");
exit();
?>
