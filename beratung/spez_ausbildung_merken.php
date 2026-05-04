<?php
require_once 'check_login.php';

$ausbildungid=isset($_POST['ausbildungid']) ? (int)$_POST['ausbildungid'] : 0;
$modids=array();
$result=$db->query("select * from gpb_spezausbildung_modul where ausbildungid=".$ausbildungid);
while($row=$result->fetch_object()) {
  $modids[$row->modulid]=true;
}
$result->free();

if(empty($modids)) {
  header('Location:spezialisten.php');
  exit;
}

$ausbildungids=array(); //nur eine Ausbildung merken isset($_SESSION['spez_ausbildungids']) ? $_SESSION['spez_ausbildungids'] : array();
$modulids=isset($_SESSION['spez_modulids']) ? $_SESSION['spez_modulids'] : array();
//ID der Ausbildung und ihre Module merken
$ausbildungids[$ausbildungid]=$modids;
//Ihre Module sind nicht mehr einzeln gebucht
foreach($modids as $modid=>$dummy) {
  if(isset($modulids[$modid])) {
    unset($modulids[$modid]);
  }
}
$_SESSION['spez_ausbildungids']=$ausbildungids;
$_SESSION['spez_modulids']=$modulids;

header('Location:spezialisten.php');
exit;
?>