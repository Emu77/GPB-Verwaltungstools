<?php
require_once 'check_login.php';

$modulid=isset($_POST['modulid']) ? (int)$_POST['modulid'] : 0;

if($modulid<=0) {
  header('Location:spezialisten.php');
  exit;
}

if(isset($_SESSION['spez_ausbildungids'])) {
  foreach($_SESSION['spez_ausbildungids'] as $ausid=>$modids) {
    if(isset($modids[$modulid])) {
      //Modul ist schon teil einer gemerkten Ausbildung
      header('Location:spezialisten.php');
      exit;
    }
  }
}

$modulids=isset($_SESSION['spez_modulids']) ? $_SESSION['spez_modulids'] : array();
$modulids[$modulid]=true;
$_SESSION['spez_modulids']=$modulids;

header('Location:spezialisten.php');
exit;
?>