<?php
require_once '../Suche.php';
@session_start();
require_once 'check_login.php';
require_once 'LeitungKurs.php';

$kurs=Kurs::einenLaden(isset($_GET['kursid']) ? (int)$_GET['kursid'] : 0,'LeitungKurs');
if(empty($kurs)) {
  header('Location:kurse.php');
  exit;
}

require_once 'LeitungSeite.php';
$seite=new LeitungSeite('Kurs '.$kurs->titel);
$seite->anfangGenerieren();
$kurs->makeSehen('sehen');
?>
<a href="kurse.php">Zurück zur Suche</a>
<?php
$seite->endeGenerieren();
?>