<?php
require_once '../Suche.php';
@session_start();
require_once 'check_login.php';
require_once '../Liste.php';
require_once 'VerwaltungMassnahme.php';
require_once 'VerwaltungTn.php';

$massnahme=Massnahme::eineLaden(isset($_GET['massnahmeid']) ? (int)$_GET['massnahmeid'] : 0,'VerwaltungMassnahme');
if(empty($massnahme)) {
  header('Location:massnahmen.php');
  exit;
}

$stmt=$db->prepare("select * from gpb_tn_view where id in(select tnid from gpb_massnahme_tn where massnahmeid=".$massnahme->id.") order by nachname,vorname");
$tns=new Liste('VerwaltungTn',$stmt);
VerwaltungTn::refsLaden($tns);

require_once $ich->istleiter ? '../leitung/LeitungSeite.php' : 'VerwaltungSeite.php';
$seite=new VerwaltungSeite('Teilnehmer der Maßnahme '.$massnahme->kuerzel);
$seite->anfangGenerieren();
$massnahme->makeSehen();
if(empty($tns)) {
  echo "Keine TN gefunden.";
} else {
?>
<table border="1" cellspacing="0" style="border-collapse:collapse;">
<?php
VerwaltungTn::makeHeaderTr();
foreach($tns->alle as $tn) {
  $tn->makeTr();
}
?>
</table>
<?php
}
$seite->endeGenerieren();
?>