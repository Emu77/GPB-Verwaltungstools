<?php
require_once 'check_login.php';
require_once 'anwesenheitsArten.php';
$tnid=isset($_GET['tnid']) ? (int)$_GET['tnid'] : 0;
if($tnid<=0) {
  echo 'Bitte TN auswählen';
  exit;
}
$tag=isset($_GET['tag']) ? $_GET['tag'] : '';
if(empty($tag)) {
  echo 'Bitte Tag auswählen';
  exit;
}
$moment=isset($_GET['moment']) ? $_GET['moment'] : false;
if($moment!='anfang' && $moment!='ende' && $moment!='kgu' && $moment!='mitis') {
  echo 'Bitte Moment auswählen';
  exit;
}
$arten=$moment=='mitis' ? $anwesenheitsArtenMitis : $anwesenheitsArten;
$val=isset($_GET['val']) ? $_GET['val'] : '';
if(!isset($arten[$val])) {
  $val='';
}
$stmt=$db->prepare("select * from gpb_anwesenheit where tag=? and tnid=? limit 1");
$stmt->bind_param('si',$tag,$tnid);
$stmt->execute();
$result=$stmt->get_result();
$anwesenheit=$result->fetch_object();
$result->free();

if($anwesenheit) {
  $stmt=$db->prepare("update gpb_anwesenheit set ".$moment."=?,geaendertam=current_timestamp() where id=? limit 1");
  $stmt->bind_param('si',$val,$anwesenheit->id);
  if($stmt->execute()) {
    echo 'OK';
    exit;
  }
} else {
  $stmt=$db->prepare("insert into gpb_anwesenheit(tag,tnid,".$moment.") values(?,?,?)");
  $stmt->bind_param('sis',$tag,$tnid,$val);
  if($stmt->execute()) {
    $id=$db->insert_id;
    echo 'OK';
    exit;
  }
}
echo $db->error;
exit;
?>