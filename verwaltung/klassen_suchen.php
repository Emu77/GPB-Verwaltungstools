<?php
require_once '../Suche.php';
@session_start();
$suche=new Suche();
$suche->inputInt('familieid');
$suche->inputInt('berufid');
$suche->inputString('bezeichnung');
$suche->inputString('berufkuerzel');
$ort=isset($_POST['ort']) ? $_POST['ort'] : (isset($_GET['ort']) ? $_GET['ort'] : '');
if(!empty($ort)) {
  $suche->ort=$ort;
  $suche->addKriterium('ort=?',$suche->ort=='-' ? '' : $suche->ort,'s');
}
$suche->am=isset($_POST['am']) ? $_POST['am'] : (isset($_GET['am']) ? $_GET['am'] : '');
$suche->starter=(isset($_POST['starter']) && $_POST['starter']!='N') || (isset($_GET['starter']) && $_GET['starter']!='N');
if($suche->starter) {
  if(empty($suche->am) || $suche->am=='0000-00-00') {
    $suche->am=date('Y-m-d');
  }
  $suche->addKriterium('beginn>=date_sub(?,interval 16 day)',$suche->am,'s');
  $suche->addKriterium('beginn<=date_add(?,interval 16 day)',$suche->am,'s');
} else if(!empty($suche->am) && $suche->am!='0000-00-00') {
  $suche->addKriterium("beginn<=?",$suche->am,'s');
  $suche->addKriterium("ende>=?",$suche->am,'s');
}
$_SESSION['klassen_suche']=$suche;
header('Location:klassen.php');
exit;
?>