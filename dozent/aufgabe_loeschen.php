<?php
require_once 'check_login.php';
require_once 'DozentKurs.php';
require_once '../Aufgabe.php';
require_once '../AufgabeTextUpload.php';
require_once '../AufgabeMultipleChoice.php';

// Nur per POST (Formular mit confirm()), damit kein Link/Prefetch versehentlich löscht
if($_SERVER['REQUEST_METHOD']!=='POST') {
  header('Location:kurse.php');
  exit;
}

$aufgabe = Aufgabe::laden(isset($_POST['aufgabeid']) ? (int)$_POST['aufgabeid'] : 0);

// Zugriffsprüfung: nur Dozenten des Kurses dürfen die Aufgabe löschen
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

$kursid = $aufgabe->kursid;
$aufgabe->loeschen(); // inkl. Abgaben, Bewertungen, MC-Fragen und hochgeladener Dateien

// Kein Erfolgshinweis nötig: die Aufgabe verschwindet direkt aus der Liste.
header('Location:mini_kurs_sehen.php?kursid='.$kursid);
exit;
?>
