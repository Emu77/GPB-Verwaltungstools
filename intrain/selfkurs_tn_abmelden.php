<?php
require_once 'check_login.php';
$tnid=isset($_GET['tnid']) ? (int)$_GET['tnid'] : 0;
$tnmoodleid=isset($_GET['tnmoodleid']) ? (int)$_GET['tnmoodleid'] : 0;
$selfkursid=isset($_GET['selfkursid']) ? (int)$_GET['selfkursid'] : 0;
$selfkursmoodleid=isset($_GET['selfkursmoodleid']) ? (int)$_GET['selfkursmoodleid'] : 0;
if($tnid>0 && $selfkursid>0) {
  $db->query("delete from gpb_selfkurs_tn where tnid=".$tnid." and selfkursid=".$selfkursid);
  
  if($selfkursmoodleid>0 && $tnmoodleid>0) {
    $daten=(object)array(
      'rolename'=>'student',
      'courseid'=>$selfkursmoodleid,
      'userid'=>$tnmoodleid,
      'anmelden'=>false
    );
    $url=$moodleurl.'webservice/rest/server.php?wstoken='.$moodletoken.'&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=nutzer_einschreibung&daten='.urlencode(json_encode($daten));
    $ergebnis=json_decode(file_get_contents($url));
    if($ergebnis!='ok' && $ergebnis!='"ok"') {
      if(isset($_SESSION['fehler_moodleid'])) {
        $_SESSION['fehler_moodleid'].="<br />Problem bei der Abmeldung im Moodle:<br />".(is_string($ergebnis) ? $ergebnis : json_encode($ergebnis));
      } else {
        $_SESSION['fehler_moodleid']="Problem bei der Abmeldung im Moodle:<br />".(is_string($ergebnis) ? $ergebnis : json_encode($ergebnis));
      }
    }
  }
}

$redirect=isset($_GET['redirect']) ? $_GET['redirect'] : 'tn_selfkurse.php?tnid='.$tnid;
header('Location:'.$redirect);
exit;
?>