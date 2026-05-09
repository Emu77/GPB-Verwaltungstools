<?php
require_once 'check_login.php';
require_once '../Liste.php';
require_once $ich->istleiter ? '../leitung/LeitungKlasse.php' : 'VerwaltungKlasse.php';

$suche=isset($_SESSION['klassen_suche']) ? $_SESSION['klassen_suche'] : new Suche('bezeichnung','ort','starter');
if(empty($suche->where)) {
  $jetztsuchen=false;
  $heute=date('Y-m-d');
  $suche->am=$heute;
  $suche->addKriterium("beginn<=?",$heute,'s');
  $suche->addKriterium("ende>=?",$heute,'s');
  if(!empty($ich->ort)) {
    $suche->ort=$ich->ort;
    $suche->addKriterium('ort=?',$suche->ort,'s');
  }
} else {
  $jetztsuchen=true;
}

$klassen=new Liste($ich->istleiter ? 'LeitungKlasse' : 'VerwaltungKlasse',empty($suche->where) || !$jetztsuchen ? null :
  $suche->prepare("select * from gpb_klasse_view where","order by beginn,ende,bezeichnung limit 50"));
  
$letzteWoche=date('Y-m-d',strtotime('-1 week'));

require_once $ich->istleiter ? '../leitung/LeitungSeite.php' : 'VerwaltungSeite.php';
$seite=VerwaltungSeite::$menueByUrl['../verwaltung/klassen.php'];
$seite->anfangGenerieren();
?>
<a href="klassen_kalender.php">Klassen-Kalender</a>
<a href="klassen_zusaetzlich.php">Sonderimports (inTrain-Sammelklassen, Prak-Klassen für PV, ...)</a>
<table border="1" cellspacing="0" style="border-collapse:collapse;margin-top:1em;">
  <tr>
    <th>Bezeichung</th>
    <th>Beruf</th>
    <th>Ort</th>
    <th>Beginn</th>
    <th>Ende</th>
    <th class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">Moodle-Kurs</th>
    <th>Anzahl TN</th>
    <th></th>
  </tr>
  <form action="klassen_suchen.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
  <tr>
<?php
$suche->makeStringInput('bezeichnung');
$suche->makeStringInput('berufkuerzel',1);
$suche->makeEnumInput('ort',array('-','Mitte','Neukölln'));
?>
    <td colspan="2">
      Am <input type="date" name="am" value="<?= $suche->am ?>" /><br />
      <input type="checkbox" name="starter" value="J" <?= $suche->starter ? 'checked' : '' ?> /> Starter-Klassen
    </td>
    <td class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>"></td>
    <td></td>
<?php
$suche->makeSubmit();
?>
  </tr>
  </form>
<?php
foreach($klassen->alle as $row) {
?>
  <tr>
    <td><?= $row->bezeichnung ?></td>
    <td><?= $row->berufkuerzel ?></td>
    <td><?= $row->ort ?></td>
    <td><?= empty($row->von) ? '-' : date('d.m.Y',$row->von) ?></td>
    <td><?= empty($row->bis) ? '-' : date('d.m.Y',$row->bis) ?></td>
<?php
  $row->makeMoodleTd();
?>
    <td align="center"><?= $row->beginn<=$letzteWoche ? $row->anzahlTN : 'Plan: '.$row->anzahlAnmeldungen.' Ist: '.$row->anzahlTN ?></td>
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
