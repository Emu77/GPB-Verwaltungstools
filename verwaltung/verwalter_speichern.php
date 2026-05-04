<?php
require_once '../Suche.php';
@session_start();
require_once 'check_login.php';

$fehler=array();
$id=isset($_POST['id']) ? (int)$_POST['id'] : 0;
$moodleid=isset($_POST['moodleid']) ? (int)$_POST['moodleid'] : 0;
$moodlenutzererstellen=isset($_POST['moodlenutzererstellen']) && $_POST['moodlenutzererstellen']!='N';
if($id<=0 && $moodleid>0) {
  //TODO Daten aus Moodle laden
}
$anrede=isset($_POST['anrede']) ? $_POST['anrede'] : '';
$vorname=isset($_POST['vorname']) ? $_POST['vorname'] : '';
if(empty($vorname)) {
  $fehler['vorname']='Bitte einen Vornamen eingeben';
}
$nachname=isset($_POST['nachname']) ? $_POST['nachname'] : '';
if(empty($nachname)) {
  $fehler['nachname']='Bitte einen Nachnamen eingeben';
}
$nutzername=isset($_POST['nutzername']) ? strtolower($_POST['nutzername']) : '';
if(empty($nutzername)) {
  $fehler['nutzername']='Bitte einen Nutzernamen eingeben';
} else {
  $stmt=$db->prepare("select id from gpb_verwalter where nutzername=? and id<>? limit 1");
  $stmt->bind_param('si',$nutzername,$id);
  $stmt->execute();
  $result=$stmt->get_result();
  $anderer=$result->fetch_object();
  $result->free();
  if($anderer) {
    $fehler['nutzername']='Nutzername schon belegt: <a href="verwalter_sehen.php?verwalterid='.$anderer->id.'">'.$nutzername.'</a>';
  }
}
$passwort=isset($_POST['passwort']) ? $_POST['passwort'] : '';
$email=isset($_POST['email']) ? $_POST['email'] : '';
if(empty($email)) {
  $fehler['email']='Bitte eine Emailadresse eingeben';
} else {
  $stmt=$db->prepare("select id from gpb_verwalter where email=? and id<>? limit 1");
  $stmt->bind_param('si',$email,$id);
  $stmt->execute();
  $result=$stmt->get_result();
  $anderer=$result->fetch_object();
  $result->free();
  if($anderer) {
    $fehler['email']='Email schon belegt: <a href="verwalter_sehen.php?verwalterid='.$anderer->id.'">'.$nutzername.'</a>';
  }
}
$massnahmenanzeigen=isset($_POST['massnahmenanzeigen']) && $_POST['massnahmenanzeigen']!='N';
$ort=isset($_POST['ort']) ? $_POST['ort'] : '';
if(!empty($fehler)) {
  $fehler['verwalter']=(object)array(
    'id'=>$id,
    'anrede'=>$anrede,
    'vorname'=>$vorname,
    'nachname'=>$nachname,
    'nutzername'=>$nutzername,
    'email'=>$email,
    'moodleid'=>$moodleid,
    'massnahmenanzeigen'=>$massnahmenanzeigen,
    'ort'=>$ort,
  );
  $_SESSION['fehler']=$fehler;
  header('Location:verwalter_bearbeiten.php?verwalterid='.$id);
  exit;
}
if($id>0) {
  if($moodleid<=0 && $moodlenutzererstellen) {
    $daten=(object)array(
      'nutzername'=>$nutzername,
      'vorname'=>$vorname,
      'nachname'=>$nachname,
      'email'=>$moodleisttest ? 'testverwalter'.$id.'@gpb.de' : $email,
      'hash'=>empty($passwort) ? '' : password_hash($passwort,PASSWORD_DEFAULT),
      'rolle'=>'verwalter'
    );
    $url=$moodleurl.'webservice/rest/server.php?wstoken='.$moodletoken.'&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=nutzer_speichern&daten='.urlencode(json_encode($daten));
    $ergebnis=json_decode(file_get_contents($url));
    if(is_object($ergebnis) && isset($ergebnis->exception)) {
      $fehler['moodleid']=json_encode($ergebnis);
    } else {
      if(!is_object($ergebnis)) {
        $ergebnis=json_decode($ergebnis);
      }
      $moodleid=$ergebnis->id;
    }
  }
  $stmt=$db->prepare("update gpb_verwalter set anrede=?,vorname=?,nachname=?,nutzername=?,email=?,moodleid=?,massnahmenanzeigen=?,ort=? where id=? limit 1");
  $stmt->bind_param('sssssiisi',$anrede,$vorname,$nachname,$nutzername,$email,$moodleid,$massnahmenanzeigen,$ort,$id);
  $stmt->execute();
  if(!empty($passwort)) {
    $passwort=password_hash($passwort,PASSWORD_DEFAULT);
    $stmt=$db->prepare("update gpb_verwalter set passwort=? where id=? limit 1");
    $stmt->bind_param('si',$passwort,$id);
    $stmt->execute();
  }
  if($moodleid>0 && !$moodlenutzererstellen) {
    // Moodle aktualisieren
    $daten=(object)array(
      'nutzername'=>$nutzername,
      'vorname'=>$vorname,
      'nachname'=>$nachname,
      'email'=>$moodleisttest ? 'testverwalter'.$id.'@gpb.de' : $email,
      'rolle'=>'verwalter'
    );
    if(!empty($passwort)) {
      $daten->hash=$passwort;
    }
    $url=$moodleurl.'webservice/rest/server.php?wstoken='.$moodletoken.'&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=nutzer_speichern&daten='.urlencode(json_encode($daten));
    $ergebnis=json_decode(file_get_contents($url));
    if(is_object($ergebnis) && isset($ergebnis->exception)) {
      $fehler['moodleid']=json_encode($ergebnis);
    }
  }
  if($ich->id==$id) {
    $ich=(object)array(
      'id'=>$id,
      'anrede'=>$anrede,
      'vorname'=>$vorname,
      'nachname'=>$nachname,
      'nutzername'=>$nutzername,
      'email'=>$email,
      'moodleid'=>$moodleid,
      'massnahmenanzeigen'=>$massnahmenanzeigen,
      'ort'=>$ort,
      'istleiter'=>$ich->istleiter,
      'siehtbewerter'=>$ich->siehtbewerter
    );
    $_SESSION['verwaltung_ich']=$ich;
    if($ich->istleiter) {
      $_SESSION['leitung_ich']=$ich;
    }
  }
} else {
  $passwort=empty($passwort) ? '' : password_hash($passwort,PASSWORD_DEFAULT);
  $stmt=$db->prepare("insert into gpb_verwalter(anrede,vorname,nachname,nutzername,passwort,email,massnahmenanzeigen,ort) values(?,?,?,?,?,?,?,?)");
  $stmt->bind_param('ssssssis',$anrede,$vorname,$nachname,$nutzername,$passwort,$email,$massnahmenanzeigen,$ort);
  $stmt->execute();
  $id=$db->insert_id;
  if($moodleid<=0 && $moodlenutzererstellen) {
    $daten=(object)array(
      'nutzername'=>$nutzername,
      'vorname'=>$vorname,
      'nachname'=>$nachname,
      'email'=>$moodleisttest ? 'testverwalter'.$id.'@gpb.de' : $email,
      'hash'=>$passwort,
      'rolle'=>'verwalter'
    );
    $url=$moodleurl.'webservice/rest/server.php?wstoken='.$moodletoken.'&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=nutzer_speichern&daten='.urlencode(json_encode($daten));
    $ergebnis=json_decode(file_get_contents($url));
    if(is_object($ergebnis) && isset($ergebnis->exception)) {
      $fehler['moodleid']=json_encode($ergebnis);
    } else {
      if(!is_object($ergebnis)) {
        $ergebnis=json_decode($ergebnis);
      }
      $moodleid=$ergebnis->id;
      $db->query("update gpb_verwalter set moodleid=".$moodleid." where id=".$id." limit 1");
    }
  }
}
if(empty($fehler)) {
  $_SESSION['nachricht']='<div class="done">Verwalter gespeichert!</div>';
} else {
  $_SESSION['fehler']=$fehler;
}
header('Location:verwalter_sehen.php?verwalterid='.$id);
exit;
?>