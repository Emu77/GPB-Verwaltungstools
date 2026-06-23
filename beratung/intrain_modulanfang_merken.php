<?php
require_once 'check_login.php';
$modulid=isset($_GET['modulid']) ? (int)$_GET['modulid'] : null;
$anfang=isset($_GET['anfang']) ? $_GET['anfang'] : '';
if($modulid!==null) {
  $modulanfaenge=isset($_SESSION['intrain_modulanfaenge']) ? $_SESSION['intrain_modulanfaenge'] : array();
  $modulanfaenge[$modulid]=$anfang;
  //eventuelle kollidierende Module verschieben, damit dieses es nicht bei der Zeitberechnung wird
  foreach($modulanfaenge as $modid=>$anf) {
    if($modid==$modulid) continue;
    if($anf!=$anfang) continue;
    if(empty($anf)) continue;
    $modulanfaenge[$modid]=date('Y-m-d',strtotime('+1 day',strtotime($anf)));
  }
  $_SESSION['intrain_modulanfaenge']=$modulanfaenge;
}
require_once 'intrain_seite.php';
exit;
?>