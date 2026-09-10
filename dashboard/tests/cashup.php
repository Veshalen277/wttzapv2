<?php
if(PHP_SAPI!=='cli')exit;
require __DIR__.'/../reports/src/Cashup.php';
function check($ok,$why){if(!$ok)throw new RuntimeException($why);}
check(\Cashups\Cashup::cents('0.10')+\Cashups\Cashup::cents('0.20')===30,'Exact cents');
check(\Cashups\Cashup::decimal(-101)==='-1.01','Signed difference');
foreach(['1e4','-1','0.001','nan',[],null] as $bad){try{\Cashups\Cashup::cents($bad);throw new RuntimeException('Bad amount accepted');}catch(InvalidArgumentException $expected){}}
$p=['report_date'=>'2025-01-01','income_cash'=>'0.10','income_card'=>'0.20','income_other'=>'1','expense_cash'=>'0.05','airtime'=>'0','notes'=>'Example'];
$d=\Cashups\Cashup::data($p,false);check($d['money']['total_income']===130&&$d['money']['net_total']===125,'Standard totals');
$p+=['staff_advances'=>'1','staff_purchases'=>'2','sector_name'=>['Counter'],'sector_cash'=>['4.50'],'sector_card'=>['3.20'],'use_sector_totals'=>'1'];
$d=\Cashups\Cashup::data($p,true);check($d['money']['total_income']===870&&$d['money']['total_expenses']===305,'Sector totals and staff costs');
echo "Cashup amount, sector and calculation tests passed.\n";
