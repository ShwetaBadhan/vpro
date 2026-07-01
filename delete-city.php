<?php
include('db/config.php');


// Handle single delete (GET or POST)
if (isset($_GET['id']) || isset($_POST['id'])) {
    // echo '<pre>';
    // print_r($_POST);
    //     // print_r(value: $_GET);
    // exit;

    $id = isset($_GET['id']) ? $_GET['id'] : $_POST['id'];
    $id = base64_decode($id);
    $id = mysqli_real_escape_string($db, $id);

    $delete_sql = "DELETE FROM city WHERE city_id = '$id'";
    if (mysqli_query($db, $delete_sql)) {
        header("location: manage-city.php?status=" . base64_encode(1));
    } else {
        header("location: manage-city.php?status=" . base64_encode(-1));
    }
    exit();
}


// Handle multiple category deletes
if (isset($_POST['city_ids'])) {
    // echo '<pre>';
    // print_r($_POST);
    // exit;
    $ids = $_POST['city_ids'];
    $success_count = 0;

    foreach ($ids as $encoded_id) {
        $id = base64_decode($encoded_id);
        $id = mysqli_real_escape_string($db, $id);

        $delete_sql = "DELETE FROM city WHERE city_id = '$id'";
        if (mysqli_query($db, $delete_sql)) {
            $success_count++;
        }
    }

    if ($success_count > 0) {
        header("location: manage-city.php?status=" . base64_encode(1));
    } else {
        header("location: manage-city.php?status=" . base64_encode(-1));
    }
    exit();
}

// Default fallback
header("location: manage-city.php?status=" . base64_encode(-1));
exit();
?>
