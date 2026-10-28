<?php
require_once 'check_login.php';
require_once 'DozentKurs.php';
require_once '../Aufgabe.php';
require_once '../AufgabeTextUpload.php';
require_once '../AufgabeMultipleChoice.php';

// Nur per POST (Formular mit confirm())
if($_SERVER['REQUEST_METHOD']!=='POST') {
  header('Location:kurse.php');
  exit;
}

$abgabeid = isset($_POST['abgabeid']) ? (int)$_POST['abgabeid'] : 0;
$aufgabe = Aufgabe::laden(isset($_POST['aufgabeid']) ? (int)$_POST['aufgabeid'] : 0);

// Zugriffsprüfung: nur Dozenten des Kurses
if(!empty($aufgabe)) {
  $kurs = Kurs::einenLaden($aufgabe->kursid, 'DozentKurs');
  if(empty($kurs) || !in_array($ich->id, $kurs->dozentenids)) {
    $aufgabe = null;
  }
}
if(empty($aufgabe) || $abgabeid<=0) {
  header('Location:kurse.php');
  exit;
}

// prüft selbst, dass die Abgabe zu dieser Aufgabe gehört
$aufgabe->abgabeZuruecksetzen($abgabeid);

// Kein Erfolgshinweis nötig: in der Zeile steht danach direkt "Keine Abgabe".
header('Location:aufgabe_bewertung.php?aufgabeid='.$aufgabe->id);
exit;
?>
