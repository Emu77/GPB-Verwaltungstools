<?php
// Zentrales Download-Skript für Datei-Anhänge an Aufgaben-Abgaben.
// Der Upload-Ordner selbst ist per .htaccess komplett gesperrt, Downloads
// laufen ausschließlich hier durch, wo die Zugriffsrechte geprüft werden.
// Analog zu mini_anhang_download.php.

require_once 'Suche.php';
@session_start();

$abgabeid = isset($_GET['abgabeid']) ? (int)$_GET['abgabeid'] : 0;
if($abgabeid<=0) {
  http_response_code(404);
  exit('Nicht gefunden.');
}

require_once 'db.php';

$result = $db->query(
  "SELECT ab.id, ab.tnid, ab.dateiname, ab.gespeicherter_dateiname, a.id AS aufgabeid, a.kursid ".
  "FROM `gpb_aufgabe_abgabe` ab ".
  "JOIN `gpb_aufgabe` a ON a.id=ab.aufgabeid ".
  "WHERE ab.id=".$abgabeid." LIMIT 1"
);
$abgabe = $result->fetch_object();
$result->free();

if(!$abgabe || empty($abgabe->gespeicherter_dateiname)) {
  http_response_code(404);
  exit('Nicht gefunden.');
}

// Zugriff prüfen - abhängig davon, als wer man eingeloggt ist.
$zugriff = false;

if(isset($_SESSION['dozent_ich'])) {
  $cwd = getcwd();
  chdir(__DIR__.'/dozent');
  require_once 'DozentKurs.php';
  chdir($cwd);
  $kurs = Kurs::einenLaden($abgabe->kursid, 'DozentKurs');
  if($kurs && in_array($_SESSION['dozent_ich']->id, $kurs->dozentenids)) {
    $zugriff = true;
  }
} elseif(isset($_SESSION['tn_ich'])) {
  // TN darf nur die eigene Abgabe herunterladen
  if($_SESSION['tn_ich']->id == $abgabe->tnid) {
    $zugriff = true;
  }
} elseif(isset($_SESSION['verwaltung_ich'])) {
  $zugriff = true;
}

if(!$zugriff) {
  http_response_code(403);
  exit('Kein Zugriff.');
}

require_once 'AufgabeTextUpload.php';
$pfad = AufgabeTextUpload::uploadOrdner($abgabe->aufgabeid).'/'.$abgabe->gespeicherter_dateiname;
if(!is_file($pfad)) {
  http_response_code(404);
  exit('Datei nicht gefunden.');
}

$dateinameSauber = str_replace(array("\r","\n",'"'), '', $abgabe->dateiname);

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="'.$dateinameSauber.'"');
header('Content-Length: '.filesize($pfad));
header('X-Content-Type-Options: nosniff');
readfile($pfad);
exit;
?>
