<?php
require_once 'check_login.php';

$kursid=isset($_GET['kursid']) ? (int)$_GET['kursid'] : 0;
if($kursid<=0) {
  header('Location:kurse.php');
  exit;
}
$notenstatus=isset($_GET['notenstatus']) ? $_GET['notenstatus'] : null;
if(!empty($notenstatus)) {
  //if($notenstatus=='keine')  {
  //  $db->query("delete from gpb_note where kursid=".$kursid);
  //}
  $stmt=$db->prepare("update gpb_kurs set notenstatus=? where id=? limit 1");
  $stmt->bind_param('si',$notenstatus,$kursid);
  $stmt->execute();
}
header('Location:kurs_il_noten.php?kursid='.$kursid);
exit;
?>