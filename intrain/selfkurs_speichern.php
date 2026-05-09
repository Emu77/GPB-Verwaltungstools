<?php
require_once 'check_login.php';
require_once 'IntrainSelfkurs.php';

$fehler=array();
$id=isset($_POST['id']) ? (int)$_POST['id'] : 0;
if($id>0) {
  $selfkurs=Selfkurs::einenLaden($id,'IntrainSelfkurs');
  if(empty($selfkurs)) {
    header('Location:selfkurse.php');
    exit;
  }
}
$moodleid=$id>0 ? $selfkurs->moodleid : (isset($_POST['moodleid']) ? (int)$_POST['moodleid'] : 0);
$titel=isset($_POST['titel']) ? $_POST['titel'] : '';
if(empty($titel)) {
  $fehler['titel']='Bitte einen Titel eingeben';
} else {
  $stmt=$db->prepare("select id from gpb_kurs where titel=? limit 1");
  $stmt->bind_param('s',$titel);
  $stmt->execute();
  $result=$stmt->get_result();
  $anderer=$result->fetch_object();
  $result->free();
  if($anderer) {
    $fehler['titel']='Titel schon belegt: <a href="../dozent/kurs_sehen.php?kursid='.$anderer->id.'" target="dozent">'.$titel.'</a>';
  } else {
    $stmt=$db->prepare("select id from gpb_selfkurs where titel=? and id<>? limit 1");
    $stmt->bind_param('si',$titel,$id);
    $stmt->execute();
    $result=$stmt->get_result();
    $anderer=$result->fetch_object();
    $result->free();
    if($anderer) {
      $fehler['titel']='Titel schon belegt: <a href="selfkurs_sehen.php?selfkursid='.$anderer->id.'">'.$titel.'</a>';
    } 
  }
}
if(!empty($fehler)) {
  $fehler['selfkurs']=(object)array(
    'id'=>$id,
    'titel'=>$titel,
    'moodleerstellen'=>($moodleid<=0),
    'moodleid'=>$moodleid
  );
  $_SESSION['fehler']=$fehler;
  header('Location:selfkurs_bearbeiten.php?selfkursid='.$id);
  exit;
}
if($id>0) {
  if($moodleid<=0) {
    $daten=(object)array(
      'kategorie'=>'inTrain',
      'titel'=>$titel,
      'kursid'=>'inTrain_'.$id
    );
    $url=$moodleurl.'webservice/rest/server.php?wstoken='.$moodletoken.'&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=kurs_speichern&daten='.urlencode(json_encode($daten));
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
  $stmt=$db->prepare("update gpb_selfkurs set titel=?,moodleid=? where id=? limit 1");
  $stmt->bind_param('sii',$titel,$moodleid,$id);
  $stmt->execute();
  if($moodleid>0) {
    $daten=(object)array(
      'courseid'=>$moodleid,
      'kategorie'=>'inTrain',
      'titel'=>$titel,
      'kursid'=>'inTrain_'.$id
    );
    $url=$moodleurl.'webservice/rest/server.php?wstoken='.$moodletoken.'&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=kurs_speichern&daten='.urlencode(json_encode($daten));
    $ergebnis=json_decode(file_get_contents($url));
    if(is_object($ergebnis) && isset($ergebnis->exception)) {
      $fehler['moodleid']=json_encode($ergebnis);
    }
  }
} else {
  $stmt=$db->prepare("insert into gpb_selfkurs(titel,moodleid) values(?,?)");
  $stmt->bind_param('si',$titel,$moodleid);
  $stmt->execute();
  $id=$db->insert_id;
  if($moodleid<=0) {
    $daten=(object)array(
      'kategorie'=>'inTrain',
      'titel'=>$titel,
      'kursid'=>'inTrain_'.$id
    );
    $url=$moodleurl.'webservice/rest/server.php?wstoken='.$moodletoken.'&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=kurs_speichern&daten='.urlencode(json_encode($daten));
    $ergebnis=json_decode(file_get_contents($url));
    if(is_object($ergebnis) && isset($ergebnis->exception)) {
      $fehler['moodleid']=json_encode($ergebnis);
    } else {
      if(!is_object($ergebnis)) {
        $ergebnis=json_decode($ergebnis);
      }
      $moodleid=$ergebnis->id;
      $db->query("update gpb_selfkurs set moodleid=".$moodleid." where id=".$id." limit 1");
    }
  }
}
if($moodleid>0) {
  //Dozenten korrigieren
  $daten=(object)array(
    'rolename'=>'editingteacher',
    'courseid'=>$moodleid,
    'userids'=>array()
  );
  $result=$db->query("select * from gpb_dozent where istintrain and moodleid>0");
  while($row=$result->fetch_object()) {
    $daten->userids[]=$row->moodleid;
  }
  $result->free();
  $url=$moodleurl.'webservice/rest/server.php?wstoken='.$moodletoken.'&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=nutzer_zuweisen&daten='.urlencode(json_encode($daten));
  $ergebnis=json_decode(file_get_contents($url));
  if($ergebnis!='ok' && $ergebnis!='"ok"') {
    if(isset($fehler['moodleid'])) {
      $fehler['moodleid'].="<br />Problem bei der Anmeldung der Dozenten:<br />".(is_string($ergebnis) ? $ergebnis : json_encode($ergebnis));
    } else {
      $fehler['moodleid']="Problem bei der Anmeldung der Dozenten:<br />".(is_string($ergebnis) ? $ergebnis : json_encode($ergebnis));
    }
  }
}
$_SESSION['nachricht']='<div class="done">inTrain-Kurs gespeichert!</div>';
if(!empty($fehler)) {
  $_SESSION['fehler']=$fehler;
}
header('Location:selfkurs_sehen.php?selfkursid='.$id);
exit;
?>