<?php
@session_start();
require_once '../db.php';

if(isset($_COOKIE['anmeldung'])) {
  $_SESSION['anmeldung']=json_decode($_COOKIE['anmeldung']);
}
if(isset($_SESSION['anmeldung'])) {
  header('Location:test_anmeldeformular.php');
  exit;
}

$testmoodleid=isset($_POST['testmoodleid']) ? (int)$_POST['testmoodleid'] : 0;
$terminid=isset($_POST['terminid']) ? (int)$_POST['terminid'] : 0;
$vorname=isset($_POST['vorname']) ? $_POST['vorname'] : '';
$nachname=isset($_POST['nachname']) ? $_POST['nachname'] : '';
if(empty($terminid) || (empty($vorname) && empty($nachname))) {
  header('Location:test_anmeldeformular.php');
  exit;
}

$result=$db->query("select * from gpb_eignungstest_termin where id=".$terminid." limit 1");
$termin=$result->fetch_object();
$result->free();
if(empty($termin)) {
  header('Location:test_anmeldeformular.php?testmoodleid='.$testmoodleid);
  exit;
}

$result=$db->query("select n.* from gpb_eignungstest_nutzer n where terminid=0 limit 1");
$nutzer=$result->fetch_object();
$result->free();
if(empty($nutzer)) {
  $_SESSION['fehler']='Für diesen Test sind leider keine Plätze mehr frei. Rufen Sie uns bitte an!';
  header('Location:test_anmeldeformular.php');
  exit;
}

// Alte Abgaben dieser Nutzer löschen
$daten=(object)array(
  'userids'=>array($nutzer->moodleid)
);
$url=$moodleurl.'webservice/rest/server.php?wstoken='.$moodletoken.'&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=tests_abgaben_loeschen&daten='.urlencode(json_encode($daten));
$ergebnis=json_decode(file_get_contents($url));
if($ergebnis!='ok' && $ergebnis!='"ok"') {
  $_SESSION['fehler'].='Fehler bei der Anmeldung :-( Rufen Sie uns bitte an!';
  header('Location:test_anmeldeformular.php');
  exit;
}

$passwort=''.random_int(1000,9999);
$daten=(object)array(
    'userid'=>$nutzer->moodleid
    ,'vorname'=>$vorname
    ,'nachname'=>$nachname
    ,'hash'=>password_hash($passwort,PASSWORD_DEFAULT)
    ,'idnumber'=>$termin->testtitel
  );
$url=$moodleurl.'webservice/rest/server.php?wstoken='.$moodletoken.'&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=nutzer_speichern&daten='.urlencode(json_encode($daten));
$user=json_decode(file_get_contents($url));
if(is_object($user) && isset($user->exception)) {
  $_SESSION['fehler'].='Fehler bei der Anmeldung :-( Rufen Sie uns bitte an!';
  header('Location:test_anmeldeformular.php');
  exit;
} else {
  $stmt=$db->prepare("update gpb_eignungstest_nutzer set vorname=?,nachname=?,passwort=?,terminid=? where moodleid=? limit 1");
  $stmt->bind_param('sssii',$vorname,$nachname,$passwort,$terminid,$nutzer->moodleid);
  $stmt->execute();
}

$anmeldung=(object)array(
  'id'=>$nutzer->id,
  'testtitel'=>$termin->testtitel,
  'vorname'=>$vorname,
  'nachname'=>$nachname,
  'wann'=>$termin->wann,
  'ort'=>$termin->ort
);
$_SESSION['anmeldung']=$anmeldung;
//setcookie("anmeldung",json_encode($anmeldung),strtotime('+1 day',strtotime($termin->wann)));
header('Location:test_anmeldeformular.php');
exit;
?>