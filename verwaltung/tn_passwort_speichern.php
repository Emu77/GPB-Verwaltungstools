<?php
require_once 'check_login.php';

$tnid=isset($_POST['tnid']) ? (int)$_POST['tnid'] : 0;
$moodleid=isset($_POST['moodleid']) ? (int)$_POST['moodleid'] : 0;
$passwort=isset($_POST['passwort']) ? $_POST['passwort'] : false;
$mussaendern=isset($_POST['mussaendern']) && $_POST['mussaendern']!='N';

if($tnid<=0) {
  header('Location:tn.php');
  exit;
}

$hash=password_hash($passwort,PASSWORD_DEFAULT);
$stmt=$db->prepare("update gpb_tn set passwort=? where id=? limit 1");
$stmt->bind_param('si',$hash,$tnid);
$stmt->execute();
$_SESSION['passwortspeichernnachricht']='<div class="done">Passwort gespeichert!</div>';

if($moodleid>0) {
  $daten=(object)array(
      'userid'=>$moodleid
      ,'hash'=>$hash
      ,'mussaendern'=>($mussaendern ? 1 : 0)
    );
  $url=$moodleurl.'webservice/rest/server.php?wstoken='.$moodletoken.'&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=nutzer_speichern&daten='.urlencode(json_encode($daten));
  $user=json_decode(file_get_contents($url));
  if(is_object($user) && isset($user->exception)) {
    $_SESSION['passwortspeichernnachricht'].='<div class="fehler">'.json_encode($user).'</div>';
  }
}
header('Location:tn_sehen.php?tnid='.$tnid);
exit;
?>