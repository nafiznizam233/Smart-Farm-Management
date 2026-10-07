<?php
$checks=[];
$checks[]=['PHP 8+',version_compare(PHP_VERSION,'8.0','>=')];
$checks[]=['PDO MySQL',extension_loaded('pdo_mysql')];
$checks[]=['Main CSS',file_exists(__DIR__.'/assets/css/style.css')];
$checks[]=['Landing CSS',file_exists(__DIR__.'/assets/css/commercial-home.css')];
$checks[]=['Bootstrap CSS',file_exists(__DIR__.'/assets/vendor/bootstrap.min.css')];
$db=false;$tables=[];$err='';$cols=[];
try{
 require __DIR__.'/config/database.php';$db=true;
 $tables=$pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
 foreach(['shop_products','shop_orders','income'] as $t){
   if(in_array($t,$tables,true))$cols[$t]=$pdo->query("SHOW COLUMNS FROM `$t`")->fetchAll(PDO::FETCH_COLUMN);
 }
}catch(Throwable $e){$err=$e->getMessage();}
$checks[]=['Database connection',$db];
$need=['users','farms','staff','stores','inventory_items','purchases','accounts','work_attendance','jobs','job_applications','shop_products','production_collections','shop_orders','shop_order_items','shop_order_status_log'];
foreach($need as $n)$checks[]=["Table: $n",in_array($n,$tables,true)];
$checks[]=['Product pictures supported',in_array('image_path',$cols['shop_products']??[],true)];
$checks[]=['Delivery assignment supported',in_array('assigned_staff_id',$cols['shop_orders']??[],true)];
$checks[]=['Delivered sale audit supported',in_array('created_by',$cols['income']??[],true)];
$all=true;foreach($checks as $c){if(!$c[1]){$all=false;break;}}
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width"><style>body{font-family:Segoe UI,Arial;padding:30px;background:#f5f8f6;color:#17362a}.box{max-width:820px;margin:auto;background:#fff;padding:28px;border-radius:18px;box-shadow:0 15px 50px #dce8e1}.ok{color:#087443}.bad{color:#b42318}li{padding:7px}.banner{padding:14px;border-radius:10px;background:<?= $all?'#e9f8ef':'#fff0ee'?>;font-weight:700}</style></head><body><div class="box"><h2>Smart Farm Complete System Check</h2><div class="banner"><?=$all?'✅ System structure is ready.':'❌ Some setup items still need attention.'?></div><?php if($err):?><p class="bad"><?=htmlspecialchars($err)?></p><?php endif;?><ul><?php foreach($checks as [$n,$ok]):?><li class="<?=$ok?'ok':'bad'?>"><?=$ok?'✅':'❌'?> <?=htmlspecialchars($n)?></li><?php endforeach;?></ul><p><a href="index.php">Public Website</a> · <a href="setup_owner.php">Owner Setup</a> · <a href="login.php?role=owner">Private Login</a> · <a href="shop/index.php">Shop</a></p></div></body></html>