<?php
require_once 'Suche.php';
@session_start();
$suche=new RaumplanSuche();
$am=isset($_POST['am']) ? $_POST['am'] : (isset($_GET['am']) ? $_GET['am'] : '');
$suche->am=$am;
if(!empty($am)) {
  $suche->addKriterium("beginn<=?",$suche->am,'s');
  $suche->addKriterium("ende>=?",$suche->am,'s');
}
$suche->inputString('ort');
$_SESSION['raumplan_suche']=$suche;
header('Location:index.php');
exit;
?>