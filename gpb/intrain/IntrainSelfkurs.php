<?php
require_once '../Selfkurs.php';
class IntrainSelfkurs extends Selfkurs {
  
  static function makeHeaderTr() {
    global $moodleisttest;
?>
  <tr>
    <th>Titel</th>
    <th class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">Moodle-ID</th>
    <th></th>
  </tr>
<?php
  }
  
  function init() {
    parent::init();
    $this->tns=array();
    // als Anmeldung geladen
    if(isset($this->einstieg)) {
      $this->von=empty($this->einstieg) || $this->einstieg=='0000-00-00' ? 0 : strtotime($this->einstieg);
    }
    if(isset($this->ausstieg)) {
      $this->bis=empty($this->ausstieg) || $this->ausstieg=='0000-00-00' ? 0 : strtotime($this->ausstieg);
    }
  }
  
  function makeTr() {
?>
  <tr>
<?php
    $this->makeTitelTd();
    $this->makeMoodleTd();
    $this->makeBearbeitenTd();
?>
  </tr>
<?php
  }
  function makeBearbeitenTd() {
?>
    <td>
      <a href="selfkurs_sehen.php?selfkursid=<?= $this->id ?>">Sehen</a>
      <a href="selfkurs_bearbeiten.php?selfkursid=<?= $this->id ?>">Bearbeiten</a>
      <a href="selfkurs_tn.php?selfkursid=<?= $this->id ?>">TN</a>
    </td>
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
    <th class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">Moodle-ID</th>
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