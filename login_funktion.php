<?php
require_once 'db.php';
require_once 'Suche.php';
@session_start();
$nutzername=isset($_POST['nutzername']) ? $_POST['nutzername'] : (isset($_COOKIE['nutzername']) ? $_COOKIE['nutzername'] : '');
$passwort=isset($_POST['passwort']) ? $_POST['passwort'] : (isset($_COOKIE['passwort']) ? base64_decode(openssl_decrypt($_COOKIE['passwort'],'AES-128-CTR','dou61.Lo,iN',0,'1675032198765315')) : '');
function login($tabelle) {
  global $db,$moodleurl,$moodletoken,$nutzername,$passwort;
  if(empty($nutzername) || empty($passwort)) {
    return false;
  }
  $stmt=$db->prepare("select * from ".$tabelle." where nutzername=? or email=? limit 1");
  $stmt->bind_param('ss',$nutzername,$nutzername);
  $stmt->execute();
  $result=$stmt->get_result();
  $ich=$result->fetch_object();
  $result->free();
  if(empty($ich)) { //Nutzer nicht gefunden
    return false;
  }
  $ok=password_verify($passwort,$ich->passwort);
  if(!$ok) { 
    //Passwort NOK, eventuell neues Passwort aus Moodle holen
    $daten=(object)array(
      'login'=>$ich->nutzername
    );
    $url=$moodleurl.'webservice/rest/server.php?wstoken='.$moodletoken.'&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=nutzer_finden&daten='.urlencode(json_encode($daten));
    $ergebnis=json_decode(file_get_contents($url));
    if($ergebnis && (!is_object($ergebnis) || !isset($ergebnis->exception))) {
      if(!is_object($ergebnis)) {
        $ergebnis=json_decode($ergebnis);
      }
      $ok=password_verify($passwort,$ergebnis->password);
      if($ok) {
        if($ergebnis->firstname!=$ich->vorname 
            || $ergebnis->lastname!=$ich->nachname
            || $ergebnis->password!=$ich->passwort
            || (!$moodleisttest && $ergebnis->email!=$ich->email)
            || $ergebnis->id!=$ich->moodleid) {
          $stmt=$db->prepare("update ".$tabelle." set vorname=?,nachname=?,passwort=?,email=?,moodleid=? where id=? limit 1");
          $stmt->bind_param('ssssii',$ergebnis->firstname,$ergebnis->lastname,$ergebnis->password,$ergebnis->email,$ergebnis->id,$ich->id);
          $stmt->execute();
          $ich->vorname=$ergebnis->firstname;
          $ich->nachname=$ergebnis->lastname;
          $ich->passwort=$ergebnis->password;
          if(!$moodleisttest || $ergebnis->email!=($moodleisttest ? 'testdozent'.$id.'@gpb.de' : $ich->email)) {
            $ich->email=$ergebnis->email;
          }
          $ich->moodleid=$ergebnis->id;
        }
        if(isset($ich->tel1) && ($ergebnis->phone1!=$ich->tel1 || $ergebnis->phone2!=$ich->tel2)) {
          $stmt=$db->prepare("update ".$tabelle." set tel1=?,tel2=? where id=? limit 1");
          $stmt->bind_param('ssi',$ergebnis->phone1,$ergebnis->phone2,$ich->id);
          $stmt->execute();
          $ich->tel1=$ergebnis->phone1;
          $ich->tel2=$ergebnis->phone2;
        }
      }
    }
  }
  if($ok) {
    setcookie('nutzername',$nutzername,time()+60*60*24*365,'/');
    setcookie('passwort',openssl_encrypt(base64_encode($passwort),'AES-128-CTR','dou61.Lo,iN',0,'1675032198765315'),time()+60*60*24*365,'/');
    return $ich;
  }
  return false;
}
?>