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

// Erkennt anhand der Dateiendung, ob der Anhang ein Bild ist, das per
// <img> dargestellt werden kann (siehe auch mini_anhang_download.php,
// wo dieselben Endungen inline statt als Download ausgeliefert werden).
function miniAnhangIstBild($dateiname) {
  $ext = strtolower(pathinfo($dateiname, PATHINFO_EXTENSION));
  return in_array($ext, array('jpg', 'jpeg', 'png', 'svg', 'gif', 'webp'), true);
}

// Einheitliche Anzeige eines Anhangs: Bilder als kleines Vorschaubild (klickbar,
// vergrößert sich per JavaScript), sonstige Dateien als Download-Link. Der
// Dateiname wird in jedem Fall mit angezeigt. $basisPfad ist der relative Pfad
// zu mini_anhang_download.php (von der aufrufenden Seite aus gesehen).
function miniAnhangAnzeigen($a, $basisPfad = '') {
  $url = $basisPfad . 'mini_anhang_download.php?id=' . $a->id;
  $name = htmlspecialchars($a->dateiname);
  $groesse = miniAnhangGroesseAnzeigen($a->groesse);
  if (miniAnhangIstBild($a->dateiname)) {
    echo '<div class="mini-anhang-bild-wrapper">';
    echo '<img src="' . $url . '" alt="' . $name . '" class="mini-anhang-bild" onclick="this.classList.toggle(\'mini-bild-gross\')" />';
    echo '<div class="mini-anhang-dateiname"><a href="' . $url . '">' . $name . '</a> (' . $groesse . ')</div>';
    echo '</div>';
  } else {
    echo '<a href="' . $url . '">' . $name . '</a> (' . $groesse . ')';
  }
}

// true, wenn der Anhang per <img> (Button "Bild einfügen") im Text verwendet wird
function miniAnhangImTextVerwendet($anhangid, $inhalt) {
  return (bool)preg_match('/mini_anhang_download\.php\?id=' . (int)$anhangid . '(?!\d)/', (string)$inhalt);
}

// Für die Ansicht (TN, Dozent-Ansichtsmodus, Verwaltung): Bilder, die schon
// im Text stehen, nicht noch einmal unter "Anhänge" wiederholen.
function miniAnhaengeOhneEingebettete($anhaenge, $inhalt) {
  return array_values(array_filter($anhaenge, function ($a) use ($inhalt) {
    return !miniAnhangImTextVerwendet($a->id, $inhalt);
  }));
}

// Entfernt alle <img>-Tags, die auf diesen Anhang zeigen, aus einem Text
function miniAnhangAusTextEntfernen($inhalt, $anhangid) {
  return preg_replace(
    '/<img\b[^>]*mini_anhang_download\.php\?id=' . (int)$anhangid . '(?!\d)[^>]*>/i',
    '',
    (string)$inhalt
  );
}

// Löscht einen Anhang komplett (Datei + DB-Zeile). Wird beim Löschen eines
// einzelnen Anhangs sowie beim Löschen eines ganzen Paragraphen benutzt.
function miniAnhangLoeschen($anhangid) {
  global $db;
  $anhangid = (int)$anhangid;
  $res = $db->query("SELECT id, dozentid, gespeicherter_dateiname FROM `gpb_mini_anhang` WHERE id=" . $anhangid . " LIMIT 1");
  $anhang = $res->fetch_object();
  $res->free();
  if ($anhang) {
    // Eingebettete Bilder aus allen Paragraphen desselben Kurses entfernen,
    // sonst bleibt im Text ein kaputtes Bild zurück
    $res = $db->query(
      "SELECT p2.id, p2.inhalt FROM `gpb_mini_anhang` a " .
      "JOIN `gpb_mini_paragraph` p ON p.id=a.paragraphid " .
      "JOIN `gpb_mini_paragraph` p2 ON p2.kursid=p.kursid " .
      "WHERE a.id=" . $anhangid
    );
    $zuAendern = array();
    while ($row = $res->fetch_object()) {
      if (miniAnhangImTextVerwendet($anhangid, $row->inhalt)) {
        $zuAendern[(int)$row->id] = miniAnhangAusTextEntfernen($row->inhalt, $anhangid);
      }
    }
    $res->free();
    if (!empty($zuAendern)) {
      $stmt = $db->prepare("UPDATE `gpb_mini_paragraph` SET inhalt=? WHERE id=?");
      foreach ($zuAendern as $pid => $neuerInhalt) {
        $stmt->bind_param('si', $neuerInhalt, $pid);
        $stmt->execute();
      }
      $stmt->close();
    }

    $pfad = miniAnhangPfad($anhang->dozentid, $anhang->gespeicherter_dateiname);
    if (is_file($pfad)) {
      unlink($pfad);
    }
    $stmt = $db->prepare("DELETE FROM `gpb_mini_anhang` WHERE id=?");
    $stmt->bind_param('i', $anhangid);
    $stmt->execute();
    $stmt->close();
  }
}

// Löscht alle Anhänge eines Paragraphen (Dateien + DB-Zeilen) - wird beim
// Löschen des Paragraphen selbst aufgerufen, damit keine verwaisten Dateien
// im Upload-Ordner zurückbleiben.
function miniAnhaengeVonParagraphLoeschen($paragraphid) {
  global $db;
  $paragraphid = (int)$paragraphid;
  $res = $db->query("SELECT id FROM `gpb_mini_anhang` WHERE paragraphid=" . $paragraphid);
  $ids = array();
  while ($row = $res->fetch_object()) {
    $ids[] = $row->id;
  }
  $res->free();
  foreach ($ids as $id) {
    miniAnhangLoeschen($id);
  }
}

// Pfad zur Datei auf der Platte, ausgehend vom Projekt-Wurzelverzeichnis
function miniAnhangPfad($dozentid, $gespeicherterDateiname) {
  return dirname(__FILE__) . '/mini_uploads/dozent_' . (int)$dozentid . '/' . $gespeicherterDateiname;
}
