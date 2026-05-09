<?php
require_once 'check_login.php';
require_once '../Liste.php';
require_once '../Tn.php';
require_once 'TnKurs.php';

$tn=Tn::einenLaden($ich->id,'Tn');
if(empty($tn)) {
  header('Location:index.php');
  exit;
}
    
$kurse=new Liste('TnKurs',$db->prepare("
  select distinct k.*
    from gpb_klasse_tn ktn 
    join gpb_kurs_klasse kk on kk.klasseid=ktn.klasseid
    join gpb_kurs_view k on k.id=kk.kursid and (k.moodleid>0 or k.sichtbar)
    where ktn.tnid=".$tn->id."
    and (ktn.einstieg<>'0000-00-00' and ktn.einstieg is not null and ktn.einstieg<=k.ende)
    and (ktn.ausstieg='0000-00-00' or ktn.ausstieg is null or ktn.ausstieg>=k.beginn)
    order by beginn,ende,titel"));
Kurs::refsLaden($kurse);
TnKurs::vonMirLaden($kurse);

require_once 'TnSeite.php';
$seite=Seite::$menueByUrl['noten.php'];
$seite->anfangGenerieren();
?>
<table border="1" cellspacing="0" style="border-collapse:collapse;">
  <tr>
    <th>
      Kurs<br />
      Zeugnis-Modul
    </th>
    <th class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">Moodle</th>
    <th>Zeitraum</th>
    <th>Dozent</th>
    <th class="note">Note</th>
    <th>Im Notenauszug<br />sichtbar</th>
  </tr>
<?php
foreach($kurse->alle as $kurs) {
?>
  <tr>
    <th align="left">
      <a href="kurs_sehen.php?kursid=<?= $kurs->id ?>"><?= $kurs->titel ?></a><br />
      <?= empty($kurs->modultitel) ? '' : $kurs->modultitel.' ('.$kurs->moduldauer.' Wochen)' ?>
    </th>
<?php
  $kurs->makeMoodleTd();
?>
    <td><?= date('d.m.Y',$kurs->von) ?> - <?= date('d.m.Y',$kurs->bis) ?></td>
<?php
  $kurs->makeDozentenTd();
  $kurs->makeNoteTd();
?>
    <td class="note">
<?php
  if(false /*!$kurs->zeugnisrelevant*/) {
?>
    Kurs nicht<br />
    Zeugnis-relevant
<?php
  } else if(isset($kurs->note)) {
?>
    <input type="checkbox" id="inauszugsichtbar_<?= $kurs->id ?>_<?= $tn->id ?>" value="J" <?= $kurs->note->inauszugsichtbar ? 'checked' : '' ?> onchange="location.href='note_inauszugsichtbar_speichern.php?kursid=<?= $kurs->id ?>&inauszugsichtbar='+this.checked" />
<?php
  }
?>
    </td>
  </tr>
<?php
}
?>
</table>
<?php
$seite->endeGenerieren();
?>