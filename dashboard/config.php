
<?php 
$con=mysqli_connect("localhost","bethelin_wttzap","!Mv130369$","bethelin_wttzap");
mysqli_set_charset($con, 'utf8mb4');
// or if you prefer explicit:
mysqli_query($con, "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

$db=$con;


define('SMTP_HOST', 'mail.bethelinternational.co.za');
define('SMTP_USER', 'info@bethelinternational.co.za');
define('SMTP_PASS', '!Mv130369$'); // change the mailbox password first
define('SMTP_PORT', 465);