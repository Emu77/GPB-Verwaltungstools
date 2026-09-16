<?php
require_once 'check_login.php';
require_once '../Liste.php';
require_once 'TnKurs.php';
require_once 'TnPVTermin.php';

$kurs=Kurs::einenLaden(isset($_GET['kursid']) ? (int)$_GET['kursid'] : 0,'TnKurs');
if(empty($kurs)) {
  header('Location:kurse.php');
  exit;
}
$kurs->ladeVonMir();

if($kurs->vonMir && $kurs->moodleid>0 && $ich->moodleid>0) {
  $daten=(object)array(
    'courseid'=>$kurs->moodleid
    ,'userid'=>$ich->moodleid
  );
  $url=$moodleurl.'webservice/rest/server.php?wstoken='.$moodletoken.'&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=auswerten&daten='.urlencode(json_encode($daten));
  $aufgaben=json_decode(file_get_contents($url));
  if(is_object($aufgaben) && isset($aufgaben->exception)) {
    error_log('Moodle-Fehler bei courseid='.$kurs->moodleid.': '.json_encode($aufgaben));
    $fehler='Die Aufgaben aus Moodle konnten gerade nicht geladen werden. Bitte versuche es später erneut.';
    unset($aufgaben);
  } else if(!is_object($aufgaben)) {
    $aufgaben=json_decode($aufgaben);
  }
  if(isset($aufgaben)) {
    foreach($aufgaben as $aufgabe) {
      $aufgabe->abgaben=(array)$aufgabe->abgaben; //dies ist eine Map mit User-IDs als Keys
      $aufgabe->istklausur=$aufgabe->titel=='Klausur' || $aufgabe->titel=='Nachklausur'
        || substr($aufgabe->titel,0,8)=='Klausur '|| substr($aufgabe->titel,0,8)=='Nachklausur ';
    }
  }
}
if($kurs->vonMir && $kurs->istPV) {
  $pvtermine=new Liste('TnPVTermin',$db->prepare("select * from gpb_pruefungsvorbereitung_termin where kursid=".$kurs->id." and (false"
    .($kurs->pv->hatAP1 ? " or betrifftAP1" : "")
    .($kurs->pv->hatAP2 ? " or betrifftAP2" : "")
    .($kurs->pv->hatMuendliche ? " or betrifftMuendliche" : "")
    .") order by beginn,beginn_uhrzeit,ende,ende_uhrzeit,was"));
}

require_once 'TnSeite.php';
$seite=new TnSeite('Kurs '.$kurs->titel);
$seite->anfangGenerieren();
$kurs->makeSehen('sehen');
if(isset($fehler)) {
?>
<div class="nok"><?= $fehler ?></div>
<?php
}
if($kurs->vonMir && $kurs->istPV && !empty($pvtermine->alle)) {
?>
<h2>Prüfungs-Termine</h2>
<table border="1" cellspacing="0" style="border-collapse:collapse;margin-bottom:1em;">
<?php
  foreach($pvtermine->alle as $termin) {
    $termin->makeTr(false);
  }
?>
</table>
<?php
}
if(isset($aufgaben) && !empty($aufgaben)) {
?>
<h2>IL-Aufgaben</h2>
<style>
.ilaufgabe {
  writing-mode:vertical-lr; /* sideways-lr nicht von Chrome unterstützt */
  width:1.5em;
  padding:2px;
}
.abgabe,
.aufgabeninhalt {
  text-align:center;
}
.abgabe.ok,
.aufgabeninhalt.ok {
  background-color:green;
}
.abgabe.nok,
.aufgabeninhalt.nok {
  color:black;
  background-color:red;
}
</style>
<table id="ilauswertung" border="1" cellspacing="0" style="border-collapse:collapse;">
  <tr>
    <th></th>
<?php
  foreach($aufgaben as $aufgabe) {
    if($aufgabe->istklausur) continue;
?>
    <td class="ilaufgabe" align="right" valign="bottom"><a href="<?= $aufgabe->url ?>" target="moodle"><?= $aufgabe->titel ?></a></td>
<?php
  }
?>
  </tr>
  <tr>
    <td align="right">Aufgabentext vorhanden?</td>
<?php
  foreach($aufgaben as $aufgabe) {
    if($aufgabe->istklausur) continue;
?>
    <td class="aufgabeninhalt <?= $aufgabe->inhalt_vorhanden<0 ? '' : ($aufgabe->inhalt_vorhanden>0 ? 'ok' : 'nok') ?>" <?= $aufgabe->inhalt_vorhanden<0 ? 'title="Bitte in Moodle nachschauen"' : '' ?>><?= $aufgabe->inhalt_vorhanden<0 ? '?' : ($aufgabe->inhalt_vorhanden>0 ? '✓' : 'X') ?></td>
<?php
  }
?>
  </tr>
  <tr>
    <td align="right">Abgabe vorhanden?</td>
<?php
  foreach($aufgaben as $aufgabe) {
    if($aufgabe->istklausur) continue;
?>
    <td class="abgabe <?= isset($aufgabe->abgaben[$ich->moodleid]) ? ($aufgabe->abgaben[$ich->moodleid] ? 'ok' : 'nok') : '' ?>">
      <?= isset($aufgabe->abgaben[$ich->moodleid]) ? ($aufgabe->abgaben[$ich->moodleid] ? '✓' : 'X') : '&nbsp;' ?>
    </td>
<?php
  }
?>
  </tr>
</table>
<br />
<?php
}
?>
<a href="kurse.php">Zurück zur Suche</a>
<?php
$seite->endeGenerieren();
?>