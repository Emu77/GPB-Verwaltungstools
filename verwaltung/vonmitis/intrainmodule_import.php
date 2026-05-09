<?php
require_once '../../db.php';

//Module aus mitisdata/MitisExportIntrainModul.csv importieren
if(!isset($_FILES['MitisExportIntrainModul']) || !file_exists($_FILES['MitisExportIntrainModul']['tmp_name'])) {
  header('Location:index.php');
  exit;
}

$MModuleByMid=array();
$f=fopen($_FILES['MitisExportIntrainModul']['tmp_name'],'rt');
//ModulID;Bereich;Kurzbezeichnung;Katalognummer;Titel;Dauer;SollUeProWoche;Preis;Beschreibung;Voraussetzungen;Lernziele;LernInhalte;NutzenBeruf;ModulTyp;
fgetcsv($f,null,';',' ');
while($row=fgetcsv($f,null,';','"')) {
  foreach($row as $k=>$v) {
    $row[$k]=str_replace("\r","\n",str_replace("\r\n","\n",$v));
  }
  $row[5]=(int)$row[5];
  $row[6]=(int)$row[6];
  $row[7]=(float)str_replace(' ','',str_replace(',','.',$row[7]));
  $row['dest']=false;
  $MModuleByMid[$row[0]]=$row;
}
fclose($f);
echo "Datei geladen, ".count($MModuleByMid)." Datensätze<br />\n";

echo "Abgleich mit schon importierten Modulen...<br />\n";
$idszuloeschen=array();
$result=$db->query("select * from gpb_intrainmodul");
while($row=$result->fetch_object()) {
  $row->anzahlue=(int)$row->anzahlue;
  $row->vollzeit_wochenue=(int)$row->vollzeit_wochenue;
  $row->preis=(float)$row->preis;
  if(isset($MModuleByMid[$row->mitisid])) {
    $MModuleByMid[$row->mitisid]['dest']=$row;
  } else {
    echo "Modul ".$row->kurzbezeichnung." mitisid=".$row->mitisid." aus MitisExport verschwunden<br />\n";
    $idszuloeschen[]=$row->id;
  }
}
$result->free();

echo "Daten korrigieren...<br />\n";
if(!empty($idszuloeschen)) {
  $db->query("delete from gpb_intrainpaket_modul where modulid in(".implode(',',$idszuloeschen).")");
  $db->query("delete from gpb_intrainmodul where id in(".implode(',',$idszuloeschen).")");
  echo count($idszuloeschen)." Module gelöscht<br />\n";
}

$updatestmt=$db->prepare("update gpb_intrainmodul set kurzbezeichnung=?,katalognummer=?,titel=?,anzahlue=?,vollzeit_wochenue=?,preis=?,beschreibung=?,voraussetzungen=?,lernziele=?,lerninhalte=?,nutzen=? where id=? limit 1");
$updatestmtBBI=$db->prepare("update gpb_intrainmodul set kurzbezeichnung=?,katalognummer=?,titel=?,anzahlue=?,vollzeit_wochenue=?,preis=? where id=? limit 1");
$insertstmt=$db->prepare("insert into gpb_intrainmodul(mitisid,kurzbezeichnung,katalognummer,titel,anzahlue,vollzeit_wochenue,preis,beschreibung,voraussetzungen,lernziele,lerninhalte,nutzen) values(?,?,?,?,?,?,?,?,?,?,?,?)");
foreach($MModuleByMid as $mitisid=>$orig) {
  $id=0;
  if($orig['dest']) {
    $dest=$orig['dest'];
    $id=$dest->id;
    //ModulID;Bereich;Kurzbezeichnung;Katalognummer;Titel;Dauer;SollUeProWoche;Preis;Beschreibung;Voraussetzungen;Lernziele;LernInhalte;NutzenBeruf;ModulTyp;
	if(!isset($orig[13]) || $orig[13]=='BBI') {
		if($dest->kurzbezeichnung!=$orig[2]
		  || $dest->katalognummer!=$orig[3]
		  || $dest->titel!=$orig[4]
		  || $dest->anzahlue!=$orig[5]
		  || $dest->vollzeit_wochenue!=$orig[6]
		  || $dest->preis!=$orig[7]) {
		  $updatestmtBBI->bind_param('sssiidi',$orig[2],$orig[3],$orig[4],$orig[5],$orig[6],$orig[7],$id);
		  $updatestmtBBI->execute();
		  echo "BBI-Modul ".$orig[4]." mitisid=".$orig[0]." korrigiert.<br />\n";
		}
    } else {
		if($dest->kurzbezeichnung!=$orig[2]
		  || $dest->katalognummer!=$orig[3]
		  || $dest->titel!=$orig[4]
		  || $dest->anzahlue!=$orig[5]
		  || $dest->vollzeit_wochenue!=$orig[6]
		  || $dest->preis!=$orig[7]
		  || $dest->beschreibung!=$orig[8]
		  || $dest->voraussetzungen!=$orig[9]
		  || $dest->lernziele!=$orig[10]
		  || $dest->lerninhalte!=$orig[11]
		  || $dest->nutzen!=$orig[12]) {
		  $updatestmt->bind_param('sssiidsssssi',$orig[2],$orig[3],$orig[4],$orig[5],$orig[6],$orig[7],$orig[8],$orig[9],$orig[10],$orig[11],$orig[12],$id);
		  $updatestmt->execute();
		  echo "Modul ".$orig[4]." mitisid=".$orig[0]." korrigiert.<br />\n";
		}
	}
  } else {
    $mitisid=(int)$orig[0];
    $insertstmt->bind_param('isssiidsssss',$mitisid,$orig[2],$orig[3],$orig[4],$orig[5],$orig[6],$orig[7],$orig[8],$orig[9],$orig[10],$orig[11],$orig[12]);
    $insertstmt->execute();
    $id=$db->insert_id;
    echo "Modul ".$orig[4]." mitisid=".$orig[0]." hinzugefügt.<br />\n";
  }
  ob_flush();
}
?>
<a href="index.php">Zurück</a>