<?php
require_once 'check_login.php';
require_once 'LeitungKlasse.php';
require_once 'LeitungKurs.php';
require_once '../Liste.php';

$klasse=Klasse::eineLaden(isset($_GET['klasseid']) ? (int)$_GET['klasseid'] : 0,'LeitungKlasse');
if(empty($klasse)) {
  header('Location:../verwaltung/klassen.php');
  exit;
}

$module=array();
$moduleById=array();
$moduleByTitel=array();
if(!empty($klasse->berufid)) {
  $result=$db->query("select m.* 
    from gpb_beruf_modul bm
    join gpb_modul m on m.id=bm.modulid
    where bm.berufid=".$klasse->berufid."
    order by bm.nummer,m.titel");
  while($row=$result->fetch_object()) {
    $row->istplan=true;
    $row->kurse=array();
    $row->geplanteDauer=0;
    $module[]=$row;
    $moduleById[$row->id]=$row;
    $moduleByTitel[$row->titel]=$row;
  }
  $result->free();
}

$kurse=new Liste('LeitungKurs',$db->prepare("select * from gpb_kurs_view where id in(select kursid from gpb_kurs_klasse where klasseid=".$klasse->id.") order by beginn,ende,titel"));
Kurs::anzahlTNLaden($kurse);
Kurs::refsLaden($kurse);
if(!empty($kurse->byId)) {
  $result=$db->query("select m.* 
    from gpb_modul m 
    where m.id in(select k.modulid from gpb_kurs_view k where k.id in(".implode(',',array_keys($kurse->byId))."))
    order by m.titel");
  while($row=$result->fetch_object()) {
    if(isset($moduleById[$row->id]) || isset($moduleByTitel[$row->titel])) continue;
    $row->istplan=false;
    $row->kurse=array();
    $row->geplanteDauer=0;
    $module[]=$row;
    $moduleById[$row->id]=$row;
    $moduleByTitel[$row->titel]=$row;
  }
  $result->free();
  foreach($kurse->alle as $kurs) {
    if($kurs->modulid>0) {
      $modul=isset($moduleById[$kurs->modulid]) ? $moduleById[$kurs->modulid] : $moduleByTitel[$kurs->modultitel];
      $modul->kurse[]=$kurs;
      $modul->geplanteDauer+=ceil(($kurs->bis-$kurs->von)/60/60/24/7);
    }
  }
}

require_once 'LeitungSeite.php';
$seite=new LeitungSeite('Soll-Plan-Vergleich Klasse '.$klasse->bezeichnung);
$seite->anfangGenerieren();
$klasse->makeSehen('sehen');
?>
<div class="tododev">Mit anderen Klassen gruppieren (BQ1 + BQ2)</div>
<table border="1" cellspacing="0" style="border-collapse:collapse;">
  <tr>
    <th>Modul</th>
    <th>Standard-<br />Dauer</th>
    <th>Geplante<br />Dauer</th>
    <th>Kurs</th>
    <th colspan="3">Zeitraum</th>
    <th>Klassen</th>
    <th>Anzahl TN</th>
    <th>Dozenten</th>
    <th class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">Moodle-ID</th>
    <th></th>
  </tr>
<?php
$plan=true;
foreach($module as $modul) {
  if($plan && !$modul->istplan) {
    $plan=false;
?>
  <tr>
    <th colspan="12" align="left">Zusätzliche Module</th>
  </tr>
<?php
  }
  if(empty($modul->kurse)) {
?>
  <tr class="todo">
    <td><?= $modul->titel ?></td>
    <td align="center"><?= $modul->dauer ?> Wo</td>
    <td align="center"><?= $modul->geplanteDauer ?> Wo</td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>"></td>
    <td><a href="../verwaltung/kurs_bearbeiten.php?ort=<?= urlencode($klasse->ort) ?>&modulid=<?= $modul->id ?>&klassenids=<?= $klasse->id ?>">Kurs erstellen</a></td>
  </tr>
<?php
  } else {
    $erst=true;
    foreach($modul->kurse as $kurs) {
?>
  <tr>
<?php
      if($erst) {
?>
    <td rowspan="<?= count($modul->kurse) ?>"><?= $modul->titel ?></td>
    <td rowspan="<?= count($modul->kurse) ?>" align="center"><?= $modul->dauer ?> Wo</td>
    <td rowspan="<?= count($modul->kurse) ?>" align="center" <?= $modul->geplanteDauer<$modul->dauer ? 'class="todo" style="font-weight:bold;"' : '' ?>><?= $modul->geplanteDauer ?> Wo</td>
<?php
        $erst=false;
      }
      $kurs->makeTitelTd();
      $kurs->makeZeitraumTds();
      $kurs->makeKlassenTd();
      $kurs->makeAnzahlTNTd(true);
      $kurs->makeDozentenTd();
      $kurs->makeMoodleTd();
      $kurs->makeBearbeitenTd();
?>
  </tr>
<?php
    }
  }
}
?>
</table>
<?php
$seite->endeGenerieren();
?>