<?php
require_once 'check_login.php';
require_once 'DozentKurs.php';
require_once '../Aufgabe.php';
require_once '../AufgabeTextUpload.php';
require_once '../AufgabeMultipleChoice.php';

$kursid = isset($_POST['kursid']) ? (int)$_POST['kursid'] : 0;
$kurs = Kurs::einenLaden($kursid, 'DozentKurs');

if(empty($kurs) || !in_array($ich->id, $kurs->dozentenids)) {
  header('Location:kurse.php');
  exit;
}

function aufgabeErstellenAbbrechen($kursid, $meldung) {
  $_SESSION['aufgabe_erstellen_fehler'] = $meldung;
  header('Location:aufgabe_erstellen.php?kursid='.$kursid);
  exit;
}

function normDatumZeit($wert) {
  if(empty($wert)) return null;
  return str_replace('T', ' ', $wert).':00';
}

$titel = isset($_POST['titel']) ? trim($_POST['titel']) : '';
if($titel==='') {
  aufgabeErstellenAbbrechen($kursid, 'Bitte einen Titel eingeben.');
}

$typ = isset($_POST['typ']) && $_POST['typ']=='multiple_choice' ? 'multiple_choice' : 'text_upload';

$fragenRoh = array();
if($typ=='multiple_choice') {
  $fragenRoh = isset($_POST['frage']) && is_array($_POST['frage']) ? $_POST['frage'] : array();
  $gueltigeFragen = array();
  foreach($fragenRoh as $key => $f) {
    $frageText = isset($f['text']) ? trim($f['text']) : '';
    if($frageText==='') continue;
    $optionen = array();
    if(isset($f['optionen']) && is_array($f['optionen'])) {
      foreach($f['optionen'] as $optIndex => $optText) {
        $optText = trim($optText);
        if($optText!=='') {
          $optionen[$optIndex] = $optText;
        }
      }
    }
    if(count($optionen) < 2) continue; // mindestens 2 Antwortoptionen nötig
    $gueltigeFragen[$key] = array(
      'text' => $frageText,
      'optionen' => $optionen,
      'richtig' => isset($f['richtig']) ? $f['richtig'] : null,
    );
  }
  if(empty($gueltigeFragen)) {
    aufgabeErstellenAbbrechen($kursid, 'Bitte mindestens eine Frage mit mindestens zwei Antwortoptionen anlegen.');
  }
}

$klasse = $typ=='multiple_choice' ? 'AufgabeMultipleChoice' : 'AufgabeTextUpload';
$aufgabe = new $klasse();
$aufgabe->kursid = $kurs->id;
$aufgabe->typ = $typ;
$aufgabe->titel = $titel;
$aufgabe->beschreibung = isset($_POST['beschreibung']) ? trim($_POST['beschreibung']) : '';
$aufgabe->punkteMax = (isset($_POST['punkte_max']) && trim($_POST['punkte_max'])!=='') ? (float)str_replace(',', '.', $_POST['punkte_max']) : null;
$aufgabe->sichtbarAb = normDatumZeit($_POST['sichtbar_ab'] ?? null);
$aufgabe->faelligAm = normDatumZeit($_POST['faellig_am'] ?? null);
$aufgabe->erstelltVon = $ich->id;
$aufgabe->speichernNeu();

if($typ=='multiple_choice') {
  $reihenfolge = 1;
  $stmtFrage = $db->prepare("insert into gpb_aufgabe_frage (aufgabeid,frage,reihenfolge) values (?,?,?)");
  $stmtOption = $db->prepare("insert into gpb_aufgabe_option (frageid,text,ist_richtig,reihenfolge) values (?,?,?,?)");

  foreach($gueltigeFragen as $f) {
    $stmtFrage->bind_param('isi', $aufgabe->id, $f['text'], $reihenfolge);
    $stmtFrage->execute();
    $frageid = $stmtFrage->insert_id;

    $optReihenfolge = 1;
    foreach($f['optionen'] as $optIndex => $optText) {
      $istRichtig = ($f['richtig']!==null && (string)$f['richtig']===(string)$optIndex) ? 1 : 0;
      $stmtOption->bind_param('isii', $frageid, $optText, $istRichtig, $optReihenfolge);
      $stmtOption->execute();
      $optReihenfolge++;
    }
    $reihenfolge++;
  }
  $stmtFrage->close();
  $stmtOption->close();
}

header('Location:mini_kurs_sehen.php?kursid='.$kurs->id);
exit;
?>
