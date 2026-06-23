<?php
require_once 'check_login.php';
$kursid=isset($_GET['kursid']) ? (int)$_GET['kursid'] : 0;
$kursmoodleid=isset($_GET['kursmoodleid']) ? (int)$_GET['kursmoodleid'] : 0;
$klassenids=isset($_GET['klassenids']) ? json_decode($_GET['klassenids']) : null;
if($kursid>0 && $klassenids!==null) {
  $db->query("delete from gpb_kurs_klasse where kursid=".$kursid);
  if(!empty($klassenids)) {
    $values=array();
    foreach($klassenids as $klasseid) {
      $values[]='('.$kursid.','.$klasseid.')';
    }
    $db->query("insert into gpb_kurs_klasse(kursid,klasseid) values".implode(',',$values));
  }
  echo 'OK';
  if($kursmoodleid>0) {
    $daten=(object)array(
      'courseid'=>$moodleid,
      'klassencourseids'=>array(),
      'ersetzen'=>true
    );
    if(!empty($klassenids)) {
      $result=$db->query("select moodleid from gpb_klasse where moodleid>0 and id in(".implode(',',$klassenids).")");
      while($row=$result->fetch_object()) {
        $daten->klassencourseids[]=$row->moodleid;
      }
      $result->free();
    }
    $url=$moodleurl.'webservice/rest/server.php?wstoken='.$moodletoken.'&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=meta_einschreibung&daten='.urlencode(json_encode($daten));
    $ergebnis=json_decode(file_get_contents($url));
    if($ergebnis!='ok' && $ergebnis!='"ok"') {
      echo is_string($ergebnis) ? $ergebnis : json_encode($ergebnis);
    }
  }
  exit;
}
echo 'Bitte Kurs und Klassen auswählen';
exit;
?>