<?php
require_once '../../db.php';
$daten=(object)array(
  'rolename'=>'student',
  'kursid'=>'Arbeitsschutz',
  'userids'=>array(),
  'ersetzen'=>false
);
$result=$db->query("select moodleid from gpb_dozent where moodleid>0");
while($row=$result->fetch_object()) {
  $daten->userids[]=$row->moodleid;
}
$result->free();
$url=$moodleurl.'webservice/rest/server.php?wstoken='.$moodletoken.'&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=nutzer_zuweisen&daten='.urlencode(json_encode($daten));
$ergebnis=json_decode(file_get_contents($url));
if($ergebnis!='ok' && $ergebnis!='"ok"') {
  echo "Arbeitsschutz-Anmeldung nicht OK: ".(is_string($ergebnis) ? $ergebnis : json_encode($ergebnis));
} else {
  echo count($daten->userids)." Dozenten in den Arbeitsschutz-Kurs angemeldet.";
}
?>
<br />
<br />
<a href="index.php">Zurück</a>