<?php
require_once '../Tn.php';
class IntrainTn extends Tn {
  static function einenLaden($id,$className) {
    $tn=Tn::einenLaden($id,$className);
    if(!empty($tn)) {
      $tn->massnahmenLaden();
      $tn->selfkurseLaden();
    }
    return $tn;
  }
  
  static function refsLaden($liste) {
    if(!empty($liste->byId)) {
      Tn::refsMassnahmenLaden($liste);
      Tn::refsSelfkurseLaden($liste);
    }
  }
  static function makeHeaderTr() {
    global $moodleisttest,$ich;
?>
  <tr>
    <th>Anrede</th>
    <th>Vorname</th>
    <th>Nachname</th>
    <th>Nutzername</th>
    <th class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">Moodle-ID</th>
    <th>Maßnahmen</th>
    <th>inTrain-Kurse</th>
    <th></th>
  </tr>
<?php
  }
  
  function init() {
    parent::init();
    // als Anmeldung geladen
    if(isset($this->einstieg)) {
      $this->von=empty($this->einstieg) || $this->einstieg=='0000-00-00' ? 0 : strtotime($this->einstieg);
    }
    if(isset($this->ausstieg)) {
      $this->bis=empty($this->ausstieg) || $this->ausstieg=='0000-00-00' ? 0 : strtotime($this->ausstieg);
    }
    $this->massnahmen=array();
    $this->selfkurse=array();
    $this->selfkurseById=array();
    $this->anwesenheiten=array();
  }
  
  function makeTr() {
?>
  <tr>
<?php
    $this->makeNameTds();
    $this->makeNutzernameTd();
    $this->makeMoodleTd();
    $this->makeMassnahmenTd();
    $this->makeSelfkurseTd();
    $this->makeBearbeitenTd();
?>
  </tr>
<?php
  }
  function makeMassnahmenTd() {
?>
    <td><table class="massnahmen" border="0" cellspacing="0" style="border-collapse:collapse;width:100%;">
<?php
    foreach($this->massnahmen as $massn) {
?>
      <tr>
        <td style="max-width:300px;"><?= $massn->kuerzel=='MODULAR' ? $massn->titel : $massn->kuerzel ?></td>
        <td align="right"><?= empty($massn->von) ? '' : date('d.m.Y',$massn->von) ?> - <?= empty($massn->bis) ? '' : date('d.m.Y',$massn->bis) ?></td>
      </tr>
<?php
    }
?>
    </table></td>
<?php
  }
  function makeSelfkurseTd() {
?>
    <td>
      <table border="0" cellspacing="0" style="border-collapse:collapse;width:100%;">
<?php
    foreach($this->selfkurse as $selfkurs) {
?>
        <tr>
          <td><a href="selfkurs_sehen.php?selfkursid=<?= $selfkurs->selfkursid ?>"><?= $selfkurs->titel ?></a></td>
          <td><?= $selfkurs->von<=0 ? '' : date('d.m.Y',$selfkurs->von) ?> - <?= $selfkurs->bis<=0 ? '' : date('d.m.Y',$selfkurs->bis) ?></td>
        </tr>
<?php
    }
?>
      </table>
    </td>
<?php
  }
  function makeBearbeitenTd() {
?>
    <td>
      <a href="tn_sehen.php?tnid=<?= $this->id ?>">Sehen</a>
      <a href="tn_selfkurse.php?tnid=<?= $this->id ?>">inTrain-Kurse</a>
      <!-- a href="tn_loginas.php?tnid=<?= $this->id ?>">LoginAs</a -->
      <a href="tn_anwesenheit.php?tnid=<?= $this->id ?>">Anwesenheit</a>
      <!-- a href="tn_noten.php?tnid=<?= $this->id ?>">Noten</a -->
    </td>
<?php
  }
  
  function makeSehen($classname='') {
    global $moodleisttest;
?>
<table border="1" cellspacing="0" style="border-collapse:collapse;" class="<?= $classname ?>">
<?php
    $this->makeNameTrs();
?>
  <tr>
    <th>Nutzername</th>
<?php
    $this->makeNutzernameTd();
?>
  </tr>
  <tr>
    <th class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">Moodle-ID</th>
<?php
    $this->makeMoodleTd();
?>
  </tr>
  <tr>
    <th>Maßnahmen</th>
<?php
    $this->makeMassnahmenTd();
?>
  </tr>
  <tr>
    <th>inTrain-Kurse</th>
<?php
    $this->makeSelfkurseTd();
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