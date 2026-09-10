<?php

include 'config.php';

function fetchData($con){

    

    $sql = "SELECT * FROM leave_applications";

    $result = $con->query($sql);

    

    if ($result->num_rows > 0) {

    $row= $result->fetch_all(MYSQLI_ASSOC);

    return $row;  

}else{

    return $row=[];

}

}

?>

