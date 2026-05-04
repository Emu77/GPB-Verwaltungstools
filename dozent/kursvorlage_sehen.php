<?php
require_once 'check_login.php';
require_once 'DozentKursvorlage.php';

$vorlage=Kursvorlage::einenLaden(isset($_GET['kursvorlageid']) ? (int)$_GET['kursvorlageid'] : 0,'DozentKursvorlage');
if(empty($vorlage)) {
  header('Location:kursvorlagen.php');
  exit;
}

require_once 'DozentSeite.php';
$seite=new DozentSeite('Kursvorlage '.$vorlage->titel);
$seite->anfangGenerieren();
$vorlage->makeSehen('sehen');
?>
<a href="kursvorlagen.php">Zurück zur Liste</a>
<?php
$seite->endeGenerieren();
?>