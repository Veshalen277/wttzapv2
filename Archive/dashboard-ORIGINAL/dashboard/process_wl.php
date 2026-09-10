<?php 

include 'header.php';
if(!empty($_GET['activity'])){$activity = $_GET['activity'];}
if(!empty($_GET['status'])){$status = $_GET['status'];}

$now = date("Y-m-d H:i:s");
// Process the task
if(isset($activity)){

	// Insert in DB

	$query = $pdo->prepare("INSERT INTO time_diff(activity) VALUE(?)");
	$query->execute([$activity]);
	$msg='<i class="fas fa-check"></i>Baby sitting hour has been succewssfully added';
	echo $msg;
} else {
	$retrieve = $pdo->prepare("SELECT * FROM time_diff WHERE activity = 'babysit' ORDER BY date DESC LIMIT 1");
	$retrieve->execute([$activity]);
	$previous_time = $retrieve->fetch();
	// Calculate the time difference 
	$datetime1 = new DateTime($previous_time['date']);
	$datetime2 = new DateTime($now);//now
	$interval = $datetime1->diff($datetime2);
	$hour = $interval->format('%h')>"hours".$interval->format('%i')."minutes".$interval->format('%s')."seconds";
	//Insert amount 
	$query = $pdo->prepare("INSERT INTO time_diff(activity, hour) VALUEs(?,?)");
    $query->execute([$activity, $hour]);



$msg='Baby sitting ended';

echo $msg;



}





?>