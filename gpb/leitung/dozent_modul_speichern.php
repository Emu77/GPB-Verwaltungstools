<?php
require_once 'check_login.php';

$dozentid=isset($_GET['dozentid']) ? (int)$_GET['dozentid'] : 0;
$modulid=isset($_GET['modulid']) ? (int)$_GET['modulid'] : 0;
$planungnotiz=isset($_GET['notiz']) ? $_GET['notiz'] : '';

if(empty($dozentid)) {
  header('Location:../verwaltung/dozenten.php');
  exit;
}
if(!$ich->istleiter) {
  header('Location:../verwaltung/dozent_sehen.php?dozentid='.$dozentid);
  exit;
}
if(!empty($modulid)) {
  $result=$db->query("select * from gpb_dozent_modul where dozentid=".$dozentid." and modulid=".$modulid." limit 1");
  $schon=$result->fetch_object();
  $result->free();
  if($schon) {
    $stmt=$db->prepare("update gpb_dozent_modul set planungnotiz=? where dozentid=? and modulid=? limit 1");
    $stmt->bind_param('sii',$planungnotiz,$dozentid,$modulid);
    $stmt->execute();
  } else {
    $stmt=$db->prepare("insert into gpb_dozent_modul(dozentid,modulid,planungnotiz) values(?,?,?)");
    $stmt->bind_param('iis',$dozentid,$modulid,$planungnotiz);
    $stmt->execute();
  }
}

header('Location:dozent_module.php?dozentid='.$dozentid);
exit;
?>