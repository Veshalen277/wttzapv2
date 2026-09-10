<?php
require_once __DIR__.'/../app/bootstrap.php';
$privateId=(int)($_GET['message_id']??$_POST['parent_message_id']??0);
if($privateId<1){http_response_code(404);exit('Conversation not found.');}
$privateActor=$portalUser->id;
$privateStatement=$con->prepare('SELECT sender_id,receiver_id FROM private_messages WHERE id=? AND (sender_id=? OR receiver_id=?)');
$privateStatement->bind_param('iii',$privateId,$privateActor,$privateActor);$privateStatement->execute();$privateParticipants=$privateStatement->get_result()->fetch_assoc();$privateStatement->close();
if(!$privateParticipants){http_response_code(404);exit('Conversation not found.');}
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'&&!\Portal\Csrf::valid($_POST['_csrf']??null)){http_response_code(403);exit('Refresh your inbox before replying.');}
$privateRecipient=(int)$privateParticipants['sender_id']===$privateActor?(int)$privateParticipants['receiver_id']:(int)$privateParticipants['sender_id'];
if(isset($_GET['message_id']))$_GET['message_id']=$privateId;
if(isset($_POST['parent_message_id']))$_POST['parent_message_id']=$privateId;
