<?php
require_once '../Suche.php';
@session_start();
$suche=new Suche();
$suche->inputVonBis('beginn','ende');
$suche->inputString('titel');
$suche->inputString('ort');
$suche->inputString('raum');
$klassebez=isset($_POST['klassebez']) ? $_POST['klassebez'] : (isset($_GET['klassebez']) ? $_GET['klassebez'] : false);
$suche->klassebez=$klassebez;
if(!empty($klassebez)) {
  $suche->addKriterium("id in(select kursid from gpb_kurs_klasse where klasseid in(select id from gpb_klasse where bezeichnung like ?))",
    '%'.$klassebez.'%','s');
}
$dozentname=isset($_POST['dozentname']) ? $_POST['dozentname'] : (isset($_GET['dozentname']) ? $_GET['dozentname'] : false);
$suche->dozentname=$dozentname;
if(!empty($dozentname)) {
  $suche->addKriterium("id in(select kursid from gpb_kurs_dozent where dozentid in(select id from gpb_dozent where concat(vorname,concat(' ',nachname)) like ?))",
    '%'.$dozentname.'%','s');
}
$suche->inputInt('moodleid');
$suche->inputBoolean('todo',"(kurzbericht_ok=0 or tagesbericht_ok=0 or notenstatus='todo')");
$_SESSION['dozent_kurse_suche']=$suche;
$dozentid=isset($_POST['dozentid']) ? (int)$_POST['dozentid'] : (isset($_GET['dozentid']) ? (int)$_GET['dozentid'] : 0);
header('Location:dozent_kurse.php?dozentid='.$dozentid);
exit;
?>