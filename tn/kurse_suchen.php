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
$suche->inputBoolean('nurich',"id in(select k.id
     from gpb_klasse_tn ktn 
  join gpb_kurs_klasse kk on kk.klasseid=ktn.klasseid
  join gpb_kurs_view k on k.id=kk.kursid
  where ktn.tnid=".$ich->id."
   and (ktn.einstieg<>'0000-00-00' and ktn.einstieg is not null and ktn.einstieg<=k.ende)
   and (ktn.ausstieg='0000-00-00' or ktn.ausstieg is null or ktn.ausstieg>=k.beginn))");
$suche->inputInt('moodleid');
$_SESSION['kurse_suche']=$suche;
header('Location:kurse.php');
exit;
?>