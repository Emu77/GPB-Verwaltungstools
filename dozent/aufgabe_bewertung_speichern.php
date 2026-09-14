<?php
require_once 'check_login.php';
require_once 'DozentKurs.php';
require_once '../Aufgabe.php';
require_once '../AufgabeTextUpload.php';
require_once '../AufgabeMultipleChoice.php';

$abgabeid = isset($_POST['abgabeid']) ? (int)$_POST['abgabeid'] : 0;
$aufgabeid = isset($_POST['aufgabeid']) ? (int)$_POST['aufgabeid'] : 0;

$aufgabe = Aufgabe::laden($aufgabeid);

// Zugriffsprüfung: nur Dozenten des Kurses dürfen bewerten, und die Abgabe
// muss wirklich zu dieser Aufgabe gehören
if(!empty($aufgabe)) {
  $kurs = Kurs::einenLaden($aufgabe->kursid, 'DozentKurs');
  if(empty($kurs) || !in_array($ich->id, $kurs->dozentenids)) {
    $aufgabe = null;
  }
}

if(!empty($aufgabe) && $abgabeid>0) {
  $result = $db->query("select id from gpb_aufgabe_abgabe where id=".$abgabeid." and aufgabeid=".intval($aufgabe->id));
  $row = $result->fetch_object();
  $result->free();
  if(empty($row)) {
    $aufgabe = null;
  }
}

if(empty($aufgabe) || $abgabeid<=0) {
  header('Location:kurse.php');
  exit;
}

$punkte = (isset($_POST['punkte']) && trim($_POST['punkte'])!=='') ? (float)str_replace(',', '.', $_POST['punkte']) : null;
$note = (isset($_POST['note']) && trim($_POST['note'])!=='') ? trim($_POST['note']) : null;
$kommentar = (isset($_POST['kommentar']) && trim($_POST['kommentar'])!=='') ? trim($_POST['kommentar']) : null;

$stmt = $db->prepare("insert into gpb_aufgabe_bewertung (abgabeid,punkte,note,kommentar,bewertet_von,bewertet_am)
  values (?,?,?,?,?,now())
  on duplicate key update punkte=values(punkte), note=values(note), kommentar=values(kommentar), bewertet_von=values(bewertet_von), bewertet_am=values(bewertet_am)");
$stmt->bind_param('idssi', $abgabeid, $punkte, $note, $kommentar, $ich->id);
$stmt->execute();
$stmt->close();

header('Location:aufgabe_bewertung.php?aufgabeid='.$aufgabe->id);
exit;
?>
