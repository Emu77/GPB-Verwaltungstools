<?php
require_once 'check_login.php';
require_once 'TnKurs.php';
require_once '../Aufgabe.php';
require_once '../AufgabeTextUpload.php';
require_once '../AufgabeMultipleChoice.php';

$aufgabeid = isset($_POST['aufgabeid']) ? (int)$_POST['aufgabeid'] : 0;
$kursid = isset($_POST['kursid']) ? (int)$_POST['kursid'] : 0;

$aufgabe = Aufgabe::laden($aufgabeid);

// Zugriffsprüfung: Aufgabe muss zu einem Kurs gehören, in dem der TN ist
if(!empty($aufgabe)) {
  $kurs = Kurs::einenLaden($aufgabe->kursid, 'TnKurs');
  if(empty($kurs)) {
    $aufgabe = null;
  } else {
    $kurs->ladeVonMir();
    if(!$kurs->vonMir) {
      $aufgabe = null;
    }
  }
}

if(empty($aufgabe)) {
  header('Location:mini_kurs_sehen.php?kursid='.$kursid);
  exit;
}

$fehler = $aufgabe->speichereAbgabe($ich->id);
if($fehler !== null) {
  $_SESSION['aufgabe_fehler'] = $fehler;
}

header('Location:mini_kurs_sehen.php?kursid='.$aufgabe->kursid);
exit;
?>
