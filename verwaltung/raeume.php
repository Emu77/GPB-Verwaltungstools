<?php
require_once 'check_login.php';
require_once '../Liste.php';
require_once 'VerwaltungRaum.php';

$suche=isset($_SESSION['raeume_suche']) ? $_SESSION['raeume_suche'] : new Suche('ort','tuer','zusatz','art','anzahlPlaetze','anzahlComputer');
if(empty($suche->where)) {
  $jetztsuchen=false;
  if(!empty($ich->ort)) {
    $suche->ort=$ich->ort;
    $suche->addKriterium('ort=?',$suche->ort,'s');
  }
} else {
  $jetztsuchen=true;
}

$raeume=new Liste('VerwaltungRaum',empty($suche->where) || !$jetztsuchen ? null : 
  $suche->prepare("select * from gpb_raum where","order by ort,tuer limit 50"));

require_once $ich->istleiter ? '../leitung/LeitungSeite.php' : 'VerwaltungSeite.php';
$seite=VerwaltungSeite::$menueByUrl['../verwaltung/raeume.php'];
$seite->anfangGenerieren();
?>
<a href="raumplanung.php">Raumplanung</a>
<a href="raum_bearbeiten.php?id=0">Neuer Raum</a>
<table border="1" cellspacing="0" style="border-collapse:collapse;margin-top:1em;">
  <tr>
    <th>Ort</th>
    <th>Raum</th>
    <th>Zusatz</th>
    <th>Art</th>
    <th>Anzahl Plätze</th>
    <th>Anzahl Computer</th>
    <th></th>
  </tr>
  <form action="raeume_suchen.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
  <tr>
<?php
$suche->makeEnumInput('ort',array('-','Mitte','Neukölln'));
$suche->makeStringInput('tuer');
$suche->makeStringInput('zusatz');
$suche->makeStringInput('art');
?>
    <td><input type="number" style="width:70px;" name="anzahlPlaetze" value="<?= $suche->anzahlPlaetze ?>" /></td>
    <td><input type="number" style="width:70px;" name="anzahlComputer" value="<?= $suche->anzahlComputer ?>" /></td>
<?php
$suche->makeSubmit();
?>
  </tr>
  </form>
<?php
foreach($raeume->alle as $row) {
?>
  <tr>
    <td><?= $row->ort ?></td>
    <td><?= $row->tuer ?></td>
    <td><?= $row->zusatz ?></td>
    <td><?= $row->art ?></td>
    <td align="center"><?= $row->anzahlPlaetze<0 ? '' : $row->anzahlPlaetze ?></td>
    <td align="center"><?= $row->anzahlComputer<0 ? '' : $row->anzahlComputer ?></td>
<?php
  $row->makeBearbeitenTd();
?>
  </tr>
<?php
}
?>
</table>
<?php
$seite->endeGenerieren();
?>