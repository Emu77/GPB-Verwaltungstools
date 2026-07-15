<?php
require_once '../../db.php';
$daten=(object)array(
  'kursid'=>'GPB Guide',
  'klassencourseids'=>array(),
  'ersetzen'=>false
);
$result=$db->query("select moodleid from gpb_klasse where moodleid>0 and bezeichnung not like '%intrain%' and bezeichnung not like '%prak%' and bezeichnung not like '%pv%'");
while($row=$result->fetch_object()) {
  $daten->klassencourseids[]=$row->moodleid;
}
$result->free();

$url=$moodleurl.'webservice/rest/server.php?wstoken='.$moodletoken.'&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=meta_einschreibung&daten='.urlencode(json_encode($daten));
$ergebnis=json_decode(file_get_contents($url));
if($ergebnis!='ok' && $ergebnis!='"ok"') {
  echo "Infokurs-Anmeldung nicht OK: ".json_encode($ergebnis)."<br />\n";
} else {
  echo count($daten->klassencourseids)." Klassen in den Infokurs angemeldet.<br />\n";
}
?>
<br />
<br />
<a href="index.php">Zurück</a>