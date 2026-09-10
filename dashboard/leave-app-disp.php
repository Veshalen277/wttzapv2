<?php
include("header.php");
include("fetch-data.php");
?>
<!DOCTYPE html>
<html>
<head>
<style>
    .status-btn{
       border:none; 
       color:white; 
       padding:5px 10px;
       width:100px;
       cursor: pointer;
       box-shadow:0px 0px 15px gray
    }
    .approve{
        background-color:green;
    }
    .disapprove{
        background-color:red;
    }
    </style>
</head>
<body>



<!-- ===== displaying data with approval button ===== -->

<table border="1" cellspacing="0" cellpadding="10" width="50%">

    <tr>

        <th>S.N</th>
        <th>User</th>
        <th>Leave Type</th>
        <th>Start</th>
        <th>End</th>
        <th>Reason</th>
        <th>Status</th>
   </tr>

<?php

$fetchData = fetchData($con);

if(count($fetchData)>0){

$i=1;

foreach($fetchData as $data){

?>

   <tr>

       <td><?php echo $i; ?></td>

       <td><?php echo $data['user_id']; ?></td>
       <td><?php echo $data['leave_type']; ?></td>
       <td><?php echo $data['start_date']; ?></td>
       <td><?php echo $data['end_date']; ?></td>
       <td><?php echo $data['reason']; ?></td>
       <td><button onclick="updateStatus(<?php echo $data['user_id'];?>)" id="statusBtn<?php echo $data['user_id'];?>" class="status-btn <?php echo $data['status']==0?'approve':'disapprove'; ?>"><?php echo $data['status']==0?'Approve':'Disapprove'; ?></button></td>

    

    <tr>

        <?php  $i++; } } ?>

</table>

<!-- ============displaying data with approval button========== -->



<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>

<script scr="custom-ajax.js"></script>



</body>

</html>