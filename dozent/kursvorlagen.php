<?php
require_once 'check_login.php';
require_once '../Liste.php';
require_once 'DozentKursvorlage.php';

$stmt=$db->prepare("select v.* from gpb_kursvorlage v where v.id in(select vd.kursvorlageid from gpb_kursvorlage_dozent vd where vd.dozentid=?) order by v.titel");
$stmt->bind_param('i',$ich->id);
$vorlagen=new Liste('DozentKursvorlage',$stmt);
Kursvorlage::refsLaden($vorlagen);

require_once 'DozentSeite.php';
$seite=Seite::$menueByUrl['kursvorlagen.php'];
$seite->anfangGenerieren('');
?>
<a href="kursvorlage_bearbeiten.php?kursvorlageid=0">Neue Kursvorlage</a><br />
<br />
<table border="1" cellspacing="0" style="border-collapse:collapse;">
<?php
DozentKursvorlage::makeHeaderTr();
foreach($vorlagen->alle as $vorlage) {
  $vorlage->makeTr();
}
?>
</table>
<?php
$seite->endeGenerieren();
exit;
?>