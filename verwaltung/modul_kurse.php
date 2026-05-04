<?php
require_once 'check_login.php';
require_once '../Liste.php';
require_once 'VerwaltungKurs.php';

$modulid=isset($_GET['modulid']) ? (int)$_GET['modulid'] : 0;
if($modulid>0) {
  $result=$db->query("select * from gpb_modul where id=".$modulid." limit 1");
  $modul=$result->fetch_object();
  $result->free();
} else {
  $modul=false;
}
if(empty($modul)) {
  header('Location:berufe.php');
  exit;
}

$kurse=new Liste('VerwaltungKurs',$db->prepare("select * from gpb_kurs_view where modulid=".$modul->id." order by beginn desc,ende desc limit 50"));
Kurs::refsLaden($kurse);

require_once 'VerwaltungSeite.php';
$seite=new VerwaltungSeite('Kurse vom Modul '.$modul->kuerzel.' - '.$modul->titel);
$seite->anfangGenerieren();
?>
<a href="kurs_bearbeiten.php?kursid=0&modulid=<?= $modul->id ?>">Neuer Kurs</a><br />
<br />
<?php
if(count($kurse->alle)>=50) {
?>
<div style="color:red;">Ältere Kurse sind vorhanden aber werden nicht angezeigt! <a href="kurse.php">Detaillierte Suche</a></div>
<br />
<?php
}
?>
<table border="1" cellspacing="0" style="border-collapse:collapse;margin-top:1em;">
<?php
VerwaltungKurs::makeHeaderTr(false);
foreach($kurse->alle as $kurs) {
  $kurs->makeTr(false);
}
?>
</table>
<?php
$seite->endeGenerieren();
?>