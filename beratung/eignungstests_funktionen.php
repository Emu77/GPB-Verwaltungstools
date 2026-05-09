<?php
function eignungstestAnmeldungLoeschen($anmeldungid) {
  global $db,$moodleurl,$moodletoken;
  if($anmeldungid>0){
    $result=$db->query("select a.*,n.moodleid as nutzermoodleid from gpb_eignungstest_anmeldung a join gpb_eignungstest_nutzer n on n.id=a.nutzerid where a.id=".$anmeldungid." limit 1");
    $an=$result->fetch_object();
    $result->free();
  } else {
    $an=false;
  }
  if(!empty($an)) {
    $_SESSION['nutzerabmeldennachricht']='';
    $ok=true;
    if($an->coursemoodleid>0) {
      $daten=(object)array(
        'rolename'=>'student',
        'courseid'=>$an->coursemoodleid,
        'userid'=>$an->nutzermoodleid,
        'anmelden'=>false
      );
      $url=$moodleurl.'webservice/rest/server.php?wstoken='.$moodletoken.'&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=nutzer_einschreibung&daten='.urlencode(json_encode($daten));
      $ergebnis=json_decode(file_get_contents($url));
      if($ergebnis!='ok' && $ergebnis!='"ok"') {
        $_SESSION['nutzerabmeldennachricht'].='<div class="fehler">'.json_encode($ergebnis).'</div>';
        $ok=false;
      }
    }
    if($an->testcmid>0) {
      // Abgaben dieses Nutzers löschen
      $daten=(object)array(
        'userids'=>array($an->nutzermoodleid)
      );
      $url=$moodleurl.'webservice/rest/server.php?wstoken='.$moodletoken.'&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=tests_abgaben_loeschen&daten='.urlencode(json_encode($daten));
      $ergebnis=json_decode(file_get_contents($url));
      if($ergebnis!='ok' && $ergebnis!='"ok"') {
        $_SESSION['nutzerabmeldennachricht'].='<div class="fehler">'.json_encode($ergebnis).'</div>';
      }
      
      $daten=(object)array(
          'userid'=>$an->nutzermoodleid
          ,'vorname'=>'Interessent'
          ,'nachname'=>''.$an->nutzerid
          ,'idnumber'=>''
        );
      $url=$moodleurl.'webservice/rest/server.php?wstoken='.$moodletoken.'&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=nutzer_speichern&daten='.urlencode(json_encode($daten));
      $user=json_decode(file_get_contents($url));
      if(is_object($user) && isset($user->exception)) {
        $_SESSION['nutzerabmeldennachricht'].='<div class="fehler">'.json_encode($user).'</div>';
        $ok=false;
      } else {
        $db->query("update gpb_eignungstest_nutzer set vorname='',nachname='' where id=".$an->nutzerid." limit 1");
      }
    }
    if($ok) {
      $db->query("delete from gpb_eignungstest_anmeldung where id=".$an->id." limit 1");
    }
  }
}
?>