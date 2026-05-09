<?php
require_once 'check_login.php';

$tnid=isset($_GET['tnid']) ? (int)$_GET['tnid'] : 0;
$moodleid=isset($_GET['moodleid']) ? (int)$_GET['moodleid'] : 0;
if($tnid>0 && $moodleid>0) {
  $daten=(object)array(
      'userid'=>$moodleid
      ,'createpassword'=>1
    );
  $url=$moodleurl.'webservice/rest/server.php?wstoken='.$moodletoken.'&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=nutzer_speichern&daten='.urlencode(json_encode($daten));
  $user=json_decode(file_get_contents($url));
  if(is_object($user) && isset($user->exception)) {
    $_SESSION['passwortresetnachricht']='<div class="fehler">'.json_encode($user).'</div>';
  } else  { //$user ist ein Nutzer-Objekt mit dem alten Passwort
    $_SESSION['passwortresetnachricht']='<div class="done">Passwort zurückgesetzt!</div>';
  }
}
header('Location:tn_sehen.php?tnid='.$tnid);
exit;
?>