<?php 
/*Logout

if(isset($_POST['emp_logout'])) {
  session_start();
  session_unset();
  session_destroy();
  header("location:../index.php");
  exit();

}

else 
{
  header("location:../404.php");
}*/


?>
<?php
session_start(); // Start the session
session_unset(); // Remove all session variables
session_destroy(); // Destroy the session
header("Location: ../index.php"); // Redirect to the login page or home page
exit();
?>
