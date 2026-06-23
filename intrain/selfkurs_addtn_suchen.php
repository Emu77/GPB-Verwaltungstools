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
$massnwann=isset($_POST['massnwann']) ? $_POST['massnwann'] : (isset($_GET['massnwann']) ? $_GET['massnwann'] : '');
$suche->massnwann=$massnwann;
if(!empty($massnwann)) {
  $suche->where[]="id in(select tnid from gpb_massnahme_tn where (einstieg<>'0000-00-00' and einstieg is not null and einstieg<=?) and (ausstieg='0000-00-00' or ausstieg is null or ausstieg>=?))";
  $suche->values[]=$massnwann;
  $suche->values[]=$massnwann;
  $suche->types.='ss';
}
$_SESSION['selfkurs_addtn_suche']=$suche;

$redirect=isset($_POST['redirect']) ? $_POST['redirect'] : "tn.php";
header('Location:'.$redirect);
exit;
?>