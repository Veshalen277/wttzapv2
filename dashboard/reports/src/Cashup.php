<?php
namespace Cashups;
final class Cashup
{
 public static function cents($value):int {
  if(!is_string($value)||!preg_match('/^\d{1,7}(?:\.\d{1,2})?$/',$value))throw new \InvalidArgumentException('Enter non-negative amounts with no more than two decimal places.');
  $parts=explode('.',$value);return (int)$parts[0]*100+(int)str_pad($parts[1]??'',2,'0');
 }
 public static function decimal(int $cents):string {return ($cents<0?'-':'').intdiv(abs($cents),100).'.'.str_pad((string)(abs($cents)%100),2,'0',STR_PAD_LEFT);}
 public static function data(array $post,bool $special):array {
  $date=$post['report_date']??null;$d=is_string($date)?\DateTimeImmutable::createFromFormat('!Y-m-d',$date):false;
  if(!$d||$d->format('Y-m-d')!==$date||$date>date('Y-m-d'))throw new \InvalidArgumentException('Select a valid report date, no later than today.');
  $notes=$post['notes']??null;if(!is_string($notes)||trim($notes)===''||strlen($notes)>10000)throw new \InvalidArgumentException('Provide notes of up to 10,000 bytes.');
  $fields=['income_cash','income_card','income_other','expense_cash','airtime'];if($special)$fields=array_merge($fields,['staff_advances','staff_purchases']);
  $money=[];foreach($fields as $f)$money[$f]=self::cents($post[$f]??null);
  $sectors=[];
  if($special){
   $names=$post['sector_name']??[];$cash=$post['sector_cash']??[];$card=$post['sector_card']??[];
   if(!is_array($names)||!is_array($cash)||!is_array($card)||count($names)>100||array_keys($names)!==array_keys($cash)||array_keys($names)!==array_keys($card))throw new \InvalidArgumentException('Check the sector rows and amounts.');
   foreach($names as $i=>$name){
    if(!is_string($name)||strlen($name)>100)throw new \InvalidArgumentException('Sector names must be at most 100 bytes.');
    $c=self::cents($cash[$i]===''?'0':$cash[$i]);$r=self::cents($card[$i]===''?'0':$card[$i]);
    if(trim($name)===''){if($c||$r)throw new \InvalidArgumentException('Name every sector with an amount.');continue;}
    $sectors[]=['name'=>trim($name),'cash'=>$c,'card'=>$r];
   }
   if(isset($post['use_sector_totals'])){if(!$sectors)throw new \InvalidArgumentException('Add sectors before using their totals.');$money['income_cash']=array_sum(array_column($sectors,'cash'));$money['income_card']=array_sum(array_column($sectors,'card'));}
  }
  $money['total_income']=$money['income_cash']+$money['income_card']+$money['income_other'];
  $money['total_expenses']=$money['expense_cash']+$money['airtime']+($money['staff_advances']??0)+($money['staff_purchases']??0);
  $money['net_total']=$money['total_income']-$money['total_expenses'];
  return ['date'=>$date,'notes'=>trim($notes),'money'=>$money,'sectors'=>$sectors];
 }
}
