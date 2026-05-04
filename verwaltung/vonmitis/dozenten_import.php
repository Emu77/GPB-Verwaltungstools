<?php
require_once '../../db.php';

//Dozenten aus DozentenAusMoodle.csv importieren
if(!isset($_FILES['DozentenAusMoodle']) || !file_exists($_FILES['DozentenAusMoodle']['tmp_name'])) {
  header('Location:index.php');
  exit;
} 
//Aktualität dieser Dozenten aus uebersicht_dozenten_jahrgaenge.csv importieren
if(!isset($_FILES['uebersicht_dozenten_jahrgaenge']) || !file_exists($_FILES['uebersicht_dozenten_jahrgaenge']['tmp_name'])) {
    header('Location:index.php');
    exit;
} 

$f=fopen($_FILES['uebersicht_dozenten_jahrgaenge']['tmp_name'],'rt');
$dozentenNamen=array();
//Nachname;Geburtsdatum;Jahrgang;Fachrichtung;Alter
fgetcsv($f,null,';',' ');
while($row=fgetcsv($f,null,';',' ')) {
  $dozentenNamen[$row[0]]=0;
}
fclose($f);
echo "Übersicht geladen, ".count($dozentenNamen)." Dozenten-Namen<br />\n";


$f=fopen($_FILES['DozentenAusMoodle']['tmp_name'],'rt');
$dozenten=array();
//"id";"auth";"confirmed";"policyagreed";"deleted";"suspended";"mnethostid";"username";"password";"idnumber";"firstname";"lastname";"email";"emailstop";"icq";"skype";"yahoo";"aim";"msn";"phone1";"phone2";"institution";"department";"address";"city";"country";"lang";"theme";"timezone";"firstaccess";"lastaccess";"lastlogin";"currentlogin";"lastip";"secret";"picture";"url";"description";"descriptionformat";"mailformat";"maildigest";"maildisplay";"autosubscribe";"trackforums";"timecreated";"timemodified";"trustbitmask";"imagealt";"lastnamephonetic";"firstnamephonetic";"middlename";"alternatename";"moodlenetprofile";"calendartype";"anrede";
//alle Felder kommen doppelt!
fgetcsv($f,null,';',' ');
while($row=fgetcsv($f,null,';','"')) {
  if(!isset($dozentenNamen[$row[22]])) {
      echo "Nicht übernommen: ".$row[20]." ".$row[22]."<br />\n";
      continue;
  }
  ++$dozentenNamen[$row[22]];
  if($dozentenNamen[$row[22]]>1) {
      echo "Name mehrmals vorhanden: ".$row[22]."<br />\n";
  }
  $dozenten[]=$row;
}
fclose($f);
echo "Datei geladen, ".count($dozenten)." Datensätze<br />\n";

$stmt=$db->prepare("insert into gpb_dozent(anrede,vorname,nachname,nutzername,passwort,moodleid,email,tel1,tel2) values(?,?,?,?,?,?,?,?,?)");
$anrede='';
$vorname='';
$nachname='';
$nutzername='';
$passwort='';
$email='';
$tel1='';
$tel2='';
$moodleid=0;
$stmt->bind_param('sssssisss',$anrede,$vorname,$nachname,$nutzername,$passwort,$moodleid,$email,$tel1,$tel2);
$id=0;
foreach($dozenten as $doz) {
  $anrede=$doz[108];
  $vorname=$doz[20];
  $nachname=$doz[22];
  echo $vorname." ".$nachname;
  $nutzername=$doz[14];
  $passwort=$doz[16];
  $email=$doz[24];
  $tel1=$doz[38];
  $tel2=$doz[40];
  $moodleid=0;
  //Moodle-Nutzer erstellen oder finden
  $daten=(object)array(
    'anrede'=>$anrede,
    'vorname'=>$vorname,
    'nachname'=>$nachname,
    'nutzername'=>$nutzername,
    'hash'=>empty($passwort) ? '' : password_hash($passwort,PASSWORD_DEFAULT),
    'email'=>$moodleisttest ? 'testdozent'.($id+1).'@gpb.de' : $email
  );
  $url=$moodleurl.'webservice/rest/server.php?wstoken='.$moodletoken.'&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=nutzer_speichern&daten='.urlencode(json_encode($daten));
  $ergebnis=json_decode(file_get_contents($url));
  if(is_object($ergebnis) && isset($ergebnis->exception)) {
    echo " Moodle-Nutzer nicht angelegt: ".json_encode($ergebnis);
  } else {
    if(!is_object($ergebnis)) {
      $ergebnis=json_decode($ergebnis);
    }
    $moodleid=$ergebnis->id;
  }
  $stmt->execute();
  $id=$db->insert_id;
  echo " gespeichert.<br />\n";
  // Arbeitsschutz
  // TODO einkommentieren, wenn Plugin installiert
//  $daten=(object)array(
//    'rolename'=>'student',
//    'kursid'=>'Arbeitsschutz',
//    'userids'=>array($moodleid),
//    'ersetzen'=>false
//  );
//  $url=$moodleurl.'webservice/rest/server.php?wstoken='.$moodletoken.'&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=nutzer_zuweisen&daten='.urlencode(json_encode($daten));
//  $ergebnis=json_decode(file_get_contents($url));
//  if($ergebnis!='ok' && $ergebnis!='"ok"') {
//    echo "Arbeitsschutz-Anmeldung von ".$vorname." ".$nachname." nicht OK: ".(is_string($ergebnis) ? $ergebnis : json_encode($ergebnis));
//  }
  ob_flush();
}
?>
<a href="index.php">Zurück</a>