<?php
require_once 'check_login.php';
require_once 'VerwaltungVerwalter.php';

$verwalter=VerwaltungVerwalter::einenLaden(isset($_GET['verwalterid']) ? (int)$_GET['verwalterid'] : 0,'VerwaltungVerwalter');
if(empty($verwalter)) {
  header('Location:verwalter.php');
  exit;
}

require_once $ich->istleiter ? '../leitung/LeitungSeite.php' : 'VerwaltungSeite.php';
$seite=new VerwaltungSeite('Verwalter '.$verwalter->vorname.' '.$verwalter->nachname);
$seite->anfangGenerieren();
$verwalter->makeSehen('sehen');
?>
<a href="verwalter.php">Zurück zur Suche</a>
<?php
$seite->endeGenerieren();
?>