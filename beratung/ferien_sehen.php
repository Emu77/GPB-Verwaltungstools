<?php
require_once 'check_login.php';
require_once 'BeratungFerien.php';

$ferien=Ferien::einenLaden(isset($_GET['ferienid']) ? (int)$_GET['ferienid'] : 0,'BeratungFerien');
if(empty($ferien)) {
  header('Location:ferien.php');
  exit;
}

require_once 'BeratungSeite.php';
$seite=new BeratungSeite('Ferienzeit '.$ferien->anlass.' '.substr($ferien->beginn,0,4));
$seite->anfangGenerieren();
$ferien->makeSehen('sehen');
?>
<a href="ferien.php">Zurück zur Suche</a>
<?php
$seite->endeGenerieren();
?>