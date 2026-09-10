<?php include 'header.php';
include 'config.php';
 ?>
<div class="py-3">
	<div class="container p-4">
		<div class="container">
	<div class="col-md-3">
		<div class="input-group searchbox">
			<center>
				<a href="include/find_friends.php">
					<button class="btn btn-danger" name="search_user" type="submit">Add New User</button>
				</a>
			</center>
		</div>
		<div class="left-chat">
			<ul>
				<?php include("include/get_users_data.php");?>
			</ul>
		</div>
	</div>

	<div class="col-md-9 col-sm-9 col-xs-12 right-sidebar">
		<div class="row">
			<!-- Getting Logged in user info -->
			 <?php 
			 
			 	$user = $SESSION['user_id'];
$sql="SELECT * FROM users_tbl";

$query=mysqli_query($con,$sql);

$rows=mysqli_num_rows($query);
			 
			 ?>
		</div>
	</div>
</div>
	</div>
</div>
<?php include 'footer.php';?>