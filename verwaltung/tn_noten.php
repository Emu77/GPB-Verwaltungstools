<?php
require_once '../Suche.php';
@session_start();
require_once 'check_login.php';
require_once '../Liste.php';
require_once 'VerwaltungTn.php';
require_once 'VerwaltungKurs.php';

$tn=VerwaltungTn::einenLaden(isset($_GET['tnid']) ? (int)$_GET['tnid'] : 0,'VerwaltungTn');
if(empty($tn)) {
  header('Location:tn.php');
  exit;
}

$kurse=new Liste('VerwaltungKurs',$db->prepare("
  select distinct k.*,n.*
    from gpb_klasse_tn ktn 
    join gpb_kurs_klasse kk on kk.klasseid=ktn.klasseid
    join gpb_kurs_view k on k.id=kk.kursid
    left outer join gpb_note n on n.kursid=k.id and n.tnid=".$tn->id."
    where ktn.tnid=".$tn->id."
    and (ktn.einstieg<>'0000-00-00' and ktn.einstieg is not null and ktn.einstieg<=k.ende)
    and (ktn.ausstieg='0000-00-00' or ktn.ausstieg is null or ktn.ausstieg>=k.beginn)
    order by beginn,ende,titel"));
Kurs::refsLaden($kurse);

require_once $ich->istleiter ? '../leitung/LeitungSeite.php' : 'VerwaltungSeite.php';
$seite=new VerwaltungSeite('Noten von TN '.$tn->tnname);
$seite->anfangGenerieren();
$tn->makeSehen('sehen');
?>
<?php
if($tn->anrede!=$tn->mitisanrede || $tn->vorname!=$tn->mitisvorname) {
?>
<div>
  <a href="tn_notenauszug.php?tnid=<?= $tn->id ?>&zeugnisart=Notenauszug&gender=J">Notenauszug mit Wunsch-Gender</a>
  <a href="tn_notenauszug.php?tnid=<?= $tn->id ?>&zeugnisart=Zwischenzeugnis&gender=J">Zwischenzeugnis mit Wunsch-Gender</a>
  <a href="tn_zeugnis.php?tnid=<?= $tn->id ?>&gender=J">Zeugnis mit Wunsch-Gender</a>
</div>
<div>
  <a href="tn_notenauszug.php?tnid=<?= $tn->id ?>&zeugnisart=Notenauszug&gender=N">Notenauszug mit MITIS-Gender</a>
  <a href="tn_notenauszug.php?tnid=<?= $tn->id ?>&zeugnisart=Zwischenzeugnis&gender=N">Zwischenzeugnis mit MITIS-Gender</a>
  <a href="tn_zeugnis.php?tnid=<?= $tn->id ?>&gender=N">Zeugnis mit MITIS-Gender</a>
</div>
<?php
} else {
?>
<div>
  <a href="tn_notenauszug.php?tnid=<?= $tn->id ?>&zeugnisart=Notenauszug">Notenauszug</a>
  <a href="tn_notenauszug.php?tnid=<?= $tn->id ?>&zeugnisart=Zwischenzeugnis">Zwischenzeugnis</a>
  <a href="tn_zeugnis.php?tnid=<?= $tn->id ?>">Zeugnis</a>
</div>
<?php
}
?>
<table border="1" cellspacing="0" style="border-collapse:collapse;margin-top:1em;">
  <tr>
    <th colspan="3">
      Kurs<br />
      Zeugnis-Modul
    </th>
    <th>Fehlt</th>
    <th>Note</th>
    <th>Nachklausur-Note</th>
    <th>In Notenauszug<br />sichtbar</th>
    <th></th>
  </tr>
<?php
foreach($kurse->alle as $kurs) {
?>
  <form action="tn_note_speichern.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
    <input type="hidden" name="tnid" value="<?= $tn->id ?>" />
    <input type="hidden" name="kursid" value="<?= $kurs->id ?>" />
  <tr>
    <th colspan="3" align="left">
      <a href="kurs_sehen.php?kursid=<?= $kurs->id ?>"><?= $kurs->titel ?></a><br />
      <?= empty($kurs->modultitel) ? '' : $kurs->modultitel.' ('.$kurs->moduldauer.' Wochen)' ?>
    </th>
<?php
  if($kurs->notenstatus=='keine') {
?>
    <td rowspan="2" colspan="4">Kurs ohne Noten</td>
<?php
  } else {
?>
    <td rowspan="2"><input type="checkbox" name="fehlt" value="J" <?= $kurs->fehlt ? 'checked' : '' ?> /></td>
    <td rowspan="2"><input type="number" name="note" value="<?= $kurs->note ?>" min="0" max="100" step="1" style="width:50px;" /></td>
    <td rowspan="2"><input type="number" name="nachnote" value="<?= $kurs->nachnote ?>" min="0" max="100" step="1" style="width:50px;" /></td>
    <td rowspan="2" align="center">
<?php
    if($kurs->zeugnisrelevant) {
?>
      <input type="checkbox" name="inauszugsichtbar" value="J" <?= $kurs->inauszugsichtbar ? 'checked' : '' ?> />
<?php
    } else {
?>
      Kurs nicht<br />
      Zeugnis-relevant
<?php
    }
?>
    </td>
    <td rowspan="2"><input type="submit" value="Speichern" /></td>
<?php
  }
?>
  </tr>
  <tr>
    <td><?= date('d.m.Y',$kurs->von) ?> - <?= date('d.m.Y',$kurs->bis) ?></td>
<?php
  $kurs->makeDozentenTd();
?>
    <td class="<?= $kurs->notenstatus=='todo' ? 'todo' : 'ok' ?>"><?= $kurs->notenstatus=='todo' ? 'Noten unvollständig' : ($kurs->notenstatus=='keine' ? 'Keine Noten' : 'Noten vollständig') ?></td>
  </tr>
  </form>
<?php
}
?>
</table>
<?php
$seite->endeGenerieren();
?>