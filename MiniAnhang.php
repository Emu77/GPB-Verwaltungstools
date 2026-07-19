<?php
// Hilfsfunktionen für Anhänge an Mini-Paragraphen (gpb_mini_anhang).
// Wird sowohl von dozent/, tn/ als auch verwaltung/mini_kurs_sehen.php
// genutzt (Anzeige der Anhänge), sowie von mini_anhang_download.php.

function miniAnhangListeLaden($paragraphid) {
  global $db;
  $paragraphid = (int)$paragraphid;
  $liste = array();
  $result = $db->query(
    "SELECT id, dateiname, groesse, hochgeladen_am FROM `gpb_mini_anhang` " .
    "WHERE paragraphid=" . $paragraphid . " ORDER BY hochgeladen_am ASC"
  );
  while ($row = $result->fetch_object()) {
    $liste[] = $row;
  }
  $result->free();
  return $liste;
}

function miniAnhangGroesseAnzeigen($bytes) {
  $bytes = (int)$bytes;
  if ($bytes < 1024) return $bytes . ' B';
  if ($bytes < 1024 * 1024) return round($bytes / 1024, 1) . ' KB';
  return round($bytes / (1024 * 1024), 1) . ' MB';
}

// Pfad zur Datei auf der Platte, ausgehend vom Projekt-Wurzelverzeichnis
function miniAnhangPfad($dozentid, $gespeicherterDateiname) {
  return dirname(__FILE__) . '/mini_uploads/dozent_' . (int)$dozentid . '/' . $gespeicherterDateiname;
}
