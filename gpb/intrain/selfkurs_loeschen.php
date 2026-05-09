<?php
require_once 'check_login.php';
$id=isset($_GET['kursvorlageid']) ? (int)$_GET['kursvorlageid'] : 0;
$result=$db->query("select * from gpb_kursvorlage where id=".$id);
$vorlage=$result->fetch_object();
$result->free();
if(!$vorlage) {
  header('Location:kursvorlagen.php');
  exit;
}
$result=$db->query("select * from gpb_kursvorlage_dozent where kursvorlageid=".$vorlage->id." and dozentid=".$ich->id);
$ok=$result->fetch_object();
$result->free();
if(!$ok) {
  header('Location:kursvorlagen.php');
  exit;
}
if($vorlage->moodleid>0) {
  $daten=(object)array(
    'courseid'=>$vorlage->moodleid,
    'kursid'=>'Vorlage_'.$vorlage->id
  );
  $url=$moodleurl.'webservice/rest/server.php?wstoken='.$moodletoken.'&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=kurs_loeschen&daten='.urlencode(json_encode($daten));
  $ergebnis=json_decode(file_get_contents($url));
  if(is_object($ergebnis) && isset($ergebnis->exception)) {
    $_SESSION['nachricht']='<div class="fehler">'.json_encode($ergebnis).'</div>';
    header('Location:kursvorlagen.php');
    exit;
  }
}
$db->query("delete from gpb_kursvorlage_dozent where kursvorlageid=".$vorlage->id);
$db->query("delete from gpb_kursvorlage where id=".$vorlage->id);
header('Location:kursvorlagen.php');
exit;
?>