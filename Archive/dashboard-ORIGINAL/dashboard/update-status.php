<?php

include("header.php");

if(isset($_POST['updateId'])){

    $updateId = $_POST['updateId'];

    updateStatus($con, $updateId);

}



function updateStatus($con, $updateId){



    $getStatus= getStatus($con, $updateId);

    if(!empty($getStatus)){



        if($getStatus['status']==0){

            $sql = "UPDATE leave_applications SET status=1 WHERE id=$updateId";

            

        if ($con->query($sql) === TRUE) {

             echo 1;

          } 

        }elseif($getStatus['status']==1){

           $sql = "UPDATE leave_applications SET status=0 WHERE id=$updateId";

           

        if ($con->query($sql) === TRUE) {

            echo 0;

          } 

        }

       



    }else{

        echo "No data is exist in database";

    }



}



function getStatus($con, $id){



    $query= "SELECT status FROM leave_applications WHERE id=$id";

    $result = $con->query($query);

    

    if ($result->num_rows > 0) {

      $row = $result->fetch_assoc();

    }else{

       $row =[];

    }

    return $row;



}

?>