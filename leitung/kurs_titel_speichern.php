<?php
require_once 'check_login.php';
$kursid=isset($_GET['kursid']) ? (int)$_GET['kursid'] : 0;
$kursmoodleid=isset($_GET['kursmoodleid']) ? (int)$_GET['kursmoodleid'] : 0;
$titel=isset($_GET['titel']) ? $_GET['titel'] : false;
if(!empty($kursid) && !empty($titel)) {
  $stmt=$db->prepare("update gpb_kurs set titel=? where id=?");
  $stmt->bind_param('si',$titel,$kursid);
  $stmt->execute();
  echo 'OK';
  if($kursmoodleid>0) {
    $result=$db->query("select * from gpb_kurs where id=".$kursid);
    $kurs=$result->fetch_object();
    $result->free();
    $daten=(object)array(
      'courseid'=>$kurs->moodleid,
      'kategorie'=>'ILKurse',
      'titel'=>$kurs->titel,
      'kursid'=>$kurs->id,
      'beginn'=>$kurs->beginn,
      'ende'=>$kurs->ende,
      'keinenoten'=>($kurs->notenstatus=='keine' ? 'J' : 'N')
    );
    $url=$moodleurl.'webservice/rest/server.php?wstoken='.$moodletoken.'&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=kurs_speichern&daten='.urlencode(json_encode($daten));
    $ergebnis=json_decode(file_get_contents($url));
    if(is_object($ergebnis) && isset($ergebnis->exception)) {
      echo json_encode($ergebnis);
    }
  }
  exit;
}
echo 'Bitte Kurs auswählen und Titel eingeben';
exit;
?>