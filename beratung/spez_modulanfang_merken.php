<?php
require_once 'check_login.php';
$modulid=isset($_GET['modulid']) ? (int)$_GET['modulid'] : null;
$anfang=isset($_GET['anfang']) ? $_GET['anfang'] : '';
if($modulid!==null) {
  $modulanfaenge=isset($_SESSION['spez_modulanfaenge']) ? $_SESSION['spez_modulanfaenge'] : array();
  $modulanfaenge[$modulid]=$anfang;
  //eventuelle kollidierende Module verschieben, damit dieses es nicht bei der Zeitberechnung wird
  foreach($modulanfaenge as $modid=>$anf) {
    if($modid<=0) continue;
    if($modid==$modulid) continue;
    if($anf!=$anfang) continue;
    if(empty($anf)) continue;
    $modulanfaenge[$modid]=date('Y-m-d',strtotime('+1 day',strtotime($anf)));
  }
  $_SESSION['spez_modulanfaenge']=$modulanfaenge;
}
header('Location:spezialisten.php');
exit;
?>