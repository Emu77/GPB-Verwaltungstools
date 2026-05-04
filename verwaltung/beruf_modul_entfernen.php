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
$db->query("delete from gpb_beruf_modul where berufid=".$berufid." and modulid=".$modulid);
header('Location:beruf_module.php?berufid='.$berufid);
exit;
?>