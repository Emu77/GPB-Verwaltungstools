<?php
require_once '../Selfkurs.php';

/**
 * Dies ist ein inTrain-Kurs inkl. Anmeldungsdaten für $ich ($_SESSION['tn_ich'])
 */
class TnSelfkurs extends Selfkurs {
  
  static function makeHeaderTr() {
    global $moodleisttest,$selfsuche;
?>
  <tr>
    <th>KW</th>
    <th>Beginn</th>
    <th>Ende</th>
    <th>Titel</th>
    <th class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">Moodle</th>
    <td><input type="checkbox" name="nurich" value="J" <?= $selfsuche->nurich ? 'checked' : '' ?> /> Nur meine Kurse</td>
  </tr>
<?php
  }
  
  function init() {
    $this->von=empty($this->einstieg) || $this->einstieg=='0000-00-00' ? 0 : strtotime($this->einstieg);
    $this->bis=empty($this->ausstieg) || $this->ausstieg=='0000-00-00' ? 0 : strtotime($this->ausstieg);
    $this->kw=array();
    if($this->von>0 && $this->bis>0) {
      for($t=$this->von;$t<=$this->bis;$t=strtotime('+1 week',$t)) {
        $this->kw[]=date('Y W',$t);
      }
    } else if($this->von>0) {
      $this->kw[]=date('Y W',$this->von);
    }
    $this->dozenten=array();
  }
  
  function makeTr() {
?>
  <tr>
<?php
    $this->makeZeitraumTds();
    $this->makeTitelTd();
    $this->makeMoodleTd();
    $this->makeBearbeitenTd();
?>
  </tr>
<?php
  }
  function makeZeitraumTds() {
?>
    <td class="kw">
<?php
    foreach($this->kw as $kw) {
?>
      <div>KW <?= substr($kw,5) ?></div>
<?php
    }
?>
    </td>
    <td class="von"><?= $this->von>0 ? date('d.m.Y',$this->von) : '' ?></td>
    <td class="bis"><?= $this->bis>0 ? date('d.m.Y',$this->bis) : '' ?></td>
<?php
  }
  function makeTitelTd() {
?>
    <td><?= $this->titel ?></td>
<?php
  }
  function makeMoodleTd() {
    global $moodleisttest,$moodleurl;
?>
    <td class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>"><a href="<?= $moodleurl ?>course/view.php?id=<?= $this->moodleid ?>" target="moodle">Moodle-ID=<?= $this->moodleid ?></a></td>
<?php
  }
  function makeBearbeitenTd() {
?>
    <td class="aktionen"></td>
<?php
  }
  
  function makeSehen($classname='') {
    global $moodleisttest;
?>
<table border="1" cellspacing="0" style="border-collapse:collapse;" class="<?= $classname ?>">
  <tr>
    <th>Titel</th>
<?php
    $this->makeTitelTd();
?>
  </tr>
  <tr>
    <th class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">Moodle</th>
<?php
    $this->makeMoodleTd();
?>
  </tr>
  <tr>
    <th></th>
<?php
    $this->makeBearbeitenTd();
?>
  </tr>
</table>
<br />
<?php
  }
}
?>