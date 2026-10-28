<?php
require_once 'check_login.php';

$kursid = isset($_POST['kursid']) ? (int)$_POST['kursid'] : 0;
$aktion = isset($_POST['aktion']) ? $_POST['aktion'] : '';

if ($kursid <= 0 || !in_array($aktion, array('freischalten', 'loeschen'))) {
  header('Location:kurse.php');
  exit;
}

if ($aktion === 'freischalten') {
  $stmt = $db->prepare("UPDATE gpb_kurs SET mini=1 WHERE id=?");
  $stmt->bind_param('i', $kursid);
  $stmt->execute();
  $stmt->close();
  $_SESSION['fehler']['done'] = 'Kurs fuer Mini freigeschaltet.';
} elseif ($aktion === 'loeschen') {
  // Erst die Anhänge aller Paragraphen löschen (Dateien + DB-Zeilen),
  // sonst bleiben verwaiste Dateien in mini_uploads/ zurück.
  require_once '../MiniAnhang.php';
  $res = $db->query("SELECT id FROM gpb_mini_paragraph WHERE kursid=" . $kursid);
  $pids = array();
  while ($row = $res->fetch_object()) {
    $pids[] = (int)$row->id;
  }
  $res->free();
  foreach ($pids as $pid) {
    miniAnhaengeVonParagraphLoeschen($pid);
  }

  $stmt = $db->prepare("DELETE FROM gpb_mini_paragraph WHERE kursid=?");
  $stmt->bind_param('i', $kursid);
  $stmt->execute();
  $stmt->close();
  $stmt = $db->prepare("UPDATE gpb_kurs SET mini=0 WHERE id=?");
  $stmt->bind_param('i', $kursid);
  $stmt->execute();
  $stmt->close();
  $_SESSION['fehler']['done'] = 'Mini-Kurs geloescht.';
}

header('Location:kurs_sehen.php?kursid=' . $kursid);
exit;
