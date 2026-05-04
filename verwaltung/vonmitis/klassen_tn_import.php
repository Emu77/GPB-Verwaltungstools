<?php
require_once '../../db.php';

//Zuweisungen aus mitisdata/MitisExportKlasse_Teilnehmer.csv importieren
if(!isset($_FILES['MitisExportKlasse_Teilnehmer']) || !file_exists($_FILES['MitisExportKlasse_Teilnehmer']['tmp_name'])) {
  header('Location:index.php');
  exit;
}

$zuwByKlasseMid=array(); 
$zuwByTNMid=array(); 
$f=fopen($_FILES['MitisExportKlasse_Teilnehmer']['tmp_name'],'rt');
//klasseid;stammdatenid;einstieg;ausstieg;
fgetcsv($f,null,';',' ');
$anzahl=0;
while($row=fgetcsv($f,null,';','"')) {
  $row[2]=empty($row[2]) || $row[2]=='null' ? '0000-00-00' : $row[2]; //implode('-',array_reverse(explode('.',substr($row[2],0,10))));
  $row[3]=empty($row[3]) || $row[3]=='null' ? '0000-00-00' : $row[3]; //implode('-',array_reverse(explode('.',substr($row[3],0,10))));
  $klassemid=$row[0];
  $tnmid=$row[1];
  if(isset($zuwByKlasseMid[$klassemid])) {
    if(isset($zuwByKlasseMid[$klassemid][$tnmid])) { // 2 Verträge: mergen
      if(empty($zuwByKlasseMid[$klassemid][$tnmid][2]) || $zuwByKlasseMid[$klassemid][$tnmid][2]=='0000-00-00') { // not null Einstieg bevorzugen
        $zuwByKlasseMid[$klassemid][$tnmid][2]=$row[2];
      } else if(!empty($row[2]) && $row[2]!='0000-00-00' && $row[2]<$zuwByKlasseMid[$klassemid][$tnmid][2]) { // unter not nulls frühesten Einstieg bevorzugen
        $zuwByKlasseMid[$klassemid][$tnmid][2]=$row[2];
      }
      if(empty($row[3]) || $row[3]=='0000-00-00') { // null Ausstieg bevorzugen, es sei denn, der Vertrag ist nicht eingestiegen
        if(!empty($row[2]) && $row[2]!='0000-00-00') {
          $zuwByKlasseMid[$klassemid][$tnmid][3]=$row[3];
        }
      } else if(!empty($zuwByKlasseMid[$klassemid][$tnmid][3]) && $zuwByKlasseMid[$klassemid][$tnmid][3]!='0000-00-00' && $zuwByKlasseMid[$klassemid][$tnmid][3]<$row[3]) { // Unter not nulls den spätesten Ausstieg bevorzugen
        $zuwByKlasseMid[$klassemid][$tnmid][3]=$row[3];
      }
    } else {
      $zuwByKlasseMid[$klassemid][$tnmid]=$row;
    }
  } else {
    $zuwByKlasseMid[$klassemid]=array($tnmid=>$row);
  }
  if(isset($zuwByTNMid[$tnmid])) {
    if(isset($zuwByTNMid[$tnmid][$klassemid])) { //2 Verträge: mergen
        if(empty($zuwByTNMid[$tnmid][$klassemid][2]) || $zuwByTNMid[$tnmid][$klassemid][2]=='0000-00-00') { // not null Einstieg bevorzugen
            $zuwByTNMid[$tnmid][$klassemid][2]=$row[2];
        } else if(!empty($row[2]) && $row[2]!='0000-00-00' && $row[2]<$zuwByTNMid[$tnmid][$klassemid][2]) { // unter not nulls frühesten Einstieg bevorzugen
            $zuwByTNMid[$tnmid][$klassemid][2]=$row[2];
        }
        if(empty($row[3]) || $row[3]=='0000-00-00') { // null Ausstieg bevorzugen, es sei denn, der Vertrag ist nicht eingestiegen
            if(!empty($row[2]) && $row[2]!='0000-00-00') {
                $zuwByTNMid[$tnmid][$klassemid][3]=$row[3];
            }
        } else if(!empty($zuwByTNMid[$tnmid][$klassemid][3]) && $zuwByTNMid[$tnmid][$klassemid][3]!='0000-00-00' && $zuwByTNMid[$tnmid][$klassemid][3]<$row[3]) { // unter not nulls spätesten Ausstieg bevorzugen
            $zuwByTNMid[$tnmid][$klassemid][3]=$row[3];
        }
      } else {
        $zuwByTNMid[$tnmid][$klassemid]=$row;
      }
  } else {
    $zuwByTNMid[$tnmid]=array($klassemid=>$row);
  }
  ++$anzahl;
}
fclose($f);
echo "Datei geladen, ".$anzahl." Datensätze<br />\n";

echo "IDs übersetzen...<br />\n";
$klassenByMid=array();
$klassenById=array();
if(!empty($zuwByKlasseMid)) {
  $result=$db->query("select * from gpb_klasse where mitisid in('".implode("','",array_keys($zuwByKlasseMid))."')");
  while($row=$result->fetch_object()) {
    $klassenByMid[$row->mitisid]=$row;
    $klassenById[$row->id]=$row;
  }
  $result->free();
}
$tnsByMid=array();
$tnsById=array();
if(!empty($zuwByTNMid)) {
  $result=$db->query("select * from gpb_tn where mitisid in('".implode("','",array_keys($zuwByTNMid))."')");
  while($row=$result->fetch_object()) {
    $tnsByMid[$row->mitisid]=$row;
    $tnsById[$row->id]=$row;
  }
  $result->free();
}

echo "Daten korrigieren...<br />\n";
$geaenderteKlassenById=array();
$updates=array();
$deletes=array();
//Sonderfälle Kadir Elci, Tristan Karl Schneider, Eric-Paul Prothmann nicht ändern
$result=$db->query("select * from gpb_klasse_tn where tnid not in(997,1108,2926)");  
while($row=$result->fetch_object()) {
  if(!isset($klassenById[$row->klasseid])) {
    continue; //Klasse nicht im Mitis-Export vorhanden: nichts ändern
  }
  if(!isset($tnsById[$row->tnid])) {
    continue; //TN nicht im Mitis-Export vorhanden: nichts ändern
  }
  $klassemid=$klassenById[$row->klasseid]->mitisid;
  $tnmid=$tnsById[$row->tnid]->mitisid;
  if(!isset($zuwByKlasseMid[$klassemid][$tnmid])) {
    $deletes[]=$row;
    $geaenderteKlassenById[$row->klasseid]=$klassenById[$row->klasseid];
    continue;
  }
  $neu=$zuwByKlasseMid[$klassemid][$tnmid];
  if((empty($row->einstieg) ? '0000-00-00' : $row->einstieg)!=(empty($neu[2]) ? '0000-00-00' : $neu[2])
   || (empty($row->ausstieg) ? '0000-00-00' : $row->ausstieg)!=(empty($neu[3]) ? '0000-00-00' : $neu[3])) {
    $row->einstieg=empty($neu[2]) || $neu[2]=='0000-00-00' ? null : $neu[2];
    $row->ausstieg=empty($neu[3]) || $neu[3]=='0000-00-00' ? null : $neu[3];
    $updates[]=$row;
    $geaenderteKlassenById[$row->klasseid]=$klassenById[$row->klasseid];
  }
  unset($zuwByKlasseMid[$klassemid][$tnmid]);
  if(isset($zuwByTNMid[$tnmid][$klassemid])) {
    unset($zuwByTNMid[$tnmid][$klassemid]);
  }
}
$result->free();

if(!empty($updates)) {
  $stmt=$db->prepare("update gpb_klasse_tn set einstieg=?,ausstieg=? where klasseid=? and tnid=?");
  foreach($updates as $up) {
    $stmt->bind_param('ssii',$up->einstieg,$up->ausstieg,$up->klasseid,$up->tnid);
    $stmt->execute();
  }
  echo count($updates)." Zuweisungen aktualisiert.<br />\n";
}
if(!empty($deletes)) {
  $stmt=$db->prepare("delete from gpb_klasse_tn where klasseid=? and tnid=?");
  foreach($deletes as $del) {
    $stmt->bind_param('ii',$del->klasseid,$del->tnid);
    $stmt->execute();
  }
  echo count($deletes)." Zuweisungen entfernt.<br />\n";
}

$values=array();
foreach($zuwByKlasseMid as $klassemid=>$zuws) {
  if(!isset($klassenByMid[$klassemid])) {
    echo "Klasse mit mitisid=".$klassemid." nicht gefunden<br />\n";
    continue;
  }
  $klasse=$klassenByMid[$klassemid];
  foreach($zuws as $tnmid=>$zuw) {
    if(!isset($tnsByMid[$tnmid])) {
      if(isset($zuwByTNMid[$tnmid])) {
        echo "TN mit mitisid=".$tnmid." nicht gefunden<br />\n";
        unset($zuwByTNMid[$tnmid]);
      }
      continue;
    }
    $tn=$tnsByMid[$tnmid];
    $values[]="(".$klasse->id.",".$tn->id.",'".$zuw[2]."','".$zuw[3]."')"; 
    $geaenderteKlassenById[$klasse->id]=$klassenById[$klasse->id];
  }
}
if(!empty($values)) {
  $db->query("insert into gpb_klasse_tn(klasseid,tnid,einstieg,ausstieg) values ".implode(",",$values));
  echo count($values)." Zuweisungen hinzugefügt.<br />\n";
}

if(!empty($geaenderteKlassenById)) {
  echo "Moodle Einschreibungen korrigieren...<br />\n";
  foreach($geaenderteKlassenById as $id=>$klasse) {
    $klasse->neueTns=array();
  }
  $result=$db->query("select * from gpb_klasse_tn where klasseid in(".implode(',',array_keys($geaenderteKlassenById)).") and einstieg is not null and einstieg<>'0000-00-00'");
  while($row=$result->fetch_object()) {
    $geaenderteKlassenById[$row->klasseid]->neueTns[]=$tnsById[$row->tnid];
  }
  $result->free();
  foreach($geaenderteKlassenById as $id=>$klasse) {
    if($klasse->moodleid<=0) {
      echo "Klasse ".$klasse->bezeichnung." mit mitisid=".$klassemid." hat keinen Moodle-Kurs<br />\n";
      continue;
    }
    $daten=(object)array(
      'rolename'=>'student',
      'courseid'=>$klasse->moodleid,
      'userids'=>array(),
      'ersetzen'=>true
    );
    foreach($klasse->neueTns as $tn) {
      if($tn->moodleid<=0) {
        echo "TN ".$tn->vorname." ".$tn->nachname." mit mitisid=".$tnmid." hat keine Moodle-ID<br />\n";
      } else {
        $daten->userids[]=$tn->moodleid;
      }
    }
    $url=$moodleurl.'webservice/rest/server.php?wstoken='.$moodletoken.'&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=nutzer_zuweisen&daten='.json_encode($daten);
    $ergebnis=json_decode(file_get_contents($url));
    if($ergebnis!='ok' && $ergebnis!='"ok"') {
      echo "Klasse ".$klasse->bezeichnung." mit mitisid=".$klassemid." Moodle-Kurs-ID: ".$klasse->moodleid." Problem: ".(is_string($ergebnis) ? $ergebnis : json_encode($ergebnis))."<br />\n";
    } else {
      echo "Klasse ".$klasse->bezeichnung." mit mitisid=".$klassemid." Moodle-Kurs-ID: ".$klasse->moodleid.": ".count($klasse->neueTns)." Zuweisungen / ".count($daten->userids)." Anmeldungen in Moodle gespeichert<br />\n";
    }
    ob_flush();
  }
}
?>
<a href="index.php">Zurück</a>