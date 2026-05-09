<?php
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
  $stmt=$db->prepare("select id from gpb_dozent where nutzername=? and id<>? limit 1");
  $stmt->bind_param('si',$nutzername,$id);
  $stmt->execute();
  $result=$stmt->get_result();
  $anderer=$result->fetch_object();
  $result->free();
  if($anderer) {
    $fehler['nutzername']='Nutzername schon belegt: <a href="dozent_sehen.php?dozentid='.$anderer->id.'">'.$nutzername.'</a>';
  }
}
$passwort=isset($_POST['passwort']) ? $_POST['passwort'] : '';
$createpassword=isset($_POST['createpassword']) && $_POST['createpassword']!='N';
$mussaendern=isset($_POST['mussaendern']) && $_POST['mussaendern']!='N';
$email=isset($_POST['email']) ? $_POST['email'] : '';
if(empty($email)) {
  $fehler['email']='Bitte eine Emailadresse eingeben';
} else {
  $stmt=$db->prepare("select id from gpb_dozent where email=? and id<>? limit 1");
  $stmt->bind_param('si',$email,$id);
  $stmt->execute();
  $result=$stmt->get_result();
  $anderer=$result->fetch_object();
  $result->free();
  if($anderer) {
    $fehler['email']='Email schon belegt: <a href="dozent_sehen.php?dozentid='.$anderer->id.'">'.$nutzername.'</a>';
  }
}
$tel1=isset($_POST['tel1']) ? $_POST['tel1'] : '';
$tel2=isset($_POST['tel2']) ? $_POST['tel2'] : '';
$istintrain=isset($_POST['istintrain']) && $_POST['istintrain']!='N';
$idrverfuegbar=isset($_POST['idrverfuegbar']) && $_POST['idrverfuegbar']!='N';
if(!empty($fehler)) {
  $fehler['dozent']=(object)array(
    'id'=>$id,
    'anrede'=>$anrede,
    'vorname'=>$vorname,
    'nachname'=>$nachname,
    'nutzername'=>$nutzername,
    'createpassword'=>$createpassword,
    'mussaendern'=>$mussaendern,
    'email'=>$email,
    'tel1'=>$tel1,
    'tel2'=>$tel2,
    'istintrain'=>$istintrain,
    'idrverfuegbar'=>$idrverfuegbar,
    'moodleid'=>$moodleid
  );
  $_SESSION['fehler']=$fehler;
  header('Location:dozent_bearbeiten.php?dozentid='.$id);
  exit;
}
if($id>0) {
  if($moodleid<=0 && $moodlenutzererstellen) {
    $daten=(object)array(
      'nutzername'=>$nutzername,
      'vorname'=>$vorname,
      'nachname'=>$nachname,
      'email'=>$moodleisttest ? 'testdozent'.$id.'@gpb.de' : $email,
      'hash'=>empty($passwort) ? '' : password_hash($passwort,PASSWORD_DEFAULT),
      'createpassword'=>($createpassword ? 1 : 0),
      'auth_forcepasswordchange'=>($mussaendern ? 1 : 0)
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
      // Arbeitsschutz
      $daten=(object)array(
        'rolename'=>'student',
        'kursid'=>'Arbeitsschutz',
        'userids'=>array($moodleid),
        'ersetzen'=>false
      );
      $url=$moodleurl.'webservice/rest/server.php?wstoken='.$moodletoken.'&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=nutzer_zuweisen&daten='.urlencode(json_encode($daten));
      $ergebnis=json_decode(file_get_contents($url));
      if($ergebnis!='ok' && $ergebnis!='"ok"') {
        $fehler['moodleid']=json_encode($ergebnis);
      }
    }
  }
  if($ich->istleiter) {
    $stmt=$db->prepare("update gpb_dozent set anrede=?,vorname=?,nachname=?,nutzername=?,moodleid=?,email=?,tel1=?,tel2=?,istintrain=?,idrverfuegbar=? where id=? limit 1");
    $stmt->bind_param('ssssisssiii',$anrede,$vorname,$nachname,$nutzername,$moodleid,$email,$tel1,$tel2,$istintrain,$idrverfuegbar,$id);
  } else {
    $stmt=$db->prepare("update gpb_dozent set anrede=?,vorname=?,nachname=?,nutzername=?,moodleid=?,email=?,tel1=?,tel2=? where id=? limit 1");
    $stmt->bind_param('ssssisssi',$anrede,$vorname,$nachname,$nutzername,$moodleid,$email,$tel1,$tel2,$id);
  }
  $stmt->execute();
  if(!empty($passwort)) {
    $passwort=password_hash($passwort,PASSWORD_DEFAULT);
    $stmt=$db->prepare("update gpb_dozent set passwort=? where id=? limit 1");
    $stmt->bind_param('si',$passwort,$id);
    $stmt->execute();
  }
  if($moodleid>0 && !$moodlenutzererstellen) {
    // Moodle aktualisieren
    $daten=(object)array(
      'nutzername'=>$nutzername,
      'vorname'=>$vorname,
      'nachname'=>$nachname,
      'email'=>$moodleisttest ? 'testdozent'.$id.'@gpb.de' : $email,
      'createpassword'=>($createpassword ? 1 : 0),
      'auth_forcepasswordchange'=>($mussaendern ? 1 : 0)
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
} else {
  $passwort=empty($passwort) ? '' : password_hash($passwort,PASSWORD_DEFAULT);
  $stmt=$db->prepare("insert into gpb_dozent(anrede,vorname,nachname,nutzername,passwort,moodleid,email,tel1,tel2,istintrain,idrverfuegbar) values(?,?,?,?,?,?,?,?,?,?,?)");
  $stmt->bind_param('sssssisssii',$anrede,$vorname,$nachname,$nutzername,$passwort,$moodleid,$email,$tel1,$tel2,$istintrain,$idrverfuegbar);
  $stmt->execute();
  $id=$db->insert_id;
  if($moodleid<=0 && $moodlenutzererstellen) {
    $daten=(object)array(
      'nutzername'=>$nutzername,
      'vorname'=>$vorname,
      'nachname'=>$nachname,
      'email'=>$moodleisttest ? 'testdozent'.$id.'@gpb.de' : $email,
      'hash'=>$passwort,
      'createpassword'=>($createpassword ? 1 : 0),
      'auth_forcepasswordchange'=>($mussaendern ? 1 : 0)
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
      $db->query("update gpb_dozent set moodleid=".$moodleid." where id=".$id." limit 1");
      // Arbeitsschutz
      $daten=(object)array(
        'rolename'=>'student',
        'kursid'=>'Arbeitsschutz',
        'userids'=>array($moodleid),
        'ersetzen'=>false
      );
      $url=$moodleurl.'webservice/rest/server.php?wstoken='.$moodletoken.'&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=nutzer_zuweisen&daten='.urlencode(json_encode($daten));
      $ergebnis=json_decode(file_get_contents($url));
      if($ergebnis!='ok' && $ergebnis!='"ok"') {
        $fehler['moodleid']=json_encode($ergebnis);
      }
    }
  }
}
if(empty($fehler)) {
  $_SESSION['nachricht']='<div class="done">Dozent gespeichert!</div>';
} else {
  $_SESSION['fehler']=$fehler;
}
header('Location:dozent_sehen.php?dozentid='.$id);
exit;
?>