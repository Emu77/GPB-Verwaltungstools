<?php
require_once 'check_login.php';

$kursid=isset($_GET['kursid']) ? (int)$_GET['kursid'] : 0;
if($kursid<=0) {
  header('Location:kurse.php');
  exit;
}
$result=$db->query("select * from gpb_kurs_dozent where kursid=".$kursid." and dozentid=".$ich->id." limit 1");
$darf=$result->fetch_object();
$result->free();
if(!$darf) {
  header('Location:kurs_noten.php?kursid='.$kursid);
  exit;
}
$notenstatus=isset($_GET['keinenoten']) && $_GET['keinenoten']!='N' ? 'keine' : 'todo';
//if($notenstatus=='keine')  {
//  $db->query("delete from gpb_note where kursid=".$kursid);
//}
$db->query("update gpb_kurs set notenstatus='".$notenstatus."' where id=".$kursid." limit 1");
header('Location:kurs_il_noten.php?kursid='.$kursid);
exit;
?>