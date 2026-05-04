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
$eingeschlosseneKlassen=array(
  "IT-Kombi_Prak_117_EAB",
  "IT-Kombi_Prak_118_EAB",
  "IT-Kombi_Prak_119_EAB",
  "IT-Kombi_Prak_120_EAB",
  "IT_Prak_126_EAB",
  "IT_Prak_127_EAB",
  "IT_Prak_128_EAB",
  "IT_Prak_126_US",
  "IT_Prak_127_US",
  "IT_Prak_128_US"
);


$MKlassenByMid=array();
$f=fopen($_FILES['MitisExportKlasse']['tmp_name'],'rt');
//KlasseID;Bezeichnung;Typ;NiederlassungID;Beginn;Ende;PraktikumBeginn;PraktikumEnde;PVBeginn;PVEnde;
fgetcsv($f,null,';',' ');
while($row=fgetcsv($f,null,';','"')) {
  if(in_array($row[2],$ausgeschlosseneKlassentypen) && !in_array($row[1],$eingeschlosseneKlassen)) continue;
  if(mb_strpos($row[1],'telc')!==false) continue; //telc ausschließen
  
  $row['dest']=false;
  $row['ort']=$row[3]=='2' ? 'Neukölln' : 'Mitte';
  $row[4]=empty($row[4]) || $row[4]=='null' ? '0000-00-00' : $row[4]; //implode('-',array_reverse(explode('.',substr($row[4],0,10))));
  $row[5]=empty($row[5]) || $row[5]=='null' ? '0000-00-00' : $row[5]; //implode('-',array_reverse(explode('.',substr($row[5],0,10))));
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
$deletetnstmt=$db->prepare("delete from gpb_klasse_tn where klasseid=?");
$deleteferienstmt=$db->prepare("delete from gpb_klasse_ferien where klasseid=?");
$deletestmt=$db->prepare("delete from gpb_klasse where id=?");
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
    if(in_array($orig[2],$ausgeschlosseneKlassentypen)) { //Klasse nicht mehr gewollt, muss gelöscht werden
      if($moodleid>0) {
        $daten=(object)array(
          'rolename'=>'student',
          'courseid'=>$moodleid,
          'userids'=>array(),
          'ersetzen'=>true
        );
        $url=$moodleurl.'webservice/rest/server.php?wstoken='.$moodletoken.'&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=nutzer_zuweisen&daten='.json_encode($daten);
        $ergebnis=json_decode(file_get_contents($url));
        if($ergebnis!='ok' && $ergebnis!='"ok"') {
          echo "Klasse ".$dest->bezeichnung." mit mitisid=".$dest->mitisid." Moodle-Kurs-ID: ".$moodleid." Problem: ".(is_string($ergebnis) ? $ergebnis : json_encode($ergebnis))."<br />\n";
        } else {
          echo "Klasse ".$dest->bezeichnung." mit mitisid=".$dest->mitisid." Moodle-Kurs-ID: ".$moodleid." Zuweisungen entfernt<br />\n";
        }
        $daten=(object)array(
          'courseid'=>$moodleid,
          'kategorie'=>'zuloeschen'.rand(0,9),
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
          echo "Klasse ".$orig[1]." mitisid=".$orig[0]." Moodle Klassenkurs ID=".$ergebnis->id." ins Zulöschen verschoben.<br />\n";
          //TODO Anmeldung in den Arbeitsschutz-Kurs entfernen
        }
      }
      $moodletodo=false;
      $deletetnstmt->bind_param('i',$id);
      $deletetnstmt->execute();
      $deleteferienstmt->bind_param('i',$id);
      $deleteferienstmt->execute();
      $deletestmt->bind_param('i',$id);
      $deletestmt->execute();
      echo "Klasse ".$orig[1]." mitisid=".$orig[0]." gelöscht.<br />\n";
      ob_flush();
      continue;
    } else if($dest->bezeichnung!=$orig[1]
        || $dest->berufid!=$origberufid
        || $dest->ort!=$orig['ort']
        || $dest->beginn!=$orig[4]
        || $dest->ende!=$orig[5]) {
      $updatestmt->bind_param('sisssi',$orig[1],$origberufid,$orig[4],$orig[5],$orig['ort'],$dest->id);
      $updatestmt->execute();
      echo "Klasse ".$orig[1]." mitisid=".$orig[0]." korrigiert.<br />\n";
      $moodletodo=true;
    }
  } else if(in_array($orig[2],$ausgeschlosseneKlassentypen)) { //Klasse nicht gewollt
    continue;
  } else if(!empty($orig[5]) && $orig[5]!='0000-00-00' && $orig[5]<'2024-06-01') {
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
        $updatemoodlestmt->bind_param('ii',$ergebnis->id,$id);
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