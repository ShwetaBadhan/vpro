<?php
session_start();
include('db/config.php');

if (isset($_GET['id'])) 
{
    $userId = base64_decode($_GET['id']);
   
    $query = "SELECT * FROM admin WHERE _id = $userId";
    $userResult = mysqli_query($db, $query);
    
    $query = "SELECT title FROM navigation_menus";
    $navigationMenuResult = $db->query($query);
    
    if ($navigationMenuResult) 
    {
        $navigation_menu_values = [];
        while ($row = $navigationMenuResult->fetch_assoc()) 
        {
            $navigation_menu_values[] = $row['title'];
        }
    } 
    else 
    {
        echo "Error fetching navigation menu values: " . mysqli_error($db);
    }
    
    if ($userResult) {
        $row = mysqli_fetch_assoc($userResult);
        $username = $row['username'];
        $email = $row['email'];
        $phone = $row['mobile'];
        $status = $row['status'];
        $password = $row['password'];
    } 
    else 
    {
        echo "Error: " . mysqli_error($db);
    }
    
    if (isset($_POST['submit'])) {
        $username = mysqli_real_escape_string($db, $_POST['username']);
        $email = mysqli_real_escape_string($db, $_POST['email']);
        $phone = mysqli_real_escape_string($db, $_POST['mobile']);
        $newPassword = mysqli_real_escape_string($db, $_POST['password']); 
        $status = mysqli_real_escape_string($db, $_POST['status']);
        
        // Check if the entered password is different from the stored one
        $checkPasswordQuery = "SELECT password FROM admin WHERE _id = $userId";
        $checkPasswordResult = mysqli_query($db, $checkPasswordQuery);
        
        if ($checkPasswordResult) {
            $storedPasswordRow = mysqli_fetch_assoc($checkPasswordResult);
            $storedPassword = $storedPasswordRow['password'];
            
            // Compare the entered password with the stored one
            if ($newPassword !== $storedPassword && !empty($newPassword)) {
                // Passwords are different and not empty, update the password
                $newPassword = md5($newPassword);
                $updateUserQuery = "UPDATE admin SET username='$username', email='$email', mobile='$phone', password='$newPassword', status='$status' WHERE _id = $userId";
            } else {
                // Passwords are the same or empty, update other fields but keep the password unchanged
                $updateUserQuery = "UPDATE tbl_admin SET username='$username', email='$email', mobile='$phone', status='$status' WHERE _id = $userId";
            }
        }
        if (mysqli_query($db, $updateUserQuery)) {
            $selectedTitles = isset($_POST['title']) ? $_POST['title'] : [];
            
            $deleteQuery = "DELETE FROM admin_permissions WHERE admin_id = $userId";
            mysqli_query($db, $deleteQuery);
            
            foreach ($selectedTitles as $selectedTitle) 
            {
                $getMenuIdQuery = "SELECT id FROM navigation_menus WHERE title = '$selectedTitle'";
                $menuIdResult = mysqli_query($db, $getMenuIdQuery);
                
                if ($menuIdResult) {
                    $menuIdRow = mysqli_fetch_assoc($menuIdResult);
                    $menuId = $menuIdRow['id'];
                    
                    $insertPermissionQuery = "INSERT INTO admin_permissions (admin_id, navigation_menu_id) VALUES ($userId, $menuId)";
                    mysqli_query($db, $insertPermissionQuery);
                } 
                else 
                {
                    echo "Error fetching menu ID: " . mysqli_error($db);
                }
            }
            $_SESSION['status'] = "User details and permissions updated successfully.";
            header("Location: user-edit.php?id=" . $_GET['id']);
            exit();
        } 
        else 
        {
            echo "Error updating user details: " . mysqli_error($db);
            exit();
        }
    }
    $db->close();
} 
else 
{
    echo "Invalid request";
}
?>
