<?php
require_once '../../db.php';

//Vertragszahlen aus /gpb/data/MitisExportVertragszahlen.csv importieren
if(!isset($_FILES['MitisExportVertragszahlen']) || !file_exists($_FILES['MitisExportVertragszahlen']['tmp_name'])) {
  header('Location:index.php');
  exit;
}

$vertragszahlen=array();
$startgruppen=array();
$f=fopen($_FILES['MitisExportVertragszahlen']['tmp_name'],'rt');
//Gruppe;MassnahmeID;bkz;Beginn;ort;typ;interessenten;vertraege;offenevertraege;beratungen;
fgetcsv($f,null,';',' ');
while($row=fgetcsv($f,null,';','"')) {
  $row['startgruppe']=str_replace("'","_",substr($row[0],strlen('Starttermin_')));
  $startgruppen[$row['startgruppe']]=true;
  $row[1]=(int)$row[1]; //MassnahmeID
  if(empty($row[3]) || $row[3]=='null') $row[3]='0000-00-00'; // Beginn
  $row['ort']=$row[4]=='2' ? 'Neukölln' : 'Mitte';
  for($i=6;$i<=9;++$i) {
    $row[$i]=(int)$row[$i];
  }
  $vertragszahlen[]=$row;
}
fclose($f);
echo "Datei geladen, ".count($vertragszahlen)." Datensätze<br />\n";

echo "Alte Daten löschen...<br />\n";
if(!empty($startgruppen)) {
  $db->query("delete from gpb_vertragszahlen where startgruppe in('".implode("','",array_keys($startgruppen))."')");
}
echo "Neue Daten speichern...<br />\n";
$start='';
$stmt=$db->prepare("insert into gpb_vertragszahlen(startgruppe,massnahme_mitisid,bkz,beginn,ort,typ,interessenten,vertraege,offenevertraege,beratungen) values(?,?,?,?,?,?,?,?,?,?)");
foreach($vertragszahlen as $vz) {
  if($vz['startgruppe']!=$start) {
    echo $vz['startgruppe']."<br />\n";
    $start=$vz['startgruppe'];
  }
  $stmt->bind_param('sissssiiii',$vz['startgruppe'],$vz[1],$vz[2],$vz[3],$vz['ort'],$vz[5],$vz[6],$vz[7],$vz[8],$vz[9]);
  $stmt->execute();
}
?>
<a href="index.php">Zurück</a>