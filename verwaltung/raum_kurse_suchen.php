<?php
require_once '../Suche.php';
@session_start();

$raumid=isset($_POST['raumid']) ? (int)$_POST['raumid'] : (isset($_GET['raumid']) ? (int)$_GET['raumid'] : 0);
if($raumid<=0) {
  header('Location:raeume.php');
  exit;
}

$suche=new Suche();
$suche->inputVonBis('beginn','ende');
$_SESSION['raum_kurse_suche']=$suche;
header('Location:raum_kurse.php?raumid='.$raumid);
exit;
?>