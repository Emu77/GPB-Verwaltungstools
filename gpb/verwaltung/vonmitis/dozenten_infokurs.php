<?php
require_once '../../db.php';
$daten=(object)array(
  'rolename'=>'student',
  'kursid'=>'Dozenten Infokurs Neukölln',
  'userids'=>array(),
  'ersetzen'=>false
);
$result=$db->query("select moodleid from gpb_dozent where moodleid>0 and id in(select wasid from gpb_planungkonfigzeile where was='dozent' and planungkonfigid in(13,14))");
while($row=$result->fetch_object()) {
  $daten->userids[]=$row->moodleid;
}
$result->free();
$url=$moodleurl.'webservice/rest/server.php?wstoken='.$moodletoken.'&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=nutzer_zuweisen&daten='.urlencode(json_encode($daten));
$ergebnis=json_decode(file_get_contents($url));
if($ergebnis!='ok' && $ergebnis!='"ok"') {
  echo "Dozenten-Infokurs Anmeldung nicht OK: ".(is_string($ergebnis) ? $ergebnis : json_encode($ergebnis));
} else {
  echo count($daten->userids)." Dozenten in den Info-Kurs angemeldet.";
}
?>
<br />
<br />
<a href="index.php">Zurück</a>