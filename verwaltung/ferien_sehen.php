<?php
require_once 'check_login.php';
require_once 'VerwaltungFerien.php';

$ferien=Ferien::einenLaden(isset($_GET['ferienid']) ? (int)$_GET['ferienid'] : 0,'VerwaltungFerien');
if(empty($ferien)) {
  header('Location:ferien.php');
  exit;
}

require_once $ich->istleiter ? '../leitung/LeitungSeite.php' : 'VerwaltungSeite.php';
$seite=new VerwaltungSeite('Ferienzeit '.$ferien->anlass.' '.substr($ferien->beginn,0,4));
$seite->anfangGenerieren();
$ferien->makeSehen('sehen');
?>
<a href="ferien.php">Zurück zur Suche</a>
<?php
$seite->endeGenerieren();
?>
