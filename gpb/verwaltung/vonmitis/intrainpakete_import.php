<?php
require_once '../../db.php';

//Pakete aus mitisdata/MitisExportIntrainPaket.csv importieren
if(!isset($_FILES['MitisExportIntrainPaket']) || !file_exists($_FILES['MitisExportIntrainPaket']['tmp_name'])) {
  header('Location:index.php');
  exit;
}

$MPaketeByMid=array();
$f=fopen($_FILES['MitisExportIntrainPaket']['tmp_name'],'rt');
//PaketID;Bereich;Kurzbezeichnung;Bezeichnung;Beschreibung;Voraussetzungen;Lernziele;Lerninhalte;NutzenBeruf;AA_Nummer_Vz;AA_Nummer_Tz;PreisProUE;
fgetcsv($f,null,';',' ');
while($row=fgetcsv($f,null,';','"')) {
  foreach($row as $k=>$v) {
    $row[$k]=str_replace("\r","\n",str_replace("\r\n","\n",$v));
  }
  $row[11]=(float)$row[11];
  $row['dest']=false;
  $MPaketeByMid[$row[0]]=$row;
}
fclose($f);
echo "Datei geladen, ".count($MPaketeByMid)." Datensätze<br />\n";


echo "Abgleich mit schon importierten Paketen...<br />\n";
$idszuloeschen=array();
$result=$db->query("select * from gpb_intrainpaket");
while($row=$result->fetch_object()) {
  $row->preisproue=(float)$row->preisproue;
  if(isset($MPaketeByMid[$row->mitisid])) {
    $MPaketeByMid[$row->mitisid]['dest']=$row;
  } else {
    echo "Paket ".$row->bezeichnung." mitisid=".$row->mitisid." aus MitisExport verschwunden<br />\n";
    $idszuloeschen[]=$row->id;
  }
}
$result->free();

echo "Daten korrigieren...<br />\n";
if(!empty($idszuloeschen)) {
  $db->query("delete from gpb_intrainpaket_modul where paketid in(".implode(',',$idszuloeschen).")");
  $db->query("delete from gpb_intrainpaket where id in(".implode(',',$idszuloeschen).")");
  echo count($idszuloeschen)." Pakete gelöscht<br />\n";
}

$bereichidsByName=array();
$result=$db->query("select * from gpb_intrainbereich");
while($row=$result->fetch_object()) {
  $bereichidsByName[mb_strtolower($row->bereichname)]=$row->id;
}
$result->free();

$insertbereichstmt=$db->prepare("insert into gpb_intrainbereich(bereichname,ort,farbe) values(?,'','rgba(255,255,255,0)')");
$updatestmt=$db->prepare("update gpb_intrainpaket set bereichid=?,kurzbezeichnung=?,bezeichnung=?,beschreibung=?,voraussetzungen=?,lernziele=?,lerninhalte=?,nutzen=?,preisproue=? where mitisid=? limit 1");
$insertstmt=$db->prepare("insert into gpb_intrainpaket(mitisid,bereichid,kurzbezeichnung,bezeichnung,beschreibung,voraussetzungen,lernziele,lerninhalte,nutzen,preisproue) values(?,?,?,?,?,?,?,?,?,?)");
foreach($MPaketeByMid as $mitisid=>$orig) {
  $id=0;
  $bereichname=mb_strtolower($orig[1]);
  if(isset($bereichidsByName[$bereichname])) {
    $bereichid=$bereichidsByName[$bereichname];
  } else {
    $insertbereichstmt->bind_param('s',$orig[1]);
    $insertbereichstmt->execute();
    $bereichid=$db->insert_id;
    $bereichidsByName[$bereichname]=$bereichid;
    echo "Neuer Bereich: ".$orig[1]."<br />\n";
  }
  if($orig['dest']) {
    $dest=$orig['dest'];
    $id=$dest->id;
    //PaketID;Bereich;Kurzbezeichnung;Bezeichnung;Beschreibung;Voraussetzungen;Lernziele;Lerninhalte;NutzenBeruf;AA_Nummer_Vz;AA_Nummer_Tz;PreisProUE;
    if($dest->bereichid!=$bereichid
      || $dest->kurzbezeichnung!=$orig[2]
      || $dest->bezeichnung!=$orig[3]
      || $dest->beschreibung!=$orig[4]
      || $dest->voraussetzungen!=$orig[5]
      || $dest->lernziele!=$orig[6]
      || $dest->lerninhalte!=$orig[7]
      || $dest->nutzen!=$orig[8]
      || $dest->preisproue!=$orig[11]) {
      $updatestmt->bind_param('isssssssdi',$bereichid,$orig[2],$orig[3],$orig[4],$orig[5],$orig[6],$orig[7],$orig[8],$orig[11],$id);
      $updatestmt->execute();
      echo "Paket ".$orig[3]." mitisid=".$orig[0]." korrigiert.<br />\n";
    }
  } else {
    $mitisid=(int)$orig[0];
    $insertstmt->bind_param('iisssssssd',$mitisid,$bereichid,$orig[2],$orig[3],$orig[4],$orig[5],$orig[6],$orig[7],$orig[8],$orig[11]);
    $insertstmt->execute();
    $id=$db->insert_id;
    echo "Paket ".$orig[3]." mitisid=".$orig[0]." hinzugefügt.<br />\n";
  }
  ob_flush();
}
?>
<a href="index.php">Zurück</a>