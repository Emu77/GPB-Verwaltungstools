<?php
require_once 'check_login.php';

$tnid=isset($_POST['tnid']) ? (int)$_POST['tnid'] : 0;
$gender=isset($_POST['gender']) && $_POST['gender']!='N';
$anrede=isset($_POST['anrede']) ? $_POST['anrede'] : '';
$vorname=isset($_POST['vorname']) ? $_POST['vorname'] : '';
if($tnid>0){
  $result=$db->query("select * from gpb_tn where id=".$tnid." limit 1");
  $tn=$result->fetch_object();
  $result->free();
} else {
  $tn=false;
}
if(!empty($tn)) {
  if($gender) {
    $stmt=$db->prepare("update gpb_tn set anrede=?,vorname=? where id=? limit 1");
    $stmt->bind_param('ssi',$anrede,$vorname,$tnid);
    $stmt->execute();
  } else {
    $db->query("update gpb_tn set anrede=mitisanrede,vorname=mitisvorname where id=".$tnid." limit 1");
    $vorname=$tn->mitisvorname;
  }
  $_SESSION['nachricht']='<div class="done">Gender gespeichert!</div>';
  if($tn->moodleid>0) {
    $daten=(object)array(
      'userid'=>$tn->moodleid,
      'vorname'=>$vorname
    );
    $url=$moodleurl.'webservice/rest/server.php?wstoken='.$moodletoken.'&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=nutzer_speichern&daten='.urlencode(json_encode($daten));
    $ergebnis=json_decode(file_get_contents($url));
    if(is_object($ergebnis) && isset($ergebnis->exception)) {
      $_SESSION['nachricht'].='<div class="nok">'.json_encode($ergebnis).'</div>';
    }
  }
}
header('Location:tn_sehen.php?tnid='.$tnid);
exit;
?>