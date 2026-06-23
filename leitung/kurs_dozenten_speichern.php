<?php
require_once 'check_login.php';
$kursid=isset($_GET['kursid']) ? (int)$_GET['kursid'] : 0;
$kursmoodleid=isset($_GET['kursmoodleid']) ? (int)$_GET['kursmoodleid'] : 0;
$dozentenids=isset($_GET['dozentenids']) ? json_decode($_GET['dozentenids']) : null;
if($kursid>0 && $dozentenids!==null) {
  $db->query("delete from gpb_kurs_dozent where kursid=".$kursid);
  if(!empty($dozentenids)) {
    $values=array();
    foreach($dozentenids as $dozentid) {
      $values[]='('.$kursid.','.$dozentid.')';
    }
    $db->query("insert into gpb_kurs_dozent(kursid,dozentid) values".implode(',',$values));
  }
  echo 'OK';
  if($kursmoodleid>0) {
    $daten=(object)array(
      'rolename'=>'editingteacher',
      'courseid'=>$moodleid,
      'userids'=>array(),
      'ersetzen'=>true
    );
    if(!empty($dozentenids)) {
      $result=$db->query("select moodleid from gpb_dozent where moodleid>0 and id in(".implode(',',$dozentenids).")");
      while($row=$result->fetch_object()) {
        $daten->userids[]=$row->moodleid;
      }
      $result->free();
    }
    $url=$moodleurl.'webservice/rest/server.php?wstoken='.$moodletoken.'&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=nutzer_zuweisen&daten='.urlencode(json_encode($daten));
    $ergebnis=json_decode(file_get_contents($url));
    if($ergebnis!='ok' && $ergebnis!='"ok"') {
      echo is_string($ergebnis) ? $ergebnis : json_encode($ergebnis);
    }
  }
  exit;
}
echo 'Bitte Kurs und Dozenten auswählen';
exit;
?>