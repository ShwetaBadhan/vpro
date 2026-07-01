<?php
include("db/config.php");
if(isset($_GET['filename']))
{
	$name=$_GET['filename'];
	
	 $fetc = "SELECT * FROM file LIMIT 5";
    $result = mysqli_query($db,$fetc);

$row1=mysqli_fetch_array($result);

   
	
	$file='db/'.$row1['filename'];
	
if(file_exists($file))
{
	 header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.basename($file).'"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
           
            flush(); // Flush system output buffer
            readfile($file);
	
}
    
   

	
}


?>