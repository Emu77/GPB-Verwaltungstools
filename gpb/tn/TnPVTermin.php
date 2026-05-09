<?php
require_once '../PVTermin.php';

class TnPVTermin extends PVTermin {
  
  static function makeHeaderTr($mitKurs) {
    global $moodleisttest;
?>
  <tr>
    <th>Wann</th>
    <th>Was</th>
    <th>Kurs</th>
    <th class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">Moodle-ID</th>
    <th></th>
  </tr>
<?php
  }
  
  function makeTr($kursRowspan=0) {
    global $moodleisttest,$moodleurl;
?>
  <tr>
    <td>
      <?= empty($this->beginn) || $this->beginn=='0000-00-00' ? 'Wird rechtzeitig bekannt gegeben' : date('d.m.Y',$this->von) ?>
      <?= empty($this->beginn_uhrzeit) || $this->beginn_uhrzeit=='00:00:00' ? '' : date('H:i',$this->von) ?>
      <?= $this->keinEnde ? '' : '-' ?>
      <?= empty($this->ende) || $this->ende=='0000-00-00' ? '' : date('d.m.Y',$this->bis) ?>
      <?= empty($this->ende_uhrzeit) || $this->ende_uhrzeit=='00:00:00' ? '' : date('H:i',$this->bis) ?>
    </td>
    <td><?= $this->was ?></td>
<?php
    if($kursRowspan>0) {
?>
    <td rowspan="<?= $kursRowspan ?>"><?= $this->titel ?></td>
    <td rowspan="<?= $kursRowspan ?>" align="center" class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>"><?= empty($this->moodleid) ? '(in Planung)' : '<a href="'.$moodleurl.'course/view.php?id='.$this->moodleid.'" target="moodle">'.$this->moodleid.'</a>' ?></td>
    <td rowspan="<?= $kursRowspan ?>">
      <a href="kurs_sehen.php?kursid=<?= $this->kursid ?>">Kurs sehen</a>
<?php
      if($this->hatProjektantrag) {
?>
      <a href="ihkprojektantrag.php?kursid=<?= $this->kursid ?>">Projektantrag</a>
<?php
      }
?>
    </td>
<?php
    }
?>
  </tr>
<?php
  }
}
?>