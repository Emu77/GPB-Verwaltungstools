<?php
require_once 'check_login.php';
$berufid=isset($_GET['berufid']) ? (int)$_GET['berufid'] : 0;
if($berufid<=0) {
  header('Location:berufe.php');
  exit;
}
$modulid=isset($_GET['modulid']) ? (int)$_GET['modulid'] : 0;
if($modulid<=0) {
  header('Location:beruf_module.php?berufid='.$berufid);
  exit;
}
$result=$db->query("select * from gpb_beruf_modul where berufid=".$berufid." and modulid=".$modulid." limit 1");
$schon=$result->fetch_object();
$result->free();
if(!$schon) {
  $db->query("insert into gpb_beruf_modul(berufid,modulid) values(".$berufid.",".$modulid.")");
}
header('Location:beruf_module.php?berufid='.$berufid);
exit;
?>