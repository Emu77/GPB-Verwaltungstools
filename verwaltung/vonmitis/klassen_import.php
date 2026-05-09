<?php
require_once '../../db.php';

//Klassen aus /gpb/data/MitisExportKlasse.csv importieren
if(!isset($_FILES['MitisExportKlasse']) || !file_exists($_FILES['MitisExportKlasse']['tmp_name'])) {
  header('Location:index.php');
  exit;
}

$ausgeschlosseneKlassentypen=array(
  'Praktikum',
  'Normal (Klasse=Maßnahme)',
  'InTrain'
);
$eingeschlosseneKlassen=array();
foreach(explode("\n",file_get_contents('zusaetzlicheklassen.txt')) as $z) {
  $z=trim($z);
  if(empty($z)) continue;
  if(substr($z,0,2)=='//' || substr($z,0,1)=='#' || substr($z,0,1)==';') continue;
  $eingeschlosseneKlassen[]=$z;
}


$MKlassenByMid=array();
$f=fopen($_FILES['MitisExportKlasse']['tmp_name'],'rt');
//KlasseID;Bezeichnung;Typ;NiederlassungID;Beginn;Ende;PraktikumBeginn;PraktikumEnde;PVBeginn;PVEnde;
fgetcsv($f,null,';',' ');
while($row=fgetcsv($f,null,';','"')) {
  if(in_array($row[2],$ausgeschlosseneKlassentypen) && !in_array($row[1],$eingeschlosseneKlassen)) {
//    echo "Ausgeschlossene Klasse: ".$row[1]."<br />\n";
    continue;
  }
  if(mb_strpos($row[1],'telc')!==false) continue; //telc ausschließen
  if(mb_strpos($row[1],'Fachkräfte')!==false) continue; //Fachkräfte ausschließen

  $row['dest']=false;
  $row['ort']=$row[3]=='2' ? 'Neukölln' : 'Mitte';
  $row[4]=$row[4]=='null' ? '0000-00-00' : $row[4]; //implode('-',array_reverse(explode('.',substr($row[4],0,10))));
  $row[5]=$row[5]=='null' ? '0000-00-00' : $row[5]; //implode('-',array_reverse(explode('.',substr($row[5],0,10))));
  $MKlassenByMid[$row[0]]=$row;
}
fclose($f);
echo "Datei geladen, ".count($MKlassenByMid)." Datensätze<br />\n";


echo "Abgleich mit schon importierten Klassen...<br />\n";
$result=$db->query("select * from gpb_klasse");
while($row=$result->fetch_object()) {
  if(isset($MKlassenByMid[$row->mitisid])) {
    $MKlassenByMid[$row->mitisid]['dest']=$row;
  } else {
    echo "Klasse ".$row->bezeichnung." mitisid=".$row->mitisid." aus MitisExport verschwunden<br />\n";
  }
}
$result->free();

echo "Daten korrigieren...<br />\n";
$berufeByMitiskuerzel=array();
$result=$db->query("select * from gpb_beruf ");
while($row=$result->fetch_object()) {
  foreach(explode(';',$row->mitiskuerzel) as $mk) {
    $mk=trim($mk);
    if(empty($mk)) continue;
    $berufeByMitiskuerzel[mb_strtolower($mk)]=$row; // voriger Eintrag wird ggf. durch K O P I E überbügelt
  }
}
$result->free();
$updatestmt=$db->prepare("update gpb_klasse set bezeichnung=?,berufid=?,beginn=?,ende=?,ort=? where id=? limit 1");
$insertstmt=$db->prepare("insert into gpb_klasse(mitisid,bezeichnung,berufid,beginn,ende,ort) values(?,?,?,?,?,?)");
$updatemoodlestmt=$db->prepare("update gpb_klasse set moodleid=? where id=? limit 1");
foreach($MKlassenByMid as $mitisid=>$orig) {
  $id=0;
  $moodleid=0;
  $moodlenumber='Klasse_'.$orig[0];
  $moodletodo=false;
  $origberufid=0;
  $bezklein=mb_strtolower($orig[1]);
  foreach($berufeByMitiskuerzel as $mk=>$beruf) {
    if(mb_strpos($bezklein,$mk)!==false) {
      $origberufid=$beruf->id;
      break;
    }
  }
  if($origberufid==0) {
    echo "Klasse ".$orig[1]." Beruf nicht gefunden.<br />\n";
  }
  if($orig['dest']) {
    $dest=$orig['dest'];
    $id=$dest->id;
    $moodleid=$dest->moodleid;
    if($dest->bezeichnung!=$orig[1]
      || $dest->berufid!=$origberufid
      || $dest->ort!=$orig['ort']
      || $dest->beginn!=$orig[4]
      || $dest->ende!=$orig[5]) {
      $updatestmt->bind_param('sisssi',$orig[1],$origberufid,$orig[4],$orig[5],$orig['ort'],$dest->id);
      $updatestmt->execute();
      echo "Klasse ".$orig[1]." mitisid=".$orig[0]." korrigiert.<br />\n";
      $moodletodo=true;
    }
  } else if($orig[5]!='0000-00-00' && $orig[5]<'2024-06-01') {
    echo "Klasse ".$orig[1]." mitisid=".$orig[0]." schon beendet (ende=".$orig[5].").<br />\n";
    continue;
  } else {
    $insertstmt->bind_param('ssisss',$mitisid,$orig[1],$origberufid,$orig[4],$orig[5],$orig['ort']);
    $insertstmt->execute();
    $id=$db->insert_id;
    echo "Klasse ".$orig[1]." mitisid=".$orig[0]." hinzugefügt.<br />\n";
    $moodletodo=true;
  }
  if($moodleid<=0 || $moodletodo) {
    $daten=(object)array(
      'courseid'=>$moodleid,
      'kategorie'=>'Klassenkurse',
      'titel'=>'Klasse: '.$orig[1],
      'kursid'=>$moodlenumber,
      'beginn'=>$orig[4],
      'ende'=>$orig[5]
    );
    $url=$moodleurl.'webservice/rest/server.php?wstoken='.$moodletoken.'&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=kurs_speichern&daten='.urlencode(json_encode($daten));
    $ergebnis=json_decode(file_get_contents($url));
    if(is_object($ergebnis) && isset($ergebnis->exception)) {
      echo "Klasse ".$orig[1]." mitisid=".$orig[0]." Moodle Klassenkurs nicht OK: ".json_encode($ergebnis)."<br />\n";
    } else {
      if(!is_object($ergebnis)) {
        $ergebnis=json_decode($ergebnis);
      }
      if($ergebnis->id!=$moodleid) {
        $moodleid=$ergebnis->id;
        $updatemoodlestmt->bind_param('ii',$moodleid,$id);
        $updatemoodlestmt->execute();
      }
      echo "Klasse ".$orig[1]." mitisid=".$orig[0]." Moodle Klassenkurs ID=".$ergebnis->id." gespeichert.<br />\n";
      //Arbeitsschutz
      // TODO einkommentieren, wenn Plugin installiert
//      $daten=(object)array(
//        'kursid'=>'Arbeitsschutz',
//        'klassencourseids'=>array($moodleid),
//        'ersetzen'=>false
//      );
//      $url=$moodleurl.'webservice/rest/server.php?wstoken='.$moodletoken.'&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=meta_einschreibung&daten='.urlencode(json_encode($daten));
//      $ergebnis=json_decode(file_get_contents($url));
//      if($ergebnis!='ok' && $ergebnis!='"ok"') {
//        echo "Klasse ".$orig[1]." mitisid=".$orig[0]." Arbeitsschutz-Anmeldung nicht OK: ".json_encode($ergebnis)."<br />\n";
//      }
    }
  }
  ob_flush();
}
?>
<a href="index.php">Zurück</a>