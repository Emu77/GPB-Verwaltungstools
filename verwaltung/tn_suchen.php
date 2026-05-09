<?php
require_once '../Suche.php';
@session_start();
$suche=new Suche();
$mitisid=isset($_POST['mitisid']) ? (int)$_POST['mitisid'] : 0;
$suche->mitisid=$mitisid;
if($mitisid>0) {
  $suche->addKriterium("mitisid=?",$mitisid,'i');
}
$suche->inputString('tnname');
$suche->inputString('berufkuerzel');
$massnkuerzel=isset($_POST['massnkuerzel']) ? $_POST['massnkuerzel'] : (isset($_GET['massnkuerzel']) ? $_GET['massnkuerzel'] : '');
$suche->massnkuerzel=$massnkuerzel;
if(!empty($massnkuerzel)) {
  $suche->addKriterium("id in(select tnid from gpb_massnahme_tn where massnahmeid in(select id from gpb_massnahme m where m.kuerzel like ?))",
    '%'.$massnkuerzel.'%','s');
}
$klassebez=isset($_POST['klassebez']) ? $_POST['klassebez'] : (isset($_GET['klassebez']) ? $_GET['klassebez'] : '');
$suche->klassebez=$klassebez;
if(!empty($klassebez)) {
  $suche->addKriterium("id in(select tnid from gpb_klasse_tn where klasseid in(select id from gpb_klasse where bezeichnung like ?))",
    '%'.$klassebez.'%','s');
}
$_SESSION['tn_suche']=$suche;
header('Location:tn.php');
exit;
?>