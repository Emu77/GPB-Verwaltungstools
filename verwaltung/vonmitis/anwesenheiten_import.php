<?php
require_once '../../db.php';

//Anwesenheiten aus mitisdata/MitisExportAnwesenheiten.csv importieren
if(!isset($_FILES['MitisExportAnwesenheiten']) || !file_exists($_FILES['MitisExportAnwesenheiten']['tmp_name'])) {
  header('Location:index.php');
  exit;
}

$anwesenheiten=array();
$tnsByMid=array();
$mindatum='9999-99-99';
$maxdatum='0000-00-00';
$f=fopen($_FILES['MitisExportAnwesenheiten']['tmp_name'],'rt');
//StammdatenID;Datum;_modified;Kuerzel;
fgetcsv($f,null,';',' ');
while($row=fgetcsv($f,null,';','"')) {
  $row[3]=trim($row[3]);
  $anwesenheiten[]=$row;
  $tnsByMid[$row[0]]=false;
  if($row[1]<$mindatum) {
    $mindatum=$row[1];
  }
  if($row[1]>$maxdatum) {
    $maxdatum=$row[1];
  }
}
fclose($f);
echo "Datei geladen, ".count($anwesenheiten)." Datensätze<br />\n";

echo "IDs übersetzen...<br />\n";
$tnsById=array();
if(!empty($tnsByMid)) {
  $result=$db->query("select id,mitisid from gpb_tn where mitisid in(".implode(',',array_keys($tnsByMid)).")");
  while($row=$result->fetch_object()) {
    $row->anwByTag=array();
    $tnsByMid[$row->mitisid]=$row;
    $tnsById[$row->id]=$row;
  }
  $result->free();
}

echo "Daten korrigieren...<br />\n";
if(!empty($tnsById)) {
  $result=$db->query("select * from gpb_anwesenheit where tnid in(".implode(',',array_keys($tnsById)).") and tag>='".$mindatum."' and tag<='".$maxdatum."'");
  while($row=$result->fetch_object()) {
    $tnsById[$row->tnid]->anwByTag[$row->tag]=$row;
  }
  $result->free();
  
  $anzahlInsert=0;
  $anzahlUpdate=0;
  $insertstmt=$db->prepare("insert into gpb_anwesenheit(tag,tnid,mitis,geaendertam) values(?,?,?,?)");
  $updatestmt=$db->prepare("update gpb_anwesenheit set mitis=?,geaendertam=? where id=? limit 1");
  foreach($anwesenheiten as $a) {
    if(!isset($tnsByMid[$a[0]])) continue;
    $tn=$tnsByMid[$a[0]];
    if(empty($tn)) continue;
    if(isset($tn->anwByTag[$a[1]])) {
      $va=$tn->anwByTag[$a[1]];
      if($va->mitis==$a[3] || $va->geaendertam>$a[2]) continue;
      $updatestmt->bind_param('ssi',$a[3],$a[2],$va->id);
      $updatestmt->execute();
      ++$anzahlUpdate;
    } else {
      $insertstmt->bind_param('siss',$a[1],$tn->id,$a[3],$a[2]);
      $insertstmt->execute();
      ++$anzahlInsert;
    }
  }
  echo $anzahlInsert." hinzugefügt, ".$anzahlUpdate." korrigiert.<br />\n";
} else {
  echo "Nichts zu tun.<br />\n";
}

?>
<a href="index.php">Zurück</a>