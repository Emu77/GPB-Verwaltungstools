<?php
require_once '../Suche.php';
@session_start();
$suche=new Suche();
$suche->inputString('tnname');
$massnbez=isset($_POST['massnbez']) ? $_POST['massnbez'] : (isset($_GET['massnbez']) ? $_GET['massnbez'] : '');
$suche->massnbez=$massnbez;
if(!empty($massnbez)) {
  $suche->where[]="id in(select tnid from gpb_massnahme_tn where massnahmeid in(select id from gpb_massnahme m where m.kuerzel like ? or m.titel like ?))";
  $suche->values[]='%'.$massnbez.'%';
  $suche->values[]='%'.$massnbez.'%';
  $suche->types.='ss';
}
$suche->von=isset($_POST['von']) ? $_POST['von'] : (isset($_GET['von']) ? $_GET['von'] : '');
if(!empty($suche->von)) {
  $suche->addKriterium("(ausstieg='0000-00-00' or ausstieg is null or ausstieg>=?)",$suche->von,'s');
}
$suche->bis=isset($_POST['bis']) ? $_POST['bis'] : (isset($_GET['bis']) ? $_GET['bis'] : '');
if(!empty($suche->bis)) {
  $suche->addKriterium("(einstieg='0000-00-00' or einstieg is null or einstieg<=?)",$suche->bis,'s');
}
$_SESSION['selfkurs_tn_suche']=$suche;

$redirect=isset($_POST['redirect']) ? $_POST['redirect'] : "tn.php";
header('Location:'.$redirect);
exit;
?>