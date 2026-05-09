<?php
require_once 'check_login.php';
require_once 'VerwaltungDozent.php';

$dozent=Dozent::einenLaden(isset($_GET['dozentid']) ? (int)$_GET['dozentid'] : 0,'VerwaltungDozent');
if(empty($dozent)) {
  header('Location:dozenten.php');
  exit;
}

require_once $ich->istleiter ? '../leitung/LeitungSeite.php' : 'VerwaltungSeite.php';
$seite=new VerwaltungSeite('Dozent '.$dozent->vorname.' '.$dozent->nachname);
$seite->anfangGenerieren();
$dozent->makeSehen('sehen');
?>
<a href="dozenten.php">Zurück zur Suche</a>
<?php
$seite->endeGenerieren();
?>