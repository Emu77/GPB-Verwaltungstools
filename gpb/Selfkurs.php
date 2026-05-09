<?php
/**
 * Ein inTrain-Kurs
 * Zu unterscheiden von class IntrainKurs = Darstellung eines IL-Kurses im Intrain-Bereich der Verw.Tools
 */
class Selfkurs {
  static function einenLaden($id,$className) {
    global $db;
    if($id>0){
      $result=$db->query("select * from gpb_selfkurs_view where id=".$id." limit 1");
      $selfkurs=$result->fetch_object($className);
      $result->free();
    } else {
      $selfkurs=false;
    }
    if(!empty($selfkurs)) {
      $selfkurs->init();
    }
    return $selfkurs;
  }
  
  static function makeHeaderTr() {
    global $moodleisttest;
?>
  <tr>
    <th>Titel</th>
    <th class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">Moodle</th>
    <th></th>
  </tr>
<?php
  }
  
  function init() {
    $this->tns=array();
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