<?php
require_once 'check_login.php';
require_once 'TnKurs.php';
require_once '../Bewertung.php';

$kurs=Kurs::einenLaden(isset($_POST['kursid']) ? (int)$_POST['kursid'] : 0,'TnKurs');
if(empty($kurs)) {
  header('Location:kurse.php');
  exit;
}
$kurs->ladeVonMir();
if(!$kurs->vonMir || $kurs->bewertungStatus!='offen') {
  header('Location:kurs_bewertung.php?kursid='.$kurs->id);
  exit;
}
$feedback=isset($_POST['feedback']) ? str_replace("\r","\n",str_replace("\r\n","\n",$_POST['feedback'])) : '';
$anonym=isset($_POST['anonym']) && $_POST['anonym']!='N' ? 1 : 0;
$db->query("delete from gpb_bewertung where kursid=".$kurs->id." and tnid=".$ich->id);

$stmt=$db->prepare("insert into gpb_bewertung(kursid,tnid,frage,wert,feedback,anonym) values(?,?,'info',0,?,?)");
$stmt->bind_param('iisi',$kurs->id,$ich->id,$feedback,$anonym);
$stmt->execute();

$stmt=$db->prepare("insert into gpb_bewertung(kursid,tnid,frage,wert,feedback,anonym) values(?,?,?,?,'',0)");
$stmt->bind_param('iisi',$kurs->id,$ich->id,$frage,$wert);
foreach(Bewertung::$fragen as $frage=>$fragentext) {
  $wert=isset($_POST[$frage]) ? (int)$_POST[$frage] : 0;
  if($wert>0) {
    $stmt->execute();
  }
}
$_SESSION['bewertungnachricht']='<div class="done">Bewertung gespeichert, danke!</div>';
if(isset($_SESSION['todos'])) {
  unset($_SESSION['todos']);
}
header('Location:kurs_bewertung.php?kursid='.$kurs->id);
exit;
?>