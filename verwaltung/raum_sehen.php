<?php
require_once '../Suche.php';
@session_start();
require_once 'check_login.php';
require_once 'VerwaltungRaum.php';

$raum=Raum::einenLaden(isset($_GET['raumid']) ? (int)$_GET['raumid'] : 0,'VerwaltungRaum');
if(empty($raum)) {
  header('Location:raeume.php');
  exit;
}

require_once $ich->istleiter ? '../leitung/LeitungSeite.php' : 'VerwaltungSeite.php';
$seite=new VerwaltungSeite('Raum '.$raum->tuer);
$seite->anfangGenerieren();
$raum->makeSehen('sehen');
?>
<a href="raeume.php">Zurück zur Suche</a>
<?php
$seite->endeGenerieren();
?>