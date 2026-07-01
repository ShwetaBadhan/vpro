<?php
include("db/config.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = strip_tags(($_POST['category']));
    $parent = strip_tags(($_POST['parent']));
    $menu = strip_tags($_POST['menu']);
    $papular = strip_tags(($_POST['popular']));

    // Insert user into the database
    $query = "insert into category (Category_Name, parent_category,show_in_menu,show_popular_list,image) values('$category','$parent','$menu','$papular','$unique_filename')";
    $result = mysqli_query($connection, $query);

    if ($result) {
        $response = array('message' => 'Registration successful!');
    } else {
        $response = array('message' => 'Invalid request.');
    }

    header('Content-Type: application/json');
    echo json_encode($response);
}