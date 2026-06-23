<?php
require_once 'check_login.php';
$redirect=isset($_GET['redirect']) && $_GET['redirect']!='N';
$mitisid=isset($_GET['mitisid']) ? (int)$_GET['mitisid'] : 0;
if(empty($mitisid)) {
  if($redirect) {
    header('Location:intrain.php');
    exit;
  }
  require_once 'intrain_seite.php';
  exit;
}
$result=$db->query("select * from gpb_basket where mitisid=".$mitisid);
$interessent=$result->fetch_object();
$result->free();
if(empty($interessent)) {
  $_SESSION['intrain_fehler']="<div class=\"nok\">Interessent mit ID=".$mitisid." nicht gefunden :-(</div>";
  if($redirect) {
    header('Location:intrain.php');
    exit;
  }
  require_once 'intrain_seite.php';
  exit;
}

if(!empty($interessent->zeitmodell)) {
  $_SESSION['intrain_zeitmodell']=$interessent->zeitmodell;
}
if(!empty($interessent->wochenue)) {
  $_SESSION['intrain_wochenue']=$interessent->wochenue;
}
$_SESSION['intrain_arbeitszeiten']=empty($interessent->arbeitszeiten) ? array() : (array)json_decode($interessent->arbeitszeiten);
if(!empty($interessent->anfangsdatum)) {
  $_SESSION['instrain_anfangsdatum']=$interessent->anfangsdatum;
}
if(empty($interessent->urlaub)) {
  $interessent->urlaub=array();
} else {
  $interessent->urlaub=json_decode($interessent->urlaub);
}
$interessent->ausbildungids=array();
if(empty($interessent->modulids) || $interessent->interessenbereich!='intrain') {
  $interessent->modulids=array();
} else {
  $interessent->modulids=(array)json_decode($interessent->modulids);
}
$_SESSION['intrain_modulids']=$interessent->modulids;
if(empty($interessent->modulanfaenge) || $interessent->interessenbereich!='intrain') {
  $interessent->modulanfaenge=array();
} else {
  $interessent->modulanfaenge=(array)json_decode($interessent->modulanfaenge);
}
$_SESSION['intrain_modulanfaenge']=$interessent->modulanfaenge;

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

$_SESSION['intrain_interessent']=$interessent;
if($redirect) {
  header('Location:intrain.php');
  exit;
}
require_once 'intrain_seite.php';
exit;
?>