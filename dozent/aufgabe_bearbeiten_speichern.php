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

// Multiple Choice: Punkte hängen von punkte_max ab -> bei Änderung alle
// vorhandenen Abgaben neu bewerten
$punkteMaxGeaendert = ($alterPunkteMax===null) !== ($aufgabe->punkteMax===null)
  || ($alterPunkteMax!==null && (float)$alterPunkteMax != (float)$aufgabe->punkteMax);
if($aufgabe->kannAutomatischBewerten() && $punkteMaxGeaendert) {
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
