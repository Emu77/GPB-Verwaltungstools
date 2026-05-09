<?php
require_once 'check_login.php';
require_once 'BeratungBerater.php';

$berater=BeratungBerater::einenLaden(isset($_GET['beraterid']) ? (int)$_GET['beraterid'] : 0,'BeratungBerater');
if(empty($berater)) {
  header('Location:berater.php');
  exit;
}

require_once 'BeratungSeite.php';
$seite=new BeratungSeite('Berater '.$berater->vorname.' '.$berater->nachname);
$seite->anfangGenerieren();
$berater->makeSehen('sehen');
?>
<a href="berater.php">Zurück zur Suche</a>
<?php
$seite->endeGenerieren();
?>