<?php
require_once 'check_login.php';

$kursid=isset($_GET['kursid']) ? (int)$_GET['kursid'] : 0;
if(empty($kursid)) {
  header('Location:kurse.php');
  exit;
}
$tnid=isset($_GET['tnid']) ? (int)$_GET['tnid'] : 0;
$spalte=isset($_GET['spalte']) ? $_GET['spalte'] : '';
if(empty($tnid) || empty($spalte)) {
  header('Location:kurs_il_noten.php?kursid='.$kursid);
  exit;
}
$wert=isset($_GET['wert']) ? $_GET['wert'] : '';
if($wert==='') $wert=null;

$result=$db->query("select * from gpb_note where kursid=".$kursid." and tnid=".$tnid." limit 1");
$schon=$result->fetch_object();
$result->free();

if($schon) {
  $stmt=$db->prepare("update gpb_note set `".addslashes($spalte)."`=? where kursid=? and tnid=? limit 1");
  $stmt->bind_param('sii',$wert,$kursid,$tnid);
  $stmt->execute();
} else {
  $stmt=$db->prepare("insert into gpb_note(kursid,tnid,`".addslashes($spalte)."`) values(?,?,?)");
  $stmt->bind_param('iis',$kursid,$tnid,$wert);
  $stmt->execute();
}
echo 'OK';
if(empty($wert)) {
  exit;
}
if($spalte=='nachschreibetermin') {
  echo $wert.'='.date('d.m.Y H:i',strtotime($wert));
  exit;
}
echo $wert;
exit;
?>