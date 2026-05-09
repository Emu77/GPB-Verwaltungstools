<?php
require_once '../Suche.php';
@session_start();
$suche=new Suche();
$suche->inputVonBis('beginn','ende');
$suche->inputString('anlass');
$suche->inputString('art');
$ort=isset($_POST['ort']) ? $_POST['ort'] : (isset($_GET['ort']) ? $_GET['ort'] : '');
if(!empty($ort)) {
  $suche->ort=$ort;
  $suche->addKriterium('length(ort)=0 or ort=?',$suche->ort=='-' ? '' : $suche->ort,'s');
}
$suche->klassebez=isset($_POST['klassebez']) ? $_POST['klassebez'] : (isset($_GET['klassebez']) ? $_GET['klassebez'] : '');
if(!empty($suche->klassebez)) {
  $suche->addKriterium("id in(select ferienid from gpb_klasse_ferien where klasseid in(select id from gpb_klasse where bezeichnung like ?))",
    '%'.$suche->klassebez.'%','s');
}
$_SESSION['ferien_suche']=$suche;
header('Location:ferien.php');
exit;
?>