<?php
$special=isset($special)&&$special===true;
require __DIR__.'/../app/bootstrap.php';require __DIR__.'/src/Cashup.php';
\Portal\Auth::requireRoles($special?[2,7]:[1,2,3,4,5,6,7]);
if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST'){http_response_code(405);header('Allow: POST');exit;}
$form=$special?'special_daily_report.php':'report_form.php';$kind=$special?'manager':'daily';$table=$special?'special_reports':'reports';$saved=false;
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
try{
 if(!\Portal\Csrf::valid($_POST['_csrf']??null))throw new InvalidArgumentException('Refresh your cashup form before submitting.');
 $key=$_POST['request_key']??null;if(!is_string($key)||!preg_match('/^[a-f0-9]{64}$/',$key))throw new InvalidArgumentException('Refresh the cashup form to get a submission reference.');
 $data=\Cashups\Cashup::data($_POST,$special);$uid=$portalUser->id;
 // Resolve attribution from the authenticated user record, never hidden inputs.
 $s=$con->prepare('SELECT user_scale FROM users_tbl WHERE id=?');$s->bind_param('i',$uid);$s->execute();$userRow=$s->get_result()->fetch_assoc();$s->close();if(!$userRow)throw new InvalidArgumentException('Your employee profile is unavailable.');$dept=(string)$userRow['user_scale'];
 $hash=hash('sha256',json_encode([$uid,$kind,$dept,$data],JSON_THROW_ON_ERROR));$con->begin_transaction();
 $s=$con->prepare('INSERT IGNORE INTO portal_cashup_requests(request_key,payload_hash,user_id,report_kind) VALUES(?,?,?,?)');$s->bind_param('ssis',$key,$hash,$uid,$kind);$s->execute();$new=$s->affected_rows;$s->close();
 if(!$new){$s=$con->prepare('SELECT payload_hash,report_id FROM portal_cashup_requests WHERE request_key=?');$s->bind_param('s',$key);$s->execute();$old=$s->get_result()->fetch_assoc();if(!hash_equals($old['payload_hash'],$hash))throw new InvalidArgumentException('This submission reference was used for different data. Refresh the form.');$con->commit();header('Location: '.$form.'?message='.urlencode('This cashup was already saved. No duplicate was created.'));exit;}
 $values=[$data['date'],$uid,$dept];$columns=['report_date','user_id','user_dept'];
 foreach($data['money'] as $f=>$cents){$columns[]=$f;$values[]=\Cashups\Cashup::decimal($cents);}$columns[]='notes';$values[]=$data['notes'];
 $sql='INSERT INTO '.$table.' ('.implode(',',$columns).') VALUES('.implode(',',array_fill(0,count($values),'?')).')';$s=$con->prepare($sql);$types='sis'.str_repeat('s',count($values)-3);$s->bind_param($types,...$values);$s->execute();$reportId=(int)$s->insert_id;$s->close();
 foreach($data['sectors'] as $sector){$cash=\Cashups\Cashup::decimal($sector['cash']);$card=\Cashups\Cashup::decimal($sector['card']);$s=$con->prepare('INSERT INTO special_report_sectors(report_id,sector_name,cash_amount,card_amount) VALUES(?,?,?,?)');$s->bind_param('isss',$reportId,$sector['name'],$cash,$card);$s->execute();$s->close();}
 $s=$con->prepare('UPDATE portal_cashup_requests SET report_id=? WHERE request_key=?');$s->bind_param('is',$reportId,$key);$s->execute();$s->close();$con->commit();$saved=true;
}catch(InvalidArgumentException $ex){try{$con->rollback();}catch(Throwable $ignored){}header('Location: '.$form.'?error='.urlencode($ex->getMessage()));exit;}
 catch(Throwable $ex){try{$con->rollback();}catch(Throwable $ignored){}error_log('Cashup save: '.$ex->getMessage());header('Location: '.$form.'?error='.urlencode('The cashup could not be saved. Ask your administrator to check the error log.'));exit;}
// Delivery is separate from persistence: never report a successful save as failed.
$mailFailed=false;
if($special){try{require_once __DIR__.'/../inc/mailer.php';$body="Manager cashup saved\nDate: ".$data['date']."\nDepartment: ".$dept."\nReport: ".$reportId."\n";foreach($data['money'] as $label=>$amount)$body.=str_replace('_',' ',$label).': R '.\Cashups\Cashup::decimal($amount)."\n";$body.="Notes: ".$data['notes'];$mailFailed=sendEmail(['admin@timefliesza.co.za','altaafs@wtt.co.za'],'Special Daily Report - '.$data['date'],$body,[])!==true;}catch(Throwable $ex){error_log('Cashup mail: '.$ex->getMessage());$mailFailed=true;}}
header('Location: '.$form.'?message='.urlencode($mailFailed?'Cashup saved. Email delivery failed; tell your administrator. Do not resubmit.':'Cashup saved successfully.'));exit;
