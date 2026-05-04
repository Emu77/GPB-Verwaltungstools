<?php
require_once 'check_login.php';
$coursemoodleid=isset($_GET['coursemoodleid']) ? (int)$_GET['coursemoodleid'] : 0;
if($coursemoodleid>0) {
  $userids=array();
  $result=$db->query("select moodleid from gpb_berater where moodleid>0");
  while($row=$result->fetch_object()) {
    $userids[]=(int)$row->moodleid;
  }
  $result->free();
  $daten=(object)array(
    'rolename'=>'editingteacher',
    'courseid'=>$coursemoodleid,
    'userids'=>$userids,
    'ersetzen'=>true
  );
  $url=$moodleurl.'webservice/rest/server.php?wstoken='.$moodletoken.'&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=nutzer_zuweisen&daten='.urlencode(json_encode($daten));
  $ergebnis=json_decode(file_get_contents($url));
  if($ergebnis!='ok' && $ergebnis!='"ok"') {
    $_SESSION['kursspeichernnachricht']='<div class="fehler">'.json_encode($ergebnis).'</div>';
  } else {
    $_SESSION['kursspeichernnachricht']='<div class="ok">Berater angemeldet!</div>';
  }
}
header('Location:eignungstests.php');
exit;
?>