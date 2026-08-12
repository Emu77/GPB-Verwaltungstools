<?php
// Zentrales Download-Skript für Anhänge an Mini-Paragraphen.
// Wird von allen drei Rollen (Dozent/TN/Verwaltung) genutzt - der
// Upload-Ordner selbst ist per .htaccess komplett gesperrt, Downloads
// laufen ausschließlich hier durch, wo die Zugriffsrechte geprüft werden.

require_once 'Suche.php';
@session_start();

$anhangid = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($anhangid <= 0) {
  http_response_code(404);
  exit('Nicht gefunden.');
}

require_once 'db.php';

$result = $db->query(
  "SELECT a.id, a.dateiname, a.gespeicherter_dateiname, a.dozentid, p.kursid " .
  "FROM `gpb_mini_anhang` a " .
  "JOIN `gpb_mini_paragraph` p ON p.id=a.paragraphid " .
  "WHERE a.id=" . $anhangid . " LIMIT 1"
);
$anhang = $result->fetch_object();
$result->free();

if (!$anhang) {
  http_response_code(404);
  exit('Nicht gefunden.');
}

// Zugriff prüfen - abhängig davon, als wer man eingeloggt ist. Spiegelt
// jeweils dieselbe Prüfung wie in dozent/tn/verwaltung/mini_kurs_sehen.php.
$zugriff = false;

if (isset($_SESSION['dozent_ich'])) {
  $cwd = getcwd();
  chdir(__DIR__ . '/dozent');
  require_once 'DozentKurs.php';
  chdir($cwd);
  $kurs = Kurs::einenLaden($anhang->kursid, 'DozentKurs');
  if ($kurs && in_array($_SESSION['dozent_ich']->id, $kurs->dozentenids)) {
    $zugriff = true;
  }
} elseif (isset($_SESSION['tn_ich'])) {
  $ich = $_SESSION['tn_ich']; // wird von ladeVonMir() als global $ich erwartet
  $cwd = getcwd();
  chdir(__DIR__ . '/tn');
  require_once 'TnKurs.php';
  chdir($cwd);
  $kurs = Kurs::einenLaden($anhang->kursid, 'TnKurs');
  if ($kurs) {
    $kurs->ladeVonMir();
    if ($kurs->vonMir) {
      $zugriff = true;
    }
  }
} elseif (isset($_SESSION['verwaltung_ich'])) {
  $cwd = getcwd();
  chdir(__DIR__ . '/verwaltung');
  require_once 'VerwaltungKurs.php';
  chdir($cwd);
  $kurs = Kurs::einenLaden($anhang->kursid, 'VerwaltungKurs');
  if ($kurs) {
    $zugriff = true;
  }
}

if (!$zugriff) {
  http_response_code(403);
  exit('Kein Zugriff.');
}

require_once 'MiniAnhang.php';
$pfad = miniAnhangPfad($anhang->dozentid, $anhang->gespeicherter_dateiname);
if (!is_file($pfad)) {
  http_response_code(404);
  exit('Datei nicht gefunden.');
}

$dateinameSauber = str_replace(array("\r", "\n", '"'), '', $anhang->dateiname);

// Bilder werden inline mit korrektem Mime-Type ausgeliefert, damit sie per
// <img>-Tag (Vorschaubild) angezeigt werden können. Alles andere bleibt ein
// erzwungener Download.
$bildMimeTypen = array(
  'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
  'svg' => 'image/svg+xml', 'gif' => 'image/gif', 'webp' => 'image/webp',
);
$ext = strtolower(pathinfo($anhang->dateiname, PATHINFO_EXTENSION));

if (isset($bildMimeTypen[$ext])) {
  header('Content-Type: ' . $bildMimeTypen[$ext]);
  header('Content-Disposition: inline; filename="' . $dateinameSauber . '"');
} else {
  header('Content-Type: application/octet-stream');
  header('Content-Disposition: attachment; filename="' . $dateinameSauber . '"');
}
header('Content-Length: ' . filesize($pfad));
header('X-Content-Type-Options: nosniff');
readfile($pfad);
exit;
