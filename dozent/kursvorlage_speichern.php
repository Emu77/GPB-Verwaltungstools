<?php
require_once 'check_login.php';
require_once '../Kursvorlage.php';
require_once 'DozentKursvorlage.php';

$fehler=array();
$id=isset($_POST['id']) ? (int)$_POST['id'] : 0;
if($id>0) {
  $vorlage=Kursvorlage::einenLaden($id,'DozentKursvorlage');
  if(empty($vorlage)) {
    header('Location:kursvorlagen.php');
    exit;
  }
}
$moodleid=$id>0 ? $vorlage->moodleid : 0; //(isset($_POST['moodleid']) ? (int)$_POST['moodleid'] : 0); //Dies wäre um eine Vorlage mit einem existierenden Moodle-Kurs zu verbinden
$moodleid_alt=$moodleid;
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
    $fehler['titel']='Titel schon belegt: <a href="kurs_sehen.php?kursid='.$anderer->id.'">'.$titel.'</a>';
  } else {
    $stmt=$db->prepare("select id from gpb_kursvorlage where titel=? and id<>? limit 1");
    $stmt->bind_param('si',$titel,$id);
    $stmt->execute();
    $result=$stmt->get_result();
    $anderer=$result->fetch_object();
    $result->free();
    if($anderer) {
      $fehler['titel']='Titel schon belegt: <a href="kursvorlage_sehen.php?kursvorlageid='.$anderer->id.'">'.$titel.'</a>';
    } 
  }
}
$dozentenids=isset($_POST['dozentenids']) && !empty($_POST['dozentenids']) ? explode(',',$_POST['dozentenids']) : array();
if(!in_array(''.$ich->id,$dozentenids)) {
  $dozentenids[]=''.$ich->id;
}
if(!empty($fehler)) {
  $fehler['kursvorlage']=(object)array(
    'id'=>$id,
    'titel'=>$titel,
    'dozentenids'=>$dozentenids,
    'moodleerstellen'=>($moodleid<=0),
    'moodleid'=>$moodleid
  );
  $_SESSION['fehler']=$fehler;
  header('Location:kursvorlage_bearbeiten.php?kursvorlageid='.$id);
  exit;
}
if($id>0) {
  if($moodleid_alt<=0) {
    $daten=(object)array(
      'kategorie'=>'Vorlagen',
      'titel'=>$titel,
      'kursid'=>'Vorlage_'.$id
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
  $stmt=$db->prepare("update gpb_kursvorlage set titel=?,moodleid=? where id=? limit 1");
  $stmt->bind_param('sii',$titel,$moodleid,$id);
  $stmt->execute();
  if($moodleid_alt>0) {
    $daten=(object)array(
      'courseid'=>$moodleid_alt,
      'kategorie'=>'Vorlagen',
      'titel'=>$titel,
      'kursid'=>'Vorlage_'.$id
    );
    $url=$moodleurl.'webservice/rest/server.php?wstoken='.$moodletoken.'&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=kurs_speichern&daten='.urlencode(json_encode($daten));
    $ergebnis=json_decode(file_get_contents($url));
    if(is_object($ergebnis) && isset($ergebnis->exception)) {
      $fehler['moodleid']=json_encode($ergebnis);
    }
  }
  $db->query("delete from gpb_kursvorlage_dozent where kursvorlageid=".$id);
} else {
  $stmt=$db->prepare("insert into gpb_kursvorlage(titel,moodleid) values(?,?)");
  $stmt->bind_param('si',$titel,$moodleid);
  $stmt->execute();
  $id=$db->insert_id;
  if($moodleid<=0) {
    $daten=(object)array(
      'kategorie'=>'Vorlagen',
      'titel'=>$titel,
      'kursid'=>'Vorlage_'.$id
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
      $result=$db->query("select * from gpb_kurs where moodleid=".$moodleid);
      $schon=$result->fetch_object();
      $result->free();
      if($schon) {
        mail('r.mouton@gpb.de','Kursvorlage hat Kurs übernommen','Moodleid='.$moodleid);
        $fehler['moodleid']=json_encode('Fehler beim Speichern des Moodle-Kurses, bitte bei R. Mouton mit Bildschirmkopie melden!');
        $fehler['kursvorlage']=(object)array(
          'id'=>$id,
          'titel'=>$titel,
          'dozentenids'=>$dozentenids,
          'moodleerstellen'=>($moodleid<=0),
          'moodleid'=>$moodleid
        );
        $_SESSION['fehler']=$fehler;
        header('Location:kursvorlage_bearbeiten.php?kursvorlageid='.$id);
        exit;
      } else {
        $db->query("update gpb_kursvorlage set moodleid=".$moodleid." where id=".$id." limit 1");
      }
    }
  }
}
$values=array();
foreach($dozentenids as $dozentid) {
  $dozentid=(int)$dozentid;
  if($dozentid>0) {
    $values[]='('.$id.','.$dozentid.')';
  }
}
if(!empty($values)) {
  $db->query("insert into gpb_kursvorlage_dozent(kursvorlageid,dozentid) values ".implode(',',$values));
}
if($moodleid>0) {
  //Dozenten korrigieren
  $daten=(object)array(
    'rolename'=>'editingteacher',
    'courseid'=>$moodleid,
    'userids'=>array()
  );
  if(!empty($dozentenids)) {
    $result=$db->query("select * from gpb_dozent where id in(".implode(',',$dozentenids).")");
    $erstellteMoodleids=array();
    while($row=$result->fetch_object()) {
      if($row->moodleid>0) {
        $daten->userids[]=$row->moodleid;
      } else {
        // Moodle-Nutzer für den Dozent anlegen
        $datendoz=(object)array(
          'nutzername'=>$row->nutzername,
          'vorname'=>$row->vorname,
          'nachname'=>$row->nachname,
          'email'=>$moodleisttest ? 'testdozent'.$id.'@gpb.de' : $row->email,
          'hash'=>empty($row->passwort) ? '' : password_hash($row->passwort,PASSWORD_DEFAULT),
          'rolle'=>'dozent'
        );
        $url=$moodleurl.'webservice/rest/server.php?wstoken='.$moodletoken.'&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=nutzer_speichern&daten='.urlencode(json_encode($datendoz));
        $ergebnis=json_decode(file_get_contents($url));
        if(is_object($ergebnis) && isset($ergebnis->exception)) {
          if(isset($fehler['dozenten'])) {
            $fehler['dozenten'].="<br />Dozent mit ID=".$row->id." hat keinen Moodle-Nutzer: ".json_encode($ergebnis);
          } else {
            $fehler['dozenten']="Dozent mit ID=".$row->id." hat keinen Moodle-Nutzer: ".json_encode($ergebnis);
          }
        } else {
          if(!is_object($ergebnis)) {
            $ergebnis=json_decode($ergebnis);
          }
          $erstellteMoodleids[$row->id]=$ergebnis->id;
          $daten->userids[]=$ergebnis->id;
        }
      }
    }
    $result->free();
  }
  $url=$moodleurl.'webservice/rest/server.php?wstoken='.$moodletoken.'&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=nutzer_zuweisen&daten='.urlencode(json_encode($daten));
  $ergebnis=json_decode(file_get_contents($url));
  if($ergebnis!='ok' && $ergebnis!='"ok"') {
    if(isset($fehler['dozenten'])) {
      $fehler['dozenten'].="<br />".(is_string($ergebnis) ? $ergebnis : json_encode($ergebnis));
    } else {
      $fehler['dozenten']=is_string($ergebnis) ? $ergebnis : json_encode($ergebnis);
    }
  }
  foreach($erstellteMoodleids as $id=>$moodleid) {
    $db->query("update gpb_dozent set moodleid=".$moodleid." where id=".$id);
  }
}
$_SESSION['nachricht']='<div class="done">Kursvorlage gespeichert!</div>';
if(!empty($fehler)) {
  $_SESSION['fehler']=$fehler;
}
header('Location:kursvorlage_sehen.php?kursvorlageid='.$id);
exit;
?>