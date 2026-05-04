<?php
require_once 'check_login.php';

$terminid=isset($_GET['terminid']) ? (int)$_GET['terminid'] : 0;

$_SESSION['terminloeschennachricht']='';

$anmeldungen=array();
$quizuserids=array();
$kursanmeldungen=array();
$result=$db->query("select a.*,n.moodleid as nutzermoodleid from gpb_eignungstest_anmeldung a join gpb_eignungstest_nutzer n on n.id=a.nutzerid where a.terminid=".$terminid);
while($row=$result->fetch_object()) {
  $anmeldungen[]=$row;
  if($row->testcmid>0) {
    $quizuserids[]=$row->nutzermoodleid;
  }
  if($row->coursemoodleid>0) {
    $kursanmeldungen[]=$row;
  }
}
$result->free();

if(!empty($quizuserids)) {
  // Abgaben dieser Nutzer löschen
  $daten=(object)array(
    'userids'=>$quizuserids
  );
  $url=$moodleurl.'webservice/rest/server.php?wstoken='.$moodletoken.'&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=tests_abgaben_loeschen&daten='.urlencode(json_encode($daten));
  $ergebnis=json_decode(file_get_contents($url));
  if($ergebnis!='ok' && $ergebnis!='"ok"') {
    $_SESSION['terminloeschennachricht'].='<div class="fehler">'.json_encode($ergebnis).'</div>';
  }
}

//Anmeldungen in Kursen entfernen
foreach($kursanmeldungen as $an) {
  $daten=(object)array(
    'rolename'=>'student',
    'courseid'=>$an->coursemoodleid,
    'userid'=>$an->nutzermoodleid,
    'anmelden'=>false
  );
  $url=$moodleurl.'webservice/rest/server.php?wstoken='.$moodletoken.'&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=nutzer_einschreibung&daten='.urlencode(json_encode($daten));
  $ergebnis=json_decode(file_get_contents($url));
  if($ergebnis!='ok' && $ergebnis!='"ok"') {
    $_SESSION['terminloeschennachricht'].='<div class="fehler">'.json_encode($ergebnis).'</div>';
  }
}

//Moodle-Nutzer zurücksetzen
foreach($anmeldungen as $an) {
  $daten=(object)array(
      'userid'=>$an->nutzermoodleid
      ,'vorname'=>'Interessent'
      ,'nachname'=>''.$an->nutzerid
      ,'idnumber'=>''
      ,'hash'=>'--'
    );
  $url=$moodleurl.'webservice/rest/server.php?wstoken='.$moodletoken.'&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=nutzer_speichern&daten='.urlencode(json_encode($daten));
  $user=json_decode(file_get_contents($url));
  if(is_object($user) && isset($user->exception)) {
    $_SESSION['terminloeschennachricht'].='<div class="fehler">'.json_encode($user).'</div>';
  } else {
    $db->query("update gpb_eignungstest_nutzer set vorname='',nachname='',passwort='' where id=".$an->nutzerid." limit 1");
  }
}

//Anmeldungen löschen
$db->query("delete from gpb_eignungstest_anmeldung where terminid=".$terminid);
//Termin löschen
$db->query("delete from gpb_eignungstest_termin where id=".$terminid);

header('Location:eignungstests.php');
exit;
?>