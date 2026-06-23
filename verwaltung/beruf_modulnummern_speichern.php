<?php
require_once 'check_login.php';
$berufid=isset($_POST['berufid']) ? (int)$_POST['berufid'] : 0;
if($berufid<=0) {
  header('Location:berufe.php');
  exit;
}
$module=array();
$result=$db->query("select * from gpb_beruf_modul where berufid=".$berufid);
while($row=$result->fetch_object()) {
  $module[]=$row;
}
$result->free();
foreach($module as $m) {
  $nummer=isset($_POST['nummer_'.$m->modulid]) ? (int)$_POST['nummer_'.$m->modulid] : 0;
  if($nummer!=$m->nummer) {
    $db->query("update gpb_beruf_modul set nummer=".$nummer." where berufid=".$berufid." and modulid=".$m->modulid);
  }
}
$_SESSION['module_nachricht']='<div class="done">Reihenfolge gespeichert!</div>';
header('Location:beruf_module.php?berufid='.$berufid);
exit;
?>