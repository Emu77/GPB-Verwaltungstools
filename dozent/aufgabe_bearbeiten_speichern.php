<?php
require_once 'check_login.php';
require_once 'DozentKurs.php';
require_once '../Aufgabe.php';
require_once '../AufgabeTextUpload.php';
require_once '../AufgabeMultipleChoice.php';

$aufgabe = Aufgabe::laden(isset($_POST['aufgabeid']) ? (int)$_POST['aufgabeid'] : 0);

// Zugriffsprüfung: nur Dozenten des Kurses dürfen die Aufgabe ändern
if(!empty($aufgabe)) {
  $kurs = Kurs::einenLaden($aufgabe->kursid, 'DozentKurs');
  if(empty($kurs) || !in_array($ich->id, $kurs->dozentenids)) {
    $aufgabe = null;
  }
}
if(empty($aufgabe)) {
  header('Location:kurse.php');
  exit;
}

$titel = isset($_POST['titel']) ? trim($_POST['titel']) : '';
if($titel==='') {
  $_SESSION['aufgabe_bearbeiten_fehler'] = 'Bitte einen Titel eingeben.';
  header('Location:aufgabe_bearbeiten.php?aufgabeid='.$aufgabe->id);
  exit;
}

$alterPunkteMax = $aufgabe->punkteMax;

$aufgabe->titel = $titel;
$aufgabe->beschreibung = isset($_POST['beschreibung']) ? trim($_POST['beschreibung']) : '';
$aufgabe->punkteMax = (isset($_POST['punkte_max']) && trim($_POST['punkte_max'])!=='') ? (float)str_replace(',', '.', $_POST['punkte_max']) : null;
$aufgabe->sichtbarAb = Aufgabe::datumAusFormular($_POST['sichtbar_ab'] ?? null);
$aufgabe->faelligAm = Aufgabe::datumAusFormular($_POST['faellig_am'] ?? null);
$aufgabe->speichernUpdate();

// Multiple Choice: richtige Antwort pro Frage übernehmen (nur Optionen, die
// wirklich zu der Frage dieser Aufgabe gehören)
$richtigGeaendert = false;
if($aufgabe instanceof AufgabeMultipleChoice && isset($_POST['richtig']) && is_array($_POST['richtig'])) {
  $stmt = $db->prepare("update gpb_aufgabe_option set ist_richtig=? where id=?");
  foreach($aufgabe->ladeFragen() as $frage) {
    if(!isset($_POST['richtig'][$frage->id])) continue;
    $gewaehlt = (int)$_POST['richtig'][$frage->id];
    $gueltig = false;
    foreach($frage->optionen as $o) {
      if($o->id==$gewaehlt) $gueltig = true;
    }
    if(!$gueltig) continue;
    foreach($frage->optionen as $o) {
      $soll = $o->id==$gewaehlt ? 1 : 0;
      if((int)$o->ist_richtig!==$soll) {
        $stmt->bind_param('ii', $soll, $o->id);
        $stmt->execute();
        $richtigGeaendert = true;
      }
    }
  }
  $stmt->close();
}

// Multiple Choice: Punkte hängen von punkte_max und der richtigen Antwort ab
// -> bei Änderung alle vorhandenen Abgaben neu bewerten
$punkteMaxGeaendert = ($alterPunkteMax===null) !== ($aufgabe->punkteMax===null)
  || ($alterPunkteMax!==null && (float)$alterPunkteMax != (float)$aufgabe->punkteMax);
if($aufgabe->kannAutomatischBewerten() && ($punkteMaxGeaendert || $richtigGeaendert)) {
  $result = $db->query("select id from gpb_aufgabe_abgabe where aufgabeid=".intval($aufgabe->id));
  $abgabeids = array();
  while($row = $result->fetch_object()) {
    $abgabeids[] = (int)$row->id;
  }
  $result->free();
  foreach($abgabeids as $abgabeid) {
    $aufgabe->automatischBewerten($abgabeid);
  }
}

// Kein Erfolgshinweis nötig: die geänderten Daten stehen direkt in der Aufgabenliste.
header('Location:mini_kurs_sehen.php?kursid='.$aufgabe->kursid);
exit;
?>
