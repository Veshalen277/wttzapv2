<?php include 'header.php';







// PDO connection

$host = 'localhost';

$dbname = 'wttzap_tasks';

$username = 'wttzap_tasks';

$password = '!Mv130369$';



try {

    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    

    // Example PDO query

    $stmt = $pdo->prepare("SELECT * FROM users_tbl WHERE id = ?");

    $stmt->execute([1]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    

   echo "User Name (PDO): " . $user['username'] . "<br>";



} catch(PDOException $e) {

    echo "PDO Connection failed: " . $e->getMessage();

}


?>







<?php 



$retrieve = $pdo->prepare("SELECT * FROM time_diff WHERE activity = 'babysit' ORDER BY date DESC LIMIT 1");

$retrieve->execute();

$activities = $retrieve->fetchAll();



//print_r($activities);






?>



<div class="container">

	<h3>Work Log</h3>

	<div class="container p-5">

<div class="row py-3">



<div class="col-md-4 col-xxl-4 my-5 mx-auto">

  <div class="d-grid gap-2">

    <a href="process_wl.php?activity=babysit&status=start" class="btn btn-success w-100 mt-2" type="button">Start</a>

    <a href="process_wl.php?activity=babysit&status=end" class="btn btn-danger w-100 mt-2" type="button">Stop</a>

  </div>

</div>









			<div class="col-md-8 border border-danger">

		<h4>Activity</h4>

		<ol>

			<?php foreach ($activities as $activity){?>

			<li><?php $date = DateTime::createFromFormat('Y-m-d H:i:s', $activity['date']); ?>

			<?php echo strtoupper($activity('activity'));?>

			at <?php echo $date->format('H:i:s, d-m-Y'); ?>

			<br>

			<?php if($activity['hour']==null){echo "Started at: <span>".$activity['date']."</span>";}

	else {

			echo "Babysitter for fafsfagag : <span>".$activity['hour']."</span>";

	}?>

	

		</li>
<?php }?>
		</ol>

		</div>







</div>

	</div>

</div>

<?php include 'footer.php';?>