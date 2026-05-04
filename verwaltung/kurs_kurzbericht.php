<?php
require_once '../Suche.php';
@session_start();
require_once 'check_login.php';
require_once 'VerwaltungKurs.php';

$kurs=Kurs::einenLaden(isset($_GET['kursid']) ? (int)$_GET['kursid'] : 0,'VerwaltungKurs');
if(empty($kurs)) {
  header('Location:kurse.php');
  exit;
}
$result=$db->query("select * from gpb_kurs_kurzbericht where kursid=".$kurs->id." limit 1");
$kurzbericht=$result->fetch_object();
$result->free();

require_once $ich->istleiter ? '../leitung/LeitungSeite.php' : 'VerwaltungSeite.php';
$seite=new VerwaltungSeite('Kurs-Kurzbericht');
$seite->anfangGenerieren('');
$kurs->makeSehen();
if(empty($kurzbericht)) {
?>
<b>Noch kein Kurzbericht gespeichert.</b>
<?php
} else {
?>
<table id="kurzbericht" border="1" cellspacing="0" style="border-collapse:collapse;">
  <tr><th>
    Zusammenfassung (Kurze, attraktive Beschreibung des Kursinhalts)
  </th></tr>
  <tr><td><?= nl2br($kurzbericht->zusammenfassung) ?></td></tr>
  <tr><th>
    Inhaltliche Gestaltung des Moduls (Abweichungen vom Lehrplan)
  </th></tr>
  <tr><td><?= nl2br($kurzbericht->inhaltsgestaltung) ?></td></tr>
  <tr><th>
    Methodisch-didaktische Hinweise (Lehr- und Lernformen)
  </th></tr>
  <tr><td><?= nl2br($kurzbericht->methoden) ?></td></tr>
  <tr><th>
    Gesamteindruck von der Klasse (Besonderheiten, Schwierigkeiten)
  </th></tr>
  <tr><td><?= nl2br($kurzbericht->eindruck) ?></td></tr>
  <tr><th>
    Was soll in nachfolgenden Modulen trainiert, vertieft bzw. in anderem Kontext eingesetzt werden?
  </th></tr>
  <tr><td><?= nl2br($kurzbericht->todos) ?></td></tr>
</table>
<?php
}
$seite->endeGenerieren();
exit;
?>