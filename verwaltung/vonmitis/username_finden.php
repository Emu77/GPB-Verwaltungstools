<?php
require_once '../../db.php';
$daten=(object)array(
  'fullname'=>'Veronica Principato'
);
$url=$moodleurl.'webservice/rest/server.php?wstoken='.$moodletoken.'&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=nutzer_finden&daten='.urlencode(json_encode($daten));
$ergebnis=json_decode(file_get_contents($url));
var_dump($ergebnis);
exit;
?>