<?php

session_start();
include("db/config.php");

    $id=$_GET['id'];
   
  $sql="DELETE from members where member_id = '$id'";
	$result=mysqli_query($db,$sql);

  $sql111 = "DELETE from media_dest where media_id = '$id'";
$result111= mysqli_query($db, $sql111);
  



?>

