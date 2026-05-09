<?php
require_once 'check_login.php';
$kursid=isset($_POST['kursid']) ? (int)$_POST['kursid'] : 0;

$result=$db->query("select * from gpb_kurs_dozent where kursid=".$kursid." and dozentid=".$ich->id." limit 1");
$darf=$result->fetch_object();
$result->free();
if(!$darf) {
  header('Location:tagesbericht.php?kursid='.$kursid);
  exit;
}

if($kursid>0){
  $result=$db->query("select * from gpb_kurs where id=".$kursid." limit 1");
  $kurs=$result->fetch_object();
  $result->free();
} else {
  $kurs=false;
}
if(empty($kurs)) {
  header('Location:kurse.php');
  exit;
}
$kurs->von=strtotime($kurs->beginn);
$kurs->bis=strtotime($kurs->ende);

$db->query("delete from gpb_kurs_tagesbericht where kursid=".$kursid);
$stmt=$db->prepare("insert into gpb_kurs_tagesbericht(kursid,tag,themen,kguil,bemerkungen) values(?,?,?,?,?)");
for($wann=$kurs->von;$wann<=$kurs->bis;$wann=strtotime('+1 day',$wann)) {
  $tagname=date('D',$wann);
  if($tagname=='Sat' || $tagname=='Sun') continue;
  $tag=date('Y-m-d',$wann);
  $themen=isset($_POST['themen_'.$tag]) ? str_replace("\r","\n",str_replace("\r\n","\n",$_POST['themen_'.$tag])) : '';
  $kguil=isset($_POST['kguil_'.$tag]) ? str_replace("\r","\n",str_replace("\r\n","\n",$_POST['kguil_'.$tag])) : '';
  $bemerkungen=isset($_POST['bemerkungen_'.$tag]) ? str_replace("\r","\n",str_replace("\r\n","\n",$_POST['bemerkungen_'.$tag])) : '';
  $stmt->bind_param('issss',$kurs->id,$tag,$themen,$kguil,$bemerkungen);
  $stmt->execute();
}
$ok=isset($_POST['ok']) && $_POST['ok']!='N' ? 1 : 0;
if($kurs->tagesbericht_ok!=$ok) {
  $db->query("update gpb_kurs set tagesbericht_ok=".$ok." where id=".$kurs->id." limit 1");
  if(isset($_SESSION['todos'])) {
    unset($_SESSION['todos']);
  }
}
$_SESSION['nachricht']='<div class="done">Tagesbericht gespeichert!</div>';
header('Location:tagesbericht.php?kursid='.$kurs->id);
exit;
?>