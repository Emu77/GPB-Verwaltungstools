<?php
require_once 'check_login.php';

$modulid=isset($_GET['modulid']) ? (int)$_GET['modulid'] : 0;

if($modulid<=0) {
  header('Location:spezialisten.php');
  exit;
}

$ausbildungids=isset($_SESSION['spez_ausbildungids']) ? $_SESSION['spez_ausbildungids'] : array();
$modulids=isset($_SESSION['spez_modulids']) ? $_SESSION['spez_modulids'] : array();
$modulanfaenge=isset($_SESSION['spez_modulanfaenge']) ? $_SESSION['spez_modulanfaenge'] : array();

$ausidsZuEntfernen=array();
foreach($ausbildungids as $ausid=>$modids) {
  if(isset($modids[$modulid])) {
    //Ausbildung entfernen, aber ihre anderen Module da lassen
    foreach($modids as $modid=>$dummy) {
      if($modid!=$modulid) {
        $modulids[$modid]=true;
      }
    }
    $ausidsZuEntfernen[]=$ausid;
  }
}
foreach($ausidsZuEntfernen as $ausid) {
  unset($ausbildungids[$ausid]);
}
if(isset($modulids[$modulid])) {
  unset($modulids[$modulid]);
}
if(isset($modulanfaenge[$modulid])) {
  unset($modulanfaenge[$modulid]);
}

$_SESSION['spez_ausbildungids']=$ausbildungids;
$_SESSION['spez_modulids']=$modulids;
$_SESSION['spez_modulanfaenge']=$modulanfaenge;

header('Location:spezialisten.php');
exit;
?>