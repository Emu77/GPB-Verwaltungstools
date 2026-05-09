<?php
require_once 'check_login.php';
$mitisid=isset($_GET['mitisid']) ? (int)$_GET['mitisid'] : 0;
if(empty($mitisid)) {
  header('Location:spezialisten.php');
  exit;
}
$result=$db->query("select * from gpb_basket where mitisid=".$mitisid);
$interessent=$result->fetch_object();
$result->free();
if(empty($interessent)) {
  $_SESSION['spez_fehler']="<div class=\"nok\">Interessent mit ID=".$mitisid." nicht gefunden :-(</div>";
  header('Location:spezialisten.php');
  exit;
}

if(!empty($interessent->zeitmodell)) {
  $_SESSION['spez_zeitmodell']=$interessent->zeitmodell;
}
if(!empty($interessent->wochenue)) {
  $_SESSION['spez_wochenue']=$interessent->wochenue;
}
if(!empty($interessent->anfangsdatum)) {
  $_SESSION['spez_anfangsdatum']=$interessent->anfangsdatum;
}
if(empty($interessent->urlaub)) {
  $interessent->urlaub=array();
} else {
  $interessent->urlaub=json_decode($interessent->urlaub);
}
if(empty($interessent->ausbildungids) || $interessent->interessenbereich!='spez') {
  $interessent->ausbildungids=array();
} else {
  $ausbids=(array)json_decode($interessent->ausbildungids);
  $interessent->ausbildungids=array();
  foreach($ausbids as $ausbid=>$modids) {
    $interessent->ausbildungids[$ausbid]=(array)$modids;
  }
}
$_SESSION['spez_ausbildungids']=$interessent->ausbildungids;

if(empty($interessent->modulids) || $interessent->interessenbereich!='spez') {
  $interessent->modulids=array();
} else {
  $interessent->modulids=(array)json_decode($interessent->modulids);
}
$_SESSION['spez_modulids']=$interessent->modulids;
if(empty($interessent->modulanfaenge) || $interessent->interessenbereich!='spez') {
  $interessent->modulanfaenge=array();
} else {
  $interessent->modulanfaenge=(array)json_decode($interessent->modulanfaenge);
}
$_SESSION['spez_modulanfaenge']=$interessent->modulanfaenge;

if(empty($interessent->interessenausmitis)) {
  $interessent->interessenausmitis=array();
} else {
  $interessent->interessenausmitis=(array)json_decode($interessent->interessenausmitis);
}
if(empty($interessent->interessenfuermitis)) {
  $interessent->interessenfuermitis=array();
} else {
  $interessent->interessenfuermitis=(array)json_decode($interessent->interessenfuermitis);
}

$_SESSION['spez_interessent']=$interessent;
header('Location:spezialisten.php');
exit;
?>