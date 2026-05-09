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
  $stmt=$db->prepare("select id from gpb_berater where nutzername=? and id<>? limit 1");
  $stmt->bind_param('si',$nutzername,$id);
  $stmt->execute();
  $result=$stmt->get_result();
  $anderer=$result->fetch_object();
  $result->free();
  if($anderer) {
    $fehler['nutzername']='Nutzername schon belegt: <a href="berater_sehen.php?beraterid='.$anderer->id.'">'.$nutzername.'</a>';
  }
}
$passwort=isset($_POST['passwort']) ? $_POST['passwort'] : '';
$createpassword=isset($_POST['createpassword']) && $_POST['createpassword']!='N';
$mussaendern=isset($_POST['mussaendern']) && $_POST['mussaendern']!='N';
$tel=isset($_POST['tel']) ? $_POST['tel'] : '';
$email=isset($_POST['email']) ? $_POST['email'] : '';
if(empty($email)) {
  $fehler['email']='Bitte eine Emailadresse eingeben';
} else {
  $stmt=$db->prepare("select id from gpb_berater where email=? and id<>? limit 1");
  $stmt->bind_param('si',$email,$id);
  $stmt->execute();
  $result=$stmt->get_result();
  $anderer=$result->fetch_object();
  $result->free();
  if($anderer) {
    $fehler['email']='Email schon belegt: <a href="berater_sehen.php?beraterid='.$anderer->id.'">'.$nutzername.'</a>';
  }
}
$ort=isset($_POST['ort']) ? $_POST['ort'] : '';
if(!empty($fehler)) {
  $fehler['berater']=(object)array(
    'id'=>$id,
    'anrede'=>$anrede,
    'vorname'=>$vorname,
    'nachname'=>$nachname,
    'nutzername'=>$nutzername,
    'createpassword'=>$createpassword,
    'mussaendern'=>$mussaendern,
    'tel'=>$tel,
    'email'=>$email,
    'ort'=>$ort,
    'signaturbild'=>'',
    'moodleid'=>$moodleid
  );
  $_SESSION['fehler']=$fehler;
  header('Location:berater_bearbeiten.php?beraterid='.$id);
  exit;
}
if($id>0) {
  if($moodleid<=0 && $moodlenutzererstellen) {
    $daten=(object)array(
      'nutzername'=>$nutzername,
      'vorname'=>$vorname,
      'nachname'=>$nachname,
      'email'=>$moodleisttest ? 'testberater'.$id.'@gpb.de' : $email,
      'hash'=>empty($passwort) ? '' : password_hash($passwort,PASSWORD_DEFAULT),
      'rolle'=>'dozent',
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
  $stmt=$db->prepare("update gpb_berater set anrede=?,vorname=?,nachname=?,nutzername=?,tel=?,email=?,ort=?,moodleid=? where id=? limit 1");
  $stmt->bind_param('sssssssii',$anrede,$vorname,$nachname,$nutzername,$tel,$email,$ort,$moodleid,$id);
  $stmt->execute();
  if(!empty($passwort)) {
    $passwort=password_hash($passwort,PASSWORD_DEFAULT);
    $stmt=$db->prepare("update gpb_berater set passwort=? where id=? limit 1");
    $stmt->bind_param('si',$passwort,$id);
    $stmt->execute();
  }
  if($moodleid>0 && !$moodlenutzererstellen) {
    // Moodle aktualisieren
    $daten=(object)array(
      'nutzername'=>$nutzername,
      'vorname'=>$vorname,
      'nachname'=>$nachname,
      'email'=>$moodleisttest ? 'testberater'.$id.'@gpb.de' : $email,
      'rolle'=>'dozent',
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
  $result=$db->query("select signaturbild from gpb_berater where id=".$id);
  $altesignatur=$result->fetch_object();
  $result->free();
  if(empty($altesignatur)) {
    $altesignatur='';
  } else {
    $altesignatur=$altesignatur->signaturbild;
  }
  if($ich->id==$id) {
    $ich=(object)array(
      'id'=>$id,
      'anrede'=>$anrede,
      'vorname'=>$vorname,
      'nachname'=>$nachname,
      'nutzername'=>$nutzername,
      'tel'=>$tel,
      'email'=>$email,
      'ort'=>$ort,
      'signaturbild'=>$altesignatur,
      'moodleid'=>$moodleid
    );
    $_SESSION['berater_ich']=$ich;
  }
} else {
  $passwort=empty($passwort) ? '' : password_hash($passwort,PASSWORD_DEFAULT);
  $stmt=$db->prepare("insert into gpb_berater(anrede,vorname,nachname,nutzername,passwort,tel,email,ort,moodleid) values(?,?,?,?,?,?,?,?,?)");
  $stmt->bind_param('ssssssssi',$anrede,$vorname,$nachname,$nutzername,$passwort,$tel,$email,$ort,$moodleid);
  $stmt->execute();
  $id=$db->insert_id;
  if($moodleid<=0 && $moodlenutzererstellen) {
    $daten=(object)array(
      'nutzername'=>$nutzername,
      'vorname'=>$vorname,
      'nachname'=>$nachname,
      'email'=>$moodleisttest ? 'testberater'.$id.'@gpb.de' : $email,
      'hash'=>$passwort,
      'rolle'=>'dozent',
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
      $db->query("update gpb_berater set moodleid=".$moodleid." where id=".$id." limit 1");
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
if(isset($_FILES['signaturbild']) && file_exists($_FILES['signaturbild']['tmp_name'])) {
  if(isset($altesignatur) && !empty($altesignatur) && file_exists('signaturbilder/'.$altesignatur)) {
    unlink('signaturbilder/'.$altesignatur);
  }
  $datei='Signaturbild'.$vorname.$nachname.strrchr($_FILES['signaturbild']['name'],'.');
  $path='signaturbilder/'.$datei;
  if(move_uploaded_file($_FILES['signaturbild']['tmp_name'],$path)) {
    chmod($path,0644);
  } else {
    $datei='';
    $fehler['signaturbild']='<span style="color:red;">Fehler beim Hochladen der Bilddatei :-(</span>';
  }
  $stmt=$db->prepare("update gpb_berater set signaturbild=? where id=? limit 1");
  $stmt->bind_param('si',$datei,$id);
  $stmt->execute();
  if($ich->id==$id) {
    $ich->signaturbild=$datei;
    $_SESSION['berater_ich']->signaturbild=$datei;
  }
} 
else if(isset($_POST['signaturbild_loeschen']) && $_POST['signaturbild_loeschen']!='N') {
  if(isset($altesignatur) && !empty($altesignatur) && file_exists('signaturbilder/'.$altesignatur)) {
    unlink('signaturbilder/'.$altesignatur);
  }
  $stmt=$db->prepare("update gpb_berater set signaturbild='' where id=? limit 1");
  $stmt->bind_param('i',$id);
  $stmt->execute();
  if($ich->id==$id) {
    $ich->signaturbild='';
    $_SESSION['berater_ich']->signaturbild='';
  }
}
if(empty($fehler)) {
  $_SESSION['nachricht']='<div class="done">Berater gespeichert!</div>';
} else {
  $_SESSION['fehler']=$fehler;
}
header('Location:berater_sehen.php?beraterid='.$id);
exit;
?>