<?php
require_once '../../db.php';

//Zuweisungen aus /gpb/data/MitisExportMassnahme_Teilnehmer.csv importieren
if(!isset($_FILES['MitisExportMassnahme_Teilnehmer']) || !file_exists($_FILES['MitisExportMassnahme_Teilnehmer']['tmp_name'])) {
  header('Location:index.php');
  exit;
}

$zuwByMassnMid=array(); 
$zuwByTNMid=array(); 
$f=fopen($_FILES['MitisExportMassnahme_Teilnehmer']['tmp_name'],'rt');
//massnahmeid;stammdatenid;einstieg_datum;ausstieg_datum;
fgetcsv($f,null,';',' ');
$anzahl=0;
while($row=fgetcsv($f,null,';','"')) {
  $row[2]=empty($row[2]) || $row[2]=='null' ? '0000-00-00' : $row[2]; //implode('-',array_reverse(explode('.',substr($row[2],0,10))));
  $row[3]=empty($row[3]) || $row[3]=='null' ? '0000-00-00' : $row[3]; //implode('-',array_reverse(explode('.',substr($row[3],0,10))));
  $massnmid=$row[0];
  $tnmid=$row[1];
  if(isset($zuwByMassnMid[$massnmid])) {
	if(isset($zuwByMassnMid[$massnmid][$tnmid])) { //2 Verträge: mergen$zuwByTNMid[$tnmid][$massnmid][3]
		if(empty($zuwByMassnMid[$massnmid][$tnmid][2]) || $zuwByMassnMid[$massnmid][$tnmid][2]=='0000-00-00') { // not null Einstieg bevorzugen
			$zuwByMassnMid[$massnmid][$tnmid][2]=$row[2];
		} else if(!empty($row[2]) && $row[2]!='0000-00-00' && $row[2]<$zuwByMassnMid[$massnmid][$tnmid][2]) { // unter not nulls frühesten Einstieg bevorzugen
			$zuwByMassnMid[$massnmid][$tnmid][2]=$row[2];
		}
		if(empty($row[3]) || $row[3]=='0000-00-00') { // null Ausstieg bevorzugen, es sei denn, der Vertrag ist nicht eingestiegen
			if(!empty($row[2]) && $row[2]!='0000-00-00') {
				$zuwByMassnMid[$massnmid][$tnmid][3]=$row[3];
			}
		} else if(!empty($zuwByMassnMid[$massnmid][$tnmid][3]) && $zuwByMassnMid[$massnmid][$tnmid][3]!='0000-00-00' && $zuwByMassnMid[$massnmid][$tnmid][3]<$row[3]) { // Unter not nulls den spätesten Ausstieg bevorzugen
			$zuwByMassnMid[$massnmid][$tnmid][3]=$row[3];
		}
	} else {
		$zuwByMassnMid[$massnmid][$tnmid]=$row;
	}
  } else {
    $zuwByMassnMid[$massnmid]=array($tnmid=>$row);
  }
  if(isset($zuwByTNMid[$tnmid])) {
	  if(isset($zuwByTNMid[$tnmid][$massnmid])) { //2 Verträge: mergen
		if(empty($zuwByTNMid[$tnmid][$massnmid][2]) || $zuwByTNMid[$tnmid][$massnmid][2]=='0000-00-00') { // not null Einstieg bevorzugen
			$zuwByTNMid[$tnmid][$massnmid][2]=$row[2];
		} else if(!empty($row[2]) && $row[2]!='0000-00-00' && $row[2]<$zuwByTNMid[$tnmid][$massnmid][2]) { // unter not nulls frühesten Einstieg bevorzugen
			$zuwByTNMid[$tnmid][$massnmid][2]=$row[2];
		}
		if(empty($row[3]) || $row[3]=='0000-00-00') { // null Ausstieg bevorzugen, es sei denn, der Vertrag ist nicht eingestiegen
			if(!empty($row[2]) && $row[2]!='0000-00-00') {
				$zuwByTNMid[$tnmid][$massnmid][3]=$row[3];
			}
		} else if(!empty($zuwByTNMid[$tnmid][$massnmid][3]) && $zuwByTNMid[$tnmid][$massnmid][3]!='0000-00-00' && $zuwByTNMid[$tnmid][$massnmid][3]<$row[3]) { // unter not nulls spätesten Ausstieg bevorzugen
			$zuwByTNMid[$tnmid][$massnmid][3]=$row[3];
		}
	  } else {
		$zuwByTNMid[$tnmid][$massnmid]=$row;
	  }
  } else {
    $zuwByTNMid[$tnmid]=array($massnmid=>$row);
  }
  ++$anzahl;
}
fclose($f);
echo "Datei geladen, ".$anzahl." Datensätze<br />\n";

echo "IDs übersetzen...<br />\n";
$massnByMid=array();
$massnById=array();
if(!empty($zuwByMassnMid)) {
  $result=$db->query("select * from gpb_massnahme where mitisid in('".implode("','",array_keys($zuwByMassnMid))."')");
  while($row=$result->fetch_object()) {
    $massnByMid[$row->mitisid]=$row;
    $massnById[$row->id]=$row;
  }
  $result->free();
}
$tnsByMid=array();
$tnsById=array();
if(!empty($zuwByTNMid)) {
  $result=$db->query("select * from gpb_tn where mitisid in('".implode("','",array_keys($zuwByTNMid))."')");
  while($row=$result->fetch_object()) {
    $row->neueBerufid=$row->berufid;
    $row->neueMassnEinstieg='0000-00-00';
    $tnsByMid[$row->mitisid]=$row;
    $tnsById[$row->id]=$row;
  }
  $result->free();
}

echo "Daten korrigieren...<br />\n";
if(!empty($massnById)) {
  $db->query("delete from gpb_massnahme_tn where massnahmeid in(".implode(",",array_keys($massnById)).")");
}
if(!empty($tnsById)) {
  $db->query("delete from gpb_massnahme_tn where tnid in('".implode("','",array_keys($tnsById))."')");
}
$values=array();
foreach($zuwByMassnMid as $massnmid=>$zuws) {
  if(!isset($massnByMid[$massnmid])) {
    echo "Maßnahme mit mitisid=".$massnmid." nicht gefunden<br />\n";
    continue;
  }
  $massn=$massnByMid[$massnmid];
  foreach($zuws as $tnmid=>$zuw) {
    if(!isset($tnsByMid[$tnmid])) {
      if(isset($zuwByTNMid[$tnmid])) {
        echo "TN mit mitisid=".$tnmid." nicht gefunden<br />\n";
        unset($zuwByTNMid[$tnmid]);
      }
      continue;
    }
    $tn=$tnsByMid[$tnmid];
    $values[]="(".$massn->id.",".$tn->id.",'".$zuw[2]."','".$zuw[3]."')";
    if($massn->berufid>0 && $zuw[2]>$tn->neueMassnEinstieg) {
      $tn->neueBerufid=$massn->berufid;
      $tn->neueMassnEinstieg=$zuw[2];
    }
  }
}
if(!empty($values)) {
  $db->query("insert into gpb_massnahme_tn(massnahmeid,tnid,einstieg,ausstieg) values ".implode(",",$values));
  echo count($values)." Zuweisungen gespeichert.<br />\n";
}
$anzahl=0;
foreach($tnsById as $id=>$tn) {
  if($tn->neueBerufid!=$tn->berufid) {
    $db->query("update gpb_tn set berufid=".$tn->neueBerufid." where id=".$tn->id." limit 1");
    ++$anzahl;
  }
}
if($anzahl>0) {
  echo "Beruf von ".$anzahl." TN korrigiert.<br />\n";
}
?>
<a href="index.php">Zurück</a>