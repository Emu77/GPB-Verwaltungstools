<?php
require_once '../Suche.php';
@session_start();
$suche=new Suche();
$suche->inputVonBis('beginn','beginn','beginn_');
$suche->inputVonBis('ende','ende','ende_');
$suche->inputString('modultitel');
$suche->inputString('titel');
$ort=isset($_POST['ort']) ? $_POST['ort'] : (isset($_GET['ort']) ? $_GET['ort'] : '');
if(!empty($ort)) {
  $suche->ort=$ort;
  $suche->addKriterium('ort=?',$suche->ort=='-' ? '' : $suche->ort,'s');
}
$suche->massnahmekuerzel=isset($_POST['massnahmekuerzel']) ? $_POST['massnahmekuerzel'] : (isset($_GET['massnahmekuerzel']) ? $_GET['massnahmekuerzel'] : '');
if(!empty($suche->massnahmekuerzel)) {
  $suche->addKriterium("id in(select k.id
    from gpb_massnahme m
    join gpb_massnahme_tn mtn on mtn.massnahmeid=m.id
    join gpb_klasse_tn ktn on ktn.tnid=mtn.tnid
    join gpb_kurs_klasse kk on kk.klasseid=ktn.klasseid
    join gpb_kurs k on k.id=kk.kursid
    where m.kuerzel like ?
    and (mtn.einstieg<>'0000-00-00' and mtn.einstieg is not null and mtn.einstieg<=k.ende)
    and (mtn.ausstieg='0000-00-00' or mtn.ausstieg is null or mtn.ausstieg>=k.beginn)
    and (ktn.einstieg<>'0000-00-00' and ktn.einstieg is not null and ktn.einstieg<=k.ende)
    and (ktn.ausstieg='0000-00-00' or ktn.ausstieg is null or ktn.ausstieg>=k.beginn))",
    '%'.$suche->massnahmekuerzel.'%','s');
}
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
$suche->inputBoolean('todo',"(kurzbericht_ok=0 or tagesbericht_ok=0 or notenstatus='todo')");
$_SESSION['bewertung_kurse_suche']=$suche;

header('Location:'.(isset($_POST['redirect']) && !empty($_POST['redirect']) ? $_POST['redirect'] : 'bewertung.php'));
exit;
?>