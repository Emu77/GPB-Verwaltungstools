<?php
require_once '../Suche.php';
@session_start();
require_once 'check_login.php';
require_once '../Liste.php';
require_once 'VerwaltungKurs.php';
require_once 'VerwaltungTn.php';

$kurs=Kurs::einenLaden(isset($_GET['kursid']) ? (int)$_GET['kursid'] : 0,'VerwaltungKurs');
if(empty($kurs)) {
  header('Location:kurse.php');
  exit;
}

$stmt=empty($kurs->klassenids) ? null : $db->prepare("select distinct tn.*,ktn.klasseid
  from gpb_klasse_tn ktn
  join gpb_tn_view tn on tn.id=ktn.tnid
  where ktn.klasseid in(".implode(',',$kurs->klassenids).")
  and (ktn.einstieg<>'0000-00-00' and ktn.einstieg is not null and ktn.einstieg<='".$kurs->ende."')
  and (ktn.ausstieg='0000-00-00' or ktn.ausstieg is null or ktn.ausstieg>='".$kurs->beginn."') order by tn.nachname,tn.vorname");
$tns=new Liste('VerwaltungTn',$stmt);
VerwaltungTN::refsLaden($tns);

require_once $ich->istleiter ? '../leitung/LeitungSeite.php' : 'VerwaltungSeite.php';
$seite=new VerwaltungSeite('Teilnehmer des Kurses '.$kurs->titel);
$seite->anfangGenerieren();
$kurs->makeSehen();
if(empty($kurs->klassen)) {
  echo 'Keine Klassen.';
} else if(empty($tns->alle)) {
  echo 'Keine TN.';
} else {
?>
<table border="1" cellspacing="0" style="border-collapse:collapse;margin-bottom:1em;margin-top:1em;">
<?php
VerwaltungTn::makeHeaderTr();
foreach($tns->alle as $tn) {
  $tn->makeTr();
}
?>
</table>
<?php
}
?>
<a href="kurse.php" style="margin-top:1em;">Zurück zur Suche</a>
<?php
$seite->endeGenerieren();
?>