<?php


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if the file was uploaded without errors
    if (isset($_FILES["uploaded_file"]) && $_FILES["uploaded_file"]["error"] == UPLOAD_ERR_OK) {
        $temp_name = $_FILES["uploaded_file"]["tmp_name"];
        $original_name = $_FILES["uploaded_file"]["name"];
        $file_size = $_FILES["uploaded_file"]["size"];

        // Check if the file is an image
        $allowed_types = ["image/jpeg", "image/png", "image/gif"];
        $file_type = mime_content_type($temp_name);
        if (!in_array($file_type, $allowed_types)) {
            echo "Only JPEG, PNG, and GIF images are allowed.";
        } elseif ($file_size > 2 * 1024 * 1024) { // 2 MB in bytes
            echo "File size exceeds the limit of 2MB.";
        } else {
            // Generate a unique filename
            $unique_filename = uniqid() . '_' . $original_name;

            // Specify the destination directory
            $upload_directory = "banner/";

            // Move the uploaded file to the desired directory
            if (move_uploaded_file($temp_name, $upload_directory . $unique_filename)) {
                echo "Image uploaded successfully.";
            } else {
                echo "Error uploading image.";
            }
        }
    } else {
        echo "Error: " . $_FILES["uploaded_file"]["error"];
    }
}