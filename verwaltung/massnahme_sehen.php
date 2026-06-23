<?php
require_once '../Suche.php';
@session_start();
require_once 'check_login.php';
require_once 'VerwaltungMassnahme.php';

$massnahme=Massnahme::eineLaden(isset($_GET['massnahmeid']) ? (int)$_GET['massnahmeid'] : 0,'VerwaltungMassnahme');
if(empty($massnahme)) {
  header('Location:massnahmen.php');
  exit;
}

require_once $ich->istleiter ? '../leitung/LeitungSeite.php' : 'VerwaltungSeite.php';
$seite=new VerwaltungSeite('Maßnahme '.$massnahme->kuerzel);
$seite->anfangGenerieren();
$massnahme->makeSehen('sehen');
?>
<a href="massnahmen.php">Zurück zur Suche</a>
<?php
$seite->endeGenerieren();
?>