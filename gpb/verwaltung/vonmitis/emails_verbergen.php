<?php
require_once '../../db.php';
$url=$moodleurl.'webservice/rest/server.php?wstoken='.$moodletoken.'&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=emails_verbergen&daten='.urlencode(json_encode(array()));
$ergebnis=json_decode(file_get_contents($url));
var_dump($ergebnis);  
?>
<a href="index.php">Zurück</a>