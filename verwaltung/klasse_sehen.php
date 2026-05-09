<?php
require_once 'check_login.php';
require_once 'VerwaltungKlasse.php';

$klasse=Klasse::eineLaden(isset($_GET['klasseid']) ? (int)$_GET['klasseid'] : 0,'VerwaltungKlasse');
if(empty($klasse)) {
  header('Location:klassen.php');
  exit;
}

require_once $ich->istleiter ? '../leitung/LeitungSeite.php' : 'VerwaltungSeite.php';
$seite=new VerwaltungSeite('Klasse '.$klasse->bezeichnung);
$seite->anfangGenerieren();
$klasse->makeSehen('sehen');
?>
<a href="klassen.php">Zurück zur Suche</a>
<?php
$seite->endeGenerieren();
?>