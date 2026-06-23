<?php
require_once 'check_login.php';

$suche=new Suche();
$suche->inputVonBis('beginn','ende');
$suche->inputString('titel');
$suche->inputString('ort');
$suche->inputString('raum');
$suche->klassebez=isset($_POST['klassebez']) ? $_POST['klassebez'] : '';
if(!empty($suche->klassebez)) {
  $suche->addKriterium("id in(select kursid from gpb_kurs_klasse where klasseid in(select id from gpb_klasse where bezeichnung like ?))",'%'.$suche->klassebez.'%','s');
}
$suche->dozentname=isset($_POST['dozentname']) ? $_POST['dozentname'] : '';
if(!empty($suche->dozentname)) {
  $suche->addKriterium("id in(select kursid from gpb_kurs_dozent where dozentid in(select id from gpb_dozent where concat(vorname,concat(' ',nachname)) like ?))",'%'.$suche->dozentname.'%','s');
}
$suche->inputBoolean('nurich',"id in (select kursid from gpb_kurs_dozent where dozentid=".$ich->id.")");
$suche->inputInt('moodleid');
$suche->inputBoolean('todo',"(kurzbericht_ok=0 or tagesbericht_ok=0 or notenstatus='todo')");
$_SESSION['kurse_suche']=$suche;
header('Location:kurse.php');
exit;
?>