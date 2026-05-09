<?php
require_once '../../db.php';

if(!isset($_FILES['MitisExportMassnahme']) || !file_exists($_FILES['MitisExportMassnahme']['tmp_name'])) {
  header('Location:index.php');
  exit;
}

$MMassnahmenByMid=array();
$f=fopen($_FILES['MitisExportMassnahme']['tmp_name'],'rt');
//MassnahmeID;EinzelMassnahme;Titel;Kurzbezeichnung;MassnahmeTyp;Beginn;Ende;BKZ;NiederlassungID;
fgetcsv($f,null,';');
while($row=fgetcsv($f,null,';','"')) {
  if(mb_strpos($row[2],'telc')!==false) continue; // telc ausschließen
  if(mb_strpos($row[2],'Spezialist')!==false) continue; // Spezialisten ausschließen
  if(mb_strpos($row[2],'Fachkraft')!==false && mb_strpos($row[2],'Bürofachkraft')===false) continue; // Fachkräfte ausschließen
  $row['dest']=false;
  $row[5]=empty($row[5]) || $row[5]=='null' ? '0000-00-00' : $row[5]; //implode('-',array_reverse(explode('.',substr($row[5],0,10))));
  $row[6]=empty($row[6]) || $row[6]=='null' ? '0000-00-00' : $row[6]; //implode('-',array_reverse(explode('.',substr($row[6],0,10))));
  $MMassnahmenByMid[$row[0]]=$row;
}
fclose($f);
echo "Datei geladen, ".count($MMassnahmenByMid)." Datensätze<br />\n";

echo "Abgleich mit schon importierten Maßnahmen...<br />\n";
$result=$db->query("select * from gpb_massnahme ");
while($row=$result->fetch_object()) {
  if(isset($MMassnahmenByMid[$row->mitisid])) {
    $MMassnahmenByMid[$row->mitisid]['dest']=$row;
  } else {
    echo "Maßnahme ".$row->titel." mitisid=".$row->mitisid." vom MITISExport verschwunden.<br />\n";
  }
}
$result->free();

$berufeByBkz=array();
$result=$db->query("select * from gpb_beruf ");
while($row=$result->fetch_object()) {
  if(!empty($row->bkz)){
    foreach(explode(';',$row->bkz) as $bkz) {
      $bkz=trim($bkz);
      if(!empty($bkz)) {
        $berufeByBkz[$bkz]=$row;
      }
    }
  }
}
$result->free();

echo "Daten korrigieren...<br />\n";
$updatestmt=$db->prepare("update gpb_massnahme set titel=?,kuerzel=?,berufid=?,beginn=?,ende=? where id=? limit 1");
$insertstmt=$db->prepare("insert into gpb_massnahme(mitisid,titel,kuerzel,berufid,beginn,ende) values(?,?,?,?,?,?)");
foreach($MMassnahmenByMid as $mitisid=>$orig){
  $bkz=$orig[7];
  if($bkz=='null' || empty($bkz)){
    $berufid=0;
  } else if(isset($berufeByBkz[$bkz])){
    $berufid=$berufeByBkz[$bkz]->id;
  } else {
    echo "Maßnahme ".$orig[2]." mitisid=".$mitisid." unbekannte BKZ ".$bkz."<br />\n";
    $berufid=0;
  }
  if($orig['dest']){
    $dest=$orig['dest'];
    if($dest->titel!=$orig[2]
      || $dest->kuerzel!=$orig[3]
      || $dest->beginn!=$orig[5]
      || $dest->ende!=$orig[6]
      || $dest->berufid!=$berufid) {
      $updatestmt->bind_param('ssissi',$orig[2],$orig[3],$berufid,$orig[5],$orig[6],$dest->id);
      $updatestmt->execute();
      echo "Maßnahme ".$orig[2]." mitisid=".$mitisid." korrigiert<br />\n";
    }
  } else if(!empty($orig[6]) && $orig[6]!='0000-00-00' && $orig[6]<'2024-06-01') {
    echo "Maßnahme ".$orig[2]." mitisid=".$mitisid." schon beendet (ende=".$orig[6].")<br />\n";
  } else {
    $insertstmt->bind_param('sssiss',$mitisid,$orig[2],$orig[3],$berufid,$orig[5],$orig[6]);
    $insertstmt->execute();
    echo "Maßnahme ".$orig[2]." mitisid=".$mitisid." hinzugefügt<br />\n";
  }
}
?>
<a href="index.php">Zurück</a>