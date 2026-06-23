<?php
require_once 'check_login.php';

$dozentid=isset($_GET['dozentid']) ? (int)$_GET['dozentid'] : 0;
$result=$db->query("select * from gpb_dozent where id=".$dozentid." limit 1");
$dozent=$result->fetch_object();
$result->free();
if(empty($dozent)) {
  header('Location:dozenten.php');
  exit;
}
unset($_SESSION['verwaltung_ich']);
$_SESSION['dozent_ich']=$dozent;
if($dozent->istintrain) {
  $_SESSION['intrain_ich']=$dozent;
  header('Location:../intrain/index.php');
  exit;
}
header('Location:../dozent/index.php');
exit;
?>