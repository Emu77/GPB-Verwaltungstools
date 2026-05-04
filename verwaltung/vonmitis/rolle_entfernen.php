<?php
require_once '../../db.php';
$daten=(object)array(
  'userid'=>928, //Rasel Miah
//  'rolle'=>'',
  'rolle_ersetzen'=>true
);
$url=$moodleurl.'webservice/rest/server.php?wstoken='.$moodletoken.'&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=nutzer_speichern&daten='.urlencode(json_encode($daten));
echo file_get_contents($url);
exit;
?>