<?php
require_once 'check_login.php';

$fehler=array();
$redirect=isset($_POST['redirect']) ? $_POST['redirect'] : null;
$id=isset($_POST['id']) ? (int)$_POST['id'] : 0;
$moodleid=isset($_POST['moodleid']) ? (int)$_POST['moodleid'] : 0;
$moodleerstellen=isset($_POST['moodleerstellen']) && $_POST['moodleerstellen']!='N';
if($id<=0 && $moodleid>0) {
  //TODO Daten aus Moodle laden
}
$titel=isset($_POST['titel']) ? $_POST['titel'] : '';
$planungnotiz=isset($_POST['planungnotiz']) ? $_POST['planungnotiz'] : '';
$planungfarbe=isset($_POST['planungfarbe']) ? $_POST['planungfarbe'] : '';
if(strtolower($planungfarbe)=='#ffffff' || $planungfarbe=='#000000') {
  $planungfarbe='';
}
$sichtbar=isset($_POST['sichtbar']) && $_POST['sichtbar']!='N';
if(empty($titel)) {
  $fehler['titel']='Bitte einen Titel eingeben';
} else {
  $stmt=$db->prepare("select id from gpb_kurs where titel=? and id<>? limit 1");
  $stmt->bind_param('si',$titel,$id);
  $stmt->execute();
  $result=$stmt->get_result();
  $anderer=$result->fetch_object();
  $result->free();
  if($anderer) {
    $fehler['titel']='Titel schon belegt: <a href="kurs_sehen.php?kursid='.$anderer->id.'">'.$titel.'</a>';
  }
}
$beginn=isset($_POST['beginn']) ? $_POST['beginn'] : '';
if(empty($beginn)) {
  $fehler['beginn']='Bitte Beginndatum eingeben';
}
$ende=isset($_POST['ende']) ? $_POST['ende'] : '';
if(empty($ende)) {
  $fehler['beginn']='Bitte Enddatum eingeben';
}
$einzeltage=isset($_POST['einzeltage']) && $_POST['einzeltage']!='N';
$warPV=isset($_POST['warPV']) && $_POST['warPV']!='N';
$istPV=isset($_POST['istPV']) && $_POST['istPV']!='N';
$hatteProjektantrag=isset($_POST['hatteProjektantrag']) && $_POST['hatteProjektantrag']!='N';
$hatProjektantrag=isset($_POST['hatProjektantrag']) && $_POST['hatProjektantrag']!='N';
$raumid=isset($_POST['raumid']) ? (int)$_POST['raumid'] : 0;
if($raumid<=0) $raumid=null;
$klassenids=isset($_POST['klassenids']) && !empty($_POST['klassenids']) ? explode(',',$_POST['klassenids']) : array();
$dozentenids=isset($_POST['dozentenids']) && !empty($_POST['dozentenids']) ? explode(',',$_POST['dozentenids']) : array();
$modulid=isset($_POST['modulid']) ? (int)$_POST['modulid'] : 0;
$zeugnisrelevant=isset($_POST['zeugnisrelevant']) && $_POST['zeugnisrelevant']!='N';
$notenstatus_alt=isset($_POST['notenstatus_alt']) ? $_POST['notenstatus_alt'] : '';
$notenstatus=isset($_POST['keinenoten']) && $_POST['keinenoten']!='N' ? 'keine' : (empty($notenstatus_alt) ? 'todo' : $notenstatus_alt);
if(!empty($fehler)) {
  $fehler['kurs']=(object)array(
    'id'=>$id,
    'titel'=>$titel,
    'beginn'=>$beginn,
    'ende'=>$ende,
    'einzeltage'=>$einzeltage,
    'raumid'=>$raumid,
    'klassenids'=>$klassenids,
    'dozentenids'=>$dozentenids,
    'moodleid'=>$moodleid,
    'sichtbar'=>$sichtbar,
    'modulid'=>$modulid,
    'zeugnisrelevant'=>$zeugnisrelevant,
    'notenstatus'=>$notenstatus_alt,
    'keinenoten'=>($notenstatus=='keine' ? 'J' : 'N'),
    'planungnotiz'=>$planungnotiz,
    'planungfarbe'=>$planungfarbe,
    'istPV'=>$istPV,
    'hatProjektantrag'=>$hatProjektantrag
  );
  $_SESSION['fehler']=$fehler;
  header('Location:kurs_bearbeiten.php?kursid='.$id);
  exit;
}
if($id>0) {
  if($warPV!=$istPV) {
    if($istPV) {
      $db->query("insert ignore into gpb_pruefungsvorbereitung(kursid) values(".$id.")");
      $redirect='kurs_pv.php?kursid='.$id;
    } else {
      $db->query("delete from gpb_pruefungsvorbereitung_termin where kursid=".$id);
      $db->query("delete from gpb_pruefungsvorbereitung_klasse where kursid=".$id);
      $db->query("delete from gpb_pruefungsvorbereitung where kursid=".$id);
    }
  }
  if($hatteProjektantrag!=$hatProjektantrag) {
    if($hatProjektantrag) {
      $db->query("insert ignore into gpb_ihkprojektantragsformular(kursid) values(".$id.")");
    } else {
      $db->query("delete from gpb_ihkprojektantrag where kursid=".$id);
      $db->query("delete from gpb_ihkprojektantragsformular where kursid=".$id);
    }
  }
  if($moodleid<=0 && $moodleerstellen) {
    $daten=(object)array(
      'kategorie'=>'ILKurse',
      'titel'=>$titel,
      'kursid'=>$id,
      'beginn'=>$beginn,
      'ende'=>$ende,
      'keinenoten'=>($notenstatus=='keine' ? 'J' : 'N')
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
  $stmt=$db->prepare("update gpb_kurs set titel=?,beginn=?,ende=?,einzeltage=?,raumid=?,moodleid=?,sichtbar=?,modulid=?,zeugnisrelevant=?,notenstatus=?,planungnotiz=?,planungfarbe=? where id=? limit 1");
  $stmt->bind_param('sssiiiiiisssi',$titel,$beginn,$ende,$einzeltage,$raumid,$moodleid,$sichtbar,$modulid,$zeugnisrelevant,$notenstatus,$planungnotiz,$planungfarbe,$id);
  $stmt->execute();
  if($moodleid>0 && !$moodleerstellen) {
    $daten=(object)array(
      'courseid'=>$moodleid,
      'kategorie'=>'ILKurse',
      'titel'=>$titel,
      'kursid'=>$id,
      'beginn'=>$beginn,
      'ende'=>$ende,
      'keinenoten'=>($notenstatus=='keine' ? 'J' : 'N')
    );
    $url=$moodleurl.'webservice/rest/server.php?wstoken='.$moodletoken.'&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=kurs_speichern&daten='.urlencode(json_encode($daten));
    $ergebnis=json_decode(file_get_contents($url));
    if(is_object($ergebnis) && isset($ergebnis->exception)) {
      $fehler['moodleid']=json_encode($ergebnis);
    }
  }
  $db->query("delete from gpb_kurs_klasse where kursid=".$id);
  $db->query("delete from gpb_kurs_dozent where kursid=".$id);
} else {
  $stmt=$db->prepare("insert into gpb_kurs(titel,beginn,ende,einzeltage,raumid,moodleid,sichtbar,modulid,zeugnisrelevant,notenstatus,planungnotiz,planungfarbe) values(?,?,?,?,?,?,?,?,?,?,?,?)");
  $stmt->bind_param('sssiiiiiisss',$titel,$beginn,$ende,$einzeltage,$raumid,$moodleid,$sichtbar,$modulid,$zeugnisrelevant,$notenstatus,$planungnotiz,$planungfarbe);
  $stmt->execute();
  $id=$db->insert_id;
  if($istPV) {
    $db->query("insert ignore into gpb_pruefungsvorbereitung(kursid) values(".$id.")");
    $redirect='kurs_pv.php?kursid='.$id;
  }
  if($hatProjektantrag) {
    $db->query("insert ingnore into gpb_ihkprojektantragsformular(kursid) values(".$id.")");
  }
  if($moodleid<=0 && $moodleerstellen) {
    $daten=(object)array(
      'kategorie'=>'ILKurse',
      'titel'=>$titel,
      'kursid'=>$id,
      'beginn'=>$beginn,
      'ende'=>$ende,
      'keinenoten'=>($notenstatus=='keine' ? 'J' : 'N')
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
      $db->query("update gpb_kurs set moodleid=".$moodleid." where id=".$id." limit 1");
    }
  }
}
$values=array();
foreach($klassenids as $klasseid) {
  $klasseid=(int)$klasseid;
  if($klasseid>0) {
    $values[]='('.$id.','.$klasseid.')';
  }
}
if(!empty($values)) {
  $db->query("insert into gpb_kurs_klasse(kursid,klasseid) values ".implode(',',$values));
}
if($istPV) {
  $db->query("delete from gpb_pruefungsvorbereitung_klasse where kursid=".$id." and klasseid not in(select klasseid from gpb_kurs_klasse where kursid=".$id.")");
  $db->query("insert into gpb_pruefungsvorbereitung_klasse(kursid,klasseid) select kursid,klasseid from gpb_kurs_klasse where kursid=".$id." and klasseid not in(select klasseid from gpb_pruefungsvorbereitung_klasse where kursid=".$id.")");
  $stmt=$db->prepare("update gpb_pruefungsvorbereitung_klasse set hatAP1=?,hatAP2=?,hatMuendliche=? where kursid=? and klasseid=?");
  $stmt->bind_param('iiiii',$hatAP1,$hatAP2,$hatMuendliche,$id,$klasseid);
  foreach($klassenids as $klasseid) {
    $hatAP1=isset($_POST['hatAP1_'.$klasseid]) && $_POST['hatAP1_'.$klasseid]!='N' ? 1 : 0;
    $hatAP2=isset($_POST['hatAP2_'.$klasseid]) && $_POST['hatAP2_'.$klasseid]!='N' ? 1 : 0;
    $hatMuendliche=isset($_POST['hatMuendliche_'.$klasseid]) && $_POST['hatMuendliche_'.$klasseid]!='N' ? 1 : 0;
    $stmt->execute();
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
  $db->query("insert into gpb_kurs_dozent(kursid,dozentid) values ".implode(',',$values));
}
if($moodleid>0) {
  //Klassen korrigieren
  $daten=(object)array(
    'courseid'=>$moodleid,
    'klassencourseids'=>array(),
    'ersetzen'=>true
  );
  if(!empty($klassenids)) {
    $result=$db->query("select id,moodleid from gpb_klasse where id in(".implode(',',$klassenids).")");
    while($row=$result->fetch_object()) {
      if($row->moodleid>0) {
        $daten->klassencourseids[]=$row->moodleid;
      } else {
        if(isset($fehler['klassen'])) {
          $fehler['klassen'].="<br />Klasse mit ID=".$row->id." hat keinen Klassenkurs im Moodle";
        } else {
          $fehler['klassen']="Klasse mit ID=".$row->id." hat keinen Klassenkurs im Moodle";
        }
      }
    }
    $result->free();
  }
  $url=$moodleurl.'webservice/rest/server.php?wstoken='.$moodletoken.'&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=meta_einschreibung&daten='.urlencode(json_encode($daten));
  $ergebnis=json_decode(file_get_contents($url));
  if($ergebnis!='ok' && $ergebnis!='"ok"') {
    if(isset($fehler['klassen'])) {
      $fehler['klassen'].="<br />".(is_string($ergebnis) ? $ergebnis : json_encode($ergebnis));
    } else {
      $fehler['klassen']=is_string($ergebnis) ? $ergebnis : json_encode($ergebnis);
    }
  }
  
  //Dozenten korrigieren
  $daten=(object)array(
    'rolename'=>'editingteacher',
    'courseid'=>$moodleid,
    'userids'=>array(),
    'ersetzen'=>true
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
$_SESSION['nachricht']='<div class="done">Kurs gespeichert!</div>';
if(!empty($fehler)) {
  $_SESSION['fehler']=$fehler;
}
header('Location:'.(empty($redirect) ? 'kurs_pv.php?kursid='.$id : $redirect));
exit;
?>