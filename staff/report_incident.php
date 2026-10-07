<?php require __DIR__.'/../config/database.php';require __DIR__.'/../config/auth.php';require_role(['staff']);require_work_checkin($pdo);require __DIR__.'/../config/smart_ops.php';$msg=$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 try{
  $img=null;
  if(!empty($_FILES['photo']['name'])){
    if($_FILES['photo']['error']!==UPLOAD_ERR_OK) throw new Exception('Photo upload failed.');
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($_FILES['photo']['tmp_name']);$ok=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    if(!isset($ok[$mime])) throw new Exception('Use JPG, PNG or WEBP photo.');
    if($_FILES['photo']['size']>5*1024*1024) throw new Exception('Photo must be under 5 MB.');
    $dir=__DIR__.'/../uploads/incidents';if(!is_dir($dir))mkdir($dir,0775,true);
    $name='incident_'.time().'_'.bin2hex(random_bytes(4)).'.'.$ok[$mime];move_uploaded_file($_FILES['photo']['tmp_name'],$dir.'/'.$name);$img='uploads/incidents/'.$name;
  }
  [$summary,$steps]=incident_initialization($_POST['incident_type'],$_POST['description'],$_POST['urgency']);
  $pdo->beginTransaction();
  $pdo->prepare("INSERT INTO incident_reports(reporter_user_id,farm_id,incident_type,title,description,image_path,urgency,ai_summary,ai_initial_steps) VALUES(?,?,?,?,?,?,?,?,?)")
      ->execute([$_SESSION['user']['id'],$_POST['farm_id']?:null,$_POST['incident_type'],trim($_POST['title']),trim($_POST['description']),$img,$_POST['urgency'],$summary,$steps]);
  $iid=(int)$pdo->lastInsertId();
  notify_managers($pdo,'incident','New incident: '.trim($_POST['title']),$summary,'manager/incidents.php?incident='.$iid,'incident',$iid);
  $pdo->commit();$msg='Incident sent to Manager. Initial safety steps are shown below.';$lastSteps=$steps;
 }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();$err=$e->getMessage();}
}
$farms=$pdo->query("SELECT id,name FROM farms WHERE status='active' ORDER BY name")->fetchAll();
$mine=$pdo->prepare("SELECT * FROM incident_reports WHERE reporter_user_id=? ORDER BY id DESC LIMIT 10");$mine->execute([$_SESSION['user']['id']]);$rows=$mine->fetchAll();
$page_title='Problem Finder';require __DIR__.'/../partials/header.php';?>
<div class="container py-4"><div class="hero-top"><span class="badge-soft">SMART INITIALIZATION</span><h2 class="mt-2">📷 Problem Finder & Incident Report</h2><p class="mb-0">Send a photo and field observation. The manager is notified immediately. Guidance here is first-response support, not a veterinary or professional diagnosis.</p></div>
<?php if($msg):?><div class="alert alert-success mt-3"><?=$msg?></div><?php endif;?><?php if($err):?><div class="alert alert-danger mt-3"><?=htmlspecialchars($err)?></div><?php endif;?>
<?php if(!empty($lastSteps)):?><div class="smart-advice mt-3"><b>Initial steps</b><p><?=htmlspecialchars($lastSteps)?></p></div><?php endif;?>
<div class="cardx p-4 mt-3"><form method="post" enctype="multipart/form-data"><div class="row g-3"><div class="col-md-6"><label>Farm</label><select class="form-select" name="farm_id"><option value="">General / Product</option><?php foreach($farms as $f):?><option value="<?=$f['id']?>"><?=htmlspecialchars($f['name'])?></option><?php endforeach;?></select></div><div class="col-md-6"><label>Problem Type</label><select class="form-select" name="incident_type"><option value="animal_health">Cow / Animal Health</option><option value="fish_health">Fish Health</option><option value="crop_problem">Crop Problem</option><option value="product_problem">Product / Quality Problem</option><option value="equipment">Equipment</option><option value="delivery">Delivery</option><option value="other">Other</option></select></div><div class="col-md-8"><label>Title</label><input class="form-control" name="title" required placeholder="Example: Cow not eating since morning"></div><div class="col-md-4"><label>Urgency</label><select class="form-select" name="urgency"><option>low</option><option selected>medium</option><option>high</option><option>critical</option></select></div><div class="col-12"><label>Observation / Description</label><textarea class="form-control" name="description" rows="4" required></textarea></div><div class="col-12"><label>Photo</label><input class="form-control" type="file" name="photo" accept="image/jpeg,image/png,image/webp"></div></div><button class="btn btn-success mt-3">Send Problem to Manager</button></form></div>
<div class="cardx p-3 mt-4"><h5>My Recent Reports</h5><div class="table-wrap"><table class="table"><thead><tr><th>Problem</th><th>Urgency</th><th>Status</th><th>Time</th></tr></thead><tbody><?php foreach($rows as $r):?><tr><td><?=htmlspecialchars($r['title'])?></td><td><?=$r['urgency']?></td><td><span class="badge-soft"><?=$r['status']?></span></td><td><?=$r['created_at']?></td></tr><?php endforeach;?></tbody></table></div></div></div>
