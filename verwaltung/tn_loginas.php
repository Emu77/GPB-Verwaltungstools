<?php
require_once 'check_login.php';

$tnid=isset($_GET['tnid']) ? (int)$_GET['tnid'] : 0;
$result=$db->query("select * from gpb_tn where id=".$tnid." limit 1");
$tn=$result->fetch_object();
$result->free();
if(empty($tn)) {
  header('Location:tn.php');
  exit;
}
unset($_SESSION['verwaltung_ich']);
$_SESSION['tn_ich']=$tn;
header('Location:../tn/index.php');
exit;
?>