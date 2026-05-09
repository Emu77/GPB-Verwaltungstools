<?php
require_once '../Suche.php';
@session_start();
$suche=new Suche();
$ort=isset($_POST['ort']) ? $_POST['ort'] : (isset($_GET['ort']) ? $_GET['ort'] : '');
if(!empty($ort)) {
  $suche->ort=$ort;
  $suche->addKriterium('ort=?',$suche->ort=='-' ? '' : $suche->ort,'s');
}
$suche->inputString('tuer');
$suche->inputString('zusatz');
$suche->inputString('art');
$anzahlPlaetze=isset($_POST['anzahlPlaetze']) ? (int)$_POST['anzahlPlaetze'] : (isset($_GET['anzahlPlaetze']) ? (int)$_GET['anzahlPlaetze'] : '');
$suche->anzahlPlaetze=$anzahlPlaetze;
if(!empty($anzahlPlaetze)) {
  $suche->addKriterium("anzahlPlaetze>=?",$anzahlPlaetze,'i');
}
$anzahlComputer=isset($_POST['anzahlComputer']) ? (int)$_POST['anzahlComputer'] : (isset($_GET['anzahlComputer']) ? (int)$_GET['anzahlComputer'] : '');
$suche->anzahlComputer=$anzahlComputer;
if(!empty($anzahlComputer)) {
  $suche->addKriterium("anzahlComputer>=?",$anzahlComputer,'i');
}
$_SESSION['raeume_suche']=$suche;
header('Location:raeume.php');
exit;
?>