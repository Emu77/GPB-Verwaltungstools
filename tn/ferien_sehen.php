<?php
require_once 'check_login.php';
require_once 'TnFerien.php';

$ferien=Ferien::einenLaden(isset($_GET['ferienid']) ? (int)$_GET['ferienid'] : 0,'TnFerien');
if(empty($ferien)) {
  header('Location:ferien.php');
  exit;
}

require_once 'TnSeite.php';
$seite=new TnSeite('Ferienzeit '.$ferien->anlass.' '.substr($ferien->beginn,0,4));
$seite->anfangGenerieren();
$ferien->makeSehen('sehen');
?>
<a href="ferien.php">Zurück zur Liste</a>
<?php
$seite->endeGenerieren();
?>