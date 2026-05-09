<?php
require_once 'check_login.php';
$kursid=isset($_POST['kursid']) ? (int)$_POST['kursid'] : 0;
$result=$db->query("select * from gpb_kurs_dozent where kursid=".$kursid." and dozentid=".$ich->id." limit 1");
$darf=$result->fetch_object();
$result->free();
if(!$darf) {
  header('Location:kurzbericht.php?kursid='.$kursid);
  exit;
}
$result=$db->query("select * from gpb_kurs_kurzbericht where kursid=".$kursid." limit 1");
$vorhanden=$result->fetch_object();
$result->free();

$zusammenfassung=isset($_POST['zusammenfassung']) ? str_replace("\r","\n",str_replace("\r\n","\n",$_POST['zusammenfassung'])) : '';
$inhaltsgestaltung=isset($_POST['inhaltsgestaltung']) ? str_replace("\r","\n",str_replace("\r\n","\n",$_POST['inhaltsgestaltung'])) : '';
$methoden=isset($_POST['methoden']) ? str_replace("\r","\n",str_replace("\r\n","\n",$_POST['methoden'])) : '';
$eindruck=isset($_POST['eindruck']) ? str_replace("\r","\n",str_replace("\r\n","\n",$_POST['eindruck'])) : '';
$todos=isset($_POST['todos']) ? str_replace("\r","\n",str_replace("\r\n","\n",$_POST['todos'])) : '';
if($vorhanden) {
  $stmt=$db->prepare("update gpb_kurs_kurzbericht set zusammenfassung=?,inhaltsgestaltung=?,methoden=?,eindruck=?,todos=? where kursid=? limit 1");
  $stmt->bind_param('sssssi',$zusammenfassung,$inhaltsgestaltung,$methoden,$eindruck,$todos,$kursid);
  $stmt->execute();
} else {
  $stmt=$db->prepare("insert into gpb_kurs_kurzbericht(kursid,zusammenfassung,inhaltsgestaltung,methoden,eindruck,todos) values(?,?,?,?,?,?)");
  $stmt->bind_param('isssss',$kursid,$zusammenfassung,$inhaltsgestaltung,$methoden,$eindruck,$todos);
  $stmt->execute();
}
$db->query("update gpb_kurs set kurzbericht_ok=true where id=".$kursid." limit 1");
$_SESSION['nachricht']='<div class="done">Kurzbericht gespeichert!</div>';
if(isset($_SESSION['todos'])) {
  unset($_SESSION['todos']);
}
header('Location:kurzbericht.php?kursid='.$kursid);
exit;
?>