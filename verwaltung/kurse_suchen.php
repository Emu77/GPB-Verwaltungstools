<?php
require_once '../Suche.php';
@session_start();
$suche=new Suche();
$am=isset($_POST['am']) ? $_POST['am'] : (isset($_GET['am']) ? $_GET['am'] : false);
$suche->am=$am;
if(!empty($am)) {
  $suche->addKriterium("beginn<=?",$am,'s');
  $suche->addKriterium("ende>=?",$am,'s');
}
$suche->inputString('titel');
$ort=isset($_POST['ort']) ? $_POST['ort'] : (isset($_GET['ort']) ? $_GET['ort'] : '');
if(!empty($ort)) {
  $suche->ort=$ort;
  if($suche->ort=='-') {
    $suche->where[]="(ort='' or ort is null)";
  } else {
    $suche->addKriterium('ort=?',$suche->ort,'s');
  }
}
$suche->inputString('raum');
$suche->inputBoolean('ohneraum',"raumid is null");
$suche->klassebez=isset($_POST['klassebez']) ? $_POST['klassebez'] : (isset($_GET['klassebez']) ? $_GET['klassebez'] : '');
if(!empty($suche->klassebez)) {
  $suche->addKriterium("id in(select kursid from gpb_kurs_klasse where klasseid in(select id from gpb_klasse where bezeichnung like ?))",
    '%'.$suche->klassebez.'%','s');
}
$suche->dozentname=isset($_POST['dozentname']) ? $_POST['dozentname'] : (isset($_GET['dozentname']) ? $_GET['dozentname'] : '');
if(!empty($suche->dozentname)) {
  $suche->addKriterium("id in(select kursid from gpb_kurs_dozent where dozentid in(select id from gpb_dozent where concat(vorname,concat(' ',nachname)) like ?))",
    '%'.$suche->dozentname.'%','s');
}
$suche->inputInt('moodleid');
$suche->inputBoolean('todo',"(kurzbericht_ok=0 or tagesbericht_ok=0 or notenstatus='todo')");
$_SESSION['kurse_suche']=$suche;
header('Location:kurse.php');
exit;
?>