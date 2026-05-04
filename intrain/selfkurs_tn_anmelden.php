<?php
require_once 'check_login.php';
$tnid=isset($_POST['tnid']) ? (int)$_POST['tnid'] : 0;
$tnmoodleid=isset($_POST['tnmoodleid']) ? (int)$_POST['tnmoodleid'] : 0;
$selfkursid=isset($_POST['selfkursid']) ? (int)$_POST['selfkursid'] : 0;
$selfkursmoodleid=isset($_POST['selfkursmoodleid']) ? (int)$_POST['selfkursmoodleid'] : 0;
$einstieg=isset($_POST['einstieg']) ? $_POST['einstieg'] : null;
$ausstieg=isset($_POST['ausstieg']) ? $_POST['ausstieg'] : null;
if($tnid>0 && $selfkursid>0) {
  $result=$db->query("select * from gpb_selfkurs_tn where tnid=".$tnid." and selfkursid=".$selfkursid." limit 1");
  $schon=$result->fetch_object();
  $result->free();

  if(!$schon) {
    $stmt=$db->prepare("insert into gpb_selfkurs_tn(selfkursid,tnid,einstieg,ausstieg) values(?,?,?,?)");
    $stmt->bind_param('iiss',$selfkursid,$tnid,$einstieg,$ausstieg);
    $stmt->execute();
  }
  
  if($selfkursmoodleid>0 && $tnmoodleid>0) {
    $daten=(object)array(
      'rolename'=>'student',
      'courseid'=>$selfkursmoodleid,
      'userid'=>$tnmoodleid,
      'anmelden'=>true
    );
    $url=$moodleurl.'webservice/rest/server.php?wstoken='.$moodletoken.'&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=nutzer_einschreibung&daten='.urlencode(json_encode($daten));
    $ergebnis=json_decode(file_get_contents($url));
    if($ergebnis!='ok' && $ergebnis!='"ok"') {
      if(isset($_SESSION['fehler_moodleid'])) {
        $_SESSION['fehler_moodleid'].="<br />Problem bei der Anmeldung im Moodle:<br />".(is_string($ergebnis) ? $ergebnis : json_encode($ergebnis));
      } else {
        $_SESSION['fehler_moodleid']="Problem bei der Anmeldung im Moodle:<br />".(is_string($ergebnis) ? $ergebnis : json_encode($ergebnis));
      }
    }
  }
}

$redirect=isset($_POST['redirect']) ? $_POST['redirect'] : 'tn_selfkurse.php?tnid='.$tnid;
header('Location:'.$redirect);
exit;
?>