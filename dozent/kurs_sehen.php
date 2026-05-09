<?php
require_once 'check_login.php';
require_once 'DozentKurs.php';
require_once 'DozentPVTermin.php';
require_once '../Liste.php';

$kurs=Kurs::einenLaden(isset($_GET['kursid']) ? (int)$_GET['kursid'] : 0,'DozentKurs');
if(empty($kurs)) {
  header('Location:kurse.php');
  exit;
}

if($kurs->istPV) {
  $kurs->pvLaden();
  $termine=new Liste('DozentPVTermin',$db->prepare("select * from gpb_pruefungsvorbereitung_termin where kursid=".$kurs->id." order by beginn,beginn_uhrzeit,ende,ende_uhrzeit,was"));
  DozentPVTermin::refsLaden($termine);
}

require_once 'DozentSeite.php';
$seite=new DozentSeite('Kurs '.$kurs->titel);
$seite->anfangGenerieren();
$kurs->makeSehen('sehen');
if($kurs->istPV && !empty($termine->alle)) {
?>
<h2>PV-Termine</h2>
<table border="1" cellspacing="0" style="border-collapse:collapse;margin-bottom:1em;" class="sehen">
<?php
DozentPVTermin::makeHeaderTr();
foreach($termine->alle as $termin) {
  $termin->makeTr();
}
?>
</table>
<?php
}
?>
<a href="kurse.php">Zurück zur Suche</a>
<?php
$seite->endeGenerieren();
?>