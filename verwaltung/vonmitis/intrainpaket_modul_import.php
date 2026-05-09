<?php
require_once '../../db.php';

//Zuweisungen aus mitisdata/MitisExportIntrainPaket_Modul.csv importieren
if(!isset($_FILES['MitisExportIntrainPaket_Modul']) || !file_exists($_FILES['MitisExportIntrainPaket_Modul']['tmp_name'])) {
  header('Location:index.php');
  exit;
}

$MZuw=array();
$f=fopen($_FILES['MitisExportIntrainPaket_Modul']['tmp_name'],'rt');
//PaketID;ModulId;
fgetcsv($f,null,';',' ');
while($row=fgetcsv($f,null,';','"')) {
  if(isset($MZuw[$row[0]])) {
    $MZuw[$row[0]][$row[1]]=true;
  } else {
    $MZuw[$row[0]]=array($row[1]=>true);
  }
}
fclose($f);
echo "Datei geladen<br />\n";

echo "Abgleich mit schon importierten Zuweisungen...<br />\n";
$zuloeschen=array();
$result=$db->query("select pm.*
  ,p.mitisid as paketmitisid
  ,m.mitisid as modulmitisid
  from gpb_intrainpaket_modul pm
  left outer join gpb_intrainpaket p on p.id=pm.paketid
  left outer join gpb_intrainmodul m on m.id=pm.modulid");
while($row=$result->fetch_object()) {
  if(empty($row->paketmitisid) || empty($row->modulmitisid)) {
    $zuloeschen[]='('.$row->paketid.','.$row->modulid.')';
    continue;
  }
  if(!isset($MZuw[$row->paketmitisid]) || !isset($MZuw[$row->paketmitisid][$row->modulmitisid])) {
    $zuloeschen[]='('.$row->paketid.','.$row->modulid.')';
    continue;
  }
  unset($MZuw[$row->paketmitisid][$row->modulmitisid]);
}
$result->free();

echo "Daten korrigieren...<br />\n";
if(!empty($zuloeschen)) {
  $db->query("delete from gpb_intrainpaket_modul where (paketid,modulid) in(".implode(',',$zuloeschen).")");
  echo count($zuloeschen)." Zuweisungen gelöscht<br />\n";
}
if(!empty($MZuw)) {
  $modulmids=array();
  foreach($MZuw as $paketmid=>$z) {
    foreach($z as $modulmid=>$dummy) {
      $modulmids[]=$modulmid;
    }
  }
  $paketidsByMid=array();
  $result=$db->query("select id,mitisid from gpb_intrainpaket where mitisid in(".implode(',',array_keys($MZuw)).")");
  while($row=$result->fetch_object()) {
    $paketidsByMid[$row->mitisid]=$row->id;
  }
  $result->free();
  $modulidsByMid=array();
  if(!empty($modulmids)) {
    $result=$db->query("select id,mitisid from gpb_intrainmodul where mitisid in(".implode(',',$modulmids).")");
    while($row=$result->fetch_object()) {
      $modulidsByMid[$row->mitisid]=$row->id;
    }
    $result->free();
  }
  $values=array();
  foreach($MZuw as $paketmid=>$z) {
    if(!isset($paketidsByMid[$paketmid])) {
      echo "Paket mit MITIS-ID ".$paketmid." nicht gefunden<br />\n";
      continue;
    }
    foreach($z as $modulmid=>$dummy) {
      if(!isset($modulidsByMid[$modulmid])) {
        echo "Modul mit MITIS-ID ".$modulmid." nicht gefunden<br />\n";
        continue;
      }
      $values[]='('.$paketidsByMid[$paketmid].','.$modulidsByMid[$modulmid].')';
    }
  }
  if(!empty($values)) {
    $db->query("insert into gpb_intrainpaket_modul(paketid,modulid) values ".implode(',',$values));
    echo count($values)." Zuweisungen gespeichert.<br />\n";
  }
}
?>
<a href="index.php">Zurück</a>