<?php
require_once 'check_login.php';

$vorname=isset($_POST['vorname']) ? $_POST['vorname'] : '';
$nachname=isset($_POST['nachname']) ? $_POST['nachname'] : '';
$coursemoodleid=isset($_POST['coursemoodleid']) ? (int)$_POST['coursemoodleid'] : 0;
$testcmid=isset($_POST['testcmid']) ? (int)$_POST['testcmid'] : 0;
$terminid=isset($_POST['terminid']) ? (int)$_POST['terminid'] : 0;

function nutzer_reservieren($idnumber) {
  global $db,$moodleurl,$moodletoken,$vorname,$nachname;
  $moodleid=0;
  $result=$db->query("select id,moodleid from gpb_eignungstest_nutzer where id not in(select nutzerid from gpb_eignungstest_anmeldung) order by moodleid limit 1");
  $nutzer=$result->fetch_object();
  $result->free();
  
  if(empty($nutzer)) {
    $_SESSION['nutzeranmeldennachricht'].='<div class="fehler">Keine freien Dummy-Nutzer mehr :-(</div>';
    return null;
  }
  
  $passwort=''.random_int(1000,9999);
  $daten=(object)array(
      'userid'=>$nutzer->moodleid
      ,'vorname'=>$vorname
      ,'nachname'=>$nachname
      ,'hash'=>password_hash($passwort,PASSWORD_DEFAULT)
      ,'idnumber'=>$idnumber
    );
  $url=$moodleurl.'webservice/rest/server.php?wstoken='.$moodletoken.'&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=nutzer_speichern&daten='.urlencode(json_encode($daten));
  $user=json_decode(file_get_contents($url));
  if(is_object($user) && isset($user->exception)) {
    $_SESSION['nutzeranmeldennachricht'].='<div class="fehler">'.json_encode($user).'</div>';
  } else {
    $stmt=$db->prepare("update gpb_eignungstest_nutzer set vorname=?,nachname=?,passwort=? where id=? limit 1");
    $stmt->bind_param('sssi',$vorname,$nachname,$passwort,$nutzer->id);
    $stmt->execute();
    $nutzer->vorname=$vorname;
    $nutzer->nachname=$nachname;
    $nutzer->passwort=$passwort;
  }
  return $nutzer;
}

$_SESSION['nutzeranmeldennachricht']='';
if($coursemoodleid>0) {
  $nutzer=nutzer_reservieren('');
  if(!empty($nutzer)) {
    $daten=(object)array(
      'rolename'=>'student',
      'courseid'=>$coursemoodleid,
      'userid'=>$nutzer->moodleid,
      'anmelden'=>true
    );
    $url=$moodleurl.'webservice/rest/server.php?wstoken='.$moodletoken.'&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=nutzer_einschreibung&daten='.urlencode(json_encode($daten));
    $ergebnis=json_decode(file_get_contents($url));
    if($ergebnis!='ok' && $ergebnis!='"ok"') {
      $_SESSION['nutzeranmeldennachricht'].='<div class="fehler">'.json_encode($ergebnis).'</div>';
    } else {
      $stmt=$db->prepare("insert into gpb_eignungstest_anmeldung(nutzerid,coursemoodleid) values(?,?)");
      $stmt->bind_param('ii',$nutzer->id,$coursemoodleid);
      $stmt->execute();
    }
    
    if(isset($_SESSION['eignung_interessent']) && $_SESSION['eignung_interessent']->vorname==$vorname && $_SESSION['eignung_interessent']->nachname==$nachname) {
      unset($_SESSION['eignung_interessent']);
    }
  }
}

if($testcmid>0) {
  $nutzer=nutzer_reservieren(''.$testcmid);
  if(!empty($nutzer)) {
    $stmt=$db->prepare("insert into gpb_eignungstest_anmeldung(nutzerid,testcmid,terminid) values(?,?,?)");
    $stmt->bind_param('iii',$nutzer->id,$testcmid,$terminid);
    $stmt->execute();
    
    if(isset($_SESSION['eignung_interessent']) && $_SESSION['eignung_interessent']->vorname==$vorname && $_SESSION['eignung_interessent']->nachname==$nachname) {
      unset($_SESSION['eignung_interessent']);
    }
  }
}
header('Location:eignungstests.php');
exit;
?>