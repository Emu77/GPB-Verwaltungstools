<?php
require_once 'check_login.php';

$kursid=isset($_POST['kursid']) ? (int)$_POST['kursid'] : 0;
$termin=isset($_POST['termin']) ? $_POST['termin'] : '';

if($kursid<=0 || empty($termin)) {
  header('Location:noten.php');
  exit;
}

$result=$db->query("select * from gpb_note where tnid=".$ich->id." and kursid=".$kursid." limit 1");
$note=$result->fetch_object();
$result->free();

if(!empty($note) && ($note->nachnote>0 || !empty($note->nachschreibestatus))) {
  header('Location:noten.php');
  exit;
}

if(empty($note)) {
  $stmt=$db->prepare("insert into gpb_note(kursid,tnid,nachschreibestatus,nachschreibetermin) values(?,?,'beantragt',?)");
  $stmt->bind_param('iis',$kursid,$ich->id,$termin);
  $stmt->execute();
} else {
  $stmt=$db->prepare("update gpb_note set nachschreibestatus='beantragt',nachschreibetermin=? where kursid=? and tnid=? limit 1");
  $stmt->bind_param('sii',$termin,$kursid,$ich->id);
  $stmt->execute();
}
$redirect=isset($_POST['redirect']) ? $_POST['redirect'] : false;
if(!empty($redirect)) {
  header('Location:'.$redirect);
  exit;
}
header('Location:noten.php');
exit;
?>